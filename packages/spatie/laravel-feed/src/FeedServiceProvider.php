<?php

namespace Larapen\Feed;

use Larapen\Feed\Http\FeedController;
use Spatie\Feed\Components\FeedLinks;
use Spatie\Feed\Helpers\Path;
use Spatie\LaravelPackageTools\Package;

class FeedServiceProvider extends \Spatie\Feed\FeedServiceProvider
{
	public function configurePackage(Package $package): void
	{
		$this->package->setBasePath($this->getPackageBaseDir());
		
		$package
			->name('laravel-feed')
			->hasConfigFile()
			->hasViews()
			->hasViewComposer('feed::links', function ($view) {
				$view->with('feeds', $this->feeds());
			})
			->hasViewComponent('', FeedLinks::class);
	}
	
	public function packageRegistered()
	{
		$this->registerRouteMacro();
	}
	
	protected function registerRouteMacro(): void
	{
		$router = $this->app['router'];
		
		$router->macro('feeds', function ($baseUrl = '') use ($router) {
			foreach (config('feed.feeds') as $name => $configuration) {
				$url = Path::merge($baseUrl, $configuration['url']);
				
				$router->get($url, '\\' . FeedController::class)->name("feeds.{$name}");
			}
		});
	}
	
	protected function getPackageBaseDir(): string
	{
		$reflector = new \ReflectionClass(get_class($this));
		
		$currPath = dirname($reflector->getFileName());
		$origPath = '/../../../../vendor/spatie/laravel-feed/src/';
		
		return $currPath . $origPath;
	}
}
