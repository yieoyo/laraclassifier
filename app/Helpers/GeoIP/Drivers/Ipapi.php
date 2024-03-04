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

class Ipapi extends AbstractDriver
{
	public function get($ip)
	{
		$data = $this->getRaw($ip);
		
		if (empty($data) || (data_get($data, 'status') !== 'success') || is_string($data)) {
			return $this->getDefault($ip, $data);
		}
		
		return [
			'driver'      => config('geoip.default'),
			'ip'          => $ip,
			'city'        => data_get($data, 'city'),
			'country'     => data_get($data, 'country'),
			'countryCode' => data_get($data, 'countryCode'),
			'latitude'    => (float)number_format(data_get($data, 'lat'), 5),
			'longitude'   => (float)number_format(data_get($data, 'lon'), 5),
			'region'      => data_get($data, 'regionName'),
			'regionCode'  => data_get($data, 'region'),
			'timezone'    => data_get($data, 'timezone'),
			'postalCode'  => data_get($data, 'zip'),
		];
	}
	
	/**
	 * ipapi
	 * https://ip-api.com/
	 * Free Plan: Unlimited requests (for non-commercial use, no API key required)
	 * 256-bit SSL encryption is not available for this free API
	 *
	 * NOTE: Documentation not available to implement pro version
	 *
	 * @param $ip
	 * @return array|mixed|string
	 */
	public function getRaw($ip)
	{
		$protocol = config('geoip.drivers.ipapi.pro') ? 'https' : 'http';
		$url = $protocol . '://ip-api.com/json/' . $ip;
		$query = [
			'lang' => 'en',
		];
		
		try {
			$response = Http::get($url, $query);
			if ($response->successful()) {
				return $response->json();
			}
		} catch (\Throwable $e) {
			$response = $e;
		}
		
		return parseHttpRequestError($response);
	}
}
