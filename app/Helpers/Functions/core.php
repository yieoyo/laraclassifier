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

use App\Helpers\Arr;
use App\Helpers\DBTool;
use App\Helpers\Localization\Country as CountryHelper;
use App\Models\HomeSection;
use App\Models\Package;
use App\Models\Post;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Collection;

/**
 * The App's version of Laravel view() function
 *
 * @param string $view
 * @param array $data
 * @param array $mergeData
 * @return \Illuminate\Contracts\View\View
 */
function appView(string $view, array $data = [], array $mergeData = [])
{
	return view()->first([
		config('larapen.core.customizedViewPath') . $view,
		$view,
	], $data, $mergeData);
}

/**
 * Get View Content
 *
 * @param string $view
 * @param array $data
 * @param array $mergeData
 * @return string|null
 */
function getViewContent(string $view, array $data = [], array $mergeData = []): ?string
{
	if (view()->exists(config('larapen.core.customizedViewPath') . $view)) {
		$view = view(config('larapen.core.customizedViewPath') . $view, $data, $mergeData);
	} else {
		$view = view($view, $data, $mergeData);
	}
	
	return $view->render();
}

/**
 * @return string
 */
function getCountryCodeRoutePattern(): string
{
	// Country Code Pattern
	$countryCodePattern = implode('|', array_map('strtolower', array_keys(getCountries())));
	$countryCodePattern = !empty($countryCodePattern) ? $countryCodePattern : 'us';
	
	/*
	 * NOTE:
	 * '(?i:foo)' : Make 'foo' case-insensitive
	 */
	
	return '(?i:' . $countryCodePattern . ')';
}

/**
 * @return bool
 */
function doesCountriesPageCanBeHomepage(): bool
{
	return (
		file_exists(storage_path('framework/plugins/domainmapping'))
		&& (config('larapen.core.dmCountriesListAsHomepage') == true)
		&& (getHost() == getHost(config('app.url')))
	);
}

/**
 * @return bool
 */
function doesCountriesPageCanBeLinkedToTheHomepage(): bool
{
	return (
		file_exists(storage_path('framework/plugins/domainmapping'))
		&& (config('larapen.core.dmCountriesListAsHomepage') == true)
		&& (getHost() != getHost(config('app.url')))
	);
}

/**
 * Generate a URL with query string for the application.
 *
 * Assumes that you want a URL with a querystring rather than route params
 * (which is what the default url() helper does)
 *
 * @param string|null $path
 * @param array|null $queryArray
 * @param $secure
 * @param bool $localized
 * @return string
 */
function qsUrl(string $path = null, ?array $queryArray = [], $secure = null, bool $localized = true): string
{
	$url = getUrlWithoutQuery($path, $secure);
	
	// $queryArray = array_merge(getUrlQuery($path), $queryArray);
	
	if (config('plugins.domainmapping.installed')) {
		if (isset($queryArray['country'])) {
			unset($queryArray['country']);
		}
		$queryArray = array_filter($queryArray, function ($v, $k) {
			if ($k == 'distance') {
				return !empty($v) || $v == 0;
			} else {
				return !empty($v);
			}
		}, ARRAY_FILTER_USE_BOTH);
	}
	
	if (!empty($queryArray)) {
		$url = $url . '?' . Arr::query($queryArray);
	}
	
	return $url;
}

/**
 * Get URL (based on Country Domain) related to the given country (or country code)
 * This is the url() function to match country domains
 *
 * @param \Illuminate\Support\Collection|string|null $country
 * @param string|null $path
 * @param bool $forceCountry
 * @param bool $forceLocale
 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\UrlGenerator|string
 */
function dmUrl(Collection|string|null $country, ?string $path = '/', bool $forceCountry = false, bool $forceLocale = false)
{
	if (empty($path)) {
		$path = '/';
	}
	
	$country = getValidCountry($country);
	if (empty($country)) {
		return url($path);
	}
	
	// Clear the path
	$path = ltrim($path, '/');
	
	// Get the country main language code
	$langCode = getCountryMainLangCode($country);
	
	// Get the country main language path
	$langPath = '';
	if ($forceLocale) {
		if (!empty($langCode)) {
			$parseUrl = mb_parse_url(url($path));
			if (!isset($parseUrl['path']) || ($parseUrl['path'] == '/')) {
				$langPath = '/locale/' . $langCode;
			}
			if (isFromUrlAlwaysContainingCountryCode($path)) {
				$langPath = '/' . $langCode;
			}
		}
	}
	
	// Get the country domain data from the Domain Mapping plugin,
	// And get a new URL related to domain, country language & given path
	$domain = collect((array)config('domains'))->firstWhere('country_code', $country->get('code'));
	if (!empty($domain['url'])) {
		$path = preg_replace('#' . $country->get('code') . '/#ui', '', $path, 1);
		
		$url = rtrim($domain['url'], '/') . $langPath;
		$url = $url . ((!empty($path)) ? '/' . $path : '');
	} else {
		$url = rtrim(config('app.url', ''), '/') . $langPath;
		$url = $url . ((!empty($path)) ? '/' . $path : '');
		if ($forceCountry) {
			$url = $url . ('?country=' . $country->get('code'));
		}
	}
	
	return $url;
}

/**
 * Get Valid Country's Object (as Laravel Collection)
 *
 * @param \Illuminate\Support\Collection|string|null $country
 * @return \Illuminate\Support\Collection|null
 */
function getValidCountry(Collection|string|null $country): ?Collection
{
	// If given country value is a string & having 2 characters (like country code),
	// Get the country collection by the country code.
	if (is_string($country)) {
		if (strlen($country) == 2) {
			$country = CountryHelper::getCountryInfo($country);
			if ($country->isEmpty() || !$country->has('code')) {
				return null;
			}
		} else {
			return null;
		}
	}
	
	// Country collection is required to continue
	if (!($country instanceof \Illuminate\Support\Collection)) {
		return null;
	}
	
	// Country collection code is required to continue
	if (!$country->has('code')) {
		return null;
	}
	
	return $country;
}

/**
 * Get Country Main Language Code
 *
 * @param \Illuminate\Support\Collection|string|null $country
 * @return string|null
 */
function getCountryMainLangCode(Collection|string|null $country): ?string
{
	$country = getValidCountry($country);
	if (empty($country)) {
		return null;
	}
	
	// Get the country main language code
	$langCode = null;
	if ($country->has('lang')) {
		$countryLang = $country->get('lang');
		if ($countryLang instanceof Collection && $countryLang->has('code')) {
			$langCode = $countryLang->get('code');
		}
	} else {
		if ($country->has('languages')) {
			$countryLang = CountryHelper::getLangFromCountry($country->get('languages'));
			if ($countryLang->has('code')) {
				$langCode = $countryLang->get('code');
			}
		} else {
			// From XML Sitemaps
			if ($country->has('locale')) {
				$langCode = $country->get('locale');
			}
		}
	}
	
	return $langCode;
}

/**
 * If the Domain Mapping plugin is installed, apply its configs.
 * NOTE: Don't apply them if the session is shared.
 *
 * @param $countryCode
 * @return void
 */
function applyDomainMappingConfig($countryCode): void
{
	if (empty($countryCode)) {
		return;
	}
	
	if (config('plugins.domainmapping.installed')) {
		/*
		 * When the session is shared, the domain name and logo columns are disabled.
		 * The dashboard per country feature is also disabled.
		 * So, it is recommended to access to the Admin panel through the main URL from the /.env file (i.e.: APP_URL/admin)
		 */
		if (!config('settings.domainmapping.share_session')) {
			$domain = collect((array)config('domains'))->firstWhere('country_code', $countryCode);
			if (!empty($domain)) {
				if (!empty($domain['url'])) {
					//\URL::forceRootUrl($domain['url']);
				}
			}
		}
	}
}

function isFromAdminPanel($url = null): bool
{
	return isAdminPanel($url);
}

/**
 * Check if user is located in the Admin panel
 * NOTE: Please see the provider of the package: lab404/laravel-impersonate
 *
 * @param string|null $url
 * @return bool
 */
function isAdminPanel(string $url = null): bool
{
	if (empty($url)) {
		$isValid = (
			request()->segment(1) == admin_uri()
			|| request()->segment(1) == 'impersonate'
			|| str_contains(currentRouteAction(), '\Admin\\')
		);
	} else {
		try {
			$urlPath = '/' . ltrim(parse_url($url, PHP_URL_PATH), '/');
			$adminUri = '/' . ltrim(admin_uri(), '/');
			
			$isValid = (
				str_starts_with($urlPath, $adminUri)
				|| str_starts_with($urlPath, '/impersonate')
			);
		} catch (\Throwable $e) {
			$isValid = false;
		}
	}
	
	return $isValid;
}

/**
 * Check dev environment
 *
 * @param string|null $url
 * @return bool
 */
function isDevEnv(string $url = null): bool
{
	if (empty($url)) {
		$url = config('app.url');
	}
	
	$domain = getDomain($url);
	
	return (
		str_contains($domain, 'bedigit.local')
		|| str_contains($domain, 'laraclassifier.local')
	);
}

/**
 * Check demo environment
 *
 * @param string|null $url
 * @return bool
 */
function isDemoEnv(string $url = null): bool
{
	if (empty($url)) {
		$url = config('app.url');
	}
	
	return (
		getDomain($url) == config('larapen.core.demo.domain')
		|| in_array(getHost($url), (array)config('larapen.core.demo.hosts'))
	);
}

/**
 * Check the demo website domain
 *
 * @param string|null $url
 * @return bool
 */
function isDemoDomain(string $url = null): bool
{
	$isDemoDomain = isDemoEnv($url);
	
	if (!$isDemoDomain) {
		return false;
	}
	
	if (auth()->check()) {
		if (
			auth()->user()->can(Permission::getStaffPermissions())
			&& md5(auth()->user()->id) == 'c4ca4238a0b923820dcc509a6f75849b'
		) {
			$isDemoDomain = false;
		}
	}
	
	return $isDemoDomain;
}

/**
 * Get the Country Code from URI Path
 *
 * @return string|null
 */
function getCountryCodeFromPath(): ?string
{
	$countryCode = null;
	
	// With these URLs, the language code and the country code can be available in the segments
	// (If the "Multi-countries URLs Optimization" is enabled)
	if (isFromUrlThatCanContainCountryCode()) {
		$countryCode = request()->segment(1);
	}
	
	// With these URLs, the language code and the country code are available in the segments
	if (isFromUrlAlwaysContainingCountryCode()) {
		$countryCode = request()->segment(2);
	}
	
	return $countryCode;
}

/**
 * Check if user is coming from a URL that can contain the country code
 * With these URLs, the language code and the country code can be available in the segments
 * (If the "Multi-countries URLs Optimization" is enabled)
 *
 * @return bool
 */
function isFromUrlThatCanContainCountryCode(): bool
{
	if (config('settings.seo.multi_country_urls')) {
		if (
			str_contains(currentRouteAction(), 'SearchController')
			|| str_contains(currentRouteAction(), 'CategoryController')
			|| str_contains(currentRouteAction(), 'CityController')
			|| str_contains(currentRouteAction(), 'UserController')
			|| str_contains(currentRouteAction(), 'TagController')
			|| str_contains(currentRouteAction(), 'CompanyController')
			|| str_contains(currentRouteAction(), 'SitemapController')
		) {
			return true;
		}
	}
	
	return false;
}

/**
 * Check if called page can always have the country code
 * With these URLs, the language code and the country code are available in the segments
 *
 * @param string|null $url
 * @return bool
 */
function isFromUrlAlwaysContainingCountryCode(string $url = null): bool
{
	if (empty($url)) {
		$isValid = (
			str_ends_with(request()->url(), '.xml')
			|| str_contains(currentRouteAction(), 'SitemapsController')
		);
	} else {
		$isValid = (str_ends_with($url, '.xml'));
	}
	
	return $isValid;
}

/**
 * Transform Description column before displaying it
 *
 * @param $string
 * @return string
 */
function transformDescription($string)
{
	if (config('settings.single.wysiwyg_editor') != 'none') {
		
		try {
			$string = \Mews\Purifier\Facades\Purifier::clean($string);
		} catch (\Throwable $e) {
			// Nothing.
		}
		$string = urls_to_links($string);
		
	} else {
		$string = nl2br(urls_to_links(mb_str_cleaner($string)));
	}
	
	return $string;
}

/**
 * Tags Cleaner
 * Prevent problem with the #hashtags when they are only numeric
 *
 * @param $tagString
 * @param bool $forceArrayReturn
 * @return array|string|null
 */
function tagCleaner($tagString, bool $forceArrayReturn = false)
{
	$limit = (int)config('settings.single.tags_limit', 15);
	
	return taggable($tagString, $limit, $forceArrayReturn);
}

/**
 * Return an array of spoken languages in the selected country
 *
 * @return array
 */
function getCountrySpokenLanguages(): array
{
	$supportedLanguages = getSupportedLanguages();
	
	$spokenLanguages = config('country.languages');
	$spokenLanguages = explode(',', $spokenLanguages);
	if (config('settings.localization.show_country_spoken_languages') == 'active_with_en') {
		$spokenLanguages[] = 'en';
	}
	if (config('settings.localization.show_country_spoken_languages') == 'active_with_main') {
		$spokenLanguages[] = strtolower(config('appLang.code'));
	}
	
	if (empty($spokenLanguages)) return [];
	
	return collect($spokenLanguages)
		->unique()
		->map(function ($item) use ($supportedLanguages) {
			if (empty($supportedLanguages)) return $item;
			
			foreach ($supportedLanguages as $code => $lang) {
				if (str_starts_with($code, $item)) {
					$item = $lang;
					break; // Important
				}
			}
			
			return $item;
		})
		->filter(fn ($item) => is_array($item))
		->keyBy('code')
		->toArray();
}

/**
 * Return an array of all supported languages
 *
 * @return array
 */
function getSupportedLanguages(): array
{
	$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400);
	
	// Get supported languages from database
	try {
		// Get all DB Languages
		$cacheId = 'languages.active.array';
		$supportedLanguages = cache()->remember($cacheId, $cacheExpiration, function () {
			return \App\Models\Language::where('active', 1)->orderBy('lft')->get();
		});
		
		$supportedLanguages = collect($supportedLanguages->toArray());
		
		if ($supportedLanguages->isNotEmpty()) {
			$supportedLanguages = $supportedLanguages->keyBy('code');
		}
	} catch (\Throwable $e) {
		/*
		 * Database or tables don't exist.
		 * The script will display an error or will start the installation.
		 * Please don't change anything here.
		 */
		$supportedLanguages = collect();
	}
	
	return $supportedLanguages->toArray();
}

/**
 * Check if language code is available
 *
 * @param string|null $code
 * @return bool
 */
function isAvailableLang(?string $code): bool
{
	$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400);
	
	$cacheId = 'language.' . $code;
	$lang = cache()->remember($cacheId, $cacheExpiration, function () use ($code) {
		return \App\Models\Language::where('code', $code)->first();
	});
	
	return !empty($lang);
}

/**
 * @return string
 */
function detectLocale(): string
{
	$lang = detectLanguage();
	
	$defaultLocale = 'en_US';
	$locale = !$lang->isEmpty() ? $lang->get('locale') : $defaultLocale;
	
	return getAsString($locale, $defaultLocale);
}

/**
 * @return \Illuminate\Support\Collection
 */
function detectLanguage(): \Illuminate\Support\Collection
{
	$obj = new App\Helpers\Localization\Language();
	
	return $obj->find();
}

/**
 * Get all countries from PHP array (umpirsky)
 *
 * @return array|null
 */
function getCountriesFromArray(): ?array
{
	$countries = new App\Helpers\Localization\Helpers\Country();
	$countries = $countries->all();
	
	if (empty($countries)) return null;
	
	$arr = [];
	foreach ($countries as $code => $value) {
		if (!file_exists(storage_path('database/geonames/countries/' . strtolower($code) . '.sql'))) {
			continue;
		}
		$row = ['value' => $code, 'text' => $value];
		$arr[] = $row;
	}
	
	return $arr;
}

/**
 * Get all countries from DB (Geonames) & Translate them
 *
 * @param bool $includeNonActive
 * @return array
 */
function getCountries(bool $includeNonActive = false): array
{
	$arr = [];
	
	// Get installed countries list
	$countries = CountryHelper::getCountries($includeNonActive);
	
	if ($countries->count() > 0) {
		foreach ($countries as $code => $country) {
			// The country entry must be a Laravel Collection object
			if (!$country instanceof \Illuminate\Support\Collection) {
				$country = collect($country);
			}
			
			// Get the country data
			$code = ($country->has('code')) ? $country->get('code') : $code;
			$name = ($country->has('name')) ? $country->get('name') : '';
			$arr[$code] = $name;
		}
	}
	
	return $arr;
}

/**
 * Pluralization
 *
 * @param $number
 * @return int
 */
function getPlural($number)
{
	return number_plural($number, config('lang.russian_pluralization'));
}

/**
 * Get URL of Page by page's type
 *
 * @param string|null $type
 * @param string|null $locale
 * @return string
 */
function getUrlPageByType(?string $type, string $locale = null): string
{
	if (is_null($locale)) {
		$locale = config('app.locale');
	}
	
	$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400);
	$cacheId = 'page.' . $locale . '.type.' . $type;
	$page = cache()->remember($cacheId, $cacheExpiration, function () use ($type, $locale) {
		$page = \App\Models\Page::type($type)->first();
		
		if (!empty($page)) {
			$page->setLocale($locale);
		}
		
		return $page;
	});
	
	$linkTarget = '';
	$linkRel = '';
	if (!empty($page)) {
		if ($page->target_blank == 1) {
			$linkTarget = ' target="_blank"';
		}
		if (!empty($page->external_link)) {
			$linkRel = ' rel="nofollow"';
			$url = $page->external_link;
		} else {
			$url = \App\Helpers\UrlGen::page($page);
		}
	} else {
		$url = '#';
	}
	
	// Get attributes
	return 'href="' . $url . '"' . $linkRel . $linkTarget;
}

/**
 * @param string|null $uploadType
 * @param bool $jsFormat
 * @return array|false|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed|string|string[]
 */
function getUploadFileTypes(?string $uploadType = 'file', bool $jsFormat = false)
{
	if ($uploadType == 'image') {
		$types = config('settings.upload.image_types', 'jpg,jpeg,gif,png');
	} else {
		$types = config('settings.upload.file_types', 'pdf,doc,docx,word,rtf,rtx,ppt,pptx,odt,odp,wps,jpeg,jpg,bmp,png');
	}
	
	$separators = ['|', '-', ';', '.', '/', '_', ' '];
	$types = str_replace($separators, ',', $types);
	
	if ($jsFormat) {
		$types = explode(',', $types);
		$types = array_filter($types, function ($value) {
			return $value !== '';
		});
		$types = json_encode($types);
	}
	
	return $types;
}

/**
 * @param string|null $uploadType
 * @return array|mixed|string
 */
function showValidFileTypes(?string $uploadType = 'file')
{
	$formats = getUploadFileTypes($uploadType);
	
	return str_replace(',', ', ', $formats);
}

/**
 * Get Public File's URL
 *
 * @param string|null $filePath
 * @return \Illuminate\Contracts\Routing\UrlGenerator|string
 */
function fileUrl(?string $filePath)
{
	// Storage Disk Init.
	$disk = \App\Helpers\Files\Storage\StorageDisk::getDisk();
	
	try {
		return $disk->url($filePath);
	} catch (\Throwable $e) {
		return url('common/file?path=' . $filePath);
	}
}

/**
 * Get Private File's URL
 *
 * @param string|null $filePath
 * @param string|null $diskName
 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\UrlGenerator|string
 */
function privateFileUrl(?string $filePath, ?string $diskName = 'private')
{
	$queryString = 'path=' . $filePath;
	
	// For JC
	if (str_starts_with($filePath, 'resumes/')) {
		$diskName = 'private';
	}
	
	if (!empty($diskName)) {
		$queryString = 'disk=' . $diskName . '&' . $queryString;
	}
	
	return url('common/file?' . $queryString);
}

/**
 * @param string|null $filePath
 * @param string|null $preConfigSize
 * @param array|null $attr
 * @return string
 */
function imgTag(?string $filePath, ?string $preConfigSize = 'big', ?array $attr = []): string
{
	$src = imgUrl($filePath, $preConfigSize);
	$attr = buildAttributes($attr);
	
	$out = '';
	if (config('settings.optimization.webp_format')) {
		$srcWebp = imgUrl($filePath, $preConfigSize, true);
		
		if (!str_ends_with($srcWebp, '.webp')) {
			$out .= '<img src="' . $src . '"' . $attr . '>';
		} else {
			$out .= '<picture>';
			$out .= '<source srcset="' . $srcWebp . '" type="image/webp">';
			$out .= '<img src="' . $src . '"' . $attr . '>';
			$out .= '</picture>';
		}
	} else {
		$out .= '<img src="' . $src . '"' . $attr . '>';
	}
	
	return $out;
}

/**
 * @param string|null $filePath
 * @param string|null $preConfigSize
 * @param bool $webpFormat
 * @return string
 */
function imgUrl(?string $filePath, ?string $preConfigSize = 'big', bool $webpFormat = false): string
{
	// Storage Disk Init.
	$disk = \App\Helpers\Files\Storage\StorageDisk::getDisk();
	
	// Check if this is the default picture
	if (
		str_contains($filePath, config('larapen.core.logo'))
		|| str_contains($filePath, config('larapen.core.favicon'))
		|| str_contains($filePath, config('larapen.core.picture.default'))
		|| str_contains($filePath, config('larapen.core.avatar.default'))
		|| str_contains($filePath, config('larapen.admin.logo.dark'))
		|| str_contains($filePath, config('larapen.admin.logo.light'))
	) {
		return $disk->url($filePath) . getPictureVersion();
	}
	
	// Get pre-resized picture URL
	$picTypesAdmin = ['logo', 'cat', 'small', 'medium', 'big'];
	$picTypesOther = array_keys((array)config('larapen.core.picture.otherTypes'));
	$picTypesGlobal = array_merge($picTypesAdmin, $picTypesOther);
	if (!in_array($preConfigSize, $picTypesGlobal)) {
		try {
			return $disk->url($filePath) . getPictureVersion();
		} catch (\Throwable $e) {
			return url('common/file?path=' . $filePath) . getPictureVersion(true);
		}
	}
	
	// Check, Create thumbnail and Get its URL
	if ($webpFormat) {
		return resizeWebp($disk, $filePath, $preConfigSize);
	} else {
		return resize($disk, $filePath, $preConfigSize);
	}
}

/**
 * @param $disk
 * @param string|null $filePath
 * @param string|null $preConfigSize
 * @param bool $webpFormat
 * @return string
 */
function resize($disk, ?string $filePath, ?string $preConfigSize = 'big', bool $webpFormat = false): string
{
	// Image Quality
	$imageQuality = config('settings.upload.image_quality', 90);
	
	// Get Dimensions
	$defaultWidth = config('larapen.core.picture.otherTypes.' . $preConfigSize . '.width', 816);
	$defaultHeight = config('larapen.core.picture.otherTypes.' . $preConfigSize . '.height', 460);
	$width = (int)config('settings.upload.img_resize_' . $preConfigSize . '_width', $defaultWidth);
	$height = (int)config('settings.upload.img_resize_' . $preConfigSize . '_height', $defaultHeight);
	
	$filename = (!str_ends_with($filePath, DIRECTORY_SEPARATOR)) ? basename($filePath) : '';
	$fileDir = str_replace($filename, '', $filePath);
	$fileDir = rtrim($fileDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	
	// WebP
	$filenameWithoutExtension = substr($filename, 0, strrpos($filename, '.'));
	$webpFilename = $filenameWithoutExtension . '.webp';
	if ($webpFormat) {
		$filename = $webpFilename;
	}
	
	// Thumb file name
	$sizeLabel = $width . 'x' . $height;
	$thumbFilename = 'thumb-' . $sizeLabel . '-' . $filename;
	$thumbFilePath = $fileDir . $thumbFilename;
	
	// Check if thumb image exists
	if (!$disk->exists($thumbFilePath)) {
		// Create thumb image if it not exists
		try {
			// Get file extension
			if ($webpFormat) {
				$extension = 'webp';
			} else {
				$extension = (is_png($disk->get($filePath))) ? 'png' : 'jpg';
			}
			
			// Init. Intervention
			$image = \Intervention\Image\Facades\Image::make($disk->get($filePath));
			
			// Get the image original dimensions
			$imgWidth = $image->width();
			$imgHeight = $image->height();
			
			// Manage Image By Type
			
			// Get Other Types Parameters
			if (in_array($preConfigSize, array_keys((array)config('larapen.core.picture.otherTypes')))) {
				// Get image manipulation settings
				$width = (int)config('larapen.core.picture.otherTypes.' . $preConfigSize . '.width', 900);
				$height = (int)config('larapen.core.picture.otherTypes.' . $preConfigSize . '.height', 900);
				$ratio = config('larapen.core.picture.otherTypes.' . $preConfigSize . '.ratio', '1');
				$upSize = config('larapen.core.picture.otherTypes.' . $preConfigSize . '.upsize', '0');
				
				// If the original dimensions are higher than the resize dimensions
				// OR the 'upsize' option is enable, then resize the image
				if ($imgWidth > $width || $imgHeight > $height) {
					// Resize
					$image = $image->resize($width, $height, function ($constraint) use ($ratio, $upSize) {
						if ($ratio == '1') {
							$constraint->aspectRatio();
						}
						if ($upSize == '1') {
							$constraint->upsize();
						}
					});
				}
			} else if (in_array($preConfigSize, ['logo', 'cat'])) {
				// Get image manipulation settings
				$ratio = config('settings.upload.img_resize_' . $preConfigSize . '_ratio', '1');
				$upSize = config('settings.upload.img_resize_' . $preConfigSize . '_upsize', '0');
				
				// If the original dimensions are higher than the resize dimensions
				// OR the 'upsize' option is enable, then resize the image
				if ($imgWidth > $width || $imgHeight > $height || $upSize == '1') {
					// Resize
					$image = $image->resize($width, $height, function ($constraint) use ($ratio, $upSize) {
						if ($ratio == '1') {
							$constraint->aspectRatio();
						}
						if ($upSize == '1') {
							$constraint->upsize();
						}
					});
				}
			} else if (in_array($preConfigSize, ['large', 'big', 'medium', 'small'])) {
				// Get image manipulation settings
				$resizeType = config('settings.upload.img_resize_' . $preConfigSize . '_resize_type', '0');
				$ratio = config('settings.upload.img_resize_' . $preConfigSize . '_ratio', '1');
				$upSize = config('settings.upload.img_resize_' . $preConfigSize . '_upsize', '0');
				$position = config('settings.upload.img_resize_' . $preConfigSize . '_position', 'center');
				$relative = config('settings.upload.img_resize_' . $preConfigSize . '_relative', false);
				$bgColor = config('settings.upload.img_resize_' . $preConfigSize . '_bg_color', 'ffffff');
				
				if ($resizeType == '0') {
					if ($imgWidth > $width || $imgHeight > $height || $upSize == '1') {
						// Resize
						$image = $image->resize($width, $height, function ($constraint) use ($ratio, $upSize) {
							if ($ratio == '1') {
								$constraint->aspectRatio();
							}
							if ($upSize == '1') {
								$constraint->upsize();
							}
						});
					}
				} else if ($resizeType == '1') {
					// Fit
					$image = $image->fit($width, $height, function ($constraint) use ($ratio, $upSize) {
						if ($ratio == '1') {
							$constraint->aspectRatio();
						}
						if ($upSize == '1') {
							$constraint->upsize();
						}
					});
				} else if ($resizeType == '2') {
					if ($imgWidth > $width || $imgHeight > $height || $upSize == '1') {
						// Resize (for ResizeCanvas)
						$image = $image->resize($width, $height, function ($constraint) use ($ratio, $upSize) {
							if ($ratio == '1') {
								$constraint->aspectRatio();
							}
							if ($upSize == '1') {
								$constraint->upsize();
							}
						});
					}
					// ResizeCanvas
					$image = $image->resizeCanvas($width, $height, $position, $relative, $bgColor)->resize($width, $height);
				} else {
					if ($imgWidth > $width || $imgHeight > $height) {
						// Resize (with hard parameters)
						$image = $image->resize($width, $height, function ($constraint) {
							$constraint->aspectRatio();
						});
					}
				}
			} else {
				if ($imgWidth > $width || $imgHeight > $height) {
					// Resize (with hard parameters)
					$image = $image->resize($width, $height, function ($constraint) {
						$constraint->aspectRatio();
					});
				}
			}
			
			// Encode the Image!
			$image = $image->encode($extension, $imageQuality);
			
		} catch (\Throwable $e) {
			$storageDisk = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'));
			
			return $storageDisk->url($filePath) . getPictureVersion();
		}
		
		// Store the image on disk.
		$disk->put($thumbFilePath, $image->stream()->__toString());
		
		// Now delete temporary intervention image as we have moved it to Storage folder with Laravel filesystem.
		$image->destroy();
	}
	
	// Get the image URL
	try {
		return $disk->url($thumbFilePath) . getPictureVersion();
	} catch (\Throwable $e) {
		return url('common/file?path=' . $thumbFilePath) . getPictureVersion();
	}
}

/**
 * @param $disk
 * @param string|null $filePath
 * @param string|null $type
 * @return string
 */
function resizeWebp($disk, ?string $filePath, ?string $type = 'big'): string
{
	return resize($disk, $filePath, $type, true);
}

/**
 * Get pictures version
 *
 * @param bool $queryStringExists
 * @return string
 */
function getPictureVersion(bool $queryStringExists = false): string
{
	$pictureVersion = '';
	if (config('larapen.core.picture.versioned') && !empty(config('larapen.core.picture.version'))) {
		$pictureVersion .= ($queryStringExists) ? '&' : '?';
		$pictureVersion .= 'v=' . config('larapen.core.picture.version');
	}
	
	return $pictureVersion;
}

/**
 * List of auth fields | List of notification channels
 *
 * @param bool $asChannel
 * @return array
 */
function getAuthFields(bool $asChannel = false): array
{
	$authFields = [
		'email' => $asChannel ? trans('settings.mail') : trans('global.email_address'),
	];
	
	$phoneIsEnabledAsAuthField = (config('settings.sms.enable_phone_as_auth_field') == '1');
	if ($phoneIsEnabledAsAuthField) {
		$authFields['phone'] = $asChannel ? trans('settings.sms') : trans('global.phone_number');
	}
	
	return $authFields;
}

/**
 * Get the auth field
 *
 * @param $entity
 * @return string
 */
function getAuthField($entity = null): string
{
	$authFields = array_keys(getAuthFields());
	$defaultAuthField = config('settings.sms.default_auth_field', 'email');
	
	// From default value
	$authField = $defaultAuthField;
	
	// From authenticated user's data
	$guard = isFromApi() ? 'sanctum' : null;
	if (auth($guard)->check()) {
		$savedValue = auth($guard)->user()->auth_field ?? $authField;
		$authField = (!empty($savedValue)) ? $savedValue : $authField;
	}
	
	// From a database table
	// '$entity' can be any table object that has 'auth_field' column
	if (!empty($entity)) {
		$savedValue = (is_array($entity))
			? ($entity['auth_field'] ?? $defaultAuthField)
			: ($entity->auth_field ?? $defaultAuthField);
		$authField = (!empty($savedValue)) ? $savedValue : $defaultAuthField;
	}
	
	// From form
	if (request()->filled('auth_field')) {
		$authField = request()->input('auth_field');
	}
	
	$authField = (in_array($authField, $authFields)) ? $authField : $defaultAuthField;
	
	$phoneIsEnabledAsAuthField = (config('settings.sms.enable_phone_as_auth_field') == '1');
	
	return ($phoneIsEnabledAsAuthField) ? $authField : 'email';
}

/**
 * Get the auth field name from its value
 *
 * @param string|null $value
 * @return string
 */
function getAuthFieldFromItsValue(?string $value = null): string
{
	$field = 'username';
	if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
		$field = 'email';
	} else if (preg_match('/^((\+|00)\d{1,3})?[\s\d]+$/', $value)) {
		$field = 'phone';
	}
	
	return $field;
}

/**
 * Get the auth field from the Token page
 *
 * @return string|null
 */
function getAuthFieldOnTokenPage(): ?string
{
	$authFields = array_keys(getAuthFields());
	
	// Get the right auth field
	$authField = null;
	if (request()->segment(2) == 'verify') {
		if (
			!empty(request()->segment(3))
			&& in_array(request()->segment(3), $authFields)
		) {
			$authField = request()->segment(3);
		}
	}
	
	return $authField;
}

/**
 * @param $defaultCountryCode
 * @return mixed
 */
function getPhoneCountry($defaultCountryCode = null)
{
	$countryCode = isFromApi() ? config('country.code') : session('countryCode');
	$countryCode = $defaultCountryCode ?? $countryCode;
	$countryCode = request()->input('country_code', $countryCode);
	
	return request()->input('phone_country', $countryCode);
}

/**
 * @param bool $allowUserToChoose
 * @return bool
 */
function isUsersCanChooseNotifyChannel(bool $allowUserToChoose = false): bool
{
	$usersCanChooseNotifyChannel = (config('settings.sms.enable_phone_as_auth_field') == '1');
	if ($allowUserToChoose) {
		return $usersCanChooseNotifyChannel;
	}
	
	if (auth()->check()) {
		$usersCanChooseNotifyChannel = (
			$usersCanChooseNotifyChannel
			&& config('settings.sms.messenger_notifications') == '1'
		);
	}
	
	return $usersCanChooseNotifyChannel;
}

/**
 * @return bool
 */
function isBothAuthFieldsCanBeDisplayed(): bool
{
	$emailNeedToBeVerified = (config('settings.mail.email_verification') == '1');
	$phoneNeedToBeVerified = (config('settings.sms.phone_verification') == '1');
	
	$isBothAuthFieldNeedToBeVerified = ($emailNeedToBeVerified && $phoneNeedToBeVerified);
	$isBothAuthFieldsCanBeDisplayed = (bool)config('larapen.core.displayBothAuthFields');
	
	if ($isBothAuthFieldNeedToBeVerified) {
		return false;
	}
	
	return $isBothAuthFieldsCanBeDisplayed;
}

/**
 * @return array|\Illuminate\Contracts\Translation\Translator|string|null
 */
function getTokenLabel()
{
	$authField = getAuthFieldOnTokenPage();
	
	if ($authField == 'email') {
		return t('Code received by Email');
	}
	if ($authField == 'phone') {
		return t('Code received by SMS');
	}
	
	return t('Code received by SMS or Email');
}

/**
 * @return array|\Illuminate\Contracts\Translation\Translator|string|null
 */
function getTokenMessage()
{
	$authField = getAuthFieldOnTokenPage();
	
	if ($authField == 'email') {
		return t('Enter the code you received by Email in the field below');
	}
	if ($authField == 'phone') {
		return t('Enter the code you received by SMS in the field below');
	}
	
	return t('Enter the code you received by SMS or Email in the field below');
}

/**
 * Replace global variables patterns from string
 *
 * @param string|null $string
 * @param bool $removeUnmatchedPatterns
 * @return string|string[]
 */
function replaceGlobalPatterns(?string $string, bool $removeUnmatchedPatterns = true)
{
	$string = str_replace('{app.name}', config('app.name'), $string);
	$string = str_replace('{country.name}', config('country.name'), $string);
	$string = str_replace('{country}', config('country.name'), $string);
	
	if (config('settings.app.slogan')) {
		$string = str_replace('{app.slogan}', config('settings.app.slogan'), $string);
	}
	
	if (str_contains($string, '{count.listings}')) {
		try {
			$countPosts = Post::query()->inCountry()->has('country')->unarchived()->count();
		} catch (\Throwable $e) {
			$countPosts = 0;
		}
		$string = str_replace('{count.listings}', $countPosts, $string);
	}
	if (str_contains($string, '{count.users}')) {
		try {
			$countUsers = User::query()->count();
		} catch (\Throwable $e) {
			$countUsers = 0;
		}
		$string = str_replace('{count.users}', $countUsers, $string);
	}
	
	if ($removeUnmatchedPatterns) {
		$string = removeUnmatchedPatterns($string);
	}
	
	return $string;
}

/**
 * Get meta tag from settings
 *
 * @param string|null $page
 * @return array
 */
function getMetaTag(?string $page): array
{
	$metaTag = ['title' => '', 'description' => '', 'keywords' => ''];
	
	// Check if the Domain Mapping plugin is available
	if (config('plugins.domainmapping.installed')) {
		$metaTag = \extras\plugins\domainmapping\Domainmapping::getMetaTag($page);
		if (!empty($metaTag) && !arrayItemsAreEmpty($metaTag)) {
			return $metaTag;
		}
	}
	
	// Get the current Language
	$languageCode = config('lang.code', config('app.locale'));
	
	// Get the Page's MetaTag
	$model = null;
	try {
		$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400);
		$cacheId = 'metaTag.' . $languageCode . '.' . $page;
		$model = cache()->remember($cacheId, $cacheExpiration, function () use ($languageCode, $page) {
			$model = \App\Models\MetaTag::where('page', $page)->first(['title', 'description', 'keywords']);
			
			if (!empty($model)) {
				$model->setLocale($languageCode);
				$model = $model->toArray();
			}
			
			return $model;
		});
	} catch (\Throwable $e) {
	}
	
	if (!empty($model)) {
		$metaTag = $model;
		
		$metaTag['title'] = getColumnTranslation($metaTag['title'], $languageCode);
		$metaTag['description'] = getColumnTranslation($metaTag['description'], $languageCode);
		$metaTag['keywords'] = getColumnTranslation($metaTag['keywords'], $languageCode);
		
		$metaTag['title'] = replaceGlobalPatterns($metaTag['title'], false);
		$metaTag['description'] = replaceGlobalPatterns($metaTag['description'], false);
		$metaTag['keywords'] = mb_strtolower(replaceGlobalPatterns($metaTag['keywords'], false));
		
		return array_values($metaTag);
	}
	
	$pagesThatHaveTheirOwnDefaultMetaTags = [
		'search',
		'searchCategory',
		'searchLocation',
		'searchProfile',
		'searchTag',
		'listingDetails',
		'staticPage',
	];
	
	if (!in_array($page, $pagesThatHaveTheirOwnDefaultMetaTags)) {
		if (config('settings.app.slogan')) {
			$metaTag['title'] = config('app.name') . ' - ' . config('settings.app.slogan');
		} else {
			$metaTag['title'] = config('app.name') . ' - ' . config('country.name');
		}
		$metaTag['description'] = $metaTag['title'];
	}
	
	if (!is_array($metaTag)) {
		$metaTag = [];
	}
	$metaTag['title'] = $metaTag['title'] ?? null;
	$metaTag['description'] = $metaTag['description'] ?? null;
	$metaTag['keywords'] = $metaTag['keywords'] ?? null;
	
	return is_array($metaTag) ? array_values($metaTag) : [];
}

/**
 * Get the Distance Calculation Unit
 *
 * @param string|null $countryCode
 * @return string
 */
function getDistanceUnit(string $countryCode = null)
{
	if (empty($countryCode)) {
		$countryCode = config('country.code');
	}
	$unit = \Larapen\LaravelDistance\Helper::getDistanceUnit($countryCode);
	
	return t($unit);
}

/**
 * Get Front Skin
 *
 * @param string|null $skin
 * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
 */
function getFrontSkin(string $skin = null)
{
	$savedSkin = config('settings.style.skin', 'default');
	
	if (!empty($skin)) {
		$skinsArray = config('larapen.core.skins');
		if (!is_array($skinsArray) || !array_key_exists($skin, $skinsArray)) {
			$skin = $savedSkin;
		}
	} else {
		$skin = $savedSkin;
	}
	
	return $skin;
}

/**
 * Hashids is a small PHP library to generate YouTube-like ids from numbers.
 * Use it when you don't want to expose your database numeric ids to users
 *
 * @param $in
 * @param bool $toNum
 * @param bool $withPrefix
 * @param int $minHashLength
 * @param string $salt
 * @return array|mixed|string|null
 */
function hashId($in, bool $toNum = false, bool $withPrefix = true, int $minHashLength = 11, string $salt = '')
{
	if (!config('settings.seo.listing_hashed_id_enabled') && !isHashedId($in)) {
		return $in;
	}
	
	$hidPrefix = $withPrefix ? config('larapen.core.hashableIdPrefix') : '';
	$hidPrefix = is_string($hidPrefix) ? $hidPrefix : '';
	
	$hashIds = new \Hashids\Hashids($salt, $minHashLength);
	
	if (!$toNum) {
		$out = $hidPrefix . $hashIds->encode($in);
	} else {
		$in = ltrim($in, $hidPrefix);
		$out = $hashIds->decode($in);
		if (isset($out[0])) {
			$out = $out[0];
		}
	}
	
	return !empty($out) ? $out : null;
}

/**
 * @param $in
 * @param int $minHashLength
 * @return bool
 */
function isHashedId($in, int $minHashLength = 11): bool
{
	$hidPrefix = config('larapen.core.hashableIdPrefix');
	$hidPrefixLength = is_string($hidPrefix) ? strlen($hidPrefix) : 0;
	
	return (
		preg_match('/[a-z0-9A-Z]+/', $in)
		&& (strlen($in) == ($minHashLength + $hidPrefixLength))
	);
}

/**
 * Get routes prefixes to ban to match listing route's path
 *
 * @return array
 */
function regexSimilarRoutesPrefixes(): array
{
	$routes = (array)config('routes');
	
	$prefixes = [];
	foreach ($routes as $route) {
		$prefix = head(explode('/', $route));
		if (!str_starts_with($prefix, '{')) {
			$prefixes[] = $prefix;
		}
	}
	
	return array_unique($prefixes);
}

/**
 * Check if the user browser is the given value.
 * The given value can be:
 * 'Firefox', 'Chrome', 'Safari', 'Opera', 'MSIE', 'Trident', 'Edge'
 *
 * Usage: doesUserBrowserIs('Chrome') or doesUserBrowserIs() == 'Chrome'
 *
 * @param string|null $browser
 * @return bool
 */
function doesUserBrowserIs(string $browser = null): bool
{
	if (!empty($browser)) {
		return (str_contains(request()->server('HTTP_USER_AGENT'), $browser));
	} else {
		$browsers = ['Firefox', 'Chrome', 'Safari', 'Opera', 'MSIE', 'Trident', 'Edge'];
		$agent = request()->server('HTTP_USER_AGENT');
		
		$userBrowser = null;
		foreach ($browsers as $browser) {
			if (str_contains($agent, $browser)) {
				$userBrowser = $browser;
				break;
			}
		}
		
		return !empty($userBrowser);
	}
}

/**
 * Get sitemaps indexes
 *
 * @param bool $htmlFormat
 * @return string
 */
function getSitemapsIndexes(bool $htmlFormat = false): string
{
	$out = '';
	
	$countries = \App\Helpers\Localization\Helpers\Country::transAll(CountryHelper::getCountries());
	if (!$countries->isEmpty()) {
		if ($htmlFormat) {
			$cmFieldStyle = ($countries->count() > 10) ? ' style="height: 205px; overflow-y: scroll;"' : '';
			$out .= '<ul' . $cmFieldStyle . '>';
		}
		foreach ($countries as $country) {
			if (!$country instanceof Collection) {
				continue;
			}
			
			$country = CountryHelper::getCountryInfo($country->get('code'));
			if ($country->isEmpty()) {
				continue;
			}
			
			/*
			// Get the Country's Language Code
			$countryLang = $country->has('lang') ? $country->get('lang') : collect();
			$countryLang = ($countryLang instanceof Collection) ? $countryLang : collect();
			$countryLangCode = $countryLang->has('code') ? $countryLang->get('code') : config('app.locale');
			*/
			
			// Add the Sitemap Index
			if ($htmlFormat) {
				$out .= '<li>' . dmUrl($country, $country->get('icode') . '/sitemaps.xml') . '</li>';
			} else {
				$out .= 'Sitemap: ' . dmUrl($country, $country->get('icode') . '/sitemaps.xml') . "\n";
			}
		}
		if ($htmlFormat) {
			$out .= '</ul>';
		}
	}
	
	return $out;
}

/**
 * Default robots.txt content
 *
 * @return string
 */
function getDefaultRobotsTxtContent(): string
{
	$out = 'User-agent: *' . "\n";
	$out .= 'Disallow:' . "\n";
	$out .= "\n";
	$out .= 'Allow: /' . "\n";
	$out .= "\n";
	$out .= 'User-agent: *' . "\n";
	$out .= 'Disallow: /' . admin_uri() . '/' . "\n";
	$out .= 'Disallow: /ajax/' . "\n";
	$out .= 'Disallow: /assets/' . "\n";
	$out .= 'Disallow: /css/' . "\n";
	$out .= 'Disallow: /js/' . "\n";
	$out .= 'Disallow: /vendor/' . "\n";
	$out .= 'Disallow: /main.php' . "\n";
	$out .= 'Disallow: /index.php' . "\n";
	$out .= 'Disallow: /mix-manifest.json' . "\n";
	$out .= 'Disallow: /*?display=*' . "\n"; // Listings list display mode
	
	$languages = getSupportedLanguages();
	if (!empty($languages)) {
		foreach ($languages as $code => $lang) {
			$out .= 'Disallow: /locale/' . $code . "\n";
		}
	}
	
	$providers = ['facebook', 'linkedin', 'twitter', 'google'];
	foreach ($providers as $provider) {
		$out .= 'Disallow: /auth/' . $provider . "\n";
	}
	
	return $out;
}

/**
 * Generate the Email Form button
 *
 * @param null $post
 * @param bool $btnBlock
 * @param bool $iconOnly
 * @return string
 */
function genEmailContactBtn($post = null, bool $btnBlock = false, bool $iconOnly = false): string
{
	$post = (is_array($post)) ? Arr::toObject($post) : $post;
	
	$out = '';
	
	if (!isVerifiedPost($post)) {
		return $out;
	}
	
	$smsNotificationCanBeSent = (
		config('settings.sms.enable_phone_as_auth_field') == '1'
		&& config('settings.sms.messenger_notifications') == '1'
		&& $post->auth_field == 'phone'
		&& !empty($post->phone)
	);
	if (empty($post->email) && !$smsNotificationCanBeSent) {
		if ($iconOnly) {
			$out = '<i class="far fa-envelope" style="color: #dadada"></i>';
		}
		
		return $out;
	}
	
	$btnLink = '#contactUser';
	$btnClass = '';
	if (!auth()->check()) {
		if (config('settings.single.guest_can_contact_authors') != '1') {
			$btnLink = '#quickLogin';
		}
	}
	
	if ($iconOnly) {
		$out .= '<a href="' . $btnLink . '" data-bs-toggle="modal">';
		$out .= '<i class="far fa-envelope" data-bs-toggle="tooltip" title="' . t('Send a message') . '"></i>';
	} else {
		if ($btnBlock) {
			$btnClass = $btnClass . ' btn-block';
		}
		
		$out .= '<a href="' . $btnLink . '" data-bs-toggle="modal" class="btn btn-default' . $btnClass . '">';
		$out .= '<i class="far fa-envelope"></i> ';
		$out .= t('Send a message');
	}
	$out .= '</a>';
	
	return $out;
}

/**
 * Generate the Phone Number button
 *
 * @param $post
 * @param bool $btnBlock
 * @return string
 */
function genPhoneNumberBtn($post, bool $btnBlock = false): string
{
	$post = (is_array($post)) ? Arr::toObject($post) : $post;
	
	$out = '';
	
	if (empty($post->phone_intl) || $post->phone_hidden == 1) {
		return $out;
	}
	
	$enableWhatsAppBtn = (config('settings.single.enable_whatsapp_btn') == 1);
	$whatsAppPreFilledMessage = (config('settings.single.pre_filled_whatsapp_message') == 1)
		? '?text=' . rawurlencode(t('whatsapp_pre_filled_message', [
			'title'   => $post->title,
			'appName' => config('app.name'),
		])) : '';
	$whatsAppLink = 'https://wa.me/' . strToDigit($post->phone) . $whatsAppPreFilledMessage;
	$waBtnClass = '';
	
	$btnLink = 'tel:' . $post->phone;
	$btnAttr = '';
	$btnClass = ' phoneBlock'; /* for the JS showPhone() function */
	$btnHint = t('Click to see');
	$phone = $post->phone_intl;
	if (config('settings.single.hide_phone_number')) {
		$phoneToHide = normalizePhoneNumber($phone);
		if (config('settings.single.hide_phone_number') == '1') {
			$phone = str($phoneToHide)->mask('X', -str($phoneToHide)->length(), str($phoneToHide)->length() - 3);
		}
		if (config('settings.single.hide_phone_number') == '2') {
			$phone = str($phoneToHide)->mask('X', 3);
		}
		if (config('settings.single.hide_phone_number') == '3') {
			$phone = str($phoneToHide)->mask('X', 0);
		}
		$btnLink = '';
		$btnAttrTooltip = 'data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . $btnHint . '"';
		$btnClassTooltip = '';
		
		$btnAttr = $btnAttrTooltip;
		$btnClass = $btnClass . $btnClassTooltip;
		
		$enableWhatsAppBtn = false;
	} else {
		if (config('settings.single.convert_phone_number_to_img')) {
			try {
				$phone = \Larapen\TextToImage\Facades\TextToImage::make($phone, config('larapen.core.textToImage'));
			} catch (\Throwable $e) {
				$phone = $post->phone;
			}
			$btnClass = '';
		}
	}
	
	if (config('settings.single.show_security_tips') == '1') {
		/*
		    Set multiple data-bs-toggle for link in Bootstrap
			Tooltip + modal in button - Bootstrap
			
			Usage of '[rel="tooltip"]' as selector instead of '[data-bs-toggle="tooltip"]' for the tooltip,
			and trigger that with on hover event from JS
		*/
		$btnAttrTooltip = 'rel="tooltip" data-bs-placement="bottom" title="' . $btnHint . '"';
		$btnClassTooltip = '';
		$btnAttrModal = 'data-bs-toggle="modal"';
		
		$btnLink = '#securityTips';
		$btnAttr = $btnAttrModal . ' ' . $btnAttrTooltip;
		$btnClass = ' phoneBlock'; /* for the JS showPhone() function */
		if (!config('settings.single.hide_phone_number')) {
			$phone = t('phone_number');
		}
		$btnClass = $btnClass . ' ' . $btnClassTooltip;
	}
	
	if (!auth()->check()) {
		if (config('settings.single.guest_can_contact_authors') != '1') {
			$btnAttrModal = 'data-bs-toggle="modal"';
			
			$phone = $btnHint;
			$btnLink = '#quickLogin';
			$btnAttr = $btnAttrModal;
			$btnClass = '';
			
			$enableWhatsAppBtn = false;
		}
	}
	
	if ($btnBlock) {
		$waBtnClass = $waBtnClass . ' btn-block';
		$btnClass = $btnClass . ' btn-block';
	}
	
	// Generate the Phone Number button
	$out .= '<a href="' . $btnLink . '" ' . $btnAttr . ' class="btn btn-warning' . $btnClass . '">';
	$out .= '<i class="fas fa-mobile-alt"></i> ';
	$out .= $phone;
	$out .= '</a>';
	
	if ($enableWhatsAppBtn) {
		$waBtnAttr = 'data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . t('chat_on_whatsapp') . '"';
		$waBtnClass = $waBtnClass . '';
		
		// Generate the WhatsApp button
		$out .= '<a href="' . $whatsAppLink . '" ' . $waBtnAttr . ' target="_blank" class="btn btn-success' . $waBtnClass . '">';
		$out .= '<i class="fab fa-whatsapp"></i> ';
		$out .= 'WhatsApp';
		$out .= '</a>';
	}
	
	return $out;
}

/**
 * Set the Backup config vars
 *
 * @param string|null $typeOfBackup
 */
function setBackupConfig(string $typeOfBackup = null)
{
	// Get the current version value
	$version = preg_replace('/[^\d+]/', '', config('version.app'));
	
	// All backup filename prefix
	config()->set('backup.backup.destination.filename_prefix', 'site-v' . $version . '-');
	
	// Database backup
	if ($typeOfBackup == 'database') {
		config()->set('backup.backup.admin_flags', [
			'--disable-notifications' => true,
			'--only-db'               => true,
		]);
		config()->set('backup.backup.destination.filename_prefix', 'database-v' . $version . '-');
	}
	
	// Languages' files backup
	if ($typeOfBackup == 'languages') {
		$include = [
			lang_path(),
		];
		$pluginsDirs = glob(config('larapen.core.plugin.path') . '*', GLOB_ONLYDIR);
		if (!empty($pluginsDirs)) {
			foreach ($pluginsDirs as $pluginDir) {
				$pluginLangFolder = $pluginDir . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
				if (file_exists($pluginLangFolder)) {
					$include[] = $pluginLangFolder;
				}
			}
		}
		
		config()->set('backup.backup.admin_flags', [
			'--disable-notifications' => true,
			'--only-files'            => true,
		]);
		config()->set('backup.backup.source.files.include', $include);
		config()->set('backup.backup.source.files.exclude', [
			//...
		]);
		config()->set('backup.backup.destination.filename_prefix', 'languages-');
	}
	
	// Generated files backup
	if ($typeOfBackup == 'files') {
		config()->set('backup.backup.admin_flags', [
			'--disable-notifications' => true,
			'--only-files'            => true,
		]);
		config()->set('backup.backup.source.files.include', [
			base_path('.env'),
			storage_path('app/public'),
			storage_path('installed'),
		]);
		config()->set('backup.backup.source.files.exclude', [
			//...
		]);
		config()->set('backup.backup.destination.filename_prefix', 'files-');
	}
	
	// App files backup
	if ($typeOfBackup == 'app') {
		config()->set('backup.backup.admin_flags', [
			'--disable-notifications' => true,
			'--only-files'            => true,
		]);
		config()->set('backup.backup.source.files.include', [
			base_path(),
			// base_path('.gitattributes'),
			base_path('.gitignore'),
		]);
		config()->set('backup.backup.source.files.exclude', [
			base_path('node_modules'),
			base_path('.git'),
			base_path('.idea'),
			base_path('.env'),
			base_path('bootstrap/cache') . DIRECTORY_SEPARATOR . '*',
			public_path('robots.txt'),
			storage_path('app/backup-temp'),
			storage_path('app/database'),
			storage_path('app/public/app/categories/custom') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/app/ico') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/app/logo') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/app/page') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/files') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/purifier') . DIRECTORY_SEPARATOR . '*',
			storage_path('database/demo'),
			storage_path('backups'),
			storage_path('dotenv-editor') . DIRECTORY_SEPARATOR . '*',
			storage_path('framework/cache') . DIRECTORY_SEPARATOR . '*',
			storage_path('framework/sessions') . DIRECTORY_SEPARATOR . '*',
			storage_path('framework/testing') . DIRECTORY_SEPARATOR . '*',
			storage_path('framework/views') . DIRECTORY_SEPARATOR . '*',
			storage_path('installed'),
			storage_path('laravel-backups'),
			storage_path('logs') . DIRECTORY_SEPARATOR . '*',
		]);
		config()->set('backup.backup.destination.filename_prefix', 'app-v' . $version . '-');
	}
}

/**
 * Check if User is online
 *
 * @param $user
 * @return bool
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function isUserOnline($user): bool
{
	$user = (is_array($user)) ? Arr::toObject($user) : $user;
	
	$isOnline = false;
	
	if (!empty($user) && isset($user->id)) {
		if (config('settings.optimization.cache_driver') == 'array') {
			$isOnline = $user->p_is_online;
		} else {
			$isOnline = cache()->store('file')->has('user-is-online-' . $user->id);
		}
	}
	
	// Allow only logged users to get the other users status
	$guard = isFromApi() ? 'sanctum' : null;
	
	return auth($guard)->check() ? $isOnline : false;
}

/**
 * @param string $key
 * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
 */
function dynamicRoute(string $key)
{
	return config($key);
}

/**
 * Set the Db Fallback Locale
 *
 * @param string|null $locale
 * @return void
 */
function setDbFallbackLocale(?string $locale): void
{
	try {
		if (!\Jackiedo\DotenvEditor\Facades\DotenvEditor::keyExists('FALLBACK_LOCALE_FOR_DB')) {
			\Jackiedo\DotenvEditor\Facades\DotenvEditor::addEmpty();
		}
		\Jackiedo\DotenvEditor\Facades\DotenvEditor::setKey('FALLBACK_LOCALE_FOR_DB', $locale);
		\Jackiedo\DotenvEditor\Facades\DotenvEditor::save();
	} catch (\Throwable $e) {
	}
}

/**
 * Remove the Db Fallback Locale
 *
 * @return void
 */
function removeDbFallbackLocale(): void
{
	try {
		if (!\Jackiedo\DotenvEditor\Facades\DotenvEditor::keyExists('FALLBACK_LOCALE_FOR_DB')) {
			\Jackiedo\DotenvEditor\Facades\DotenvEditor::addEmpty();
		}
		\Jackiedo\DotenvEditor\Facades\DotenvEditor::setKey('FALLBACK_LOCALE_FOR_DB', 'null');
		\Jackiedo\DotenvEditor\Facades\DotenvEditor::save();
	} catch (\Throwable $e) {
	}
}

/**
 * @param $locale
 * @return void
 */
function addMissingTranslations($locale): void
{
	$masterLocale = config('appLang.code');
	if (empty($locale) || empty($masterLocale)) {
		return;
	}
	
	if ($locale == $masterLocale) {
		return;
	}
	
	// Update home sections translatable options
	$section = HomeSection::where('method', 'getSearchForm')->first();
	if (!empty($section)) {
		$value = $section->value;
		
		$masterKey = 'title_' . $masterLocale;
		$localeKey = 'title_' . $locale;
		if (isset($value[$masterKey])) {
			$value[$localeKey] = $value[$masterKey];
		}
		
		$masterKey = 'sub_title_' . $masterLocale;
		$localeKey = 'sub_title_' . $locale;
		if (isset($value[$masterKey])) {
			$value[$localeKey] = $value[$masterKey];
		}
		
		$section->value = $value;
		
		if ($section->isDirty()) {
			$section->saveQuietly();
		}
	}
	
	// Update the translatable tables columns
	$modelClasses = DBTool::getAppModelClasses(translatable: true);
	if (empty($modelClasses)) {
		return;
	}
	
	foreach ($modelClasses as $modelClass) {
		$model = new $modelClass;
		
		// Get the translatable columns
		$columns = method_exists($model, 'getTranslatableAttributes')
			? $model->getTranslatableAttributes()
			: [];
		if (empty($columns)) {
			continue;
		}
		
		$modelCollection = $modelClass::query()->withoutGlobalScopes();
		if ($modelCollection->doesntExist()) {
			continue;
		}
		
		foreach ($modelCollection->cursor() as $item) {
			foreach ($columns as $column) {
				$value = $item->getTranslations($column);
				
				if (isset($value[$masterLocale])) {
					$value[$locale] = $value[$masterLocale];
					
					$item->setTranslations($column, $value)
						->saveQuietly();
				}
			}
		}
	}
}

/**
 * SEO Website Verification using meta tags
 * Allow full HTML tag or content="" value
 *
 * @return string
 */
function seoSiteVerification(): string
{
	$engines = [
		'google' => [
			'name'    => 'google-site-verification',
			'content' => config('settings.seo.google_site_verification'),
		],
		'bing'   => [
			'name'    => 'msvalidate.01',
			'content' => config('settings.seo.msvalidate'),
		],
		'yandex' => [
			'name'    => 'yandex-verification',
			'content' => config('settings.seo.yandex_verification'),
		],
		'alexa'  => [
			'name'    => 'alexaVerifyID',
			'content' => config('settings.seo.alexa_verify_id'),
		],
	];
	
	$out = '';
	foreach ($engines as $engine) {
		if (isset($engine['name'], $engine['content']) && $engine['content']) {
			if (preg_match('|<meta[^>]+>|i', $engine['content'])) {
				$out .= $engine['content'] . "\n";
			} else {
				$out .= '<meta name="' . $engine['name'] . '" content="' . $engine['content'] . '" />' . "\n";
			}
		}
	}
	
	return $out;
}

/**
 * Is 'utf8mb4' is set as the database Charset
 * and 'utf8mb4_unicode_ci' is set as the database collation
 *
 * @return bool
 */
function isUtf8mb4Enabled(): bool
{
	$defaultConnection = config('database.default');
	$databaseCharset = config("database.connections.{$defaultConnection}.charset");
	$databaseCollation = config("database.connections.{$defaultConnection}.collation");
	
	// Allow Emojis when the database charset is 'utf8mb4'
	// and the database collation is 'utf8mb4_unicode_ci'
	if ($databaseCharset == 'utf8mb4' && $databaseCollation == 'utf8mb4_unicode_ci') {
		return true;
	}
	
	return false;
}

/**
 * @param string|null $path
 * @return string|null
 */
function relativeAppPath(?string $path): ?string
{
	if (isDemoDomain()) {
		return getRelativePath($path);
	}
	
	return $path;
}

/**
 * @param string|null $url
 * @return string|null
 */
function getFilterClearBtn(?string $url): ?string
{
	$out = '';
	
	if (!empty($url)) {
		$float = (config('lang.direction') == 'rtl') ? 'left' : 'right';
		$out .= '<a href="' . $url . '" title="' . t('Remove this filter') . '">';
		$out .= '<i class="far fa-window-close" style="float: ' . $float . '; margin-top: 6px; color: #999;"></i>';
		$out .= '</a>';
	}
	
	return $out;
}

/**
 * @return bool
 */
function socialLoginIsEnabled(): bool
{
	return (
		config('settings.social_auth.social_login_activation')
		&& (
			(config('settings.social_auth.facebook_client_id') && config('settings.social_auth.facebook_client_secret'))
			|| (config('settings.social_auth.linkedin_client_id') && config('settings.social_auth.linkedin_client_secret'))
			|| (config('settings.social_auth.twitter_client_id') && config('settings.social_auth.twitter_client_secret'))
			|| (config('settings.social_auth.google_client_id') && config('settings.social_auth.google_client_secret'))
		)
	);
}

/**
 * Get Form Border Radius CSS
 *
 * @param $formBorderRadius
 * @param $fieldsBorderRadius
 * @return string
 */
function getFormBorderRadiusCSS($formBorderRadius, $fieldsBorderRadius): string
{
	$searchFormOptions['form_border_radius'] = $formBorderRadius . 'px';
	$searchFormOptions['fields_border_radius'] = $fieldsBorderRadius . 'px';
	
	$out = "\n";
	if (config('lang.direction') == 'rtl') {
		$out .= '#homepage .search-row .search-col:first-child .search-col-inner {' . "\n";
		$out .= 'border-top-right-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-right-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col:first-child .form-control {' . "\n";
		$out .= 'border-top-right-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-right-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col .search-btn-border {' . "\n";
		$out .= 'border-top-left-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-left-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col .btn {' . "\n";
		$out .= 'border-top-left-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-left-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
	} else {
		$out .= '#homepage .search-row .search-col:first-child .search-col-inner {' . "\n";
		$out .= 'border-top-left-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-left-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col:first-child .form-control {' . "\n";
		$out .= 'border-top-left-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-left-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col .search-btn-border {' . "\n";
		$out .= 'border-top-right-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-right-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col .btn {' . "\n";
		$out .= 'border-top-right-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-right-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
	}
	
	$out .= '@media (max-width: 767px) {' . "\n";
	$out .= '#homepage .search-row .search-col:first-child .form-control,' . "\n";
	$out .= '#homepage .search-row .search-col:first-child .search-col-inner,' . "\n";
	$out .= '#homepage .search-row .search-col .form-control,' . "\n";
	$out .= '#homepage .search-row .search-col .search-col-inner,' . "\n";
	$out .= '#homepage .search-row .search-col .btn,' . "\n";
	$out .= '#homepage .search-row .search-col .search-btn-border {' . "\n";
	$out .= 'border-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
	$out .= '}' . "\n";
	$out .= '}' . "\n";
	
	return $out;
}

/**
 * Get the user's possible subscription features
 *
 * @param $user
 * @param string|null $feature
 * @return int|int[]|null
 */
function getUserSubscriptionFeatures($user, ?string $feature = null): array|int|null
{
	$array = [
		'postsLimit'     => null,
		'picturesLimit'  => null,
		'expirationTime' => null,
	];
	
	if (empty($user)) {
		return empty($feature) ? $array : ($array[$feature] ?? null);
	}
	
	/*
	 * With the 120 seconds of caching, we have to:
	 * - Accept that the current payment will expire 2 minutes later than expected.
	 * - Make sure that a new payment cannot be make in 2 minutes.
	 */
	$seconds = 120;
	$cacheId = 'user.subscription.payment.package';
	$user = cache()->remember($cacheId, $seconds, function () use ($user) {
		/*
		 * Important:
		 * The basic packages can be saved as paid in the "payments" table by the OfflinePayment plugin
		 * So, don't apply the fake basic features, so we have to exclude packages whose price is 0.
		 */
		$isNotBasic = fn ($q) => $q->where('price', '>', 0);
		$user->loadMissing(['payment' => fn ($q) => $q->withWhereHas('package', $isNotBasic)]);
		
		return $user;
	});
	
	if (!empty($user->payment) && !empty($user->payment->package)) {
		$basicPostsLimit = config('settings.single.listings_limit', 5);
		$basicPicturesLimit = config('settings.single.pictures_limit', 5);
		$basicExpirationTime = config('settings.cron.activated_listings_expiration', 30);
		
		$postsLimit = $user->payment->package->listings_limit ?? $basicPostsLimit;
		$picturesLimit = $user->payment->package->pictures_limit ?? $basicPicturesLimit;
		$expirationTime = $user->payment->package->expiration_time ?? $basicExpirationTime;
		
		$postsLimit = ($postsLimit > 0) ? $postsLimit : $basicPostsLimit;
		$picturesLimit = ($picturesLimit > 0) ? $picturesLimit : $basicPicturesLimit;
		$expirationTime = ($expirationTime > 0) ? $expirationTime : $basicExpirationTime;
		
		$array['postsLimit'] = $postsLimit;
		$array['picturesLimit'] = $picturesLimit;
		$array['expirationTime'] = $expirationTime;
	}
	
	return empty($feature) ? $array : ($array[$feature] ?? null);
}

/**
 * Get possible promotion features to a listing
 *
 * @param \App\Models\Post $post
 * @param string|null $feature
 * @return int|int[]|null
 */
function getPostPromotionFeatures(Post $post, ?string $feature = null): array|int|null
{
	$array = [
		'picturesLimit'  => null,
		'expirationTime' => null,
	];
	
	/*
	 * Important:
	 * The basic packages can be saved as paid in the "payments" table by the OfflinePayment plugin
	 * So, don't apply the fake basic features, so we have to exclude packages whose price is 0.
	 */
	$isNotBasic = fn ($q) => $q->where('price', '>', 0);
	$post->loadMissing(['payment' => fn ($q) => $q->withWhereHas('package', $isNotBasic)]);
	
	if (!empty($post->payment) && !empty($post->payment->package)) {
		$basicPicturesLimit = config('settings.single.pictures_limit', 5);
		$basicExpirationTime = config('settings.cron.activated_listings_expiration', 30);
		
		$picturesLimit = $post->payment->package->pictures_limit ?? $basicPicturesLimit;
		$expirationTime = $post->payment->package->expiration_time ?? $basicExpirationTime;
		
		$picturesLimit = ($picturesLimit > 0) ? $picturesLimit : $basicPicturesLimit;
		$expirationTime = ($expirationTime > 0) ? $expirationTime : $basicExpirationTime;
		
		$array['picturesLimit'] = $picturesLimit;
		$array['expirationTime'] = $expirationTime;
	}
	
	return empty($feature) ? $array : ($array[$feature] ?? null);
}

/**
 * Get package ID request through 'package_id' or 'package'
 *
 * @return int|null
 */
function requestPackageId(): ?int
{
	$packageId = null;
	
	if (request()->filled('package_id')) {
		$packageId = request()->input('package_id');
	}
	
	if (empty($packageId)) {
		if (request()->filled('package')) {
			$packageId = request()->query('package');
		}
	}
	
	if (empty($packageId)) {
		$packageId = (int)old('package_id');
		if (!empty($packageId)) {
			if (!request()->has('package_id')) {
				request()->request->add(['package_id' => $packageId]);
			}
			
			return $packageId;
		}
	}
	
	return (int)$packageId;
}

/**
 * Get package by ID
 *
 * @param $packageId
 * @return \App\Models\Package|null
 */
function getPackageById($packageId): ?Package
{
	$cacheExpiration = (int)config('settings.optimization.cache_expiration');
	$cacheId = 'package.id.' . $packageId . '.' . config('app.locale');
	
	return cache()->remember($cacheId, $cacheExpiration, function () use ($packageId) {
		return Package::with(['currency'])->where('id', $packageId)->first();
	});
}

/**
 * Get the package type relating to the current request
 *
 * @return string|null
 */
function getRequestPackageType(): ?string
{
	$isPromoting = isFromApi()
		? str_contains(currentRouteAction(), '\Api\PostController')
		: (
			str_contains(currentRouteAction(), '\Api\PostController')
			|| str_contains(currentRouteAction(), '\Web\Public\Post')
		);
	
	$isSubscripting = isFromApi()
		? str_contains(currentRouteAction(), '\Api\UserController')
		: (
			str_contains(currentRouteAction(), '\Api\UserController')
			|| str_contains(currentRouteAction(), '\Web\Public\Auth')
			|| str_contains(currentRouteAction(), '\Web\Public\Account')
		);
	
	$type = null;
	if ($isPromoting) {
		$type = 'promotion';
	}
	if ($isSubscripting) {
		$type = 'subscription';
	}
	
	return $type;
}

/**
 * Country table's 'admin_type' enum column possible values
 *
 * @return array
 */
function enumCountryAdminTypes(): array
{
	return [
		'0' => trans('admin.none'),
		'1' => trans('admin.admin_division1'),
		'2' => trans('admin.admin_division2'),
	];
}

function getCountryFlagShapes(): array
{
	return [
		'rectangle' => 'Rectangle Flags', // Default
		'circle'    => 'Circle Flags',
		'hexagon'   => 'Hexagon Flags',
	];
}

/**
 * @param string|null $countryCode
 * @param int|null $size
 * @return string|null
 */
function getCountryFlagUrl(?string $countryCode, ?int $size = 16): ?string
{
	if (empty($countryCode)) return null;
	$size = !empty($size) ? $size : 16;
	
	$flagUrl = null;
	
	$shape = config('settings.localization.country_flag_shape', 'rectangle');
	if ($shape == 'rectangle') {
		$missingIslandFlags = [
			'BQ' => 'NL', // Bonaire, Sint Eustatius, and Saba (Kingdom of the Netherlands)
			'BV' => 'NO', // Bouvet Island (Norway)
			'GF' => 'FR', // French Guiana (France)
			'GP' => 'FR', // Guadeloupe (France)
			'PM' => 'FR', // Saint Pierre and Miquelon (France)
			'RE' => 'FR', // Réunion (France)
			'SX' => 'NL', // Sint Maarten (Kingdom of the Netherlands)
		];
	} else {
		$missingIslandFlags = [
			'BV' => 'NO', // Bouvet Island (Norway)
			'CW' => 'NL', // Curaçao (Kingdom of the Netherlands)
			'AN' => 'NL', // Netherlands Antilles (Kingdom of the Netherlands)
			'FM' => 'US', // Federated States of Micronesia (US | UN)
			'CC' => 'AU', // Cocos (Keeling) Islands (Australia)
			'AX' => 'FI', // Åland Islands (Finland)
			'IM' => 'GB', // Isle of Man (United Kingdom)
		];
	}
	$code = $missingIslandFlags[$countryCode] ?? $countryCode;
	
	$flagPath = 'images/flags/' . $shape . '/' . $size . '/' . strtolower($code) . '.png';
	if (file_exists(public_path($flagPath))) {
		$flagUrl = url($flagPath) . getPictureVersion();
	}
	
	return getAsStringOrNull($flagUrl);
}
