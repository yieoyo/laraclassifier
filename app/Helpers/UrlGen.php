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

namespace App\Helpers;

use App\Helpers\UrlGen\ClearFiltersTrait;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class UrlGen
{
	use ClearFiltersTrait;
	
	/**
	 * @param $entry
	 * @param bool $encoded
	 * @return string
	 */
	public static function postPath($entry, bool $encoded = false): string
	{
		$entry = (is_array($entry)) ? Arr::toObject($entry) : $entry;
		
		if (isset($entry->id) && isset($entry->title)) {
			$preview = !isVerifiedPost($entry) ? '?preview=1' : '';
			
			$slug = ($encoded) ? rawurlencode($entry->slug) : $entry->slug;
			
			$path = str_replace(['{slug}', '{hashableId}', '{id}'], [$slug, hashId($entry->id), $entry->id], config('routes.post'));
			$path = $path . $preview;
		} else {
			$path = '#';
		}
		
		return getAsString($path);
	}
	
	/**
	 * @param $id
	 * @param string $slug
	 * @return string
	 */
	public static function postPathBasic($id, string $slug = 'listing-slug'): string
	{
		$path = str_replace(['{slug}', '{hashableId}', '{id}'], [$slug, $id, $id], config('routes.post'));
		
		return getAsString($path);
	}
	
	/**
	 * @param $entry
	 * @param bool $encoded
	 * @return string
	 */
	public static function postUri($entry, bool $encoded = false): string
	{
		return self::postPath($entry, $encoded);
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public static function post($entry): string
	{
		$entry = (is_array($entry)) ? Arr::toObject($entry) : $entry;
		
		if (config('plugins.domainmapping.installed')) {
			$url = dmUrl($entry->country_code, self::postUri($entry));
		} else {
			$url = url(self::postPath($entry));
		}
		
		return getAsString($url);
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public static function reportPost($entry): string
	{
		$entry = (is_array($entry)) ? Arr::toObject($entry) : $entry;
		
		$url = url('posts/' . hashId($entry->id) . '/report');
		
		return getAsString($url);
	}
	
	/**
	 * @param bool $httpError
	 * @return string
	 */
	public static function addPost(bool $httpError = false): string
	{
		$url = (config('settings.single.publication_form_type') == '2')
			? url('create')
			: url('posts/create');
		
		return getAsString($url);
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public static function editPost($entry): string
	{
		$entry = (is_array($entry)) ? Arr::toObject($entry) : $entry;
		
		if (isset($entry->id)) {
			$url = (config('settings.single.publication_form_type') == '2')
				? url('edit/' . $entry->id)
				: url('posts/' . $entry->id . '/edit');
		} else {
			$url = '#';
		}
		
		return getAsString($url);
	}
	
	/**
	 * @param $cat
	 * @param $city
	 * @param array $exceptArr
	 * @return string|null
	 */
	public static function getCatParentUrl($cat, $city = null, array $exceptArr = []): ?string
	{
		if (empty($cat)) {
			return null;
		}
		
		$cat = (is_array($cat)) ? Arr::toObject($cat) : $cat;
		$city = (is_array($city)) ? Arr::toObject($city) : $city;
		
		$exceptArr = array_filter($exceptArr); // Remove empty elements
		
		if (!in_array('page', $exceptArr)) {
			$exceptArr[] = 'page';
		}
		if (!in_array('cf', $exceptArr)) {
			$exceptArr[] = 'cf';
		}
		if (!in_array('minPrice', $exceptArr)) {
			$exceptArr[] = 'minPrice';
		}
		if (!in_array('maxPrice', $exceptArr)) {
			$exceptArr[] = 'maxPrice';
		}
		
		$routeSearchPostsByCat = str_replace('{countryCode}/', '', config('routes.searchPostsByCat'));
		$idx = (config('settings.seo.multi_country_urls')) ? 2 : 1;
		$catFirstSegment = request()->segment($idx);
		
		if (str_starts_with($routeSearchPostsByCat, $catFirstSegment . '/')) {
			
			// Category permalink
			if (!empty($cat->parent)) {
				$catParentUrl = UrlGen::category($cat->parent, null, null, false, $exceptArr);
			} else {
				$catParentUrl = UrlGen::category($cat, null, null, false, $exceptArr);
			}
			
		} else {
			
			// Search base permalink + category queries string
			if (request()->filled('c') && request()->filled('sc')) {
				if (!in_array('sc', $exceptArr)) {
					$exceptArr[] = 'sc';
				}
			} else {
				if (request()->filled('c')) {
					if (!in_array('c', $exceptArr)) {
						$exceptArr[] = 'c';
					}
				}
				if (request()->filled('sc')) {
					if (!in_array('sc', $exceptArr)) {
						$exceptArr[] = 'sc';
					}
				}
			}
			if (request()->filled('filterBy')) {
				if (!in_array('filterBy', $exceptArr)) {
					$exceptArr[] = 'filterBy';
				}
			}
			
			$catParentUrl = self::search([], $exceptArr);
			
		}
		
		return getAsString($catParentUrl);
	}
	
	/**
	 * @param $entry
	 * @param string|null $countryCode
	 * @param $city
	 * @param bool $findParent
	 * @param array $exceptArr
	 * @return string|null
	 */
	public static function category($entry, string $countryCode = null, $city = null, bool $findParent = true, array $exceptArr = []): ?string
	{
		if (empty($entry)) {
			return null;
		}
		
		$entry = (is_array($entry)) ? Arr::toObject($entry) : $entry;
		$city = (is_array($city)) ? Arr::toObject($city) : $city;
		
		$exceptArr = array_filter($exceptArr); // Remove empty elements
		
		if (!in_array('page', $exceptArr)) {
			$exceptArr[] = 'page';
		}
		if (!in_array('cf', $exceptArr)) {
			$exceptArr[] = 'cf';
		}
		if (!in_array('minPrice', $exceptArr)) {
			$exceptArr[] = 'minPrice';
		}
		if (!in_array('maxPrice', $exceptArr)) {
			$exceptArr[] = 'maxPrice';
		}
		
		// Search base permalink + category queries string
		$locationExists = (!empty($city) && isset($city->id));
		$filterByExists = request()->filled('filterBy');
		if ($locationExists || $filterByExists) {
			$params = [];
			if (!empty($entry->parent)) {
				$params['c'] = $entry->parent->id;
				$params['sc'] = $entry->id;
			} else {
				if (!in_array('sc', $exceptArr)) {
					$exceptArr[] = 'sc';
				}
				$params['c'] = $entry->id;
			}
			if ($locationExists) {
				$params['l'] = $city->id;
			}
			if ($filterByExists) {
				$params['filterBy'] = request()->query('filterBy');
			}
			
			return self::search(array_merge(request()->except($exceptArr + array_keys($params)), $params), $exceptArr);
		}
		
		// Category permalink
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_country_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if (isset($entry->slug)) {
			if ($findParent && !empty($entry->parent)) {
				$path = str_replace(
					['{countryCode}/', '{catSlug}', '{subCatSlug}'],
					['', $entry->parent->slug, $entry->slug],
					config('routes.searchPostsBySubCat')
				);
			} else {
				$path = str_replace(['{countryCode}/', '{catSlug}'], ['', $entry->slug], config('routes.searchPostsByCat'));
			}
			$url = url($countryCodePath . $path);
		} else {
			$url = self::search([], $exceptArr);
		}
		
		return getAsString($url);
	}
	
	/**
	 * @param $entry
	 * @param string|null $countryCode
	 * @param $cat
	 * @param array $exceptArr
	 * @return string|null
	 */
	public static function city($entry, string $countryCode = null, $cat = null, array $exceptArr = []): ?string
	{
		if (empty($entry)) {
			return null;
		}
		
		$entry = (is_array($entry)) ? Arr::toObject($entry) : $entry;
		$cat = (is_array($cat)) ? Arr::toObject($cat) : $cat;
		
		$exceptArr = array_filter($exceptArr); // Remove empty elements
		
		if (!in_array('page', $exceptArr)) {
			$exceptArr[] = 'page';
		}
		if (!in_array('location', $exceptArr)) {
			$exceptArr[] = 'location';
		}
		
		// Search base permalink + location queries string
		$categoryExists = (!empty($cat) && isset($cat->id));
		$filterByExists = request()->filled('filterBy');
		if ($categoryExists || $filterByExists) {
			$params = [];
			$params['l'] = $entry->id;
			if ($categoryExists) {
				if (!empty($cat->parent)) {
					$params['c'] = $cat->parent->id;
					$params['sc'] = $cat->id;
				} else {
					if (!in_array('sc', $exceptArr)) {
						$exceptArr[] = 'sc';
					}
					$params['c'] = $cat->id;
				}
			}
			if ($filterByExists) {
				$params['filterBy'] = request()->query('filterBy');
			}
			
			return self::search(array_merge(request()->except($exceptArr + array_keys($params)), $params), $exceptArr);
		}
		
		// Location permalink
		if (empty($countryCode)) {
			if (!empty($entry->country_code)) {
				$countryCode = $entry->country_code;
			} else {
				$countryCode = config('country.code');
			}
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_country_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if (isset($entry->id, $entry->name)) {
			$path = str_replace(
				['{countryCode}/', '{city}', '{id}'],
				['', ($entry->slug ?? slugify($entry->name)), $entry->id],
				config('routes.searchPostsByCity')
			);
			$path = $countryCodePath . $path;
			if (isAdminPanel()) {
				$url = dmUrl($entry->country_code, $path);
			} else {
				$url = url($path);
			}
		} else {
			$url = '#';
		}
		
		return getAsString($url);
	}
	
	/**
	 * @param $entry
	 * @param string|null $countryCode
	 * @return string|null
	 */
	public static function user($entry, string $countryCode = null): ?string
	{
		if (empty($entry)) {
			return null;
		}
		
		$entry = (is_array($entry)) ? Arr::toObject($entry) : $entry;
		
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_country_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if (!empty($entry->username)) {
			$path = str_replace(['{countryCode}/', '{username}'], ['', $entry->username], config('routes.searchPostsByUsername'));
			$url = url($countryCodePath . $path);
		} else {
			if (isset($entry->id)) {
				$path = str_replace(['{countryCode}/', '{id}'], ['', $entry->id], config('routes.searchPostsByUserId'));
				$url = url($countryCodePath . $path);
			} else {
				$url = '#';
			}
		}
		
		return getAsString($url);
	}
	
	/**
	 * @param string $tag
	 * @param string|null $countryCode
	 * @return string
	 */
	public static function tag(string $tag, string $countryCode = null): string
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_country_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		$path = str_replace(['{countryCode}/', '{tag}'], ['', $tag], config('routes.searchPostsByTag'));
		
		$url = url($countryCodePath . $path);
		
		return getAsString($url);
	}
	
	/**
	 * @param string|null $countryCode
	 * @param int|null $companyId
	 * @return string
	 */
	public static function company(string $countryCode = null, int $companyId = null): string
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_country_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if (!empty($companyId)) {
			$path = str_replace(['{countryCode}/', '{id}'], ['', $companyId], config('routes.searchPostsByCompanyId'));
			$url = url($countryCodePath . $path);
		} else {
			$url = url($countryCodePath . config('routes.companies'));
		}
		
		return getAsString($url);
	}
	
	/**
	 * @param bool $currentUrl
	 * @param string|null $countryCode
	 * @return string
	 */
	public static function searchWithoutQuery(bool $currentUrl = false, string $countryCode = null): string
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_country_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if ($currentUrl) {
			$url = request()->url();
		} else {
			$path = str_replace(['{countryCode}/'], [''], config('routes.search'));
			$url = $countryCodePath . $path;
			// request()->server->set('REQUEST_URI', $url);
		}
		
		$url =  url($url);
		
		return getAsString($url);
	}
	
	/**
	 * @param array $queryArr
	 * @param array $exceptArr
	 * @param bool $currentUrl
	 * @param string|null $countryCode
	 * @return string
	 */
	public static function search(array $queryArr = [], array $exceptArr = [], bool $currentUrl = false, string $countryCode = null): string
	{
		$url = self::searchWithoutQuery($currentUrl, $countryCode);
		
		$currentQueryArr = request()->except($exceptArr + array_keys($queryArr));
		$queryArr = array_merge($currentQueryArr, $queryArr);
		
		return qsUrl($url, $queryArr, null, false);
	}
	
	/**
	 * @param $entry
	 * @return string
	 */
	public static function page($entry): string
	{
		$entry = (is_array($entry)) ? Arr::toObject($entry) : $entry;
		
		if (isset($entry->slug)) {
			$path = str_replace(['{slug}'], [$entry->slug], config('routes.pageBySlug'));
			$url = url($path);
		} else {
			$url = '#';
		}
		
		return getAsString($url);
	}
	
	/**
	 * @param string|null $countryCode
	 * @return string
	 */
	public static function sitemap(string $countryCode = null): string
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_country_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		$path = str_replace(['{countryCode}/'], [''], config('routes.sitemap'));
		
		$url = url($countryCodePath . $path);
		
		return getAsString($url);
	}
	
	public static function countries(): string
	{
		$url = url(config('routes.countries'));
		
		if (doesCountriesPageCanBeLinkedToTheHomepage()) {
			$crawler = new CrawlerDetect();
			if ($crawler->isCrawler()) {
				$url = rtrim(config('app.url'), '/') . '/';
			} else {
				$url = rtrim(config('app.url'), '/') . '/locale/' . config('app.locale');
			}
		}
		
		return getAsString($url);
	}
	
	public static function contact(): string
	{
		return getAsString(url(config('routes.contact')));
	}
	
	public static function pricing(): string
	{
		return getAsString(url(config('routes.pricing')));
	}
	
	public static function loginPath(): string
	{
		return getAsString(config('routes.login'));
	}
	
	public static function logoutPath(): string
	{
		return getAsString(config('routes.logout'));
	}
	
	public static function registerPath(): string
	{
		return getAsString(config('routes.register'));
	}
	
	public static function login(): string
	{
		return getAsString(url(self::loginPath()));
	}
	
	public static function logout(): string
	{
		return getAsString(url(self::logoutPath()));
	}
	
	public static function register(): string
	{
		return getAsString(url(self::registerPath()));
	}
}
