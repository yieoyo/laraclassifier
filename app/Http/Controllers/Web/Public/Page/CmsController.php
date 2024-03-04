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

namespace App\Http\Controllers\Web\Public\Page;

use App\Http\Controllers\Web\Public\FrontController;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class CmsController extends FrontController
{
	/**
	 * @param $slug
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function index($slug)
	{
		// Get Packages - Call API endpoint
		$endpoint = '/pages/' . $slug;
		$data = makeApiRequest('get', $endpoint);
		
		$message = $this->handleHttpError($data);
		$page = data_get($data, 'result');
		
		// Check if an external link is available
		if (!empty(data_get($page, 'external_link'))) {
			return redirect()->away(data_get($page, 'external_link'), 301)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('staticPage');
		$title = str_replace('{page.title}', data_get($page, 'seo_title'), $title);
		$title = str_replace('{app.name}', config('app.name'), $title);
		$title = str_replace('{country.name}', config('country.name'), $title);
		
		$description = str_replace('{page.description}', data_get($page, 'seo_description'), $description);
		$description = str_replace('{app.name}', config('app.name'), $description);
		$description = str_replace('{country.name}', config('country.name'), $description);
		
		$keywords = str_replace('{page.keywords}', data_get($page, 'seo_keywords'), $keywords);
		$keywords = str_replace('{app.name}', config('app.name'), $keywords);
		$keywords = str_replace('{country.name}', config('country.name'), $keywords);
		
		if (empty($title)) {
			$title = data_get($page, 'title') . ' - ' . config('app.name');
		}
		if (empty($description)) {
			$description = str(str_strip(strip_tags(data_get($page, 'content'))))->limit(200);
		}
		
		$title = removeUnmatchedPatterns($title);
		$description = removeUnmatchedPatterns($description);
		$keywords = removeUnmatchedPatterns($keywords);
		
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		MetaTag::set('keywords', $keywords);
		
		// Open Graph
		$this->og->title($title)->description($description);
		if (!empty(data_get($page, 'picture_url'))) {
			if ($this->og->has('image')) {
				$this->og->forget('image')->forget('image:width')->forget('image:height');
			}
			$this->og->image(data_get($page, 'picture_url'), [
				'width'  => (int)config('settings.seo.og_image_width', 1200),
				'height' => (int)config('settings.seo.og_image_height', 630),
			]);
		}
		view()->share('og', $this->og);
		
		return appView('pages.cms', compact('page'));
	}
}
