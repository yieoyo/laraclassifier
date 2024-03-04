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

namespace App\Http\Controllers\Web\Install\Traits\Install;

use App\Helpers\Cookie;
use App\Helpers\GeoIP;
use Illuminate\Support\Facades\Http;

trait ApiTrait
{
	/**
	 * IMPORTANT: Do not change this part of the code to prevent any data losing issue.
	 *
	 * @param $purchaseCode
	 * @return false|mixed|string
	 */
	private function purchaseCodeChecker($purchaseCode)
	{
		$data = [];
		$endpoint = getPurchaseCodeApiEndpoint($purchaseCode, config('larapen.core.itemId'));
		try {
			/*
			 * Make the request and wait for 30 seconds for response.
			 * If it does not receive one, wait 5000 milliseconds (5 seconds), and then try again.
			 * Keep trying up to 2 times, and finally give up and throw an exception.
			 */
			$response = Http::withoutVerifying()->timeout(30)->retry(2, 5000)->get($endpoint)->throw();
			$data = $response->json();
		} catch (\Throwable $e) {
			$endpoint = (str_starts_with($endpoint, 'https:'))
				? str_replace('https:', 'http:', $endpoint)
				: str_replace('http:', 'https:', $endpoint);
			
			try {
				$response = Http::withoutVerifying()->timeout(30)->retry(2, 5000)->get($endpoint)->throw();
				$data = $response->json();
			} catch (\Throwable $e) {
				$data['message'] = parseHttpRequestError($e);
			}
		}
		
		return $data;
	}
	
	/**
	 * @param array|null $defaultDrivers
	 * @return array|string|null
	 */
	private static function getCountryCodeFromIPAddr(?array $defaultDrivers = ['ipapi', 'ipapico'])
	{
		if (empty($defaultDrivers)) {
			return null;
		}
		
		$countryCode = Cookie::get('ipCountryCode');
		if (empty($countryCode)) {
			// Localize the user's country
			try {
				foreach ($defaultDrivers as $driver) {
					config()->set('geoip.default', $driver);
					
					$data = (new GeoIP())->getData();
					$countryCode = data_get($data, 'countryCode');
					if ($countryCode == 'UK') {
						$countryCode = 'GB';
					}
					
					if (!is_string($countryCode) || strlen($countryCode) != 2) {
						// Remove the current element (driver) from the array
						$currDefaultDrivers = array_diff($defaultDrivers, [$driver]);
						if (!empty($currDefaultDrivers)) {
							return self::getCountryCodeFromIPAddr($currDefaultDrivers);
						}
						
						return null;
					} else {
						break;
					}
				}
			} catch (\Throwable $t) {
				return null;
			}
			
			// Set data in cookie
			Cookie::set('ipCountryCode', $countryCode);
		}
		
		return $countryCode;
	}
}
