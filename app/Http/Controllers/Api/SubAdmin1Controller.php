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

use App\Http\Resources\SubAdmin1Resource;
use App\Http\Resources\EntityCollection;
use App\Models\SubAdmin1;

/**
 * @group Countries
 */
class SubAdmin1Controller extends BaseController
{
	/**
	 * List admin. divisions (1)
	 *
	 * @queryParam embed string Comma-separated list of the administrative division (1) relationships for Eager Loading - Possible values: country. Example: null
	 * @queryParam q string Get the administrative division list related to the entered keyword. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: name. Example: -name
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 * @queryParam page int Items page number. From 1 to ("total items" divided by "items per page value - perPage"). Example: 1
	 *
	 * @urlParam countryCode string The country code of the country of the cities to retrieve. Example: US
	 *
	 * @param $countryCode
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index($countryCode): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		$keyword = request()->query('q');
		$locale = config('app.locale');
		$page = request()->integer('page');
		
		// Cache ID
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheFiltersId = '.filters.' . $keyword;
		$cachePageId = '.page.' . $page . '.of.' . $this->perPage;
		$cacheId = $countryCode . '.admins1.' . $cacheEmbedId . $cacheFiltersId . $cachePageId . '.' . $locale;
		$cacheId = md5($cacheId);
		
		// Cached Query
		$admins1 = cache()->remember($cacheId, $this->cacheExpiration, function () use ($embed, $countryCode, $keyword) {
			$admins1 = SubAdmin1::query();
			
			if (in_array('country', $embed)) {
				$admins1->with('country');
			}
			
			$admins1->where('country_code', $countryCode);
			if (!empty($keyword)) {
				$admins1->transWhere('name', 'LIKE', '%' . $keyword . '%');
			}
			
			// Sorting
			$admins1 = $this->applySorting($admins1, ['name']);
			
			return $admins1->paginate($this->perPage);
		});
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$admins1 = setPaginationBaseUrl($admins1);
		
		$resourceCollection = new EntityCollection(class_basename($this), $admins1);
		
		$message = ($admins1->count() <= 0) ? t('no_admin_divisions_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get admin. division (1)
	 *
	 * @queryParam embed string Comma-separated list of the administrative division (1) relationships for Eager Loading - Possible values: country. Example: null
	 *
	 * @urlParam code string required The administrative division (1)'s code. Example: CH.VD
	 *
	 * @param $code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($code): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		
		// Cache ID
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheId = 'admin1.' . $code . $cacheEmbedId;
		
		// Cached Query
		$admin1 = cache()->remember($cacheId, $this->cacheExpiration, function () use ($code, $embed) {
			$admin1 = SubAdmin1::query()->where('code', $code);
			
			if (in_array('country', $embed)) {
				$admin1->with('country');
			}
			
			return $admin1->first();
		});
		
		abort_if(empty($admin1), 404, t('admin_division_not_found'));
		
		$resource = new SubAdmin1Resource($admin1);
		
		return apiResponse()->withResource($resource);
	}
}
