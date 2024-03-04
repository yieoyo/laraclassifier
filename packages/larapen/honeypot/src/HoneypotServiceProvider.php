<?php

namespace Larapen\Honeypot;

use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;
use Larapen\Honeypot\app\View\Composers\HoneypotComposer;

class HoneypotServiceProvider extends ServiceProvider
{
	public function boot(): void
	{
		// Load plugin views
		$this->loadViewsFrom(__DIR__ . '/resources/views', 'honeypot');
		
		// Publish configuration files
		$this->publishes([__DIR__ . '/../config/honeypot.php' => config_path('honeypot.php')]);
	}
	
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void
	{
		$this->registerConfig()->registerBladeClasses();
		
		$this->app->bind(Honeypot::class, fn () => new Honeypot(config('honeypot')));
	}
	
	protected function registerConfig(): self
	{
		// Merge plugin config
		$this->mergeConfigFrom(__DIR__ . '/../config/honeypot.php', 'honeypot');
		
		return $this;
	}
	
	protected function registerBladeClasses(): self
	{
		Facades\View::composer('honeypot::honeypot', HoneypotComposer::class);
		Facades\Blade::directive('honeypot', function () {
			return "<?php echo view('honeypot::honeypot'); ?>";
		});
		
		return $this;
	}
}
