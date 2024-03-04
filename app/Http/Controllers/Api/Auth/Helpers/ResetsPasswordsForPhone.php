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
use App\Models\PasswordReset;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

trait ResetsPasswordsForPhone
{
	/**
	 * Reset password token
	 *
	 * Reset password token verification
	 *
	 * @bodyParam code string required Verification code (received by SMS). Example: null
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendResetToken(Request $request): \Illuminate\Http\JsonResponse
	{
		// Form validation
		$request->validate(['code' => 'required']);
		
		// Check if the token exists
		$passwordReset = PasswordReset::where('token', $request->input('code'))->first();
		if (empty($passwordReset)) {
			$msg = t('The entered code is invalid');
			
			return apiResponse()->error($msg);
		}
		
		return apiResponse()->success();
	}
	
	/**
	 * Reset the given user's password.
	 *
	 * @bodyParam token string required Verification code (received by SMS). Example: null
	 * @bodyParam phone string required The user's phone number. Example: null
	 * @bodyParam phone_country string required The user's phone number's country code (Required when the 'phone' field is filled). Example: null
	 * @bodyParam password string required The user's password. Example: null
	 * @bodyParam password_confirmation string required The confirmation of the user's password. Example: null
	 * @bodyParam device_name string required The user's device name. Example: null
	 *
	 * @param \App\Http\Requests\Front\ResetPasswordRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resetForPhone(ResetPasswordRequest $request): \Illuminate\Http\JsonResponse
	{
		// Check if Password request exists
		$passwordReset = PasswordReset::query()
			->where('token', $request->input('token'))
			->where('phone', $request->input('phone'))
			->first();
		if (empty($passwordReset)) {
			$msg = t('The code does not match your email or phone number');
			
			return apiResponse()->error($msg);
		}
		
		// Get User
		$user = User::where('phone', $passwordReset->phone)->first();
		if (empty($user)) {
			$msg = t('The entered value is not registered with us');
			
			return apiResponse()->error($msg);
		}
		
		// Update the User
		$user->password = Hash::make($request->input('password'));
		
		$user->phone_verified_at = now();
		if ($user->can(Permission::getStaffPermissions())) {
			// Email address auto-verified (for Admin Users)
			$user->email_verified_at = now();
		}
		
		$user->save();
		
		// Remove password reset data
		$passwordReset->delete();
		
		// Auto-Auth the User (API)
		// By creating an API token for the User
		return $this->createUserApiToken($user, $request->input('device_name'));
	}
}
