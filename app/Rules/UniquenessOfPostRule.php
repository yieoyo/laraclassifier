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

namespace App\Rules;

use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquenessOfPostRule implements ValidationRule
{
	public bool $unverifiedPost = false;
	public ?string $email = null;
	public ?string $phone = null;
	public ?string $phoneCountry = null;
	
	public function __construct()
	{
		if (request()->filled('email')) {
			$this->email = request()->input('email');
		}
		if (request()->filled('phone')) {
			$this->phone = request()->input('phone');
		}
		$this->phoneCountry = request()->input('phone_country', config('country.code'));
		
		if (!empty($this->phone) && !empty($this->phoneCountry)) {
			$this->phone = phoneE164($this->phone, $this->phoneCountry);
		} else {
			$this->phone = null;
		}
	}
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			if ($this->unverifiedPost) {
				$message = trans('validation.uniqueness_of_unverified_listing_rule');
			} else {
				$message = trans('validation.uniqueness_of_listing_rule');
			}
			
			$fail($message);
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 * Check the uniqueness of the Post
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$value = is_stringable($value) ? $value : '';
		$value = trim($value);
		
		$guard = isFromApi() ? 'sanctum' : null;
		$authUser = auth($guard)->user();
		
		$posts = Post::query()->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class]);
		
		if (!empty($authUser)) {
			
			$posts->where(function ($query) use ($authUser) {
				$query->where('user_id', $authUser->id)->orWhere('email', $authUser->email);
			});
			
		} else {
			
			if (!empty($this->email) && !empty($this->phone)) {
				$posts->where('email', $this->email)->orWhere('phone', $this->phone);
			} else {
				if (!empty($this->email)) {
					$posts->where('email', $this->email);
				}
				if (!empty($this->phone)) {
					$posts->where('phone', $this->phone);
				}
			}
			
		}
		
		// Passes, If a logged user isn't found and If email and phone are not filled
		if (empty($authUser) && empty($this->email) && empty($this->phone)) {
			return true;
		}
		
		// Exclude current Post ID during update
		$isUpdateRequest = (in_array(request()->method(), ['PUT', 'PATCH', 'UPDATE']));
		if ($isUpdateRequest) {
			$parameters = request()->route()->parameters();
			if (!empty($parameters['id'])) {
				$posts->where('id', '!=', $parameters['id']);
			} else {
				return true;
			}
		}
		
		// Check if this user hasn't yet posted a listing with this title
		$posts->where('title', 'LIKE', $value);
		
		// Don't pass, If a listing with the same title found for the user
		if ($posts->count() > 0) {
			$post = $posts->orderByDesc('id')->first();
			
			if (!isVerifiedPost($post)) {
				// Conditions to Verify User's Email or Phone
				if (!empty($authUser)) {
					$emailVerificationRequired = config('settings.mail.email_verification') == 1
						&& request()->filled('email')
						&& request()->input('email') != $authUser->email;
					$phoneVerificationRequired = config('settings.sms.phone_verification') == 1
						&& request()->filled('phone')
						&& request()->input('phone') != $authUser->phone;
				} else {
					$emailVerificationRequired = config('settings.mail.email_verification') == 1 && request()->filled('email');
					$phoneVerificationRequired = config('settings.sms.phone_verification') == 1 && request()->filled('phone');
				}
				
				if ($emailVerificationRequired || $phoneVerificationRequired) {
					$this->unverifiedPost = true;
					
					return false;
				} else {
					return true;
				}
			}
			
			return false;
		}
		
		return true;
	}
}
