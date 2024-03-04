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

namespace App\Helpers\Localization;

use App\Helpers\Arr;
use App\Models\Language as LanguageModel;
use App\Helpers\Localization\Helpers\Country as CountryHelper;
use App\Models\Scopes\ActiveScope;
use Illuminate\Support\Collection;

class Language
{
	protected ?string $defaultLocale;
	
	protected static ?Collection $languages = null;
	
	public static int $cacheExpiration = 3600;
	
	public function __construct()
	{
		// Set Default Locale
		$this->defaultLocale = config('app.locale');
		
		// Get all languages
		self::$languages = self::getLanguages();
		
		// Cache Expiration Time
		self::$cacheExpiration = (int)config('settings.optimization.cache_expiration', self::$cacheExpiration);
	}
	
	/**
	 * Find Language
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function find(): Collection
	{
		// Get the Language
		if (isFromApi()) {
			
			// API call
			$lang = $this->fromHeader();
			if ($lang->isEmpty()) {
				$lang = $this->fromUser();
			}
			
		} else {
			
			// Non API call
			$lang = $this->fromSession();
			if ($lang->isEmpty()) {
				$lang = $this->fromBrowser();
			}
			
		}
		
		// If Language didn't find, Get the Default Language.
		if ($lang->isEmpty()) {
			$lang = $this->fromConfig();
		}
		
		return $lang;
	}
	
	/**
	 * Get Language from logged User (for API)
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function fromUser(): Collection
	{
		$lang = collect();
		
		$guard = isFromApi() ? 'sanctum' : null;
		if (auth($guard)->check()) {
			$user = auth($guard)->user();
			if (!empty($user) && isset($user->language_code)) {
				$langCode = $user->language_code;
				if (!empty($langCode)) {
					// Get the Language Details
					$isAvailableLang = self::$languages->has($langCode) ? self::$languages->get($langCode) : [];
					$isAvailableLang = collect($isAvailableLang);
					
					if ($isAvailableLang->isNotEmpty()) {
						$lang = $isAvailableLang;
					}
				}
			}
		}
		
		return $lang;
	}
	
	/**
	 * Get Language from HTTP Header (for API)
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function fromHeader(): Collection
	{
		$lang = collect();
		
		// Get language code from the 'Accept-Language' header
		$acceptLanguage = request()->header('Accept-Language');
		$acceptLanguageArray = parseAcceptLanguageHeader($acceptLanguage);
		$langCode = array_key_first($acceptLanguageArray);
		
		// Get language code from the 'Content-Language' header
		$langCode = request()->header('Content-Language', $langCode);
		
		if (!empty($langCode)) {
			// Get the Language Details
			$isAvailableLang = self::$languages->has($langCode) ? self::$languages->get($langCode) : [];
			$isAvailableLang = collect($isAvailableLang);
			
			if ($isAvailableLang->isNotEmpty()) {
				$lang = $isAvailableLang;
			}
		}
		
		return $lang;
	}
	
	/**
	 * Get Language from Session
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function fromSession(): Collection
	{
		$lang = collect();
		
		if (session()->has('langCode')) {
			$langCode = session('langCode');
			if (!empty($langCode)) {
				// Get the Language Details
				$isAvailableLang = self::$languages->has($langCode) ? self::$languages->get($langCode) : [];
				$isAvailableLang = collect($isAvailableLang);
				
				if ($isAvailableLang->isNotEmpty()) {
					$lang = $isAvailableLang;
				}
			}
		}
		
		return $lang;
	}
	
	/**
	 * Get Language from Browser
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function fromBrowser(): Collection
	{
		$lang = collect();
		
		if (config('settings.localization.auto_detect_language') != 'from_browser') {
			return $lang;
		}
		
		// Parse the browser's languages
		$langTab = parseAcceptLanguageHeader();
		
		// Get country info \w country language
		$country = self::getCountryFromIP();
		
		// Search the default language (Intersection Browser & Country language OR First Browser language)
		$langCode = '';
		if (!empty($langTab)) {
			foreach ($langTab as $code => $q) {
				if (!$country->isEmpty() && $country->has('lang')) {
					$countryLang = $country->get('lang');
					if (
						$countryLang instanceof Collection
						&& !$countryLang->isEmpty()
						&& $countryLang->has('code')
					) {
						if (str_contains($code, $countryLang->get('code'))) {
							$langCode = substr($code, 0, 2);
							break;
						}
					}
				} else {
					if ($langCode == '') {
						$langCode = substr($code, 0, 2);
					}
				}
			}
		}
		
		// Check language
		if ($langCode != '') {
			// Get the Language details
			$isAvailableLang = self::$languages->has($langCode) ? self::$languages->get($langCode) : [];
			$isAvailableLang = collect($isAvailableLang);
			
			if ($isAvailableLang->isNotEmpty()) {
				$lang = $isAvailableLang;
			}
		}
		
		return $lang;
	}
	
	/**
	 * Get Language from Database or Config file
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function fromConfig(): Collection
	{
		$locale = config('app.locale');
		
		$defaultLang = [
			'code' => $locale,
			'tag'  => getLangTag($locale),
		];
		
		// Get the default Language (from DB)
		$langCode = config('appLang.code');
		
		// Get the Language details
		try {
			// Get the Language details
			$lang = self::$languages->has($langCode) ? self::$languages->get($langCode) : [];
			$lang = collect($lang);
		} catch (\Throwable $e) {
			$lang = collect($defaultLang);
		}
		
		// Check if language code exists
		if (!$lang->has('code')) {
			$lang = collect($defaultLang);
		}
		
		return $lang;
	}
	
	/**
	 * Get all languages
	 *
	 * @param bool $includeNonActive
	 * @return \Illuminate\Support\Collection
	 */
	public static function getLanguages(bool $includeNonActive = false): Collection
	{
		$languages = [];
		
		try {
			$cacheId = 'languages.all';
			$languages = cache()->remember($cacheId, self::$cacheExpiration, function () use ($includeNonActive) {
				$languages = LanguageModel::query();
				if ($includeNonActive) {
					$languages->withoutGlobalScopes([ActiveScope::class]);
				} else {
					$languages->active();
				}
				$languages = $languages->orderBy('lft')->get();
				
				if ($languages->count() > 0) {
					$languages = $languages->keyBy('code');
				}
				
				return $languages;
			});
		} catch (\Throwable $e) {
			$locale = config('app.locale');
			$languages[$locale] = [
				'code' => $locale,
				'tag'  => getLangTag($locale),
			];
		}
		
		return collect($languages);
	}
	
	/**
	 * Translate & (re)sort a given countries list
	 *
	 * @param $countries
	 * @param string $locale
	 * @param string $source
	 * @return \Illuminate\Support\Collection
	 */
	public function countries($countries, string $locale = 'en', string $source = 'cldr'): Collection
	{
		// Security
		if (!$countries instanceof Collection) {
			return collect();
		}
		
		// $locale = 'en'; // debug
		$countryLang = new CountryHelper();
		$tab = [];
		foreach ($countries as $code => $country) {
			$tab[$code] = $country;
			if ($name = $countryLang->get($code, $locale, $source)) {
				$tab[$code]['name'] = $name;
			}
		}
		
		$countries = collect($tab);
		
		// Sort
		return Arr::mbSortBy($countries, 'name', $locale);
	}
	
	/**
	 * Translate a given country
	 * (Only the country name will be translated)
	 *
	 * @param $country
	 * @param string $locale
	 * @param string $source
	 * @return \Illuminate\Support\Collection
	 */
	public function country($country, string $locale = 'en', string $source = 'cldr'): Collection
	{
		// Security
		if (!$country instanceof Collection) {
			return collect();
		}
		
		// $locale = 'en'; // debug
		$countryLang = new CountryHelper();
		if ($name = $countryLang->get($country->get('code'), $locale, $source)) {
			return $country->merge(['name' => $name]);
		} else {
			return $country;
		}
	}
	
	/**
	 * @return \Illuminate\Support\Collection
	 */
	public static function getCountryFromIP(): Collection
	{
		// GeoIP
		$countryCode = Country::getCountryCodeFromIP();
		if (empty($countryCode)) {
			return collect();
		}
		
		return Country::getCountryInfo($countryCode);
	}
}
