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

namespace App\Models;

use App\Models\Traits\PermissionTrait;
use App\Observers\PermissionObserver;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use Spatie\Permission\Models\Permission as OriginalPermission;

class Permission extends OriginalPermission
{
	use Crud;
	use PermissionTrait;
	
	protected $fillable = ['name', 'guard_name', 'updated_at', 'created_at'];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	protected static function boot()
	{
		parent::boot();
		
		Permission::observe(PermissionObserver::class);
	}
	
	/**
	 * Default Super Admin users permissions
	 *
	 * @return array
	 */
	public static function getSuperAdminPermissions(): array
	{
		return [
			'permission-list',
			'permission-create',
			'permission-update',
			'permission-delete',
			'role-list',
			'role-create',
			'role-update',
			'role-delete',
		];
	}
	
	/**
	 * Default Staff users permissions
	 *
	 * @return array
	 */
	public static function getStaffPermissions(): array
	{
		return [
			'dashboard-access',
		];
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
