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

use App\Notifications\EmailVerification;

trait EmailVerificationTrait
{
	/**
	 * Email: Send link (It's not an endpoint)
	 * Send email verification link to the user
	 *
	 * @param $entity
	 * @param bool $displayFlashMessage
	 * @return array
	 */
	public function sendEmailVerification($entity, bool $displayFlashMessage = true): array
	{
		$data = []; // No $extra here.
		
		$data['success'] = true;
		$data['emailVerificationSent'] = false;
		
		// Get Entity
		$entityRef = $this->getEntityRef();
		if (empty($entity) || empty($entityRef)) {
			$message = t('Entity ID not found');
			
			$data['success'] = false;
			$data['message'] = $message;
			
			return $data;
		}
		
		// Send Confirmation Email
		try {
			if (request()->filled('locale')) {
				$locale = (array_key_exists(request()->query('locale'), getSupportedLanguages()))
					? request()->query('locale')
					: null;
				
				if (!empty($locale)) {
					$entity->notify((new EmailVerification($entity, $entityRef))->locale($locale));
				} else {
					$entity->notify(new EmailVerification($entity, $entityRef));
				}
			} else {
				$entity->notify(new EmailVerification($entity, $entityRef));
			}
			
			if ($displayFlashMessage) {
				$message = t('An activation link has been sent to you to verify your email address');
				
				$data['success'] = true;
				$data['message'] = $message;
			}
			
			$data['emailVerificationSent'] = true;
			
			return $data;
		} catch (\Throwable $e) {
			$message = changeWhiteSpace($e->getMessage());
			
			$data['success'] = false;
			$data['message'] = $message;
			
			return $data;
		}
	}
	
	/**
	 * Email: Re-send link
	 *
	 * Re-send email verification link to the user
	 *
	 * @queryParam entitySlug string The slug of the entity to verify ('users' or 'posts'). Example: users
	 *
	 * @urlParam entityId int The entity/model identifier (ID). Example: null
	 *
	 * @param $entityId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function reSendEmailVerification($entityId): \Illuminate\Http\JsonResponse
	{
		// Get Entity Reference ID
		$entitySlug = request()->query('entitySlug');
		
		$data = [];
		$data['success'] = true;
		
		$extra = [];
		$extra['emailVerificationSent'] = true;
		
		// Get Entity
		$entityRef = $this->getEntityRef($entitySlug);
		if (empty($entityRef)) {
			return apiResponse()->notFound(t('Entity ID not found'));
		}
		
		// Get Entity by ID
		$model = $entityRef['namespace'];
		$entity = $model::query()->withoutGlobalScopes($entityRef['scopes'])->where('id', $entityId)->first();
		if (empty($entity)) {
			return apiResponse()->notFound(t('Entity ID not found'));
		}
		
		// Check if the Email is already verified
		if (!empty($entity->email_verified_at)) {
			
			$data['success'] = false;
			$data['message'] = t('Your field is already verified', ['field' => t('email_address')]);
			
			// Remove Notification Trigger
			$extra['emailVerificationSent'] = false;
			
		} else {
			
			// Re-Send the confirmation
			if ($this->sendEmailVerification($entity, false)) {
				if (isAdminPanel()) {
					$message = t('The activation link has been sent to the user to verify his email address');
				} else {
					$message = t('The activation link has been sent to you to verify your email address');
				}
				
				$data['success'] = true;
				$data['message'] = $message;
				
				// Remove Notification Trigger
				$extra['emailVerificationSent'] = false;
			}
			
		}
		
		$data['extra'] = $extra;
		
		return apiResponse()->json($data);
	}
}
