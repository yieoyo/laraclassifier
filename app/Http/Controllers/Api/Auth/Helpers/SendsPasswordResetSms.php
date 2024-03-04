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

use App\Models\PasswordReset;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;

trait SendsPasswordResetSms
{
	/**
	 * Send a reset code to the given user.
	 *
	 * @bodyParam phone string required The user's phone number. Example: null
	 * @bodyParam phone_country string required The user's phone number's country code (Required when the 'phone' field is filled). Example: null
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendResetTokenSms(Request $request): \Illuminate\Http\JsonResponse
	{
		// Form validation
		$request->validate(['phone' => 'required']);
		
		// Check if the phone exists
		$user = User::where('phone', $request->input('phone'))->first();
		if (empty($user)) {
			$msg = t('The entered value is not registered with us');
			
			return apiResponse()->error($msg);
		}
		
		// Create the token in database
		$token = mt_rand(100000, 999999);
		$passwordReset = PasswordReset::where('phone', $request->input('phone'))->first();
		if (empty($passwordReset)) {
			$passwordResetInfo = [
				'email'         => null,
				'phone'         => $request->input('phone'),
				'phone_country' => $request->input('phone_country'),
				'token'         => $token,
				'created_at'    => date('Y-m-d H:i:s'),
			];
			$passwordReset = new PasswordReset($passwordResetInfo);
		} else {
			$passwordReset->token = $token;
			$passwordReset->created_at = date('Y-m-d H:i:s');
		}
		$passwordReset->save();
		
		try {
			// Send the token by SMS
			$passwordReset->notify(new ResetPasswordNotification($user, $token, 'phone'));
		} catch (\Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
		
		$message = t('code_sent_by_sms');
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => null,
			'extra'   => [
				'codeSentTo' => 'phone',
				'code'       => $token,
			],
		];
		
		return apiResponse()->json($data);
	}
}
