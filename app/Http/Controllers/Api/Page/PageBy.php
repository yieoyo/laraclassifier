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

namespace App\Http\Controllers\Api\Page;

use App\Models\Page;

trait PageBy
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
		
		$embed = explode(',', request()->query('embed'));
		
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheId = 'page.slug.' . $slug . $cacheEmbedId . '.' . $locale;
		
		$page = cache()->remember($cacheId, $this->cacheExpiration, function () use ($slug, $locale, $embed) {
			$page = Page::query()->where('slug', $slug);
			
			if (in_array('parent', $embed)) {
				$page->with('parent');
			}
			
			return $page->first();
		});
		
		if (!empty($page)) {
			$page->setLocale($locale);
		}
		
		return $page;
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
		$embed = explode(',', request()->query('embed'));
		
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheId = 'page.' . $pageId . $cacheEmbedId . '.' . $locale;
		
		$page = cache()->remember($cacheId, $this->cacheExpiration, function () use ($pageId, $locale, $embed) {
			$page = Page::query()->where('id', $pageId);
			
			if (in_array('parent', $embed)) {
				$page->with('parent');
			}
			
			return $page->first();
		});
		
		if (!empty($page)) {
			$page->setLocale($locale);
		}
		
		return $page;
	}
}
