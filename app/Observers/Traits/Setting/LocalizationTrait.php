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

namespace App\Observers\Traits\Setting;

use App\Helpers\GeoIP;
use Prologue\Alerts\Facades\Alert;

trait LocalizationTrait
{
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return false|void
	 */
	public function localizationUpdating($setting, $original)
	{
		$geolocationActive = $setting->value['geoip_activation'] ?? false;
		if ($geolocationActive) {
			if (!empty($setting->value['default_country_code'])) {
				$message = trans('admin.activating_geolocation_validator');
				
				if (isAdminPanel()) {
					Alert::error($message)->flash();
				} else {
					flash($message)->error();
				}
				
				return false;
			}
		} else {
			if (empty($setting->value['default_country_code'])) {
				$message = trans('admin.disabling_geolocation_validator');
				
				if (isAdminPanel()) {
					Alert::warning($message)->flash();
				} else {
					flash($message)->warning();
				}
			}
		}
		
		// Validate the selected GeoIP driver's parameters
		$validateDriverParameters = $setting->value['geoip_validate_driver'] ?? false;
		if ($validateDriverParameters) {
			$this->updateLocalizationConfig($setting);
			
			try {
				$data = (new GeoIP())->getData();
				$countryCode = data_get($data, 'countryCode');
				
				if (!is_string($countryCode) || strlen($countryCode) != 2) {
					$message = data_get($data, 'error');
					if (isAdminPanel()) {
						Alert::error($message)->flash();
					} else {
						flash($message)->error();
					}
					
					return false;
				}
			} catch (\Throwable $e) {
				$message = $e->getMessage();
				if (isAdminPanel()) {
					Alert::error($message)->flash();
				} else {
					flash($message)->error();
				}
				
				return false;
			}
		}
	}
	
	/**
	 * Saved
	 *
	 * @param $setting
	 */
	public function localizationSaved($setting)
	{
		$this->saveTheDefaultCountryCodeInSession($setting);
	}
	
	/**
	 * @param $setting
	 */
	private function updateLocalizationConfig($setting): void
	{
		if (!isset($setting->value) || !is_array($setting->value)) {
			return;
		}
		
		// geoip
		config()->set('geoip.default', $setting->value['geoip_driver'] ?? null);
		config()->set('geoip.randomIp', true);
		
		// ipinfo
		if (config('geoip.default') == 'ipinfo') {
			config()->set('geoip.drivers.ipinfo.token', $setting->value['ipinfo_token']);
		}
		
		// dbip
		if (config('geoip.default') == 'dbip') {
			config()->set('geoip.drivers.dbip.apiKey', $setting->value['dbip_api_key']);
			config()->set('geoip.drivers.dbip.pro', $setting->value['dbip_pro']);
		}
		
		// ipbase
		if (config('geoip.default') == 'ipbase') {
			config()->set('geoip.drivers.ipbase.apiKey', $setting->value['ipbase_api_key']);
		}
		
		// ip2location
		if (config('geoip.default') == 'ip2location') {
			config()->set('geoip.drivers.ip2location.apiKey', $setting->value['ip2location_api_key']);
		}
		
		// ipapi
		if (config('geoip.default') == 'ipapi') {
			config()->set('geoip.drivers.ipapi.pro', $setting->value['ipapi_pro']);
		}
		
		// ipapico
		if (config('geoip.default') == 'ipapico') {
			//...
		}
		
		// ipgeolocation
		if (config('geoip.default') == 'ipgeolocation') {
			config()->set('geoip.drivers.ipgeolocation.apiKey', $setting->value['ipgeolocation_api_key']);
		}
		
		// iplocation
		if (config('geoip.default') == 'iplocation') {
			config()->set('geoip.drivers.iplocation.apiKey', $setting->value['iplocation_api_key']);
			config()->set('geoip.drivers.iplocation.pro', $setting->value['iplocation_pro']);
		}
		
		// ipstack
		if (config('geoip.default') == 'ipstack') {
			config()->set('geoip.drivers.ipstack.accessKey', $setting->value['ipstack_access_key']);
			config()->set('geoip.drivers.ipstack.pro', $setting->value['ipstack_pro']);
		}
		
		// maxmind_api
		if (config('geoip.default') == 'maxmind_api') {
			config()->set('geoip.drivers.maxmind_api.accountId', $setting->value['maxmind_api_account_id']);
			config()->set('geoip.drivers.maxmind_api.licenseKey', $setting->value['maxmind_api_license_key']);
		}
		
		// maxmind_database
		if (config('geoip.default') == 'maxmind_database') {
			config()->set('geoip.drivers.maxmind_database.licenseKey', $setting->value['maxmind_database_license_key']);
		}
	}
	
	/**
	 * If the Default Country is changed,
	 * Then clear the 'country_code' from the sessions,
	 * And save the new value in session.
	 *
	 * @param $setting
	 */
	private function saveTheDefaultCountryCodeInSession($setting): void
	{
		if (isset($setting->value['default_country_code'])) {
			session()->forget('countryCode');
			session()->put('countryCode', $setting->value['default_country_code']);
		}
	}
}
