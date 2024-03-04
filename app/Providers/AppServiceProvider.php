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

use App\Models\Sanctum\PersonalAccessToken;
use App\Providers\AppService\AclSystemTrait;
use App\Providers\AppService\ConfigTrait;
use App\Providers\AppService\SymlinkTrait;
use App\Providers\AppService\TelescopeTrait;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
	use TelescopeTrait, AclSystemTrait, ConfigTrait, SymlinkTrait;
	
	private int $cacheExpiration = 86400; // Cache for 1 day (60 * 60 * 24)
	
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
	
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->runInspection();
		
		// Set Bootstrap as default client assets
		Paginator::useBootstrap();
		
		// Specified key was too long error
		try {
			Schema::defaultStringLength(191);
		} catch (\Throwable $e) {
		}
		
		// Setup Laravel Sanctum
		try {
			Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
		} catch (\Throwable $e) {
		}
		
		// Setup Storage Symlink
		$this->setupStorageSymlink();
		
		// Setup ACL system
		$this->setupAclSystem();
		
		// Setup Https
		$this->setupHttps();
		
		// Setup Configs
		$this->setupConfigs();
	}
	
	/**
	 * Setup Https
	 */
	private function setupHttps()
	{
		// Force HTTPS protocol
		if (config('larapen.core.forceHttps')) {
			URL::forceScheme('https');
		}
	}
}
