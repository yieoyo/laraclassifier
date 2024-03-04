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

namespace App\Http\Controllers\Api\HomeSection;

use App\Helpers\Search\PostQueries;
use App\Helpers\UrlGen;
use App\Models\Advertising;
use App\Models\Category;
use App\Models\City;
use App\Models\Post;
use App\Models\User;

trait SectionDataTrait
{
	private array $embed = ['user', 'category', 'parent', 'postType', 'city', 'savedByLoggedUser', 'pictures', 'payment', 'package'];
	
	/**
	 * Get search form (Always in Top)
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getSearchForm(?array $value = []): array
	{
		return [];
	}
	
	/**
	 * Get locations & SVG map
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getLocations(?array $value = []): array
	{
		$data = [];
		
		$cacheExpiration = (int)($value['cache_expiration'] ?? 0);
		$maxItems = (int)($value['max_items'] ?? 14);
		
		// Get cities
		$isItWithPostsCount = (config('settings.list.count_cities_listings') == '1');
		$cacheId = config('country.code') . 'home.getLocations.cities.withCountPosts.' . (int)$isItWithPostsCount;
		$cities = cache()->remember($cacheId, $cacheExpiration, function () use ($maxItems, $isItWithPostsCount) {
			return City::query()
				->inCountry()
				->when($isItWithPostsCount, fn ($query) => $query->withCount('posts'))
				->take($maxItems)
				->orderByDesc('population')
				->orderBy('name')
				->get();
		});
		
		$cities = collect($cities->toArray());
		
		// Add "More Cities" link
		$adminType = config('country.admin_type', 0);
		$adminCodeCol = 'subadmin' . $adminType . '_code';
		$moreCities = [
			'id'          => 0,
			'name'        => t('more_cities') . ' &raquo;',
			$adminCodeCol => 0,
		];
		$cities = $cities->push($moreCities);
		
		// Get cities number of columns
		$numberOfCols = 4;
		if (data_get($value, 'show_map') == '1') {
			$mapFilePath = config('larapen.core.maps.path') . strtolower(config('country.code')) . '.svg';
			if (file_exists($mapFilePath)) {
				$numberOfCols = !empty(data_get($value, 'items_cols')) ? (int)data_get($value, 'items_cols') : 3;
			}
		}
		
		// Chunk
		$maxRowsPerCol = round($cities->count() / $numberOfCols, 0); // PHP_ROUND_HALF_EVEN
		$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1;  // Fix array_chunk with 0
		$cities = $cities->chunk($maxRowsPerCol);
		
		$data['cities'] = $cities->toArray();
		
		return $data;
	}
	
	/**
	 * Get premium listings
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getPremiumListings(?array $value = []): array
	{
		$freeListingsInPremium = config('settings.list.free_listings_in_premium');
		config()->set('settings.list.free_listings_in_premium', '0');
		
		$listingsSection = $this->getListingsSection('premium', $value);
		
		config()->set('settings.list.free_listings_in_premium', $freeListingsInPremium);
		
		return $listingsSection;
	}
	
	/**
	 * Get latest listings
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getLatestListings(?array $value = []): array
	{
		return $this->getListingsSection('latest', $value);
	}
	
	/**
	 * Get listings' section
	 *
	 * @param string $op
	 * @param array|null $setting
	 * @return array
	 */
	private function getListingsSection(string $op = 'latest', ?array $setting = []): array
	{
		$data = [];
		
		if (!in_array($op, ['latest', 'premium'])) return $data;
		
		// Get the section's settings
		$cacheExpiration = (int)($setting['cache_expiration'] ?? 0);
		$maxItems = (int)($setting['max_items'] ?? 12);
		$orderBy = ($op == 'premium') ? 'random' : 'date';
		$orderBy = $setting['order_by'] ?? $orderBy;
		
		// Get the listings
		// Add the 'embed' tables
		request()->query->add(['embed' => implode(',', $this->embed)]);
		
		$input = [
			'op'              => $op,
			'cacheExpiration' => $cacheExpiration,
			'perPage'         => $maxItems,
			'orderBy'         => $orderBy,
		];
		
		// Search
		$queriesToRemove = ['op', 'embed'];
		$searchData = (new PostQueries($input))->fetch($queriesToRemove);
		
		// Remove the 'embed' tables
		request()->query->remove('embed');
		
		$postsResult = data_get($searchData, 'posts', []);
		$posts = data_get($postsResult, 'data', []);
		$totalPosts = data_get($postsResult, 'meta.total', 0);
		
		// Get the section's data
		$section = null;
		if ($totalPosts > 0) {
			$title = ($op == 'premium')
				? t('Home - Premium Listings')
				: (($orderBy == 'random') ? t('Home - Random Listings') : t('Home - Latest Listings'));
			
			$url = UrlGen::searchWithoutQuery();
			if ($op == 'premium') {
				$url .= (str_contains($url, '?')) ? '&' : '?';
				$url .= 'filterBy=' . $op;
			}
			
			$section = [
				'title'      => $title,
				'link'       => $url,
				'posts'      => $posts,
				'totalPosts' => $totalPosts,
			];
		}
		
		$data[$op] = $section;
		
		return $data;
	}
	
	/**
	 * Get list of categories
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getCategories(?array $value = []): array
	{
		$data = [];
		
		$cacheExpiration = (int)($value['cache_expiration'] ?? 0);
		$maxItems = (int)($value['max_items'] ?? null);
		$catDisplayType = $value['cat_display_type'] ?? 'c_bigIcon_list';
		$numberOfCols = 3;
		
		$cacheId = 'categories.parents.' . config('app.locale') . '.' . $catDisplayType . '.take.' . $maxItems;
		
		if (in_array($catDisplayType, ['cc_normal_list', 'cc_normal_list_s'])) {
			
			$categories = cache()->remember($cacheId, $cacheExpiration, function () {
				return Category::query()->orderBy('lft')->get();
			});
			$categories = collect($categories)->keyBy('id');
			$categories = $subCategories = $categories->groupBy('parent_id');
			
			if ($categories->has(null)) {
				if (!empty($maxItems)) {
					$categories = $categories->get(null)->take($maxItems);
				} else {
					$categories = $categories->get(null);
				}
				$subCategories = $subCategories->forget(null);
				
				$maxRowsPerCol = round($categories->count() / $numberOfCols, 0, PHP_ROUND_HALF_EVEN);
				$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1;
				$categories = $categories->chunk($maxRowsPerCol);
			} else {
				$categories = collect();
				$subCategories = collect();
			}
			
			$data['categories'] = $categories;
			$data['subCategories'] = $subCategories;
			
		} else {
			
			$categories = cache()->remember($cacheId, $cacheExpiration, function () use ($maxItems) {
				$categories = Category::query()->root();
				if (!empty($maxItems)) {
					$categories = $categories->take($maxItems);
				}
				
				return $categories->orderBy('lft')->get();
			});
			
			if (in_array($catDisplayType, ['c_picture_list', 'c_bigIcon_list'])) {
				$categories = collect($categories)->keyBy('id');
			} else {
				$maxRowsPerCol = ceil($categories->count() / $numberOfCols);
				$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1; // Fix array_chunk with 0
				$categories = $categories->chunk($maxRowsPerCol);
			}
			
			$data['categories'] = $categories;
			
		}
		
		// Count Posts by category (if the option is enabled)
		$countPostsPerCat = [];
		if (config('settings.list.count_categories_listings')) {
			$cacheId = config('country.code') . '.count.posts.per.cat.' . config('app.locale');
			$countPostsPerCat = cache()->remember($cacheId, $cacheExpiration, function () {
				return Category::countPostsPerCategory();
			});
		}
		
		$data['countPostsPerCat'] = $countPostsPerCat;
		
		return $data;
	}
	
	/**
	 * Get mini stats data
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getStats(?array $value = []): array
	{
		$cacheExpiration = (int)($value['cache_expiration'] ?? 0);
		
		// Count Posts
		$countPosts = ($value['custom_counts_listings'] ?? 0);
		if (empty($countPosts)) {
			$cacheId = config('country.code') . '.count.posts';
			$countPosts = cache()->remember($cacheId, $cacheExpiration, function () {
				return Post::query()->inCountry()->unarchived()->count();
			});
		}
		
		// Count Users
		$countUsers = ($value['custom_counts_users'] ?? 0);
		if (empty($countUsers)) {
			$cacheId = 'count.users';
			$countUsers = cache()->remember($cacheId, $cacheExpiration, function () {
				return User::query()->count();
			});
		}
		
		// Count Locations (Cities)
		$countLocations = ($value['custom_counts_locations'] ?? 0);
		if (empty($countLocations)) {
			$cacheId = config('country.code') . '.count.cities';
			$countLocations = cache()->remember($cacheId, $cacheExpiration, function () {
				return City::query()->inCountry()->count();
			});
		}
		
		return [
			'count' => [
				'posts'     => $countPosts,
				'users'     => $countUsers,
				'locations' => $countLocations,
			],
		];
	}
	
	/**
	 * Get the text area data
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getTextArea(?array $value = []): array
	{
		return [];
	}
	
	/**
	 * @param array|null $value
	 * @return array
	 */
	protected function getTopAdvertising(?array $value = []): array
	{
		$cacheId = 'advertising.top';
		$topAdvertising = cache()->remember($cacheId, $this->cacheExpiration, function () {
			return Advertising::query()
				->where('integration', 'unitSlot')
				->where('slug', 'top')
				->first();
		});
		
		return [
			'topAdvertising' => $topAdvertising,
		];
	}
	
	/**
	 * @param array|null $value
	 * @return array
	 */
	protected function getBottomAdvertising(?array $value = []): array
	{
		$cacheId = 'advertising.bottom';
		$bottomAdvertising = cache()->remember($cacheId, $this->cacheExpiration, function () {
			return Advertising::query()
				->where('integration', 'unitSlot')
				->where('slug', 'bottom')
				->first();
		});
		
		return [
			'bottomAdvertising' => $bottomAdvertising,
		];
	}
}
