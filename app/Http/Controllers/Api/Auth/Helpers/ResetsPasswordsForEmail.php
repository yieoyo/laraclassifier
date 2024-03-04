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

namespace App\Http\Controllers\Api\Auth\Helpers;

use App\Http\Requests\Front\ResetPasswordRequest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

trait ResetsPasswordsForEmail
{
	/**
	 * Reset the given user's password.
	 *
	 * @bodyParam token string required Verification code (received by SMS). Example: null
	 * @bodyParam email string required The user's email address. Example: null
	 * @bodyParam password string required The user's password. Example: null
	 * @bodyParam password_confirmation string required The confirmation of the user's password. Example: null
	 * @bodyParam device_name string required The user's device name. Example: null
	 *
	 * @param \App\Http\Requests\Front\ResetPasswordRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resetForEmail(ResetPasswordRequest $request): \Illuminate\Http\JsonResponse
	{
		$credentials = $request->only('email', 'password', 'password_confirmation', 'token');
		
		// Here we will attempt to reset the user's password. If it is successful we
		// will update the password on an actual user model and persist it to the
		// database. Otherwise, we will parse the error and return the response.
		$status = Password::reset(
			$credentials,
			function ($user, $password) use ($request) {
				$user->password = Hash::make($password);
				
				$user->setRememberToken(Str::random(60));
				
				$user->email_verified_at = now();
				if ($user->can(Permission::getStaffPermissions())) {
					// Phone auto-verified (for Admin Users)
					$user->phone_verified_at = now();
				}
				
				$user->save();
				
				event(new PasswordReset($user));
			}
		);
		
		if ($status == Password::PASSWORD_RESET) {
			$user = User::where('email', $request->input('email'))->first();
			
			if (!empty($user)) {
				if (Hash::check($request->input('password'), $user->password)) {
					// Auto-Auth the User (API)
					// By creating an API token for the User
					return $this->createUserApiToken($user, $request->input('device_name'), trans($status));
				}
			}
			
			return apiResponse()->success(trans($status));
		} else {
			return apiResponse()->error(trans($status));
		}
	}
}
