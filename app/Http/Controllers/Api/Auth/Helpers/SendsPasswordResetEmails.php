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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

trait SendsPasswordResetEmails
{
	/**
	 * Send a reset link to the given user.
	 *
	 * @bodyParam email string required The user's email address. Example: null
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendResetLinkEmail(Request $request): \Illuminate\Http\JsonResponse
	{
		$request->validate(['email' => 'required|email']);
		
		$credentials = $request->only('email');
		
		// We will send the password reset link to this user. Once we have attempted
		// to send the link, we will examine the response then see the message we
		// need to show to the user. Finally, we'll send out a proper response.
		$status = Password::sendResetLink($credentials);
		
		$message = trans($status);
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => null,
			'extra'   => [
				'codeSentTo' => 'email',
			],
		];
		
		return $status === Password::RESET_LINK_SENT
			? apiResponse()->json($data)
			: apiResponse()->error($message);
	}
}
