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

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use Illuminate\Http\Response;

trait Access
{
	/*
	|--------------------------------------------------------------------------
	|                                   CRUD ACCESS
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * @param $access
	 * @return array
	 */
	public function allowAccess($access)
	{
		return $this->access = array_merge(array_diff((array)$access, $this->access), $this->access);
	}
	
	/**
	 * @param $access
	 * @return array
	 */
	public function denyAccess($access)
	{
		return $this->access = array_diff($this->access, (array)$access);
	}
	
	/**
	 * Check if a permission is enabled for a Crud Panel. Return false if not.
	 *
	 * @param $permission
	 * @return bool
	 */
	public function hasAccess($permission): bool
	{
		return in_array($permission, $this->access);
	}
	
	/**
	 * Check if any permission is enabled for a Crud Panel. Return false if not.
	 *
	 * @param $permissionArray
	 * @return bool
	 */
	public function hasAccessToAny($permissionArray): bool
	{
		foreach ($permissionArray as $key => $permission) {
			if (in_array($permission, $this->access)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Check if all permissions are enabled for a Crud Panel. Return false if not.
	 *
	 * @param $permissionArray
	 * @return bool
	 */
	public function hasAccessToAll($permissionArray): bool
	{
		foreach ($permissionArray as $key => $permission) {
			if (!in_array($permission, $this->access)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Check if a permission is enabled for a Crud Panel. Fail if not.
	 *
	 * @param $permission
	 */
	public function hasAccessOrFail($permission)
	{
		if (!in_array($permission, $this->access)) {
			abort(Response::HTTP_FORBIDDEN, trans('admin.unauthorized_access'));
		}
	}
}
