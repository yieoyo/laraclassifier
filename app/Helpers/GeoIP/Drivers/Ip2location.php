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

class Ip2location extends AbstractDriver
{
	public function get($ip)
	{
		$data = $this->getRaw($ip);
		
		if (empty($data) || (data_get($data, 'response') !== 'OK') || is_string($data)) {
			return $this->getDefault($ip, $data);
		}
		
		return [
			'driver'      => config('geoip.default'),
			'ip'          => $ip,
			'city'        => data_get($data, 'city_name'),
			'country'     => data_get($data, 'country_name'),
			'countryCode' => data_get($data, 'country_code'),
			'latitude'    => (float)number_format(data_get($data, 'latitude'), 5),
			'longitude'   => (float)number_format(data_get($data, 'longitude'), 5),
			'region'      => data_get($data, 'region_name'),
			'regionCode'  => data_get($data, 'region.code'),
			'timezone'    => data_get($data, 'time_zone'),
			'postalCode'  => data_get($data, 'zip_code'),
		];
	}
	
	/**
	 * ip2location
	 * https://www.ip2location.com/
	 * Free Plan: 5000 credits (valid for 1 year)
	 *
	 * @param $ip
	 * @return array|mixed|string
	 */
	public function getRaw($ip)
	{
		$apiKey = config('geoip.drivers.ip2location.apiKey');
		$url = 'https://api.ip2location.com/v2/';
		$query = [
			'ip'      => $ip,
			'key'     => $apiKey,
			'package' => 'WS3', // Country, Region, City (2 credits/call)
			'format'  => 'json',
			'addon'   => 'continent,country,region',
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
