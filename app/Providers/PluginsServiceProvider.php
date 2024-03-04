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

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class PluginsServiceProvider extends ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		// Set routes
		$this->setupRoutes($this->app->router);
	}
	
	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register(): void
	{
		// $this->app->bind('plugins', fn () => new Plugins());
		
		// Load the plugins Services Provider & register them
		$pluginsDirs = glob(config('larapen.core.plugin.path') . '*', GLOB_ONLYDIR);
		if (!empty($pluginsDirs)) {
			foreach ($pluginsDirs as $pluginDir) {
				$plugin = load_plugin(basename($pluginDir));
				if (!empty($plugin)) {
					$this->app->register($plugin->provider);
				}
			}
		}
	}
	
	/**
	 * Define the global routes for the plugins.
	 *
	 * NOTE:
	 * Prevent browser HTTP error like "net : Failed to load resource: net::ERR_SPDY_PROTOCOL_ERROR" on Chrome.
	 * The problem was that web hosting adds HTTP header Content-Encoding: gzip for all the PHP content
	 * even when the 'Content-Type: image/jpeg' is in the output from that script.
	 * For the hotfix I added HTTP header 'Content-Encoding: none' into that script. And it worked.
	 * But now I am asking web hosting provider to not add the wrong header if 'Content-Type: image/jpeg' is present. At HTTPS it makes sense.
	 *
	 * @param Router $router
	 */
	public function setupRoutes(Router $router): void
	{
		// Public - Images
		Route::get('plugins/{pluginName}/images/{filename}', function ($pluginName, $filename) {
			$path = plugin_path($pluginName, 'public/images/' . $filename);
			if (File::exists($path)) {
				$type = File::mimeType($path);
				
				return response()->file($path, [
					'Content-Type'     => $type,
					'Content-Encoding' => 'none',
				]);
			}
			
			abort(404);
		})->where('pluginName', '[a-z0-9]+');
		
		// Public - Assets
		Route::get('plugins/{pluginName}/assets/{type}/{file}', function ($pluginName, $type, $file) {
			$path = plugin_path($pluginName, 'public/assets/' . $type . '/' . $file);
			if (File::exists($path)) {
				if ($type == 'js' && file_extension($file) != 'css') {
					return response()->file($path, [
						'Content-Type'     => 'application/javascript',
						'Content-Encoding' => 'none',
					]);
				} else {
					return response()->file($path, [
						'Content-Type'     => 'text/css',
						'Content-Encoding' => 'none',
					]);
				}
			}
			
			abort(404);
		})->where('pluginName', '[a-z0-9]+')
			->where('type', '[^/]*');
	}
}
