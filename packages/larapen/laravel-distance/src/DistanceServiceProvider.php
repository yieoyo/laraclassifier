<?php

namespace Larapen\LaravelDistance;

use Illuminate\Support\ServiceProvider;

class DistanceServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		// Merge plugin config
		$this->mergeConfigFrom(realpath(__DIR__ . '/config/distance.php'), 'distance');
	}
	
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void
	{
		$this->app->bind('distance', fn () => new Distance());
	}
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['distance'];
    }
}
