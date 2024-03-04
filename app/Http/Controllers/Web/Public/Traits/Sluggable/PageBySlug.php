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

namespace App\Http\Controllers\Web\Public\Traits\Sluggable;

use App\Models\Page;

trait PageBySlug
{
	/**
	 * Get Page by Slug
	 * NOTE: Slug must be unique
	 *
	 * @param $slug
	 * @param null $locale
	 * @return mixed
	 */
	private function getPageBySlug($slug, $locale = null)
	{
		if (empty($locale)) {
			$locale = config('app.locale');
		}
		
		$cacheId = 'page.slug.' . $slug . '.' . $locale;
		
		return cache()->remember($cacheId, $this->cacheExpiration, function () use ($slug, $locale) {
			$page = Page::where('slug', $slug)->first();
			
			if (!empty($page)) {
				$page->setLocale($locale);
			}
			
			return $page;
		});
	}
	
	/**
	 * Get Page by ID
	 *
	 * @param $pageId
	 * @param null $locale
	 * @return mixed
	 */
	public function getPageById($pageId, $locale = null)
	{
		if (empty($locale)) {
			$locale = config('app.locale');
		}
		
		$cacheId = 'page.' . $pageId . '.' . $locale;
		
		return cache()->remember($cacheId, $this->cacheExpiration, function () use ($pageId, $locale) {
			$page = Page::find($pageId);
			
			if (!empty($page)) {
				$page->setLocale($locale);
			}
			
			return $page;
		});
	}
}
