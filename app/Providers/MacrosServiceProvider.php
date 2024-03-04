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

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class MacrosServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		// $files = glob(__DIR__ . '/../Macros/*.php');
		$files = glob_recursive(__DIR__ . '/../Macros/*.php');
		
		$files = Collection::make($files)->mapWithKeys(function ($path) {
			return [$path => pathinfo($path, PATHINFO_FILENAME)];
		})->reject(function ($macro) {
			return Collection::hasMacro($macro);
		});
		
		$files->each(function ($macro, $path) {
			require_once $path;
		});
	}
}
