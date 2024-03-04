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

namespace App\Http\Controllers\Web\Admin\Traits\InlineRequest;

use App\Helpers\DBTool;
use App\Models\City;
use App\Models\Post;
use App\Models\SubAdmin1;
use App\Models\SubAdmin2;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

trait CountryTrait
{
	/**
	 * Update the 'active' column of the country table
	 * And import|remove the Geonames data: Country, Admin Divisions & Cities
	 *
	 * @param $model
	 * @param $column
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function updateCountryData($model, $column): \Illuminate\Http\JsonResponse
	{
		$isValidCondition = ($this->table == 'countries' && $column == 'active' && !empty($model));
		if (!$isValidCondition) {
			$error = trans('admin.inline_req_condition', ['table' => $this->table, 'column' => $column]);
			
			return $this->responseError($error, 400);
		}
		
		$defaultCountryCode = config('settings.localization.default_country_code');
		$isDefaultCountry = (strtolower($defaultCountryCode) == strtolower($model->code));
		
		// Update|import|remove data
		if ($model->{$column} == 0) {
			// Import Geonames Data
			$resImport = $this->importGeonamesSql($model->code);
			if (!$resImport) {
				return $this->responseError(trans('admin.inline_req_geonames_data_import_error'));
			}
		} else {
			// Don't disable|remove data for the default country
			if ($isDefaultCountry) {
				return $this->responseError(trans('admin.inline_req_skip_default_country'), Response::HTTP_UNAUTHORIZED);
			}
			
			// Remove Geonames Data
			$resImport = $this->removeGeonamesDataByCountryCode($model->code);
			if (!$resImport) {
				return $this->responseError(trans('admin.inline_req_geonames_data_removing_error'));
			}
		}
		
		// Save data
		$model->{$column} = ($model->{$column} != 1) ? 1 : 0;
		$model->save();
		
		return $this->responseSuccess($model, $column);
	}
	
	/**
	 * Import the Geonames data for the country
	 *
	 * @param $countryCode
	 * @return bool
	 */
	private function importGeonamesSql($countryCode): bool
	{
		// Remove all the country's data
		$this->removeGeonamesDataByCountryCode($countryCode);
		
		// Default Country SQL File
		$filePath = storage_path('database/geonames/countries/' . strtolower($countryCode) . '.sql');
		if (!File::exists($filePath)) {
			return false;
		}
		
		// Import the SQL file
		return DBTool::importSqlFile(DB::connection()->getPdo(), $filePath, DB::getTablePrefix());
	}
	
	/**
	 * Remove all the country's data
	 *
	 * @param $countryCode
	 * @return bool
	 */
	private function removeGeonamesDataByCountryCode($countryCode): bool
	{
		// Delete all SubAdmin1
		$admin1s = SubAdmin1::inCountry($countryCode);
		if ($admin1s->count() > 0) {
			foreach ($admin1s->cursor() as $admin1) {
				$admin1->delete();
			}
		}
		
		// Delete all SubAdmin2
		$admin2s = SubAdmin2::inCountry($countryCode);
		if ($admin2s->count() > 0) {
			foreach ($admin2s->cursor() as $admin2) {
				$admin2->delete();
			}
		}
		
		// Delete all Cities
		$cities = City::inCountry($countryCode);
		if ($cities->count() > 0) {
			foreach ($cities->cursor() as $city) {
				$city->delete();
			}
		}
		
		// Delete all Posts
		$posts = Post::inCountry($countryCode);
		if ($posts->count() > 0) {
			foreach ($posts->cursor() as $post) {
				$post->delete();
			}
		}
		
		return true;
	}
}
