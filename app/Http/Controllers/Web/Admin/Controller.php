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

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Public\Traits\CommonTrait;
use App\Http\Controllers\Web\Public\Traits\RobotsTxtTrait;
use App\Models\Setting;

class Controller extends \App\Http\Controllers\Controller
{
	use RobotsTxtTrait, CommonTrait;
	
	public $request;
	
	/**
	 * Controller constructor.
	 */
	public function __construct()
	{
		// Set the storage disk
		$this->setStorageDisk();
		
		// Check & Change the App Key (If needed)
		$this->checkAndGenerateAppKey();
		
		// Get Settings (for Sidebar Menu)
		$this->getSettings();
		
		// Load the Plugins
		$this->loadPlugins();
		
		// Generated the robots.txt file (If not exists)
		$this->checkRobotsTxtFile();
	}
	
	/**
	 * Get Settings (for Sidebar Menu)
	 *
	 * @return void
	 */
	private function getSettings(): void
	{
		$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400);
		
		try {
			$cacheId = 'all.settings.admin.sidebar';
			$settings = cache()->remember($cacheId, $cacheExpiration, function () {
				return Setting::query()->get(['id', 'key', 'name']);
			});
		} catch (\Throwable $e) {
			$settings = collect();
		}
		
		view()->share('settings', $settings);
	}
}
