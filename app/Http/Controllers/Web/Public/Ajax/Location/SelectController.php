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

class SelectController extends FrontController
{
	/**
	 * Form Select Box
	 * Get Countries
	 *
	 * @return string
	 */
	public function getCountries(): string
	{
		if (is_null($this->countries)) {
			return collect()->toJson();
		}
		
		return $this->countries->toJson();
	}
	
	/**
	 * Form Select Box
	 * Get country Locations (admin1 OR admin2)
	 *
	 * @param $countryCode
	 * @param $adminType
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getAdmins($countryCode, $adminType): \Illuminate\Http\JsonResponse
	{
		$languageCode = request()->query('languageCode', config('app.locale'));
		$adminEndpoints = [
			'1' => '/countries/' . $countryCode . '/subAdmins1',
			'2' => '/countries/' . $countryCode . '/subAdmins2',
		];
		
		// If an admin type does not exist, set the default type
		if (!isset($adminEndpoints[$adminType])) {
			$adminType = 1;
		}
		
		// Get country's admin. divisions - Call API endpoint
		$endpoint = $adminEndpoints[$adminType];
		$queryParams = [
			'sort'          => '-name',
			'language_code' => $languageCode,
			'perPage'       => ($adminType == 2) ? 5000 : 200,
		];
		if ($adminType == 2) {
			$queryParams['embed'] = 'subAdmin1';
		}
		if (!empty($page)) {
			$queryParams['page'] = $page;
		}
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		$admins = data_get($apiResult, 'data');
		
		// No admin. division found. Display error.
		if (empty($admins)) {
			$message = $apiMessage ?? t('admin_division_does_not_exists', [], 'global', $languageCode);
			$result = ['message' => $message];
			
			return ajaxResponse()->json($result, 404);
		}
		
		// Get & formats the admin. divisions
		$adminsArr = [];
		foreach ($admins as $admin) {
			$name = data_get($admin, 'name');
			
			// Change the name for admin. division 2
			if ($adminType == 2) {
				$admin1Name = data_get($admin, 'subAdmin1.name');
				$name = !empty($admin1Name) ? $name . ', ' . $admin1Name : $name;
			}
			
			$adminsArr[] = [
				'code' => data_get($admin, 'code'),
				'name' => $name,
			];
		}
		
		return ajaxResponse()->json(['data' => $adminsArr]);
	}
	
	/**
	 * Form Select Box
	 * Get Admin1 or Admin2's Cities
	 *
	 * @param $countryCode
	 * @param $adminType
	 * @param $adminCode
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCities($countryCode, $adminType, $adminCode): \Illuminate\Http\JsonResponse
	{
		$languageCode = request()->query('languageCode', config('app.locale'));
		$query = request()->query('q');
		$page = request()->integer('page', 1);
		$adminTypes = ['1', '2'];
		
		$queryParams = [
			'q'             => $query,
			'autocomplete'  => 1,
			'sort'          => 'population',
			'language_code' => $languageCode,
			'perPage'       => 10,
		];
		if (!empty($page)) {
			$queryParams['page'] = $page;
		}
		$headers = [];
		
		if (!in_array($adminType, $adminTypes) || $adminCode == '0') {
			$queryParams['embed'] = 'subAdmin1,subAdmin2';
			
			// Get country's cities - Call API endpoint
			$apiResult = $this->getCitiesFromApi($countryCode, $queryParams, $headers);
			$cities = data_get($apiResult, 'data');
		} else {
			$embedQsValue = 'subAdmin' . $adminType;
			$adminCodeQs = 'admin' . $adminType . 'Code';
			$queryParams['embed'] = $embedQsValue;
			$queryParams[$adminCodeQs] = $adminCode;
			$headers['X-WEB-REQUEST-URL'] = request()->fullUrlWithQuery([$adminCodeQs => $adminCode]);
			
			// Get country's cities - Call API endpoint
			$apiResult = $this->getCitiesFromApi($countryCode, $queryParams, $headers);
			$cities = data_get($apiResult, 'data');
			
			// If the admin. division's type is 2 and If no cities are found...
			// then, get cities from their admin. division 1
			if ($adminType == 2) {
				if (isset($queryParams[$adminCodeQs])) {
					unset($queryParams[$adminCodeQs]);
				}
				$queryParams['embed'] = 'subAdmin1';
				if (empty($cities)) {
					$queryParams['admin1Code'] = $adminCode;
					$headers['X-WEB-REQUEST-URL'] = request()->fullUrlWithQuery(['admin1Code' => $adminCode]);
					
					// Get country's cities - Call API endpoint
					$apiResult = $this->getCitiesFromApi($countryCode, $queryParams, $headers);
					$cities = data_get($apiResult, 'data');
				}
			}
		}
		$totalEntries = (int)data_get($apiResult, 'meta.total', 0);
		
		// Get Cities Array
		$items = [];
		if (!empty($cities)) {
			foreach ($cities as $city) {
				$cityName = data_get($city, 'name');
				$admin2Name = data_get($city, 'subAdmin2.name');
				$admin1Name = data_get($city, 'subAdmin1.name');
				
				$fullCityName = !empty($admin2Name)
					? $cityName . ', ' . $admin2Name
					: (!empty($admin1Name) ? $cityName . ', ' . $admin1Name : $cityName);
				
				$items[] = [
					'id'   => data_get($city, 'id'),
					'text' => $fullCityName,
				];
			}
		}
		
		return ajaxResponse()->json(['items' => $items, 'totalEntries' => $totalEntries]);
	}
	
	/**
	 * Form Select Box
	 * Get the selected City
	 *
	 * @param $countryCode
	 * @param $cityId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSelectedCity($countryCode, $cityId): \Illuminate\Http\JsonResponse
	{
		$languageCode = request()->query('languageCode', config('app.locale'));
		
		// Get the City by its ID - Call API endpoint
		$endpoint = '/cities/' . $cityId;
		$queryParams = [
			'embed'         => 'subAdmin1,subAdmin2',
			'language_code' => $languageCode,
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		$apiMessage = $this->handleHttpError($data);
		$city = data_get($data, 'result');
		
		if (empty($city)) {
			$item = [
				'id'   => 0,
				'text' => t('select_a_city', [], 'global', $languageCode),
			];
			
			return ajaxResponse()->json($item);
		}
		
		$cityName = data_get($city, 'name');
		$admin2Name = data_get($city, 'subAdmin2.name');
		$admin1Name = data_get($city, 'subAdmin1.name');
		
		$fullCityName = !empty($admin2Name)
			? $cityName . ', ' . $admin2Name
			: (!empty($admin1Name) ? $cityName . ', ' . $admin1Name : $cityName);
		
		$item = [
			'id'   => data_get($city, 'id'),
			'text' => $fullCityName,
		];
		
		return ajaxResponse()->json($item);
	}
	
	/**
	 * @param string $countryCode
	 * @param array $queryParams
	 * @param array $headers
	 * @return array
	 */
	private function getCitiesFromApi(string $countryCode, array $queryParams = [], array $headers = []): array
	{
		$endpoint = '/countries/' . $countryCode . '/cities';
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams, $headers);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		
		return is_array($apiResult) ? $apiResult : [];
	}
}
