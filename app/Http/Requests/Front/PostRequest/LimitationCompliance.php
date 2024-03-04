<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

namespace App\Http\Requests\Front\PostRequest;

use App\Helpers\UrlGen;
use App\Http\Requests\Request;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;

class LimitationCompliance extends Request
{
	protected array $errorMessages = [];
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$request = request();
		
		$rules = [];
		
		// Posts limitation compliance
		$user = $this->getAuthUser();
		
		// For guest
		if (empty($user)) {
			$postsLimit = (int)config('settings.single.listings_limit');
			$countPosts = $this->countGuestPosts($request);
			$remainingPosts = $postsLimit - $countPosts;
			
			if ($remainingPosts <= 0) {
				$rules['posts_limitation_compliance'] = 'required';
				$this->errorMessages['posts_limitation_compliance.required'] = t('listings_limit_reached_as_guest');
			}
			
			return $rules;
		}
		
		// For logged-in user
		$payment = $user->payment ?? null;
		
		// User without a valid & active subscription
		if (empty($payment)) {
			$remainingPosts = $user->remaining_posts ?? 0;
			if ($remainingPosts <= 0) {
				$rules['posts_limitation_compliance'] = 'required';
				
				$message = t('listings_limit_reached_with_no_subscription', ['url' => UrlGen::pricing() . '?type=subscription']);
				if (isFromApi() && !doesRequestIsFromWebApp()) {
					$message = strip_tags($message);
				}
				$this->errorMessages['posts_limitation_compliance.required'] = $message;
			}
			
			return $rules;
		}
		
		// User with a valid & active subscription
		// Issue to retrieve the 'remaining_posts'?
		// Allow user to post listings!
		if (is_null($payment->remaining_posts)) {
			return $rules;
		}
		
		if ($payment->remaining_posts <= 0) {
			$rules['posts_limitation_compliance'] = 'required';
			
			$message = t('listings_limit_reached_with_subscription', ['url' => UrlGen::pricing() . '?type=subscription']);
			if (isFromApi() && !doesRequestIsFromWebApp()) {
				$message = strip_tags($message);
			}
			$this->errorMessages['posts_limitation_compliance.required'] = $message;
		}
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages()
	{
		return $this->errorMessages;
	}
	
	// PRIVATE
	
	/**
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */
	private function getAuthUser()
	{
		$guard = isFromApi() ? 'sanctum' : null;
		$authUser = auth($guard)->user();
		
		if (!empty($authUser)) {
			$postScopes = [VerifiedScope::class, ReviewedScope::class];
			$authUser->loadMissing([
				'payment' => function ($q) use ($postScopes) {
					$q->with('package');
					$q->with(['posts' => fn ($q) => $q->withoutGlobalScopes($postScopes)->unarchived()]);
				},
				'posts'   => fn ($q) => $q->withoutGlobalScopes($postScopes)->unarchived(),
			]);
		}
		
		return $authUser;
	}
	
	/**
	 * Count Guest's Listings (by email|phone number)
	 *
	 * @param $request
	 * @return int
	 */
	private function countGuestPosts($request): int
	{
		$count = 0;
		
		if ($request->filled('email')) {
			$count = Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('email', $request->input('email'))
				->count();
		}
		
		if ($request->filled('phone')) {
			$count = Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->inCountry(getPhoneCountry())
				->where('phone', $request->input('phone'))
				->count();
		}
		
		return $count;
	}
}
