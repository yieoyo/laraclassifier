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

use App\Models\Package;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PackageResource;

/**
 * @group Packages
 */
class PackageController extends BaseController
{
	/**
	 * List packages
	 *
	 * @queryParam embed string Comma-separated list of the package relationships for Eager Loading - Possible values: currency. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: lft. Example: -lft
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$type = getRequestSegment(3);
		$isPromoting = ($type == 'promotion');
		$isSubscripting = ($type == 'subscription');
		
		$embed = explode(',', request()->query('embed'));
		$locale = config('app.locale');
		$page = request()->integer('page');
		
		// Cache control
		$this->updateCachingParameters();
		
		// Cache ID
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheFiltersId = '.filters.' . $type;
		$cachePageId = '.page.' . $page . '.of.' . $this->perPage;
		$cacheId = 'packages.' . $cacheEmbedId . $cacheFiltersId . $cachePageId . $locale;
		
		// Cached Query
		$packages = cache()->remember($cacheId, $this->cacheExpiration, function () use ($isPromoting, $isSubscripting, $embed) {
			$packages = Package::query();
			
			$packages->when($isPromoting, fn ($query) => $query->promotion());
			$packages->when($isSubscripting, fn ($query) => $query->subscription());
			
			$packages->applyCurrency();
			
			if (in_array('currency', $embed)) {
				$packages->with('currency');
			}
			
			// Sorting
			$packages = $this->applySorting($packages, ['lft']);
			
			return $packages->get();
		});
		
		// Reset caching parameters
		$this->resetCachingParameters();
		
		$resourceCollection = new EntityCollection(class_basename($this), $packages);
		
		$message = ($packages->count() <= 0) ? t('no_packages_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get package
	 *
	 * @queryParam embed string Comma-separated list of the package relationships for Eager Loading - Possible values: currency. Example: currency
	 *
	 * @urlParam id int required The package's ID. Example: 2
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		
		// Cache control
		$this->updateCachingParameters();
		
		// Cache ID
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheId = 'package.id.' . $id . '.' . $cacheEmbedId . config('app.locale');
		
		// Cached Query
		$package = cache()->remember($cacheId, $this->cacheExpiration, function () use ($id, $embed) {
			$package = Package::query()->where('id', $id);
			
			if (in_array('currency', $embed)) {
				$package->with('currency');
			}
			
			return $package->first();
		});
		
		// Reset caching parameters
		$this->resetCachingParameters();
		
		abort_if(empty($package), 404, t('package_not_found'));
		
		$package->setLocale(config('app.locale'));
		
		$resource = new PackageResource($package);
		
		return apiResponse()->withResource($resource);
	}
}
