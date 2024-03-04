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

namespace App\Helpers\GeoIP;

abstract class AbstractDriver
{
	public function __construct()
	{
		//...
	}
	
	/**
	 * Get GeoIP info from IP.
	 *
	 * @param string|null $ip
	 *
	 * @return array
	 */
	abstract public function get(?string $ip);
	
	/**
	 * Get the raw GeoIP info from the driver.
	 *
	 * @param string|null $ip
	 *
	 * @return mixed
	 */
	abstract public function getRaw(?string $ip);
	
	/**
	 * Get the default values (all null).
	 *
	 * @param string|null $ip
	 * @param $responseError
	 * @return array
	 */
	protected function getDefault(?string $ip, $responseError = null): array
	{
		$responseError = parseHttpRequestError($responseError); // required!
		
		return [
			'driver'      => config('geoip.default'),
			'ip'          => $ip,
			'error'       => $responseError,
			'city'        => null,
			'country'     => null,
			'countryCode' => null,
			'latitude'    => null,
			'longitude'   => null,
			'region'      => null,
			'regionCode'  => null,
			'timezone'    => null,
			'postalCode'  => null,
		];
	}
}
