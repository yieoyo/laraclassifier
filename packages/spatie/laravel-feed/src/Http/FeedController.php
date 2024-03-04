<?php

namespace Larapen\Feed\Http;

use App\Http\Controllers\Web\Public\FrontController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Spatie\Feed\Feed;
use Spatie\Feed\Helpers\ResolveFeedItems;

class FeedController extends FrontController
{
	public function __construct()
	{
		parent::__construct();
		
		// Update feed's config values
		[$title, $description, $keywords] = getMetaTag('home');
		Config::set('feed.feeds.main.title', $title);
		Config::set('feed.feeds.main.description', $description);
		Config::set('feed.feeds.main.language', getLangTag(config('app.locale'), false));
	}
	
	public function __invoke()
	{
		$feeds = config('feed.feeds');
		
		$name = Str::after(app('router')->currentRouteName(), 'feeds.');
		
		$feed = $feeds[$name] ?? null;
		
		abort_unless($feed, 404);
		
		$items = ResolveFeedItems::resolve($name, $feed['items']);
		
		return new Feed(
			$feed['title'],
			$items,
			request()->url(),
			$feed['view'] ?? 'feed::feed',
			$feed['description'] ?? '',
			$feed['language'] ?? 'en-US',
			$feed['image'] ?? '',
			$feed['format'] ?? 'atom'
		);
	}
}
