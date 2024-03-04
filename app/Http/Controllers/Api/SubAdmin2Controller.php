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

use App\Http\Resources\SubAdmin2Resource;
use App\Http\Resources\EntityCollection;
use App\Models\SubAdmin2;

/**
 * @group Countries
 */
class SubAdmin2Controller extends BaseController
{
	/**
	 * List admin. divisions (2)
	 *
	 * @queryParam embed string Comma-separated list of the administrative division (2) relationships for Eager Loading - Possible values: country,subAdmin1. Example: null
	 * @queryParam admin1Code string Get the administrative division 2 list related to the administrative division 1 code. Example: null
	 * @queryParam q string Get the administrative division 2 list related to the entered keyword. Example: null
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
		$admin1Code = request()->query('admin1Code');
		$keyword = request()->query('q');
		$locale = config('app.locale');
		$page = request()->integer('page');
		
		// Cache ID
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheFiltersId = '.filters.' . $admin1Code . $keyword;
		$cachePageId = '.page.' . $page . '.of.' . $this->perPage;
		$cacheId = $countryCode . '.admins2.' . $cacheEmbedId . $cacheFiltersId . $cachePageId . '.' . $locale;
		$cacheId = md5($cacheId);
		
		// Cached Query
		$admins2 = cache()->remember($cacheId, $this->cacheExpiration, function () use ($embed, $countryCode, $admin1Code, $keyword) {
			$admins2 = SubAdmin2::query();
			
			if (in_array('country', $embed)) {
				$admins2->with('country');
			}
			if (in_array('subAdmin1', $embed)) {
				$admins2->with('subAdmin1');
			}
			
			$admins2->where('country_code', $countryCode);
			if (!empty($admin1Code)) {
				$admins2->where('subadmin1_code', $admin1Code);
			}
			if (!empty($keyword)) {
				$admins2->transWhere('name', 'LIKE', '%' . $keyword . '%');
			}
			
			// Sorting
			$admins2 = $this->applySorting($admins2, ['name']);
			
			return $admins2->paginate($this->perPage);
		});
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$admins2 = setPaginationBaseUrl($admins2);
		
		$resourceCollection = new EntityCollection(class_basename($this), $admins2);
		
		$message = ($admins2->count() <= 0) ? t('no_admin_divisions_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get admin. division (2)
	 *
	 * @queryParam embed string Comma-separated list of the administrative division (2) relationships for Eager Loading - Possible values: country,subAdmin1. Example: null
	 *
	 * @urlParam code string required The administrative division (2)'s code. Example: CH.VD.2225
	 *
	 * @param $code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($code): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		
		// Cache ID
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheId = 'admin2.' . $code . $cacheEmbedId;
		
		// Cached Query
		$admin2 = cache()->remember($cacheId, $this->cacheExpiration, function () use ($code, $embed) {
			$admin2 = SubAdmin2::query()->where('code', $code);
			
			if (in_array('country', $embed)) {
				$admin2->with('country');
			}
			if (in_array('subAdmin1', $embed)) {
				$admin2->with('subAdmin1');
			}
			
			return $admin2->first();
		});
		
		abort_if(empty($admin2), 404, t('admin_division_not_found'));
		
		$resource = new SubAdmin2Resource($admin2);
		
		return apiResponse()->withResource($resource);
	}
}
