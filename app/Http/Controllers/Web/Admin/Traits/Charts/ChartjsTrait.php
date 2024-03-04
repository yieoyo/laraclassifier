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

namespace App\Http\Controllers\Web\Admin\Traits\Charts;

/*
 * $colorOptions = ['luminosity' => 'light', 'hue' => ['red','orange','yellow','green','blue','purple','pink']];
 * $colorOptions = ['luminosity' => 'light'];
 */

use App\Helpers\RandomColor;
use App\Models\Country;

trait ChartjsTrait
{
	/**
	 * Graphic chart: Get total listings per country (limited to X countries)
	 *
	 * @param int $limit
	 * @param array|null $colorOptions
	 * @return array
	 */
	private function getPostsPerCountryForChartjs(int $limit = 10, ?array $colorOptions = []): array
	{
		// Init.
		$limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
		$colorOptions = (is_array($colorOptions)) ? $colorOptions : [];
		$data = [];
		
		// Get Data
		if ($this->countCountries > 1) {
			$countries = Country::active()->has('posts')->withCount('posts')->get();
			
			// Format Data
			if ($countries->count() > 0) {
				$countries = $countries->sortByDesc(function ($country) {
					return $country->posts_count;
				})->take($limit);
				
				foreach ($countries as $country) {
					$data['datasets'][0]['data'][] = $country->posts_count;
					$data['datasets'][0]['backgroundColor'][] = RandomColor::one($colorOptions);
					$data['labels'][] = (!empty($country->name)) ? $country->name : $country->code;
				}
				$data['datasets'][0]['label'] = trans('admin.Posts Dataset');
			}
		}
		
		$data = json_encode($data, JSON_NUMERIC_CHECK);
		
		return [
			'title'          => trans('admin.Listings per Country') . ' (' . trans('admin.Most active Countries') . ')',
			'data'           => $data,
			'countCountries' => $this->countCountries,
		];
	}
	
	/**
	 * Graphic chart: Get total users per country (limited to X countries)
	 *
	 * @param int $limit
	 * @param array|null $colorOptions
	 * @return array
	 */
	private function getUsersPerCountryForChartjs(int $limit = 10, ?array $colorOptions = []): array
	{
		// Init.
		$limit = (is_numeric($limit) && $limit > 0) ? $limit : 10;
		$colorOptions = (is_array($colorOptions)) ? $colorOptions : [];
		$data = [];
		
		// Get Data
		if ($this->countCountries > 1) {
			$countries = Country::active()->has('users')->withCount('users')->get();
			
			// Format Data
			if ($countries->count() > 0) {
				$countries = $countries->sortByDesc(function ($country) {
					return $country->users_count;
				})->take($limit);
				
				foreach ($countries as $country) {
					$data['datasets'][0]['data'][] = $country->users_count;
					$data['datasets'][0]['backgroundColor'][] = RandomColor::one($colorOptions);
					$data['labels'][] = (!empty($country->name)) ? $country->name : $country->code;
				}
				$data['datasets'][0]['label'] = trans('admin.Users Dataset');
			}
		}
		
		$data = json_encode($data, JSON_NUMERIC_CHECK);
		
		return [
			'title'          => trans('admin.Users per Country') . ' (' . trans('admin.Most active Countries') . ')',
			'data'           => $data,
			'countCountries' => $this->countCountries,
		];
	}
}
