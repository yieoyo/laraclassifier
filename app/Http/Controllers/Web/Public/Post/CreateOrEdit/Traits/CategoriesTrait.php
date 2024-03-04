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

namespace App\Http\Controllers\Web\Public\Post\CreateOrEdit\Traits;

trait CategoriesTrait
{
	/**
	 * @return array
	 */
	private function getCategoriesOptions(): array
	{
		// Get homeSections - Call API endpoint
		$cacheId = 'api.homeSections.getCategories';
		$apiResult = cache()->remember($cacheId, $this->cacheExpiration, function () {
			$endpoint = '/homeSections/getCategories';
			$data = makeApiRequest('get', $endpoint);
			
			$categoriesOptions = [
				'cat_display_type' => 'c_bigIcon_list',
				'max_items'        => '12',
				'cache_expiration' => $this->cacheExpiration,
			];
			
			$apiMessage = $this->handleHttpError($data);
			
			return data_get($data, 'result.getCategoriesOp', $categoriesOptions);
		});
		
		return is_array($apiResult) ? $apiResult : [];
	}
	
	/**
	 * @param int|null $catId
	 * @param string|null $languageCode
	 * @return array|null
	 */
	private function getCategoryById(?int $catId, ?string $languageCode = null): ?array
	{
		if (empty($catId)) return null;
		
		// Get categories - Call API endpoint
		$cacheId = 'api.categories.show.' . $catId . '.' . $languageCode;
		$apiResult = cache()->remember($cacheId, $this->cacheExpiration, function () use ($catId, $languageCode) {
			$endpoint = '/categories/' . $catId;
			$queryParams = [
				'embed'           => 'children,parent',
				'language_code'   => $languageCode ?? config('app.locale'),
				'cacheExpiration' => $this->cacheExpiration,
			];
			$queryParams = array_merge(request()->all(), $queryParams);
			$data = makeApiRequest('get', $endpoint, $queryParams);
			
			$apiMessage = $this->handleHttpError($data);
			
			return data_get($data, 'result');
		});
		
		return is_array($apiResult) ? $apiResult : null;
	}
	
	/**
	 * @param int|null $catId
	 * @param string|null $languageCode
	 * @param string|null $apiMessage
	 * @param int|null $page
	 * @return array
	 */
	private function getCategories(
		?int    $catId = null,
		?string $languageCode = null,
		?string &$apiMessage = null,
		?int    $page = null
	): array
	{
		$catId = $catId ?? 0;
		
		// Get categories - Call API endpoint
		$cacheId = 'api.categories.list.' . $catId . '.take.' . (int)$this->maxItems . '.' . $languageCode . '.page.' . $page;
		$apiResult = cache()->remember($cacheId, $this->cacheExpiration, function () use ($catId, $languageCode, $page) {
			$endpoint = '/categories';
			$queryParams = [
				'parentId'        => $catId,
				'nestedIncluded'  => (in_array($this->catDisplayType, $this->catsWithSubCatsTypes)),
				'embed'           => 'children,parent',
				'sort'            => '-lft',
				'language_code'   => $languageCode ?? config('app.locale'),
				'cacheExpiration' => $this->cacheExpiration,
				'perPage'         => $this->maxItems,
			];
			if (!empty($page)) {
				$queryParams['page'] = $page;
			}
			$queryParams = array_merge(request()->all(), $queryParams);
			$headers = [
				'X-WEB-REQUEST-URL' => request()->fullUrlWithQuery(['catId' => $catId]),
			];
			$categoriesData = makeApiRequest('get', $endpoint, $queryParams, $headers);
			
			$apiMessage = $this->handleHttpError($categoriesData);
			
			return data_get($categoriesData, 'result');
		});
		
		return is_array($apiResult) ? $apiResult : [];
	}
	
	/**
	 * Format Categories
	 * If catId is null, get list of categories (and their children), related to type of display
	 * If catId is not null, get the selected category's list of subcategories (and their children), ...
	 * NOTE: The 'type of display' is related to the homepage categories section settings
	 *
	 * @param array|null $origCats
	 * @param int|null $catId
	 * @return array|array[]
	 */
	private function formatCategories(?array $origCats, ?int $catId = null): array
	{
		// Number of columns
		$numberOfCols = 3;
		
		$categories = collect();
		$subCategories = collect();
		
		$origCats = collect($origCats);
		if ($origCats->count() <= 0) {
			return [
				'categories'    => [],
				'subCategories' => [],
			];
		}
		
		if (in_array($this->catDisplayType, $this->catsWithSubCatsTypes)) {
			
			$origCats = $origCats->keyBy('id');
			$origCats = $tmpSubCats = $origCats->groupBy('parent_id');
			if ($origCats->has($catId)) {
				$categories = $origCats->get($catId);
				$subCategories = $tmpSubCats->forget($catId);
				
				$maxRowsPerCol = round($categories->count() / $numberOfCols, 0, PHP_ROUND_HALF_EVEN);
				$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1;
				
				$categories = $categories->chunk($maxRowsPerCol);
			}
			
		} else {
			
			if (in_array($this->catDisplayType, $this->catsWithPictureTypes)) {
				$categories = collect($origCats)->keyBy('id');
			} else {
				$maxRowsPerCol = ceil($origCats->count() / $numberOfCols);
				$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1; // Fix array_chunk with 0
				
				$categories = $origCats->chunk($maxRowsPerCol);
			}
			
		}
		
		return [
			'categories'    => $categories,
			'subCategories' => $subCategories,
		];
	}
}
