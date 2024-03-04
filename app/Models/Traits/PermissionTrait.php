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
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

trait PermissionTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function createDefaultEntriesButton($xPanel = false): ?string
	{
		if (!config('larapen.admin.allow_permission_create')) {
			return null;
		}
		
		$url = admin_url('permissions/create_default_entries');
		
		$out = '<a class="btn btn-success shadow" href="' . $url . '">';
		$out .= '<i class="fa fa-industry"></i> ';
		$out .= trans('admin.Reset the Permissions');
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	/**
	 * Check default permissions
	 *
	 * @return bool
	 */
	public static function checkDefaultPermissions(): bool
	{
		if (!Role::checkSuperAdminRole() || !Permission::checkSuperAdminPermissions()) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check Super Admin permissions
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return bool
	 */
	public static function checkSuperAdminPermissions(): bool
	{
		try {
			$superAdminPermissions = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
			if (!empty($superAdminPermissions)) {
				foreach ($superAdminPermissions as $superAdminPermission) {
					$permission = Permission::where('name', $superAdminPermission)->first();
					if (empty($permission)) {
						return false;
					}
				}
			} else {
				return false;
			}
		} catch (\Throwable $e) {
		}
		
		return true;
	}
	
	/**
	 * Reset default permissions
	 * NOTE: Must use try {...} catch {...}
	 */
	public static function resetDefaultPermissions(): void
	{
		try {
			// Create the default Super Admin role
			$role = Role::resetDefaultRole();
			if (empty($role)) {
				return;
			}
			
			// Remove all current permissions & their relationship
			$permissions = Permission::all();
			$permissions->each(function ($item, $key) {
				if ($item->roles()->count() > 0) {
					$item->roles()->detach();
				}
				$item->delete();
			});
			
			// Reset permissions table ID auto-increment
			DB::statement('ALTER TABLE ' . DBTool::table(config('permission.table_names.permissions')) . ' AUTO_INCREMENT = 1;');
			
			// Create default Super Admin permissions
			$superAdminPermissions = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
			if (!empty($superAdminPermissions)) {
				foreach ($superAdminPermissions as $superAdminPermission) {
					$permission = self::where('name', $superAdminPermission)->first();
					if (empty($permission)) {
						$permission = self::create(['name' => $superAdminPermission]);
					}
					$role->givePermissionTo($permission);
				}
			}
			
			// Assign the Super Admin role to the old admin users
			$admins = User::where('is_admin', 1)->get();
			if ($admins->count() > 0) {
				foreach ($admins as $admin) {
					if (isset($role->name)) {
						$admin->removeRole($role->name);
						$admin->assignRole($role->name);
					}
				}
			}
		} catch (\Throwable $e) {
		}
	}
	
	/**
	 * Get all Admin Controllers public methods
	 *
	 * @return array
	 */
	public static function defaultPermissions(): array
	{
		$permissions = Permission::getRoutesPermissions();
		
		return collect($permissions)->mapWithKeys(function ($item) {
			return [$item['permission'] => $item['permission']];
		})->sort()->toArray();
	}
	
	/**
	 * @return array
	 */
	public static function getRoutesPermissions(): array
	{
		$routeCollection = Route::getRoutes();
		
		$defaultAccess = ['list', 'create', 'update', 'delete', 'reorder', 'details_row'];
		$defaultAllowAccess = ['list', 'create', 'update', 'delete'];
		$defaultDenyAccess = ['reorder', 'details_row'];
		
		// Controller's Action => Access
		$accessOfActionMethod = [
			'index'                    => 'list',
			'show'                     => 'list',
			'create'                   => 'create',
			'store'                    => 'create',
			'edit'                     => 'update',
			'update'                   => 'update',
			'reorder'                  => 'update',
			'saveReorder'              => 'update',
			'listRevisions'            => 'update',
			'restoreRevision'          => 'update',
			'destroy'                  => 'delete',
			'bulkDelete'               => 'delete',
			'saveAjaxRequest'          => 'update',
			'dashboard'                => 'access', // Dashboard
			'redirect'                 => 'access', // Dashboard
			'syncFilesLines'           => 'update', // Languages
			'showTexts'                => 'update', // Languages
			'updateTexts'              => 'update', // Languages
			'createDefaultPermissions' => 'create', // Permissions
			'reset'                    => 'delete', // Homepage Sections
			'download'                 => 'download', // Backup
			'banUser'                  => 'ban-users', // Blacklist
			'make'                     => 'make', // Inline Requests
			'install'                  => 'install', // Plugins
			'uninstall'                => 'uninstall', // Plugins
			'reSendEmailVerification'  => 'resend-verification-notification',
			'reSendPhoneVerification'  => 'resend-verification-notification',
			'systemInfo'               => 'info',
			
			'createBulkCountriesSubDomain' => 'create', // Domain Mapping
			'generate'                     => 'create',
		];
		$tab = $data = [];
		foreach ($routeCollection as $key => $value) {
			
			// Init.
			$data['filePath'] = null;
			$data['actionMethod'] = null;
			$data['methods'] = [];
			$data['permission'] = null;
			
			// Get & Clear the route prefix
			$routePrefix = $value->getPrefix();
			$routePrefix = trim($routePrefix, '/');
			if ($routePrefix != 'admin') {
				$routePrefix = head(explode('/', $routePrefix));
			}
			
			// Exit, if the prefix is still not that of the admin panel
			if ($routePrefix != 'admin') {
				continue;
			}
			
			$data['methods'] = $value->methods();
			
			$data['uri'] = $value->uri();
			$data['uri'] = preg_replace('#\{[^}]+}#', '*', $data['uri']);
			
			$controllerActionPath = $value->getActionName();
			
			try {
				$controllerNamespace = '\\' . preg_replace('#@.+#i', '', $controllerActionPath);
				$reflector = new \ReflectionClass($controllerNamespace);
				$data['filePath'] = $filePath = $reflector->getFileName();
			} catch (\Throwable $e) {
				$data['filePath'] = $filePath = null;
			}
			
			$data['actionMethod'] = $actionMethod = $value->getActionMethod();
			$access = (isset($accessOfActionMethod[$actionMethod])) ? $accessOfActionMethod[$actionMethod] : null;
			
			if (!empty($filePath) && file_exists($filePath)) {
				$content = file_get_contents($filePath);
				
				if (str_contains($content, 'extends PanelController')) {
					$allowAccess = [];
					$denyAccess = [];
					
					if (str_contains($controllerActionPath, '\PermissionController')) {
						if (!config('larapen.admin.allow_permission_create')) {
							$denyAccess[] = 'create';
						}
						if (!config('larapen.admin.allow_permission_update')) {
							$denyAccess[] = 'update';
						}
						if (!config('larapen.admin.allow_permission_delete')) {
							$denyAccess[] = 'delete';
						}
					} else if (str_contains($controllerActionPath, '\RoleController')) {
						if (!config('larapen.admin.allow_role_create')) {
							$denyAccess[] = 'create';
						}
						if (!config('larapen.admin.allow_role_update')) {
							$denyAccess[] = 'update';
						}
						if (!config('larapen.admin.allow_role_delete')) {
							$denyAccess[] = 'delete';
						}
					} else {
						// Get allowed accesses
						$matches = [];
						preg_match('#->allowAccess\(([^)]+)\);#', $content, $matches);
						$allowAccessStr = !empty($matches[1]) ? $matches[1] : '';
						
						if (!empty($allowAccessStr)) {
							$matches = [];
							preg_match_all("#'([^']+)'#", $allowAccessStr, $matches);
							$allowAccess = !empty($matches[1]) ? $matches[1] : [];
							
							if (empty($denyAccess)) {
								$matches = [];
								preg_match_all('#"([^"]+)"#', $allowAccessStr, $matches);
								$allowAccess = !empty($matches[1]) ? $matches[1] : [];
							}
						}
						
						// Get denied accesses
						$matches = [];
						preg_match('#->denyAccess\(([^)]+)\);#', $content, $matches);
						$denyAccessStr = !empty($matches[1]) ? $matches[1] : '';
						
						if (!empty($denyAccessStr)) {
							$matches = [];
							preg_match_all("#'([^']+)'#", $denyAccessStr, $matches);
							$denyAccess = !empty($matches[1]) ? $matches[1] : [];
							
							if (empty($denyAccess)) {
								$matches = [];
								preg_match_all('#"([^"]+)"#', $denyAccessStr, $matches);
								$denyAccess = !empty($matches[1]) ? $matches[1] : [];
							}
						}
					}
					
					$allowAccess = array_merge($defaultAllowAccess, (array)$allowAccess);
					$denyAccess = array_merge($defaultDenyAccess, (array)$denyAccess);
					
					$availableAccess = array_merge(array_diff($allowAccess, $defaultAccess), $defaultAccess);
					$availableAccess = array_diff($availableAccess, $denyAccess);
					
					if (in_array($access, $defaultAccess)) {
						if (!in_array($access, $availableAccess)) {
							continue;
						}
					}
				}
			}
			
			if (str_contains($controllerActionPath, '\ActionController')) {
				$data['permission'] = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $actionMethod));
			} else {
				$matches = [];
				preg_match('#\\\([a-zA-Z0-9]+)Controller@#', $controllerActionPath, $matches);
				$controllerSlug = !empty($matches[1]) ? $matches[1] : '';
				$controllerSlug = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $controllerSlug));
				$data['permission'] = (!empty($access)) ? $controllerSlug . '-' . $access : null;
			}
			
			if (empty($data['permission'])) {
				continue;
			}
			
			if (array_key_exists('filePath', $data)) {
				unset($data['filePath']);
			}
			if (array_key_exists('actionMethod', $data)) {
				unset($data['actionMethod']);
			}
			
			// Save It!
			$tab[$key] = $data;
			
		}
		
		return $tab;
	}
}
