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
use App\Helpers\Cookie;
use App\Helpers\GeoIP;
use App\Models\City;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Country as CountryModel;
use App\Models\Currency;
use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use Illuminate\Support\Collection;
use App\Models\Setting;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class Country
{
	public ?string $defaultCountryCode = '';
	public string $defaultUrl = '/';
	public string $defaultPage = '/';
	
	protected static ?Collection $countries = null;
	protected static ?Collection $languages = null;
	
	public Collection $country;
	public Collection $ipCountry;
	
	public static int $cacheExpiration = 3600;
	public static int $cookieExpiration = 3600;
	
	// Maxmind Database URL
	private static string $maxmindDatabaseUrl = 'https://dev.maxmind.com/geoip/geoip2/geolite2/';
	
	public function __construct()
	{
		// Default values
		$this->defaultCountryCode = config('settings.localization.default_country_code');
		$this->defaultUrl = url(config('larapen.localization.default_uri', $this->defaultUrl));
		$this->defaultPage = url(config('larapen.localization.countries_list_uri', $this->defaultPage));
		
		// Cache & Cookies Expiration Time
		self::$cacheExpiration = (int)config('settings.optimization.cache_expiration', self::$cacheExpiration);
		self::$cookieExpiration = (int)config('settings.other.cookie_expiration');
		
		// Get all countries
		self::$countries = self::getCountries();
		
		// Get all languages
		self::$languages = Language::getLanguages();
		
		// Init. Country Infos
		$this->country = collect();
		$this->ipCountry = collect();
	}
	
	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function find(): Collection
	{
		// Get the user's country by its IP address
		$this->ipCountry = $this->getCountryFromIP();
		$this->country = collect();
		
		// Get the country
		if (isFromApi()) {
			// API call
			
			// 'countryCode' query parameter is required for guests
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryFromQueryString();
			}
			
			// Don't fill the 'countryCode' parameter if a user is logged
			// To change country, user needs to update their country.
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryFromLoggedUser();
			}
			
			// If the country is not found,
			// and if the Administrator has been set a default country,
			// then, get it.
			if ($this->country->isEmpty()) {
				$this->country = $this->getDefaultCountry($this->defaultCountryCode);
			}
			
			// If the country is not found,
			// Set the country related to the user's IP address as default country.
			if ($this->country->isEmpty()) {
				if (!$this->ipCountry->isEmpty() && $this->ipCountry->has('code')) {
					$this->country = $this->ipCountry;
				}
			}
			
			// If the country is not found & if it's a call from the API plugin,
			// then, get the most populated country as default country.
			// NOTE: This prevents any HTTP redirection.
			if ($this->country->isEmpty()) {
				$this->country = $this->getMostPopulatedCountry();
			}
			
		} else {
			// WEB call
			
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryFromDomain();
			}
			
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryFromQueryString();
			}
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryFromPost();
			}
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryFromURIPath();
			}
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryFromCity();
			}
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryFromSession();
			}
			if ($this->country->isEmpty()) {
				$this->country = $this->getCountryForBots();
			}
			
			// If the country is not found,
			// and if the Administrator has been set a default country,
			// then, get it.
			if ($this->country->isEmpty()) {
				$this->country = $this->getDefaultCountry($this->defaultCountryCode);
			}
			
			// If the country is not found,
			// set the country related to the user's IP address as default country.
			if ($this->country->isEmpty()) {
				if (!$this->ipCountry->isEmpty() && $this->ipCountry->has('code')) {
					$this->country = $this->ipCountry;
				}
			}
		}
		
		return $this->country;
	}
	
	/**
	 * @return void
	 */
	public function validateTheCountry()
	{
		if (isFromApi()) {
			return;
		}
		
		/*
		 * --------------------------------------------------------------------
		 * Skip possible redirection:
		 * --------------------------------------------------------------------
		 * - On the installation or update URLs
		 * - On static files (JS or CSS) content generation URLs
		 * - On the robots.txt file content generation URL
		 * - On AJAX URLs (including CAPTCHA code generation URLs)
		 * - On pages where a selected country is not needed (even prohibited):
		 *   Countries list page, Feeds page, XML sitemaps, etc.
		 * --------------------------------------------------------------------
		 */
		$firstUriSegmentsToSkip = [
			'install',
			'upgrade',
			config('larapen.localization.countries_list_uri'),
			'robots',
			'robots.txt',
			'lang',
			'page',
			'feed',
			'common', // .js
			'plugins', // Plugins URLs
		];
		
		if (
			!appInstallFilesExist()
			|| in_array(request()->segment(1), $firstUriSegmentsToSkip)
			|| (isAdminPanel() && request()->segment(1) == 'captcha')
			|| str_ends_with(request()->url(), '.xml')
			|| str_ends_with(request()->url(), '.css')
		) {
			return;
		}
		
		// REDIRECT... If Country not found, then redirect to country selection page
		if (!$this->isAvailableCountry($this->country->get('code'))) {
			if (!doesCountriesPageCanBeHomepage()) {
				redirectUrl($this->defaultPage, 301, config('larapen.core.noCacheHeaders'));
			} else {
				if (request()->path() != '/' && request()->path() != '') {
					redirectUrl('/', 301, config('larapen.core.noCacheHeaders'));
				}
			}
		}
	}
	
	/**
	 * Get the Most Populated Country (for API)
	 * NOTE: Prevent Country Selection's Page redirection.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getMostPopulatedCountry(): Collection
	{
		try {
			$country = CountryModel::query()->orderByDesc('population')->firstOrFail();
			if (!empty($country)) {
				if ($this->isAvailableCountry($country->code)) {
					return self::getCountryInfo($country->code);
				}
			}
		} catch (\Throwable $e) {
		}
		
		return collect();
	}
	
	/**
	 * Get the Default Country
	 *
	 * @param $defaultCountryCode
	 * @return \Illuminate\Support\Collection
	 */
	public function getDefaultCountry($defaultCountryCode): Collection
	{
		// Check default country
		if (trim($defaultCountryCode) != '') {
			if ($this->isAvailableCountry($defaultCountryCode)) {
				return self::getCountryInfo($defaultCountryCode);
			}
		} else {
			// If only one country is activated, auto-select it as the default country.
			try {
				$countries = CountryModel::all();
			} catch (\Throwable $e) {
				$countries = collect();
			}
			if ($countries->count() == 1) {
				if ($countries->has(0)) {
					return self::getCountryInfo($countries->get(0)->code);
				}
			}
		}
		
		return collect();
	}
	
	/**
	 * Get Country from Session
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromSession(): Collection
	{
		if (!isFromApi()) { // Session is never started from API Middleware
			if (session()->has('countryCode')) {
				if ($this->isAvailableCountry(session('countryCode'))) {
					return self::getCountryInfo(session('countryCode'));
				}
			}
		}
		
		return collect();
	}
	
	/**
	 * Get Country from logged User (for API)
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromLoggedUser(): Collection
	{
		$guard = isFromApi() ? 'sanctum' : null;
		if (auth($guard)->check()) {
			$user = auth($guard)->user();
			if (isset($user->country_code)) {
				if ($this->isAvailableCountry($user->country_code)) {
					return self::getCountryInfo($user->country_code);
				}
			}
		}
		
		return collect();
	}
	
	/**
	 * Get Country from listing details page
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromPost(): Collection
	{
		$country = collect();
		
		// Check if the Post Details controller is called
		$isFromPostDetailsPage = (
			str_contains(currentRouteAction(), 'Post\ShowController')
			|| str_contains(currentRouteAction(), 'MultiSteps\EditController')
			|| str_contains(currentRouteAction(), 'SingleStep\EditController')
		);
		if (!$isFromPostDetailsPage) {
			return $country;
		}
		
		// Get and Check the Controller's Method Parameters
		$parameters = request()->route()->parameters();
		
		// Check if the Listing's ID key exists
		$idKey = array_key_exists('hashableId', $parameters) ? 'hashableId' : 'id';
		$idKeyDoesNotExist = (
			empty($parameters[$idKey])
			|| (!isHashedId($parameters[$idKey]) && !is_numeric($parameters[$idKey]))
		);
		
		// Return an empty collection if the Listing ID does not found
		if ($idKeyDoesNotExist) {
			return collect();
		}
		
		// Set the Parameters
		$postId = $parameters[$idKey];
		
		// Decode Hashed ID
		$postId = hashId($postId, true) ?? $postId;
		
		// Get the Post
		$cacheId = 'post.' . $postId . '.auto.find.country';
		$post = cache()->remember($cacheId, self::$cacheExpiration, function () use ($postId) {
			return Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('id', $postId)
				->first();
		});
		
		if (empty($post)) {
			return collect();
		}
		
		// Get the Post's Country Info (If available)
		if ($this->isAvailableCountry($post->country_code)) {
			$country = self::getCountryInfo($post->country_code);
		}
		
		return $country;
	}
	
	/**
	 * Get Country from Domain
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromDomain(): Collection
	{
		$domains = config('domains');
		if (!is_array($domains)) {
			return collect();
		}
		
		$host = getHost(url()->current());
		
		$domain = collect($domains)->firstWhere('host', $host);
		if (!empty($domain) && !empty($domain['country_code'])) {
			$countryCode = $domain['country_code'];
			if ($this->isAvailableCountry($countryCode)) {
				return self::getCountryInfo($countryCode);
			}
		}
		
		return collect();
	}
	
	/**
	 * Get Country from Sub-Domain
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromSubDomain(): Collection
	{
		$countryCode = getSubDomainName();
		if ($this->isAvailableCountry($countryCode)) {
			return self::getCountryInfo($countryCode);
		}
		
		return collect();
	}
	
	/**
	 * Get Country from Request (GET & POST|PUT)
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromQueryString(): Collection
	{
		$oldQueryValue = request()->query('d', request()->query('site'));
		
		$countryCode = isFromApi()
			? request()->query('countryCode')
			: request()->query('country', $oldQueryValue);
		
		if ($this->isAvailableCountry($countryCode)) {
			return self::getCountryInfo($countryCode);
		}
		
		return collect();
	}
	
	/**
	 * Get Country from URI Path
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromURIPath(): Collection
	{
		$country = collect();
		
		$countryCode = getCountryCodeFromPath();
		if (!empty($countryCode)) {
			if ($this->isAvailableCountry($countryCode)) {
				$country = self::getCountryInfo($countryCode);
			}
		}
		
		return $country;
	}
	
	/**
	 * Get Country from City
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromCity(): Collection
	{
		$countryCode = null;
		$cityId = null;
		
		if (str_contains(currentRouteAction(), 'Search\CityController')) {
			if (!config('settings.seo.multi_country_urls')) {
				$cityId = request()->segment(3);
			} else {
				$cityId = request()->segment(4);
			}
		}
		if (str_contains(currentRouteAction(), 'Search\SearchController')) {
			if (request()->filled('l')) {
				$cityId = request()->query('l');
			}
		}
		
		if (!empty($cityId)) {
			$city = cache()->remember('city.' . $cityId, self::$cacheExpiration, function () use ($cityId) {
				return City::find($cityId);
			});
			if (!empty($city)) {
				$countryCode = $city->country_code;
				if ($this->isAvailableCountry($countryCode)) {
					return self::getCountryInfo($countryCode);
				}
			}
		}
		
		return collect();
	}
	
	/**
	 * Get Country for Bots if not found
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryForBots(): Collection
	{
		$crawler = new CrawlerDetect();
		if ($crawler->isCrawler()) {
			// Don't set the default country for homepage
			if (!str_contains(currentRouteAction(), 'HomeController')) {
				$countryCode = config('settings.localization.default_country_code');
				if ($this->isAvailableCountry($countryCode)) {
					return self::getCountryInfo($countryCode);
				}
			}
		}
		
		return collect();
	}
	
	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getCountryFromIP(): Collection
	{
		// GeoIP
		$countryCode = self::getCountryCodeFromIP(true);
		if (empty($countryCode)) {
			return collect();
		}
		
		return self::getCountryInfo($countryCode);
	}
	
	/**
	 * Localize the user's country
	 *
	 * @param bool $showMaxmindDatabaseInfo
	 * @return string|null
	 */
	public static function getCountryCodeFromIP(bool $showMaxmindDatabaseInfo = false): ?string
	{
		$countryCode = Cookie::get('ipCountryCode');
		if (empty($countryCode)) {
			try {
				
				$data = (new GeoIP())->getData();
				$countryCode = data_get($data, 'countryCode');
				if ($countryCode == 'UK') {
					$countryCode = 'GB';
				}
				
				if (!is_string($countryCode) || strlen($countryCode) != 2) {
					if ($showMaxmindDatabaseInfo) {
						self::maxmindDatabaseInfo();
					}
					
					return null;
				}
				
				// Set data in cookie
				Cookie::set('ipCountryCode', $countryCode);
				
			} catch (\Throwable $e) {
				return null;
			}
		}
		
		return strtolower($countryCode);
	}
	
	/**
	 * @param $countryCode
	 * @return \Illuminate\Support\Collection
	 */
	public static function getCountryInfo($countryCode): Collection
	{
		if (is_null(self::$countries)) {
			self::$countries = self::getCountries();
		}
		
		if (trim($countryCode) == '') {
			return collect();
		}
		$countryCode = strtoupper($countryCode);
		
		// Get the Country details
		$country = null;
		try {
			$country = self::$countries->has($countryCode) ? self::$countries->get($countryCode) : collect();
			if (!$country->isEmpty()) {
				$country = Arr::toObject($country->toArray());
			}
		} catch (\Throwable $e) {
		}
		
		if (is_null($country)) {
			return collect();
		}
		
		// Get the Country's TimeZone
		$timeZone = config('app.timezone');
		$countryTimeZone = (isset($country->time_zone)) ? $country->time_zone : null;
		
		if (!empty($countryTimeZone)) {
			$timeZone = $countryTimeZone;
		} else {
			// If country is an instance of Country Model instead of \Std object
			// @todo: Find a best way to take to account this feature
			if (method_exists($country, 'save')) {
				// Get the Country's most populated City
				$cacheId = 'country.' . $countryCode . '.mostPopulatedCity';
				$city = cache()->remember($cacheId, self::$cacheExpiration, function () use ($countryCode) {
					return City::where('country_code', $countryCode)->orderByDesc('population')->first();
				});
				
				// Get the Country's most populated City's TimeZone
				$timeZone = (!empty($city) && !empty($city->time_zone)) ? $city->time_zone : $timeZone;
				
				// Save the TimeZone to prevent performance issue
				$country->time_zone = $timeZone;
				$country->save();
			}
		}
		
		// Get Country as Array
		$country = ($country instanceof Collection) ? $country->toArray() : Arr::fromObject($country);
		
		// Get the Country's Currency
		$currency = null;
		$currencyCode = $country['currency_code'] ?? null;
		if (!empty($currencyCode)) {
			$cacheId = 'currency.' . $currencyCode;
			$currency = cache()->remember($cacheId, self::$cacheExpiration, function () use ($currencyCode) {
				return Currency::find($currencyCode);
			});
		}
		
		// Get the Country's Language
		$languages = $country['languages'] ?? '';
		$countryCode = $country['code'] ?? null;
		$lang = self::getLangFromCountry($languages, $countryCode);
		
		// Update some existing columns & Add new columns
		$country['time_zone'] = $timeZone;
		$country['currency'] = !empty($currency) ? $currency : [];
		$country['lang'] = $lang;
		
		// Get the Country as Collection
		return collect($country);
	}
	
	/**
	 * Only used for search bots
	 *
	 * @param $languages
	 * @param string|null $countryCode
	 * @return \Illuminate\Support\Collection
	 */
	public static function getLangFromCountry($languages, ?string $countryCode = null): Collection
	{
		if (is_null(self::$languages)) {
			self::$languages = Language::getLanguages();
		}
		
		// Get language code
		$langCode = '';
		if (trim($languages) != '') {
			// Get the country's languages codes
			$countryLanguageCodes = explode(',', $languages);
			
			// Get all languages
			$availableLanguages = self::$languages;
			
			if ($availableLanguages->count() > 0) {
				$found = false;
				foreach ($countryLanguageCodes as $countryLangCode) {
					foreach ($availableLanguages as $lang) {
						if (str_starts_with(strtolower($lang->code), strtolower($countryLangCode))) {
							$langCode = $lang->code;
							$found = true;
							break;
						}
					}
					
					if ($found) {
						break;
					}
				}
			}
		}
		
		// Get language info
		if ($langCode != '') {
			$isAvailableLang = self::$languages->has($langCode) ? self::$languages->get($langCode) : [];
			
			if (!empty($isAvailableLang)) {
				$lang = collect($isAvailableLang);
			} else {
				$lang = self::getLangFromConfig();
			}
		} else {
			$lang = self::getLangFromConfig();
		}
		
		return $lang;
	}
	
	/**
	 * @return \Illuminate\Support\Collection
	 */
	public static function getLangFromConfig(): Collection
	{
		if (is_null(self::$languages)) {
			self::$languages = Language::getLanguages();
		}
		
		$langCode = config('appLang.code');
		
		// Default language (from Admin panel OR Config)
		$lang = self::$languages->has($langCode) ? self::$languages->get($langCode) : [];
		
		return collect($lang);
	}
	
	/**
	 * Get all countries
	 *
	 * @param bool $includeNonActive
	 * @return \Illuminate\Support\Collection
	 */
	public static function getCountries(bool $includeNonActive = false): Collection
	{
		// Get Countries from DB
		try {
			$cacheId = 'countries.with.continent.currency.' . (int)$includeNonActive;
			$countries = cache()->remember($cacheId, self::$cacheExpiration, function () use ($includeNonActive) {
				$countries = CountryModel::query();
				if ($includeNonActive) {
					$countries->withoutGlobalScopes([ActiveScope::class]);
				} else {
					$countries->active();
				}
				$countries = $countries->with(['continent', 'currency'])->orderBy('name')->get();
				
				if ($countries->count() > 0) {
					$countries = $countries->keyBy('code');
				}
				
				return $countries;
			});
		} catch (\Throwable $e) {
			// To prevent HTTP 500 Error when site is not installed.
			return collect(['US' => collect(['code' => 'US', 'name' => 'United States'])]);
		}
		
		// Country filters
		$tab = [];
		if ($countries->count() > 0) {
			foreach ($countries as $code => $country) {
				$countryArray = $country->toArray();
				$countryArray['name'] = $country->name;
				
				// Get only Countries with currency
				if (!empty($country->currency)) {
					$tab[$code] = collect($countryArray);
				} else {
					// Just for debug
					// dd(collect($item));
				}
				
				// Get only allowed Countries with active Continent
				if (!isset($country->continent) || $country->continent->active != 1) {
					unset($tab[$code]);
				}
			}
		}
		$countries = collect($tab);
		
		// Sort
		return Arr::mbSortBy($countries, 'name', app()->getLocale());
	}
	
	/**
	 * @param $countryCode
	 * @return bool
	 */
	public function isAvailableCountry($countryCode): bool
	{
		if (!is_string($countryCode) || strlen($countryCode) != 2) {
			return false;
		}
		
		$countries = self::$countries->keys();
		$countries = $countries->map(function ($item, $key) {
			return strtolower($item);
		})->flip();
		
		return $countries->has(strtolower($countryCode));
	}
	
	/**
	 * Show the Maxmind database information to admin users
	 *
	 * @return void
	 */
	private static function maxmindDatabaseInfo(): void
	{
		if (isFromApi()) {
			return;
		}
		if (!config('settings.localization.geoip_activation')) {
			return;
		}
		if (config('geoip.default') != 'maxmind_database') {
			return;
		}
		if (!auth()->check()) {
			return;
		}
		if (!auth()->user()->can(Permission::getStaffPermissions())) {
			return;
		}
		
		try {
			// Get settings
			$setting = Setting::where('key', 'localization')->first(['id']);
			
			// Notice message for admin users
			if (!empty($setting)) {
				$url = admin_url('settings/' . $setting->id . '/edit');
				$maxmindDbDir = storage_path('database/maxmind/');
				
				$msg = "<h4><strong>Only Admin Users can see this message</strong></h4>";
				$msg .= "The <strong>Maxmind database file</strong> is not found on your server. ";
				$msg .= "You have to download the Maxmind's ";
				$msg .= "<a href='" . self::$maxmindDatabaseUrl . "' target='_blank'>GeoLite2-City.mmdb</a> ";
				$msg .= "database file and extract it in the <code>" . $maxmindDbDir . "</code> ";
				$msg .= "folder on your server like this <code>" . $maxmindDbDir . "GeoLite2-City.mmdb</code>";
				$msg .= "<br><br>";
				$msg .= "<a href='" . $url . "' class='btn btn-xs btn-thin btn-default-lite' id='disableGeoOption'>";
				$msg .= "Disable the Geolocation";
				$msg .= "</a>";
				
				flash($msg)->warning();
			}
		} catch (\Throwable $e) {
		}
	}
}
