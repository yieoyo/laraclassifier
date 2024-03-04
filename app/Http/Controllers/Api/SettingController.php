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

use App\Models\Setting;

/**
 * @group Settings
 */
class SettingController extends BaseController
{
	/**
	 * List settings
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$settings = config('settings');
		
		// Remove the 'purchase_code' value
		if (isset($settings['app'])) {
			$app = $settings['app'];
			if (isset($app['purchase_code'])) {
				unset($app['purchase_code']);
				$settings['app'] = $app;
			}
		}
		
		// Remove settings hidden values
		$settings = collect($settings)->mapWithKeys(function ($value, $key) {
			$value = collect($value)->reject(function ($v, $k) {
				return (in_array($k, Setting::optionsThatNeedToBeHidden()));
			});
			
			return [$key => $value];
		})->reject(function ($v, $k) {
			return (empty($v) || ($v->count() <= 0));
		})->toArray();
		
		$data = [
			'success' => true,
			'result'  => $settings,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Get setting
	 *
	 * @urlParam key string required The setting's key. Example: app
	 *
	 * @param $key
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($key): \Illuminate\Http\JsonResponse
	{
		$settingKey = 'settings.' . $key;
		
		if (!config()->has($settingKey)) {
			return apiResponse()->notFound();
		}
		
		$settings = config($settingKey);
		
		// Remove the 'purchase_code' value
		if (is_array($settings)) {
			if (isset($settings['purchase_code'])) {
				unset($settings['purchase_code']);
			}
		}
		if (is_string($settings)) {
			if (str_ends_with($settingKey, 'purchase_code')) {
				$settings = null;
			}
		}
		
		// Remove settings hidden values
		if (is_array($settings)) {
			$settings = collect($settings)->reject(function ($v, $k) {
				return (in_array($k, Setting::optionsThatNeedToBeHidden()));
			})->toArray();
		}
		if (is_string($settings)) {
			foreach (Setting::optionsThatNeedToBeHidden() as $hiddenValue) {
				if (str_ends_with($settingKey, $hiddenValue)) {
					$settings = null;
					break;
				}
			}
		}
		
		if (empty($settings)) {
			return apiResponse()->notFound();
		}
		
		$data = [
			'success' => true,
			'result'  => $settings,
		];
		
		return apiResponse()->json($data);
	}
}
