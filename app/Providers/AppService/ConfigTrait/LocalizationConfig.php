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

namespace App\Providers\AppService\ConfigTrait;

trait LocalizationConfig
{
	private function updateLocalizationConfig(?array $settings = []): void
	{
		// geoip
		config()->set('geoip.default', env('GEOIP_DRIVER', config('settings.localization.geoip_driver')));
		config()->set('geoip.randomIp', env('GEOIP_RANDOM_IP', false));
		
		// ipinfo
		if (config('geoip.default') == 'ipinfo') {
			config()->set(
				'geoip.drivers.ipinfo.token',
				env('GEOIP_IPINFO_TOKEN', config('settings.localization.ipinfo_token'))
			);
		}
		
		// dbip
		if (config('geoip.default') == 'dbip') {
			config()->set(
				'geoip.drivers.dbip.apiKey',
				env('GEOIP_DBIP_API_KEY', config('settings.localization.dbip_api_key'))
			);
			config()->set(
				'geoip.drivers.dbip.pro',
				env('GEOIP_DBIP_PRO', config('settings.localization.dbip_pro'))
			);
		}
		
		// ipbase
		if (config('geoip.default') == 'ipbase') {
			config()->set(
				'geoip.drivers.ipbase.apiKey',
				env('GEOIP_IPBASE_API_KEY', config('settings.localization.ipbase_api_key'))
			);
		}
		
		// ip2location
		if (config('mail.default') == 'ip2location') {
			config()->set(
				'geoip.drivers.ip2location.apiKey',
				env('GEOIP_IP2LOCATION_API_KEY', config('settings.localization.ip2location_api_key'))
			);
		}
		
		// ipapi
		if (config('geoip.default') == 'ipapi') {
			config()->set(
				'geoip.drivers.ipapi.pro',
				env('GEOIP_IPAPI_PRO', config('settings.localization.ipapi_pro'))
			);
		}
		
		// ipapico
		if (config('geoip.default') == 'ipapico') {
			//...
		}
		
		// ipgeolocation
		if (config('geoip.default') == 'ipgeolocation') {
			config()->set(
				'geoip.drivers.ipgeolocation.apiKey',
				env('GEOIP_IPGEOLOCATION_API_KEY', config('settings.localization.ipgeolocation_api_key'))
			);
		}
		
		// iplocation
		if (config('geoip.default') == 'iplocation') {
			config()->set(
				'geoip.drivers.iplocation.apiKey',
				env('GEOIP_IPLOCATION_API_KEY', config('settings.localization.iplocation_api_key'))
			);
			config()->set(
				'geoip.drivers.iplocation.pro',
				env('GEOIP_IPLOCATION_PRO', config('settings.localization.iplocation_pro'))
			);
		}
		
		// ipstack
		if (config('geoip.default') == 'ipstack') {
			config()->set(
				'geoip.drivers.ipstack.accessKey',
				env('GEOIP_IPSTACK_ACCESS_KEY', config('settings.localization.ipstack_access_key'))
			);
			config()->set(
				'geoip.drivers.ipstack.pro',
				env('GEOIP_IPLOCATION_PRO', config('settings.localization.ipstack_pro'))
			);
		}
		
		// maxmind_api
		if (config('geoip.default') == 'maxmind_api') {
			config()->set(
				'geoip.drivers.maxmind_api.accountId',
				env('GEOIP_MAXMIND_ACCOUNT_ID', config('settings.localization.maxmind_api_account_id'))
			);
			config()->set(
				'geoip.drivers.maxmind_api.licenseKey',
				env('GEOIP_MAXMIND_LICENSE_KEY', config('settings.localization.maxmind_api_license_key'))
			);
		}
		
		// maxmind_database
		if (config('geoip.default') == 'maxmind_database') {
			config()->set(
				'geoip.drivers.maxmind_database.licenseKey',
				env('GEOIP_MAXMIND_LICENSE_KEY', config('settings.localization.maxmind_database_license_key'))
			);
		}
	}
}
