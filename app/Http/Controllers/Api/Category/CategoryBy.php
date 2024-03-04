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

namespace App\Http\Controllers\Api\Category;

use App\Models\Category;

trait CategoryBy
{
	private array $relations = [
		'parent',
		'parent.children',
		'parent.children.parent',
		'children',
		'children.parent',
	];
	
	/**
	 * Get Category by Slug
	 * NOTE: Slug must be unique
	 *
	 * @param $catSlug
	 * @param null $parentCatSlug
	 * @param null $locale
	 * @return mixed
	 */
	public function getCategoryBySlug($catSlug, $parentCatSlug = null, $locale = null)
	{
		if (empty($locale)) {
			$locale = config('app.locale');
		}
		
		if (!empty($parentCatSlug)) {
			$cacheId = 'cat.' . $parentCatSlug . '.' . $catSlug . '.' . $locale . '.with.parent-children';
			$cat = cache()->remember($cacheId, $this->cacheExpiration, function () use ($parentCatSlug, $catSlug, $locale) {
				$cat = Category::with($this->relations)
					->whereHas('parent', fn($query) => $query->where('slug', $parentCatSlug))
					->where('slug', $catSlug)
					->first();
				
				if (!empty($cat)) {
					$cat->setLocale($locale);
				}
				
				return $cat;
			});
		} else {
			$cacheId = 'cat.' . $catSlug . '.' . $locale . '.with.parent-children';
			$cat = cache()->remember($cacheId, $this->cacheExpiration, function () use ($catSlug, $locale) {
				$cat = Category::with($this->relations)
					->where('slug', $catSlug)
					->first();
				
				if (!empty($cat)) {
					$cat->setLocale($locale);
				}
				
				return $cat;
			});
		}
		
		return $cat;
	}
	
	/**
	 * Get Category by ID
	 *
	 * @param $catId
	 * @param null $locale
	 * @return mixed
	 */
	public function getCategoryById($catId, $locale = null)
	{
		if (empty($locale)) {
			$locale = config('app.locale');
		}
		
		$cacheId = 'cat.' . $catId . '.' . $locale . '.with.parent-children';
		
		return cache()->remember($cacheId, $this->cacheExpiration, function () use ($catId, $locale) {
			$cat = Category::with($this->relations)
				->where('id', $catId)
				->first();
			
			if (!empty($cat)) {
				$cat->setLocale($locale);
			}
			
			return $cat;
		});
	}
}
