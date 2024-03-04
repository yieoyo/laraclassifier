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

class Ipstack extends AbstractDriver
{
	public function get($ip)
	{
		$data = $this->getRaw($ip);
		
		if (empty($data) || !empty(data_get($data, 'error')) || is_string($data)) {
			return $this->getDefault($ip, $data);
		}
		
		return [
			'driver'      => config('geoip.default'),
			'ip'          => $ip,
			'city'        => data_get($data, 'city'),
			'country'     => data_get($data, 'country_name'),
			'countryCode' => data_get($data, 'country_code'),
			'latitude'    => (float)number_format(data_get($data, 'latitude'), 5),
			'longitude'   => (float)number_format(data_get($data, 'longitude'), 5),
			'region'      => data_get($data, 'region_name'),
			'regionCode'  => data_get($data, 'region_code'),
			'timezone'    => null,
			'postalCode'  => data_get($data, 'zip'),
		];
	}
	
	/**
	 * ipstack
	 * https://ipstack.com/
	 * Free Plan: 100 requests / Month
	 * 256-bit SSL encryption is not available for this free API
	 *
	 * @param $ip
	 * @return array|mixed|string
	 */
	public function getRaw($ip)
	{
		$accessKey = config('geoip.drivers.ipstack.accessKey');
		$protocol = config('geoip.drivers.ipstack.pro') ? 'https' : 'http';
		
		$url = $protocol . '://api.ipstack.com/' . $ip;
		$query = [
			'access_key' => $accessKey,
			'output'     => 'json',
			'language'   => 'en',
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
