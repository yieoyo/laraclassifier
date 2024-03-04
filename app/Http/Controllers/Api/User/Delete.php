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

namespace App\Http\Controllers\Api\User;

use App\Models\Permission;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;

trait Delete
{
	/**
	 * Close the User's Account
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function closeAccount($id): \Illuminate\Http\JsonResponse
	{
		// Get User
		$user = User::withoutGlobalScopes([VerifiedScope::class])->where('id', $id)->first();
		
		if (empty($user)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$authUser = request()->user() ?? auth('sanctum')->user();
		
		// Check logged User
		// Get the User Personal Access Token Object
		$personalAccess = $authUser->tokens()->where('id', getApiAuthToken())->first();
		if (!empty($personalAccess)) {
			if ($personalAccess->tokenable_id != $user->id) {
				return apiResponse()->unauthorized();
			}
		} else {
			$validUserFromGuard = (auth('sanctum')->check() && auth('sanctum')->user()->id == $user->id);
			if (!$validUserFromGuard) {
				return apiResponse()->unauthorized();
			}
		}
		
		// This way can not delete admin users
		if ($user->can(Permission::getStaffPermissions())) {
			return apiResponse()->forbidden(t('admin_users_cannot_be_deleted'));
		}
		
		// Close User's session (by revoking all the user's tokens)
		$user->tokens()->delete();
		
		// Delete User
		$user->delete();
		
		$message = t('your_account_has_been_deleted_1');
		
		return apiResponse()->noContentResource($message);
	}
}
