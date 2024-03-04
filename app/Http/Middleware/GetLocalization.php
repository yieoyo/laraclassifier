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

namespace App\Http\Middleware;

use App\Helpers\Localization\Country as CountryHelper;
use App\Helpers\Localization\Language as LanguageHelper;
use App\Http\Middleware\GetLocalization\GetAdminLocalization;
use App\Models\Currency;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GetLocalization
{
	use GetAdminLocalization;
	
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 * @throws \Exception
	 */
	public function handle(Request $request, Closure $next)
	{
		// Exception for Install & Upgrade Routes
		if (str_contains(currentRouteAction(), 'Web\Install\\')) {
			return $next($request);
		}
		
		// Load Localization Data from the Admin Panel
		if (isAdminPanel()) {
			$this->loadAdminLocalizationData();
			
			return $next($request);
		}
		
		// Load Localization Data
		$this->loadLocalizationData();
		
		return $next($request);
	}
	
	/**
	 * Load Localization Data for the API & Web Front
	 *
	 * @return void
	 */
	private function loadLocalizationData(): void
	{
		// Language
		$langObj = new LanguageHelper();
		$lang    = $langObj->find();
		
		// Country
		$countryObj = new CountryHelper();
		$countryObj->find();
		
		if (!isFromApi()) {
			$countryObj->validateTheCountry();
		}
		
		// Get selected country & the user's IP country
		$country   = $countryObj->country;
		$ipCountry = $countryObj->ipCountry;
		
		// Session: Set Country Code
		// Config: Country
		if ($country->isNotEmpty() && $country->has('code')) {
			if (!isFromApi()) {
				session()->put('countryCode', $country->get('code'));
			}
			
			config()->set('country.locale', config('app.locale'));
			config()->set('country.lang', []);
			
			if ($country->has('lang')) {
				$countryLang = $country->get('lang');
				if ($countryLang instanceof Collection) {
					if ($countryLang->has('code')) {
						config()->set('country.locale', $countryLang->get('code'));
					}
					config()->set('country.lang', $countryLang->toArray());
				}
			}
			
			config()->set('country.code', $country->get('code'));
			config()->set('country.icode', $country->get('icode'));
			config()->set('country.iso3', $country->get('iso3'));
			config()->set('country.name', $country->get('name'));
			config()->set('country.currency', $country->get('currency_code'));
			config()->set('country.phone', $country->get('phone'));
			config()->set('country.languages', $country->get('languages'));
			config()->set('country.time_zone', $country->has('time_zone') ? $country->get('time_zone') : config('app.timezone'));
			config()->set('country.date_format', $country->has('date_format') ? $country->get('date_format') : null);
			config()->set('country.datetime_format', $country->has('datetime_format') ? $country->get('datetime_format') : null);
			config()->set('country.admin_type', $country->get('admin_type'));
			config()->set('country.flag_url', $country->get('flag_url'));
			config()->set('country.flag24_url', $country->get('flag24_url'));
			config()->set('country.flag32_url', $country->get('flag32_url'));
			config()->set('country.background_image_url', $country->get('background_image_url'));
		}
		
		// Config: IP Country
		if ($ipCountry->isNotEmpty() && $ipCountry->has('code')) {
			config()->set('ipCountry.code', $ipCountry->get('code'));
			config()->set('ipCountry.name', $ipCountry->get('name'));
			config()->set('ipCountry.time_zone', ($ipCountry->has('time_zone')) ? $ipCountry->get('time_zone') : null);
		}
		
		// Config: Currency
		if ($country->isNotEmpty() && $country->has('currency')) {
			$currency = $country->get('currency');
			if ($currency instanceof Currency || $currency instanceof Collection) {
				config()->set('currency', $currency->toArray());
			}
		}
		
		// Config: Language
		if ($lang->isNotEmpty()) {
			config()->set('lang.code', $lang->get('code'));
			config()->set('lang.locale', $lang->get('locale'));
			config()->set('lang.iso_locale', $lang->get('iso_locale'));
			config()->set('lang.tag', $lang->get('tag'));
			config()->set('lang.direction', $lang->get('direction'));
			config()->set('lang.russian_pluralization', $lang->get('russian_pluralization'));
			config()->set('lang.date_format', $lang->get('date_format'));
			config()->set('lang.datetime_format', $lang->get('datetime_format'));
		}
		
		if (!isFromApi()) {
			// Config: Currency Exchange Plugin
			if (config('plugins.currencyexchange.installed')) {
				$currencies = $country->has('currencies') ? $country->get('currencies') : '';
				config()->set('country.currencies', $currencies);
			} else {
				config()->set('selectedCurrency', config('currency'));
			}
			
			// Config: Domain Mapping Plugin
			if (config('plugins.domainmapping.installed')) {
				applyDomainMappingConfig(config('country.code'));
			}
		}
		
		/*
		 * IMPORTANT
		 * The code below is executed both for Web & API calls
		 *
		 * API: It's only here that the language for API calls is applied
		 * Web: This middleware needs to be called before the
		 * 'SetBrowserLocale', 'SetCountryLocale' and 'SetDefaultLocale' middlewares,
		 * that also need to be called in this order.
		 */
		
		// Apply the country's language to the app
		// & to the system (if its locale is available on the server)
		app()->setLocale(config('lang.code'));
		systemLocale()->setLocale(config('lang.locale'));
	}
}
