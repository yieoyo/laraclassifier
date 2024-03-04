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

namespace App\Http\Controllers\Web\Public\Ajax\Location;

use App\Http\Controllers\Web\Public\FrontController;

class AutoCompleteController extends FrontController
{
	/**
	 * Autocomplete Cities
	 *
	 * @param $countryCode
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index($countryCode): \Illuminate\Http\JsonResponse
	{
		$languageCode = request()->input('languageCode', config('app.locale'));
		$query = request()->input('query');
		
		$cityListArray = [];
		
		// XHR data
		$result = [
			'query'       => $query,
			'suggestions' => $cityListArray,
		];
		
		if (mb_strlen($query) <= 0) {
			return ajaxResponse()->json($result);
		}
		
		// Get country's cities - Call API endpoint
		$endpoint = '/countries/' . $countryCode . '/cities';
		$queryParams = [
			'embed'         => 'subAdmin1,subAdmin2',
			'q'             => $query,
			'autocomplete'  => 1,
			'sort'          => '-name',
			'language_code' => $languageCode,
			'perPage'       => 25,
		];
		if (!empty($page)) {
			$queryParams['page'] = $page;
		}
		$queryParams = array_merge(request()->all(), $queryParams);
		$headers = [
			'X-WEB-REQUEST-URL' => request()->fullUrlWithQuery(['query' => $query]),
		];
		$data = makeApiRequest('get', $endpoint, $queryParams, $headers);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		
		$cities = data_get($apiResult, 'data');
		
		// No cities found
		if (empty($cities)) {
			$status = (int)data_get($data, 'status', 200);
			$status = isValidHttpStatus($status) ? $status : 200;
			$result['message'] = $apiMessage;
			
			return ajaxResponse()->json($result, $status);
		}
		
		// Get & formats cities
		foreach ($cities as $city) {
			$cityName = data_get($city, 'name');
			$admin2Name = data_get($city, 'subAdmin2.name');
			$admin1Name = data_get($city, 'subAdmin1.name');
			
			$fullCityName = !empty($admin2Name)
				? $cityName . ', ' . $admin2Name
				: (!empty($admin1Name) ? $cityName . ', ' . $admin1Name : $cityName);
			
			$cityListArray[] = [
				'data'  => data_get($city, 'id'),
				'value' => $fullCityName,
			];
		}
		
		// XHR data
		$result['query'] = $query;
		$result['suggestions'] = $cityListArray;
		
		return ajaxResponse()->json($result);
	}
}
