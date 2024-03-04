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

namespace App\Helpers\GeoIP\Drivers;

use App\Helpers\GeoIP\AbstractDriver;
use Illuminate\Support\Facades\Http;

class Ipapico extends AbstractDriver
{
	public function get($ip)
	{
		$data = $this->getRaw($ip);
		
		if (empty($data) || data_get($data, 'error') || !empty(data_get($data, 'reason')) || is_string($data)) {
			return $this->getDefault($ip, $data);
		}
		
		return [
			'driver'      => config('geoip.default'),
			'ip'          => $ip,
			'city'        => data_get($data, 'city'),
			'country'     => data_get($data, 'country_name'),
			'countryCode' => data_get($data, 'country'),
			'latitude'    => (float)number_format(data_get($data, 'latitude'), 5),
			'longitude'   => (float)number_format(data_get($data, 'longitude'), 5),
			'region'      => data_get($data, 'region'),
			'regionCode'  => data_get($data, 'region_code'),
			'timezone'    => data_get($data, 'timezone'),
			'postalCode'  => data_get($data, 'postal', data_get($data, 'postalCode')),
		];
	}
	
	/**
	 * ipapico
	 * https://ipapi.co/
	 * Free Plan: 30,000 requests / Month (No SignUp Required)
	 *
	 * @param $ip
	 * @return array|mixed|string
	 */
	public function getRaw($ip)
	{
		$url = 'https://ipapi.co/' . $ip . '/json/';
		
		try {
			$response = Http::get($url);
			if ($response->successful()) {
				return $response->json();
			}
		} catch (\Throwable $e) {
			$response = $e;
		}
		
		return parseHttpRequestError($response);
	}
}
