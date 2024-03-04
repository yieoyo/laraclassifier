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

namespace App\Http\Controllers\Web\Public\Traits;

trait HasIntlTelInput
{
	/**
	 * Country list for the 'intl-tel-input' plugin
	 * URI: common/js/intl-tel-input/countries.js
	 *
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
	 */
	public function intlTelInputData()
	{
		// Get the country list with calling code
		$countries = $this->getItiCountries();
		
		// dd(collect($countries)->keyBy('iso2')->toArray()); // debug
		
		$out = 'var itiErrorMessage = "Impossible to load countries with phone codes.";';
		if (!empty($countries)) {
			$out = 'var phoneCountries = ' . json_encode($countries) . ';' . "\n";
			$code = 200;
		}
		
		return response($out, $code ?? 400)->header('Content-Type', 'application/javascript');
	}
	
	/**
	 * Get the 'Intl Tel Input' countries
	 *
	 * @return array
	 */
	private function getItiCountries(): array
	{
		$phoneOfCountries = config('settings.sms.phone_of_countries', 'local');
		$cacheExpiration = (int)config('settings.optimization.cache_expiration', 3600);
		$countryCode = config('country.code', 'US');
		
		$cacheId = isFromAdminPanel()
			? 'web.iti.countries'
			: 'web.iti.countries.' . $phoneOfCountries . '.' . $countryCode . '.' . app()->getLocale();
		
		$countries = cache()->remember($cacheId, $cacheExpiration, function () use ($countryCode) {
			return $this->getItiCountriesWithoutCache();
		});
		
		return is_array($countries) ? $countries : [];
	}
	
	/**
	 * Get the 'Intl Tel Input' countries (Without Cache)
	 *
	 * @return array
	 */
	private function getItiCountriesWithoutCache(): array
	{
		// Call API endpoint
		$endpoint = '/countries';
		$queryParams = [
			'iti'              => true,
			'isFromAdminPanel' => isFromAdminPanel(),
		];
		$data = makeApiRequest('get', $endpoint, $queryParams);
		$countries = data_get($data, 'result', []);
		
		return is_array($countries) ? $countries : [];
	}
}
