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

class Ipgeolocation extends AbstractDriver
{
	public function get($ip)
	{
		$data = $this->getRaw($ip);
		
		if (empty($data) || is_string($data)) {
			return $this->getDefault($ip, $data);
		}
		
		return [
			'driver'      => config('geoip.default'),
			'ip'          => $ip,
			'city'        => data_get($data, 'city'),
			'country'     => data_get($data, 'country_name'),
			'countryCode' => data_get($data, 'country_code2'),
			'latitude'    => (float)number_format(data_get($data, 'latitude'), 5),
			'longitude'   => (float)number_format(data_get($data, 'longitude'), 5),
			'region'      => data_get($data, 'state_prov'),
			'regionCode'  => null,
			'timezone'    => data_get($data, 'time_zone.name'),
			'postalCode'  => data_get($data, 'zipcode'),
		];
	}
	
	/**
	 * ipgeolocation
	 * https://ipgeolocation.io/
	 * Free Plan: 30,000 requests / Month (for non-commercial usage)
	 *
	 * @param $ip
	 * @return array|mixed|string
	 */
	public function getRaw($ip)
	{
		$apiKey = config('geoip.drivers.ipgeolocation.apiKey');
		$url = 'https://api.ipgeolocation.io/ipgeo';
		$query = [
			'apiKey' => $apiKey,
			'ip'     => $ip,
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
