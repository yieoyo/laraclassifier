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

namespace App\Providers\AppService;

use App\Models\Permission;

trait AclSystemTrait
{
	/**
	 * Setup ACL system
	 * Check & Migrate Old admin authentication to ACL system
	 */
	private function setupAclSystem(): void
	{
		if (isAdminPanel()) {
			// Check & Fix the default Permissions
			if (!Permission::checkDefaultPermissions()) {
				Permission::resetDefaultPermissions();
			}
		}
	}
}
