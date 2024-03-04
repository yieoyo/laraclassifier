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

namespace App\Helpers\UrlGen;

use App\Helpers\Arr;
use App\Helpers\UrlGen;

trait ClearFiltersTrait
{
	/**
	 * @param $cat
	 * @param $city
	 * @return string
	 */
	public static function getCategoryFilterClearLink($cat, $city = null): string
	{
		$cat = (is_array($cat)) ? Arr::toObject($cat) : $cat;
		$city = (is_array($city)) ? Arr::toObject($city) : $city;
		
		$out = '';
		if (
			request()->filled('c')
			|| request()->filled('sc')
			|| str_contains(currentRouteAction(), 'Search\CategoryController')
		) {
			$exceptArr = ['page', 'cf', 'minPrice', 'maxPrice'];
			if (!empty($cat)) {
				if (!empty($cat->parent)) {
					$exceptArr[] = 'sc';
				} else {
					$exceptArr[] = 'c';
				}
			}
			$url = UrlGen::search([], $exceptArr);
			
			if (!empty($cat)) {
				if (str_contains(currentRouteAction(), 'Search\CategoryController')) {
					if (!empty($cat->parent)) {
						$url = UrlGen::category($cat->parent, null, $city);
					}
				}
			}
			
			$out = getFilterClearBtn($url);
		}
		
		return $out;
	}
	
	/**
	 * @param $cat
	 * @param $city
	 * @return string
	 */
	public static function getCityFilterClearLink($cat = null, $city = null): string
	{
		$cat = (is_array($cat)) ? Arr::toObject($cat) : $cat;
		$city = (is_array($city)) ? Arr::toObject($city) : $city;
		
		$out = '';
		if (
			request()->filled('l')
			|| request()->filled('location')
			|| str_contains(currentRouteAction(), 'Search\CityController')
		) {
			$exceptArr = ['page', 'l', 'location', 'distance'];
			$url = UrlGen::search([], $exceptArr);
			
			if (!empty($city)) {
				if (str_contains(currentRouteAction(), 'Search\CityController')) {
					$url = UrlGen::city($city, null, $cat);
				}
			}
			
			$out = getFilterClearBtn($url);
		}
		
		return $out;
	}
	
	/**
	 * @param null $cat
	 * @param null $city
	 * @return string
	 */
	public static function getDateFilterClearLink($cat = null, $city = null): string
	{
		$cat = (is_array($cat)) ? Arr::toObject($cat) : $cat;
		$city = (is_array($city)) ? Arr::toObject($city) : $city;
		
		$out = '';
		if (request()->filled('postedDate')) {
			$queryArr = [];
			if (!empty($cat) && isset($cat->id)) {
				if (!empty($cat->parent)) {
					$queryArr['c'] = $cat->parent->id;
					$queryArr['sc'] = $cat->id;
				} else {
					$queryArr['c'] = $cat->id;
				}
			}
			if (!empty($city) && isset($city->id)) {
				$queryArr['l'] = $city->id;
			}
			$url = UrlGen::search($queryArr, ['page', 'postedDate']);
			$out = getFilterClearBtn($url);
		}
		
		return $out;
	}
	
	/**
	 * @param null $cat
	 * @param null $city
	 * @return string
	 */
	public static function getPriceFilterClearLink($cat = null, $city = null): string
	{
		$cat = (is_array($cat)) ? Arr::toObject($cat) : $cat;
		$city = (is_array($city)) ? Arr::toObject($city) : $city;
		
		$out = '';
		if (request()->filled('minPrice') || request()->filled('maxPrice')) {
			$queryArr = [];
			if (!empty($cat) && isset($cat->id)) {
				if (!empty($cat->parent)) {
					$queryArr['c'] = $cat->parent->id;
					$queryArr['sc'] = $cat->id;
				} else {
					$queryArr['c'] = $cat->id;
				}
			}
			if (!empty($city) && isset($city->id)) {
				$queryArr['l'] = $city->id;
			}
			$url = UrlGen::search($queryArr, ['page', 'minPrice', 'maxPrice']);
			$out = getFilterClearBtn($url);
		}
		
		return $out;
	}
	
	/**
	 * @param null $cat
	 * @param null $city
	 * @return string
	 */
	public static function getTypeFilterClearLink($cat = null, $city = null): string
	{
		$cat = (is_array($cat)) ? Arr::toObject($cat) : $cat;
		$city = (is_array($city)) ? Arr::toObject($city) : $city;
		
		$out = '';
		if (request()->filled('type')) {
			$queryArr = [];
			if (!empty($cat) && isset($cat->id)) {
				if (!empty($cat->parent)) {
					$queryArr['c'] = $cat->parent->id;
					$queryArr['sc'] = $cat->id;
				} else {
					$queryArr['c'] = $cat->id;
				}
			}
			if (!empty($city) && isset($city->id)) {
				$queryArr['l'] = $city->id;
			}
			$url = UrlGen::search($queryArr, ['page', 'type']);
			$out = getFilterClearBtn($url);
		}
		
		return $out;
	}
	
	/**
	 * @param $field
	 * @param null $cat
	 * @param null $city
	 * @return string
	 */
	public static function getCustomFieldFilterClearLink($field, $cat = null, $city = null): string
	{
		$cat = (is_array($cat)) ? Arr::toObject($cat) : $cat;
		$city = (is_array($city)) ? Arr::toObject($city) : $city;
		
		$out = '';
		if (request()->filled($field)) {
			$queryArr = [];
			if (!empty($cat) && isset($cat->id)) {
				if (!empty($cat->parent)) {
					$queryArr['c'] = $cat->parent->id;
					$queryArr['sc'] = $cat->id;
				} else {
					$queryArr['c'] = $cat->id;
				}
			}
			if (!empty($city) && isset($city->id)) {
				$queryArr['l'] = $city->id;
			}
			$url = UrlGen::search($queryArr, ['page', $field]);
			$out = getFilterClearBtn($url);
		}
		
		return $out;
	}
}
