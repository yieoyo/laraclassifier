<?php

namespace extras\plugins\paypal;

use Illuminate\Support\ServiceProvider;

class PaypalServiceProvider extends ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		// Load plugin views
		$this->loadViewsFrom(realpath(__DIR__ . '/resources/views'), 'payment');
		
		// Load plugin languages files
		$this->loadTranslationsFrom(realpath(__DIR__ . '/lang'), 'paypal');
		
		// Merge plugin config
		$this->mergeConfigFrom(realpath(__DIR__ . '/config.php'), 'payment');
	}
	
	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register(): void
	{
		$this->app->bind('paypal', fn () => new Paypal());
	}
}
