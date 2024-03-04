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

use App\Http\Controllers\Api\Page\PageBy;
use App\Models\Page;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PageResource;

/**
 * @group Pages
 */
class PageController extends BaseController
{
	use PageBy;
	
	/**
	 * List pages
	 *
	 * @queryParam excludedFromFooter boolean Select or unselect pages that can list in footer. Example: 0
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: lft, created_at. Example: -lft
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$pages = Page::query();
		
		if (request()->query('excludedFromFooter') == 1) {
			$pages->columnIsEmpty('excluded_from_footer');
		}
		
		// Sorting
		$pages = $this->applySorting($pages, ['lft', 'created_at']);
		
		$pages = $pages->paginate($this->perPage);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$pages = setPaginationBaseUrl($pages);
		
		$resourceCollection = new EntityCollection(class_basename($this), $pages);
		
		$message = ($pages->count() <= 0) ? t('no_pages_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get page
	 *
	 * @urlParam slugOrId string required The slug or ID of the page. Example: terms
	 *
	 * @param $slugOrId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($slugOrId): \Illuminate\Http\JsonResponse
	{
		if (is_numeric($slugOrId)) {
			$page = $this->getPageById($slugOrId);
		} else {
			$page = $this->getPageBySlug($slugOrId);
		}
		
		abort_if(empty($page), 404, t('page_not_found'));
		
		$resource = new PageResource($page);
		
		return apiResponse()->withResource($resource);
	}
}
