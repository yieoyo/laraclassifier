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

namespace App\Http\Controllers\Web\Public\Traits;

use App\Helpers\Arr;
use App\Helpers\Files\Storage\StorageDisk;
use Illuminate\Support\Facades\Artisan;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

trait CommonTrait
{
	public $disk;
	
	/**
	 * Set the storage disk
	 */
	private function setStorageDisk(): void
	{
		// Get the storage disk
		$this->disk = StorageDisk::getDisk();
		view()->share('disk', $this->disk);
	}
	
	/**
	 * Check & Change the App Key (If needed, for security reasons)
	 */
	private function checkAndGenerateAppKey(): void
	{
		try {
			if (DotenvEditor::keyExists('APP_KEY')) {
				if (DotenvEditor::getValue('APP_KEY') == 'SomeRandomStringWith32Characters') {
					// Generating a new App Key, remove (or clear) all the sessions and cookies
					Artisan::call('key:generate', ['--force' => true]);
				}
			}
		} catch (\Throwable $e) {}
	}
	
	/**
	 * Load all the installed plugins
	 */
	private function loadPlugins(): void
	{
		$plugins = plugin_installed_list();
		$plugins = collect($plugins)->map(function ($item, $key) {
			if (is_object($item)) {
				$item = Arr::fromObject($item);
			}
			if (!empty($item['item_id'])) {
				$item['installed'] = plugin_check_purchase_code($item);
			}
			
			return $item;
		})->toArray();
		
		config()->set('plugins', $plugins);
		config()->set('plugins.installed', collect($plugins)->whereStrict('installed', true)->toArray());
	}
}
