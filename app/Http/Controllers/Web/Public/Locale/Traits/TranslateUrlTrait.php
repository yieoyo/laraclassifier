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

namespace App\Http\Controllers\Web\Public\Locale\Traits;

use App\Helpers\Arr;
use App\Http\Controllers\Web\Public\Traits\Sluggable\CategoryBySlug;
use App\Http\Controllers\Web\Public\Traits\Sluggable\PageBySlug;

trait TranslateUrlTrait
{
	use CategoryBySlug, PageBySlug;
	
	/**
	 * @param string|null $url
	 * @param string|null $langCode
	 * @param string|null $baseUrl
	 * @return string|null
	 */
	private function translateUrl(?string $url, ?string $langCode, ?string $baseUrl = null): ?string
	{
		$defaultUrl = !empty($baseUrl) ? $baseUrl : url('/');
		$defaultUrl = getAsString($defaultUrl);
		
		try {
			$route = app('router')->getRoutes()->match(request()->create($url, request()->method()));
			if (empty($route)) {
				return $defaultUrl;
			}
			
			$prevUriPattern = $route->uri;
			$prevUriParameters = $route->parameters();
			
			if (str_contains($route->action['controller'], 'Search\CategoryController')) {
				$prevUriParameters = $this->translateRouteUriParametersForCat($prevUriParameters, $langCode);
			}
			if (str_contains($route->action['controller'], 'PageController')) {
				$prevUriParameters = $this->translateRouteUriParametersForPage($prevUriParameters, $langCode);
			}
			
			// Get possible translatable route key
			// $routeKey = array_search($prevUriPattern, trans('routes'));
			$routeKey = array_search($prevUriPattern, config('routes'));
			
			// Non-translatable route
			if (empty($routeKey)) {
				return $url;
			}
			
			// Translatable route
			$queryString = '';
			$queryArray = getUrlQuery($url, 'from');
			if (!empty($queryArray)) {
				$queryString = '?' . Arr::query($queryArray);
			}
			
			$search = collect($prevUriParameters)
				->mapWithKeys(function ($value, $key) {
					return ['{' . $key . '}' => $key];
				})
				->keys()
				->toArray();
			
			$replace = collect($prevUriParameters)
				->mapWithKeys(function ($value, $key) {
					return [$value => $key];
				})
				->keys()
				->toArray();
			
			// $prevUriPattern = trans('routes.' . $routeKey, [], $langCode);
			$translatedUrl = str_replace($search, $replace, $prevUriPattern);
			
			return $translatedUrl . $queryString;
		} catch (\Throwable $e) {
		}
		
		return $defaultUrl;
	}
	
	/**
	 * @param array|null $prevUriParameters
	 * @param string|null $langCode
	 * @return array|null
	 */
	private function translateRouteUriParametersForCat(?array $prevUriParameters, ?string $langCode): ?array
	{
		$countryCode = $prevUriParameters['countryCode'] ?? null;
		$parentCatSlug = $prevUriParameters['catSlug'] ?? null;
		$catSlug = $prevUriParameters['subCatSlug'] ?? null;
		if (empty($catSlug)) {
			$catSlug = $parentCatSlug;
			$parentCatSlug = null;
		}
		
		$cat = $this->getCategoryBySlug($catSlug, $parentCatSlug, $langCode);
		if (!empty($cat)) {
			$cat = $this->getCategoryById($cat->id, $langCode);
		}
		
		if (!empty($cat)) {
			$prevUriParameters = [
				'countryCode' => $countryCode,
				'catSlug'     => $cat->slug,
			];
			if (!empty($parentCatSlug)) {
				if (!empty($cat->parent)) {
					$cat->parent->setLocale($langCode);
				}
				$prevUriParameters = [
					'countryCode' => $countryCode,
					'catSlug'     => $cat->parent->slug,
					'subCatSlug'  => $cat->slug,
				];
			}
		}
		
		return $prevUriParameters;
	}
	
	/**
	 * @param array|null $prevUriParameters
	 * @param string|null $langCode
	 * @return array|null
	 */
	private function translateRouteUriParametersForPage(?array $prevUriParameters, ?string $langCode): ?array
	{
		$slug = $prevUriParameters['slug'] ?? null;
		
		$page = $this->getPageBySlug($slug, $langCode);
		if (!empty($page)) {
			$page = $this->getPageById($page->id, $langCode);
		}
		
		if (!empty($page)) {
			$prevUriParameters = ['slug' => $page->slug];
		}
		
		return $prevUriParameters;
	}
}
