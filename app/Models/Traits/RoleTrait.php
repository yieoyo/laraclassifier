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

namespace App\Models\Traits;

use App\Helpers\DBTool;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

trait RoleTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function updateButton($xPanel = false): string
	{
		$out = '';
		
		if (strtolower($this->name) == strtolower(Role::getSuperAdminRole())) {
			return $out;
		}
		
		$url = admin_url('roles/' . $this->id . '/edit');
		
		$out = '<a href="' . $url . '" class="btn btn-xs btn-primary">';
		$out .= '<i class="fa fa-edit"></i> ';
		$out .= trans('admin.edit');
		$out .= '</a>';
		
		return $out;
	}
	
	public function deleteButton($xPanel = false): string
	{
		$out = '';
		
		if (strtolower($this->name) == strtolower(Role::getSuperAdminRole())) {
			return $out;
		}
		
		$url = admin_url('roles/' . $this->id);
		
		$out = '<a href="' . $url . '" class="btn btn-xs btn-danger" data-button-type="delete">';
		$out .= '<i class="fa fa-trash"></i> ';
		$out .= trans('admin.delete');
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	/**
	 * Check Super Admin role
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return bool
	 */
	public static function checkSuperAdminRole(): bool
	{
		try {
			$role = Role::where('name', Role::getSuperAdminRole())->first();
			if (empty($role)) {
				return false;
			}
		} catch (\Throwable $e) {
		}
		
		return true;
	}
	
	/**
	 * Reset default roles
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return \App\Models\Role|\Illuminate\Database\Eloquent\Model
	 */
	public static function resetDefaultRole()
	{
		try {
			// Remove all current roles & their relationship
			$roles = Role::all();
			$roles->each(function ($item, $key) {
				if ($item->permissions()) {
					$item->permissions()->detach();
				}
				$item->delete();
			});
			
			// Reset roles table ID auto-increment
			DB::statement('ALTER TABLE ' . DBTool::table(config('permission.table_names.roles')) . ' AUTO_INCREMENT = 1;');
			
			// Create the default Super Admin role
			$role = Role::where('name', Role::getSuperAdminRole())->first();
			if (empty($role)) {
				$role = Role::create(['name' => Role::getSuperAdminRole()]);
			}
		} catch (\Throwable $e) {
			return null;
		}
		
		return $role;
	}
}
