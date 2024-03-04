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

namespace App\Http\Controllers\Api\Auth\Traits;

use App\Models\Post;
use App\Models\Scopes\VerifiedScope;
use App\Models\Scopes\ReviewedScope;
use App\Models\User;

trait RecognizedUserActionsTrait
{
	use CompleteMissingAuthDataTrait;
	
	/**
	 * After Email or Phone verification (from new user registration),
	 * Match the user's listings (that he was published as guest)
	 *
	 * WARNING: For security reasons, never call this method if the email and|or phone number verification is not enabled
	 *
	 * @param $user
	 * @return void
	 */
	public function findAndMatchPostsToUser($user): void
	{
		if (empty($user)) {
			return;
		}
		
		// Update listings created with this email
		$isVerifiedEmail = (
			config('settings.mail.email_verification') == '1'
			&& !empty($user->email)
			&& !empty($user->email_verified_at)
		);
		if ($isVerifiedEmail) {
			Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('email', $user->email)
				->update(['user_id' => $user->id]);
		}
		
		// Update listings created with this phone number (for this country)
		$isVerifiedPhone = (
			config('settings.sms.phone_verification') == '1'
			&& !empty($user->phone)
			&& !empty($user->phone_verified_at)
		);
		if ($isVerifiedPhone) {
			Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('phone', $user->phone)
				->update(['user_id' => $user->id]);
		}
	}
	
	/**
	 * After Email or Phone verification (from new Listing creation),
	 * Match the user's listings (posted as guest) & the user's data (if missed)
	 *
	 * @param $post
	 * @return void
	 */
	public function findAndMatchUserToPost($post): void
	{
		if (empty($post)) {
			return;
		}
		
		$user = null;
		
		// Get (verified) user by (verified) email
		$isVerifiedEmail = (
			config('settings.mail.email_verification') == '1'
			&& !empty($post->email)
			&& !empty($post->email_verified_at)
		);
		if ($isVerifiedEmail) {
			$user = User::where('email', $post->email)->first();
		}
		
		// Get (verified) user by (verified) phone number
		$isVerifiedPhone = (
			config('settings.sms.phone_verification') == '1'
			&& !empty($post->phone)
			&& !empty($post->phone_verified_at)
		);
		if ($isVerifiedPhone) {
			if (empty($user)) {
				$user = User::where('phone', $post->phone)->first();
			}
		}
		
		if (empty($user)) {
			return;
		}
		
		// If the listing email address or phone number is verified
		// and that also the case for the account related to them (even the account user is not logged)
		// Then, attribute this listing to the user that has the same email address or the same phone number
		// And fill the listing's missing contact information with the account data.
		if (empty($post->user_id)) {
			$post->user_id = $user->id;
		}
		if (empty($post->email)) {
			$post->email = $user->email;
		}
		if (empty($post->phone)) {
			$post->phone = $user->phone;
		}
		if ($post->isDirty()) {
			$post->save();
		}
		
		// Use the listing data to complete the user's missing auth data
		$this->completeMissingAuthData($post, $user);
	}
}
