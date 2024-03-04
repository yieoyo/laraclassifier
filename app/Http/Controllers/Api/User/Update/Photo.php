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

namespace App\Http\Controllers\Api\User\Update;

use App\Helpers\Files\Upload;
use App\Http\Resources\UserResource;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Http\Request;

trait Photo
{
	/**
	 * Update the User's Photo
	 *
	 * @param $userId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updateUserPhoto($userId, Request $request): \Illuminate\Http\JsonResponse
	{
		$user = User::withoutGlobalScopes([VerifiedScope::class])->where('id', $userId)->first();
		
		if (empty($user)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$authUser = auth('sanctum')->user();
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Check logged User
		if ($authUser->getAuthIdentifier() != $user->id) {
			return apiResponse()->unauthorized();
		}
		
		$file = $request->file('photo');
		if (empty($file)) {
			return apiResponse()->error('File is empty.');
		}
		
		// Upload & save the picture
		$param = [
			'destPath' => 'avatars/' . strtolower($user->country_code) . '/' . $user->id,
			'width'    => (int)config('larapen.core.picture.otherTypes.user.width', 800),
			'height'   => (int)config('larapen.core.picture.otherTypes.user.height', 800),
			'ratio'    => config('larapen.core.picture.otherTypes.user.ratio', '1'),
			'upsize'   => config('larapen.core.picture.otherTypes.user.upsize', '0'),
		];
		$user->photo = Upload::image($param['destPath'], $file, $param);
		$user->save();
		
		// Result data
		$data = [
			'success' => true,
			'message' => t('Your photo or avatar have been updated'),
			'result'  => (new UserResource($user))->toArray($request),
		];
		
		$extra = [];
		if (doesRequestIsFromWebApp()) {
			// Get the FileInput plugin's data
			$fileInput = [];
			$fileInput['initialPreview'] = [];
			$fileInput['initialPreviewConfig'] = [];
			
			if (!empty($user->photo)) {
				// Get Deletion Url
				$initialPreviewConfigUrl = url('account/photo/delete');
				
				$photoSize = (isset($this->disk) && $this->disk->exists($user->photo))
					? (int)$this->disk->size($user->photo)
					: 0;
				
				// Extra Fields for AJAX file removal (related to the $initialPreviewConfigUrl)
				$initialPreviewConfigExtra = [
					'_token'  => csrf_token(),
					'_method' => 'PUT',
				];
				
				// Build Bootstrap-FileInput plugin's parameters
				$fileInput['initialPreview'][] = imgUrl($user->photo, 'user');
				
				$fileInput['initialPreviewConfig'][] = [
					'caption' => basename($user->photo),
					'size'    => $photoSize,
					'url'     => $initialPreviewConfigUrl,
					'key'     => $user->id,
					'extra'   => $initialPreviewConfigExtra,
				];
			}
			$extra['fileInput'] = $fileInput;
		}
		
		$data['extra'] = $extra;
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Remove the User's photo
	 *
	 * @param $userId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function removeUserPhoto($userId, Request $request): \Illuminate\Http\JsonResponse
	{
		$user = User::withoutGlobalScopes([VerifiedScope::class])->where('id', $userId)->first();
		
		if (empty($user)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$authUser = auth('sanctum')->user();
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Check logged User
		if ($authUser->getAuthIdentifier() != $user->id) {
			return apiResponse()->unauthorized();
		}
		
		// Remove all the current user's photos, by removing his photos' directory
		$destinationPath = substr($user->photo, 0, strrpos($user->photo, '/'));
		if (!empty($destinationPath) && $this->disk->exists($destinationPath)) {
			$this->disk->deleteDirectory($destinationPath);
		}
		
		// Delete the photo path from DB
		$user->photo = null;
		$user->save();
		
		// Result data
		$data = [
			'success' => true,
			'message' => t('Your photo or avatar has been deleted'),
			'result'  => (new UserResource($user))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
}
