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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Category\CategoryBy;
use App\Http\Controllers\Api\Category\FieldTrait;
use App\Models\Category;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\CategoryResource;

/**
 * @group Categories
 */
class CategoryController extends BaseController
{
	use CategoryBy, FieldTrait;
	
	/**
	 * List categories
	 *
	 * @queryParam parentId int The ID of the parent category of the sub categories to retrieve. Example: 0
	 * @queryParam nestedIncluded int If parent ID is not provided, are nested entries will be included? - Possible values: 0,1. Example: 0
	 * @queryParam embed string The Comma-separated list of the category relationships for Eager Loading - Possible values: parent,children. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: lft. Example: -lft
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 * @queryParam page int Items page number. From 1 to ("total items" divided by "items per page value - perPage"). Example: 1
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$cacheExpiration = request()->filled('cacheExpiration') ? request()->integer('cacheExpiration') : $this->cacheExpiration;
		$parentId = request()->integer('parentId');
		$areNestedEntriesIncluded = (request()->filled('nestedIncluded') && request()->integer('nestedIncluded') == 1);
		$locale = config('app.locale');
		$page = request()->integer('page');
		
		$embed = explode(',', request()->query('embed'));
		
		// Cache ID
		$cacheNestedId = '.nestedIncluded.' . (int)$areNestedEntriesIncluded;
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cachePageId = '.page.' . $page . '.of.' . $this->perPage;
		$cacheId = 'cats.' . $parentId . $cacheNestedId . $cacheEmbedId . $cachePageId . '.' . $locale;
		$cacheId = md5($cacheId);
		
		// Cached Query
		$categories = cache()->remember($cacheId, $cacheExpiration, function () use ($parentId, $embed, $areNestedEntriesIncluded) {
			$categories = Category::query();
			
			if (!empty($parentId)) {
				$categories->childrenOf($parentId);
			} else {
				if (!$areNestedEntriesIncluded) {
					$categories->root();
				}
			}
			
			if (in_array('parent', $embed)) {
				$categories->with('parent');
			} else {
				$categories->with('parentClosure');
			}
			if (in_array('children', $embed)) {
				$categories->with('children');
			}
			
			// Sorting
			$categories = $this->applySorting($categories, ['lft']);
			
			if ($areNestedEntriesIncluded) {
				$categories = $categories->get();
				if ($categories->count() > 0) {
					$categories = $categories->keyBy('id');
				}
				
				return $categories;
			}
			
			return $categories->paginate($this->perPage);
		});
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		if (!$areNestedEntriesIncluded) {
			$categories = setPaginationBaseUrl($categories);
		}
		
		$resourceCollection = new EntityCollection(class_basename($this), $categories);
		
		$message = ($categories->count() <= 0) ? t('no_categories_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get category
	 *
	 * Get category by its unique slug or ID.
	 *
	 * @queryParam parentCatSlug string The slug of the parent category to retrieve used when category's slug provided instead of ID. Example: automobiles
	 *
	 * @urlParam slugOrId string required The slug or ID of the category. Example: cars
	 *
	 * @param int|string $slugOrId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(int|string $slugOrId): \Illuminate\Http\JsonResponse
	{
		if (is_numeric($slugOrId)) {
			$category = $this->getCategoryById($slugOrId);
		} else {
			$parentCatSlug = request()->query('parentCatSlug') ?? null;
			$category = $this->getCategoryBySlug($slugOrId, $parentCatSlug);
		}
		
		abort_if(empty($category), 404, t('category_not_found'));
		
		$resource = new CategoryResource($category);
		
		return apiResponse()->withResource($resource);
	}
}
