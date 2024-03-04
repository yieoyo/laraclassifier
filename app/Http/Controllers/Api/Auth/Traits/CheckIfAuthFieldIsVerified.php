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

use App\Helpers\Arr;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;

trait CheckIfAuthFieldIsVerified
{
	/**
	 * Check if a model object has an auth field to be verified
	 * If yes, check that field is verified,
	 * If it's not verified, output the verification code re-send message
	 *
	 * @param $authUser
	 * @param $user
	 * @param string|null $authField
	 * @return array
	 */
	public function userHasVerifiedLogin($authUser = null, $user = null, ?string $authField = null): array
	{
		$error = [
			'type'   => null,
			'field'  => null,
			'button' => [
				'url'   => null,
				'label' => null,
			],
		];
		$data = [
			'success' => false,
			'message' => 'Error: Unauthorized',
			'extra'   => ['error' => $error],
		];
		
		if (empty($authUser)) {
			return $data;
		}
		
		if (!empty($authField)) {
			// Get the login verification field's name
			$loginVerificationField = $authField . '_verified_at';
			
			// Check if the 'users' table is up-to-date
			// If the 'users' table's login verification field are not available, don't check them. So keep user logged.
			$usersTableIsUpToDate = (in_array($authField, ['email', 'phone']))
				? Arr::keyExists($loginVerificationField, $user)
				: (Arr::keyExists('email_verified_at', $user) && Arr::keyExists('phone_verified_at', $user));
			if (!$usersTableIsUpToDate) {
				$data['success'] = true;
				$data['message'] = null;
				
				return $data;
			}
			
			// Check if the user has a verified login (email address or/and phone number)
			$userHasVerifiedLogin = (in_array($authField, ['email', 'phone']))
				? !empty($user->{$loginVerificationField})
				: (!empty($authUser->email_verified_at) && !empty($authUser->phone_verified_at));
			
		} else {
			// Check if the 'users' table is up-to-date
			// If the 'users' table's login verification field are not available, don't check them. So keep user logged.
			$usersTableIsUpToDate = (Arr::keyExists('email_verified_at', $authUser) && Arr::keyExists('phone_verified_at', $authUser));
			if (!$usersTableIsUpToDate) {
				$data['success'] = true;
				$data['message'] = null;
				
				return $data;
			}
			
			// Check if the user has a verified login (email address or/and phone number)
			$userHasVerifiedLogin = (!empty($authUser->email_verified_at) && !empty($authUser->phone_verified_at));
		}
		
		// The user's login (email address and/or phone number) is/are verified
		if ($userHasVerifiedLogin) {
			$data['success'] = true;
			$data['message'] = null;
			
			return $data;
		}
		
		// The user's login (email address and/or phone number) is/are NOT verified
		// Invalid user (Log out user) that does not have a non-verified login (email address or/and phone number)
		$this->invalidateTheAuthenticatedUser();
		
		$user = null;
		$apiPath = (isFromApi() && !doesRequestIsFromWebApp()) ? 'api/' : '';
		
		// phone
		if (empty($authUser->phone_verified_at)) {
			if (empty($authUser->phone_token)) {
				if (empty($user)) {
					$user = User::query()
						->withoutGlobalScopes([VerifiedScope::class])
						->where('id', $authUser->id)
						->first();
				}
				$user->phone_token = mt_rand(100000, 999999);
			}
			
			$error['type'] = 'unverified';
			$error['field'] = 'phone';
			$error['button']['url'] = url($apiPath . 'users/' . $authUser->id . '/verify/resend/sms');
			$error['button']['label'] = t('Re-send');
			
			$data['message'] = t('need_to_confirm_your_account_text_phone');
			$data['message'] .= ' ' . t('Resend the verification message to verify your phone number');
		}
		
		// email
		if (empty($authUser->email_verified_at)) {
			if (empty($authUser->email_token)) {
				if (empty($user)) {
					$user = User::query()
						->withoutGlobalScopes([VerifiedScope::class])
						->where('id', $authUser->id)
						->first();
				}
				$user->email_token = md5(microtime() . mt_rand());
			}
			
			$error['type'] = 'unverified';
			$error['field'] = 'email';
			$error['button']['url'] = url($apiPath . 'users/' . $authUser->id . '/verify/resend/email');
			$error['button']['label'] = t('Re-send');
			
			$data['message'] = t('need_to_confirm_your_account_text_email');
			$data['message'] .= ' ' . t('Resend the verification message to verify your email address');
		}
		
		if (!isFromApi() || doesRequestIsFromWebApp()) {
			if (empty($authUser->phone_verified_at) || empty($authUser->email_verified_at)) {
				$data['message'] .= ' ';
				$data['message'] .= '<a href="' . $error['button']['url'] . '" class="btn btn-sm btn-danger">';
				$data['message'] .= $error['button']['label'];
				$data['message'] .= '</a>';
			}
		}
		
		// Fill the fields tokens (If they are missed)
		$isLoginTokenUpdated = !empty($user)
			&& (
				$user->phone_token != $authUser->phone_token
				|| $user->email_token != $authUser->email_token
			);
		if ($isLoginTokenUpdated) {
			$user->save();
		}
		
		$data['extra']['error'] = $error;
		
		return $data;
	}
	
	/**
	 * Invalidate the authenticated user
	 *
	 * @return void
	 */
	private function invalidateTheAuthenticatedUser(): void
	{
		if (isFromApi()) {
			
			try {
				$authUser = request()->user() ?? auth('sanctum')->user();
				
				auth()->logout();
				
				// Revoke all tokens
				$authUser->tokens()->delete();
			} catch (\Throwable $e) {
			}
			
		} else {
			
			// Get the current Country
			$countryCode = null;
			if (session()->has('countryCode')) {
				$countryCode = session('countryCode');
			}
			$allowMeFromReferrer = null;
			if (session()->has('allowMeFromReferrer')) {
				$allowMeFromReferrer = session('allowMeFromReferrer');
			}
			
			// Remove all session vars
			auth()->logout();
			request()->session()->flush();
			request()->session()->regenerate();
			
			// Retrieve the current Country
			if (!empty($countryCode)) {
				session()->put('countryCode', $countryCode);
			}
			if (!empty($allowMeFromReferrer)) {
				session()->put('allowMeFromReferrer', $allowMeFromReferrer);
			}
			
		}
	}
}
