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

namespace App\Http\Controllers\Api\Post\Store;

use App\Helpers\Ip;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Notifications\SendPasswordAndVerificationInfo;
use Illuminate\Support\Facades\Hash;

trait AutoRegistrationTrait
{
	/**
	 * Auto registers a new user account.
	 *
	 * @param \App\Models\Post|null $post
	 * @param $request
	 * @return array|null
	 */
	public function autoRegister(?Post $post, $request): ?array
	{
		// Don't auto-register the User if he's logged in, ...
		// or when the 'auto_registration' option is disabled,
		// or when the User unchecks the auto-registration checkbox.
		if (
			auth('sanctum')->check()
			|| config('settings.single.auto_registration') == '0'
			|| !request()->filled('auto_registration')
		) {
			return null;
		}
		
		// Don't auto-register the User if the Listing is empty
		if (empty($post)) {
			return null;
		}
		
		// Don't auto-register the User if Email Address and Phone Number are not filled.
		if (empty($post->email) && empty($post->phone)) {
			return null;
		}
		
		// Don't auto-register the User if his Email Address or Phone Number already exist(s)
		$user = User::query()
			->withoutGlobalScopes([VerifiedScope::class])
			->where(function ($query) use ($post) {
				if (!empty($post->email) && !empty($post->phone)) {
					$query->where('email', $post->email)->orWhere('phone', $post->phone);
				} else {
					if (!empty($post->email)) {
						$query->where('email', $post->email);
					}
					if (!empty($post->phone)) {
						$query->where('phone', $post->phone);
					}
				}
			})->first();
		
		if (!empty($user)) {
			return null;
		}
		
		// AUTO-REGISTRATION
		
		// Conditions to Verify User's Email or Phone
		$emailVerificationRequired = config('settings.mail.email_verification') == '1' && !empty($post->email);
		$phoneVerificationRequired = config('settings.sms.phone_verification') == '1' && !empty($post->phone);
		
		// New User
		$user = new User();
		
		// Generate random password
		$randomPassword = getRandomPassword(8);
		
		$user->country_code = $request->input('country_code') ?? config('country.code');
		$user->language_code = $request->input('language_code') ?? config('app.locale');
		$user->name = $post->contact_name;
		$user->auth_field = $post->auth_field ?? getAuthField();
		$user->email = $post->email;
		$user->phone = $post->phone;
		$user->phone_country = $post->phone_country;
		$user->phone_hidden = 0;
		$user->password = Hash::make($randomPassword);
		$user->create_from_ip = $request->input('create_from_ip', Ip::get());
		$user->email_verified_at = now();
		$user->phone_verified_at = now();
		
		// Email verification key generation
		if ($emailVerificationRequired) {
			$user->email_token = md5(microtime() . mt_rand());
			$user->email_verified_at = null;
		}
		
		// Mobile activation key generation
		if ($phoneVerificationRequired) {
			$user->phone_token = mt_rand(100000, 999999);
			$user->phone_verified_at = null;
		}
		
		// Save
		$user->save();
		
		$userResource = (new UserResource($user))->toArray($request);
		
		$data = [];
		
		$data['success'] = true;
		$data['result'] = $userResource;
		
		// Send Generated Password by Email or SMS
		try {
			$user->notify(new SendPasswordAndVerificationInfo($user, $randomPassword));
		} catch (\Throwable $e) {
			$data['success'] = false;
			$data['message'] = $e->getMessage();
		}
		
		return $data;
	}
}
