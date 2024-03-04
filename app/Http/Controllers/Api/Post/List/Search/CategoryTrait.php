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

namespace App\Http\Controllers\Api\Post\List\Search;

use App\Http\Controllers\Api\Category\CategoryBy;
use App\Models\Category;

trait CategoryTrait
{
	use CategoryBy;
	
	/**
	 * Get Category (Auto-detecting ID or Slug)
	 *
	 * @return mixed|null
	 */
	public function getCategory()
	{
		// Get the Category's right arguments
		$catParentId = request()->filled('c') ? request()->query('c') : null;
		$catId = request()->filled('sc') ? request()->query('sc') : $catParentId;
		$catParentId = ($catParentId == $catId) ? null : $catParentId;
		
		// Validate parameters values
		$catParentId = (is_numeric($catParentId) || is_string($catParentId)) ? $catParentId : null;
		$catId = (is_numeric($catId) || is_string($catId)) ? $catId : null;
		
		// Get the Category
		$cat = null;
		if (!empty($catId)) {
			if (is_numeric($catId)) {
				$cat = $this->getCategoryById($catId);
			} else {
				$isCatIdString = is_string($catId);
				$isCatParentIdStringOrEmpty = (is_string($catParentId) || empty($catParentId));
				
				if ($isCatIdString && $isCatParentIdStringOrEmpty) {
					$cat = $this->getCategoryBySlug($catId, $catParentId);
				}
			}
			
			if (empty($cat)) {
				abort(404, t('category_not_found'));
			}
		}
		
		return $cat;
	}
	
	/**
	 * Get Root Categories
	 *
	 * @return mixed
	 */
	public function getRootCategories()
	{
		$cacheId = 'cat.0.categories.' . config('app.locale');
		$cats = cache()->remember($cacheId, $this->cacheExpiration, function () {
			return Category::root()->orderBy('lft')->get();
		});
		
		if ($cats->count() > 0) {
			$cats = $cats->keyBy('id');
		}
		
		return $cats;
	}
}
