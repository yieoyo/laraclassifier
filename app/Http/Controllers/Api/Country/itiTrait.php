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

namespace App\Http\Controllers\Api\Country;

use App\Helpers\Arr;
use App\Models\Country;
use App\Models\Scopes\ActiveScope;

trait itiTrait
{
	/**
	 * Get the 'Intl Tel Input' countries
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getItiCountries(): \Illuminate\Http\JsonResponse
	{
		// Get the countries from the plugin JS file
		// to get eventual missed information (e.g. priority)
		$pluginCountries = $this->getItiCountriesFromConfig();
		if (!empty($pluginCountries)) {
			$pluginCountries = collect($pluginCountries)->keyBy('iso2')->toArray();
		}
		
		// Get the countries from DB
		$dbCountries = $this->getItiCountriesFromDb();
		
		if ($dbCountries->isEmpty()) {
			return apiResponse()->noContent();
		}
		
		$dbCountries = $dbCountries->toArray();
		
		$countries = [];
		foreach ($dbCountries as $country) {
			if (
				empty($country['name'])
				|| empty($country['code'])
				|| empty($country['phone'])
			) {
				continue;
			}
			
			$name = str($country['name'])->limit(50)->toString();
			$iso2 = strtolower($country['code']);
			
			$newItem = [
				'name'      => $name,
				'iso2'      => $iso2,
				'dialCode'  => null,
				'priority'  => 0,
				'areaCodes' => null,
			];
			
			// dialCode
			$phoneCode = str_replace('+', '', $country['phone']);
			if (
				str_contains($phoneCode, '-')
				|| str_contains($phoneCode, '/')
				|| str_contains($phoneCode, ',')
				|| str_contains($phoneCode, '|')
			) {
				$areaCodes = [];
				if (str_contains($phoneCode, '-')) {
					$tmp = explode('-', $phoneCode);
					$newItem['dialCode'] = $tmp[0];
					if (isset($tmp[1])) {
						$tmp2 = preg_split('#/|,|\|#', $tmp[1]);
						$areaCodes = [$tmp2[0]];
					}
				}
				if (
					str_contains($phoneCode, '/')
					|| str_contains($phoneCode, ',')
					|| str_contains($phoneCode, '|')
				) {
					$tmp = preg_split('#/|,|\|#', $phoneCode);
					foreach ($tmp as $areaCode) {
						if (str_contains($areaCode, '-')) {
							$areaCode = Arr::last(explode('-', $areaCode));
						}
						$areaCodes[] = $areaCode;
					}
					$areaCodes = array_unique($areaCodes);
				}
				$newItem['areaCodes'] = $areaCodes;
			} else {
				$newItem['dialCode'] = $phoneCode;
			}
			
			if (empty($newItem['dialCode'])) {
				continue;
			}
			
			// priority
			$newItem['priority'] = $pluginCountries[$iso2]['priority'] ?? $newItem['priority'];
			
			$countries[] = $newItem;
		}
		
		$data = [
			'success' => true,
			'result'  => $countries,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Get the countries from the plugin JS file
	 *
	 * @return array
	 */
	public function getItiCountriesFromConfig(): array
	{
		$pluginFilePath = public_path('assets/plugins/intl-tel-input/17.0.18/js/intlTelInput.js');
		if (file_exists($pluginFilePath)) {
			$buffer = file_get_contents($pluginFilePath);
			$buffer = str($buffer)->betweenFirst('var allCountries =', '];')->trim()->toString() . ']';
			
			if (str_starts_with($buffer, '[') && str_ends_with($buffer, ']]')) {
				eval('$itiCountries = ' . $buffer . ';');
			}
		}
		
		if (empty($itiCountries)) {
			return [];
		}
		
		// Build and output the intl-tel-input 'data.js' file
		$countries = [];
		foreach ($itiCountries as $key => $item) {
			$countries[$key] = [
				'name'      => str($item[0])->limit(50)->toString(),
				'iso2'      => $item[1],
				'dialCode'  => $item[2],
				'priority'  => $item[3] ?? 0,
				'areaCodes' => $item[4] ?? null,
			];
		}
		
		return $countries;
	}
	
	/**
	 * Get the countries from DB
	 *
	 * @return \Illuminate\Support\Collection
	 */
	private function getItiCountriesFromDb(): \Illuminate\Support\Collection
	{
		$phoneOfCountries = config('settings.sms.phone_of_countries', 'local');
		$isFromAdminPanel = (request()->filled('isFromAdminPanel') && (int)request()->query('isFromAdminPanel') == 1);
		$countryCode = config('country.code', 'US');
		
		$dbQueryCanBeSkipped = (!isFromAdminPanel() && $phoneOfCountries == 'local' && !empty(config('country')));
		if ($dbQueryCanBeSkipped) {
			return collect([$countryCode => collect(config('country'))]);
		}
		
		try {
			$cacheId = $isFromAdminPanel
				? 'iti.countries'
				: 'iti.countries.' . $phoneOfCountries . '.' . $countryCode . '.' . app()->getLocale();
			$countries = cache()->remember(
				$cacheId,
				$this->cacheExpiration,
				function () use ($phoneOfCountries, $isFromAdminPanel, $countryCode) {
					$countries = Country::query();
					
					if ($isFromAdminPanel) {
						$countries->withoutGlobalScopes([ActiveScope::class]);
					} else {
						// Skipped
						if ($phoneOfCountries == 'local') {
							$countries->where('code', $countryCode);
						}
						if ($phoneOfCountries == 'activated') {
							$countries->active();
						}
						if ($phoneOfCountries == 'all') {
							$countries->withoutGlobalScopes([ActiveScope::class]);
						}
					}
					
					$countries = $countries->orderBy('name')->get();
					
					if ($countries->count() > 0) {
						$countries = $countries->keyBy('code');
					}
					
					return $countries;
				});
		} catch (\Throwable $e) {
			$country = [
				'code'  => $countryCode,
				'name'  => config('country.name', 'United States'),
				'phone' => config('country.phone', '1'),
			];
			$countries = [$countryCode => collect($country)];
		}
		
		$countries = collect($countries);
		
		// Sort
		return Arr::mbSortBy($countries, 'name', app()->getLocale());
	}
}
