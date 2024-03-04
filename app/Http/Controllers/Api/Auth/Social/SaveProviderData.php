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

namespace App\Http\Controllers\Api\Auth\Social;

use App\Helpers\Ip;
use App\Http\Resources\UserResource;
use App\Models\Blacklist;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Notifications\SendPasswordAndVerificationInfo;
use App\Notifications\UserNotification;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

trait SaveProviderData
{
	private string $userNotSavedError = "Unknown error. User data not saved.";
	
	/**
	 * @param string $provider
	 * @param SocialiteUser $providerData
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function saveUser(string $provider, SocialiteUser $providerData): \Illuminate\Http\JsonResponse
	{
		// Get the Country Code
		$countryCode = config('country.code', config('ipCountry.code'));
		
		try {
			$remoteId = $providerData->getId();
			$name = $this->getName($providerData);
			$email = $providerData->getEmail();
			// $avatar = $providerData->getAvatar();
			
			// Check if the user's email address has been banned
			$bannedUser = Blacklist::ofType('email')->where('entry', $email)->first();
			if (!empty($bannedUser)) {
				return apiResponse()->error(t('This user has been banned'));
			}
			
			// GET LOCAL USER
			$user = User::query()
				->withoutGlobalScopes([VerifiedScope::class])
				->where('provider', $provider)
				->where('provider_id', $remoteId)
				->first();
			
			// CREATE LOCAL USER IF DON'T EXISTS
			if (empty($user)) {
				// Before... Check if user has not signed up with an email
				$user = User::query()
					->withoutGlobalScopes([VerifiedScope::class])
					->where('email', $email)
					->first();
				
				if (empty($user)) {
					// Generate random password
					$randomPassword = getRandomPassword(8);
					
					// Register the User (As New User)
					$userInfo = [
						'country_code'      => $countryCode,
						'language_code'     => config('app.locale'),
						'name'              => $name,
						'auth_field'        => 'email',
						'email'             => $email,
						'password'          => Hash::make($randomPassword),
						'create_from_ip'    => Ip::get(),
						'email_verified_at' => now(),
						'phone_verified_at' => now(),
						'provider'          => $provider,
						'provider_id'       => $remoteId,
						'created_at'        => now()->format('Y-m-d H:i:s'),
					];
					$user = new User($userInfo);
					$user->save();
					
					// Send Generated Password by Email
					try {
						$user->notify(new SendPasswordAndVerificationInfo($user, $randomPassword));
					} catch (\Throwable $e) {
					}
					
					// Update Listings created by this email
					if (isset($user->id) && $user->id > 0) {
						Post::query()
							->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
							->where('email', $userInfo['email'])
							->update(['user_id' => $user->id]);
					}
					
					// Send Admin Notification Email
					if (config('settings.mail.admin_notification') == 1) {
						try {
							// Get all admin users
							$admins = User::permission(Permission::getStaffPermissions())->get();
							if ($admins->count() > 0) {
								Notification::send($admins, new UserNotification($user));
							}
						} catch (\Throwable $e) {
						}
					}
					
				} else {
					// Update 'created_at' if empty (for time ago module)
					if (empty($user->created_at)) {
						$user->created_at = now()->format('Y-m-d H:i:s');
					}
					$user->email_verified_at = now();
					$user->phone_verified_at = now();
					$user->save();
				}
			} else {
				// Update 'created_at' if empty (for time ago module)
				if (empty($user->created_at)) {
					$user->created_at = now()->format('Y-m-d H:i:s');
				}
				$user->email_verified_at = now();
				$user->phone_verified_at = now();
				$user->save();
			}
			
			return $this->loginUser($user, $provider);
		} catch (\Throwable $e) {
			$message = $e->getMessage();
			if (empty($message)) {
				$message = $this->userNotSavedError ?? '';
			}
			
			return apiResponse()->error($message);
		}
	}
	
	/**
	 * @param \App\Models\User $user
	 * @param string|null $deviceName
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function loginUser(User $user, ?string $deviceName = null): \Illuminate\Http\JsonResponse
	{
		// Revoke previous tokens
		$user->tokens()->delete();
		
		if (auth()->loginUsingId($user->id)) {
			// Create the API access token
			$deviceName = !empty($deviceName) ? ucfirst($deviceName) : 'Desktop Web';
			$token = $user->createToken($deviceName);
			
			$data = [
				'success' => true,
				'result'  => new UserResource($user),
				'extra'   => [
					'authToken' => $token->plainTextToken,
					'tokenType' => 'Bearer',
				],
			];
			
			return apiResponse()->json($data);
		} else {
			return apiResponse()->error(t('Error on user\'s login.'));
		}
	}
	
	/**
	 * @param \Laravel\Socialite\Contracts\User $providerData
	 * @return string|null
	 */
	private function getName(SocialiteUser $providerData): ?string
	{
		$name = $providerData->getName();
		if ($name != '') {
			return $name;
		}
		
		// Get the user's name (First Name & Last Name)
		$name = (isset($providerData->name) && is_string($providerData->name)) ? $providerData->name : '';
		if ($name == '') {
			// facebook
			if (isset($providerData->user['first_name']) && isset($providerData->user['last_name'])) {
				$name = $providerData->user['first_name'] . ' ' . $providerData->user['last_name'];
			}
		}
		if ($name == '') {
			// linkedin
			$name = (isset($providerData->user['formattedName'])) ? $providerData->user['formattedName'] : '';
			if ($name == '') {
				if (isset($providerData->user['firstName']) && isset($providerData->user['lastName'])) {
					$name = $providerData->user['firstName'] . ' ' . $providerData->user['lastName'];
				}
			}
		}
		
		return is_string($name) ? $name : 'Unnamed User';
	}
}
