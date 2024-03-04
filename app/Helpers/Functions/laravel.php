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
use App\Helpers\Ip;
use App\Helpers\Response\Ajax;
use App\Helpers\Response\Api;
use App\Helpers\SystemLocale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/**
 * Get the current route action
 * This function prevents null return of the core function
 *
 * @return string
 */
function currentRouteAction(): string
{
	$value = Route::currentRouteAction();
	
	return getAsString($value);
}

/**
 * @param string $string
 * @return string
 */
function currentRouteActionContains(string $string): string
{
	return (isApiLocalRequestsEnabled() || str_contains(currentRouteAction(), $string));
}

function getRequestSegment(int $index, $default = null)
{
	if (!isFromApi()) {
		return request()->segment($index, $default);
	}
	
	if (isApiCurlRequestsEnabled()) {
		return request()->segment($index, $default);
	}
	
	$requestUri = request()->server('REQUEST_URI');
	$segments = explode('/', $requestUri);
	
	if (!str_starts_with($requestUri, '/')) {
		$index = $index - 1;
	} else {
		$firstSegment = $segments[0] ?? '';
		$firstSegment = is_stringable($firstSegment) ? strtolower($firstSegment) : '';
		if (str_starts_with($firstSegment, 'http')) {
			$index = $index + 3;
		}
	}
	
	$value = $segments[$index] ?? $default;
	
	return is_stringable($value) ? $value : null;
}

/**
 * Check if a Model has translation fields
 *
 * @param $model
 * @return bool
 */
function isTranslatableModel($model): bool
{
	$isTranslatable = false;
	
	try {
		if (!($model instanceof Model)) {
			return false;
		}
		
		$isTranslatableModel = (
			property_exists($model, 'translatable')
			&& (!empty($model->translatable) && is_array($model->translatable))
		);
		
		if ($isTranslatableModel) {
			$isTranslatable = true;
		}
	} catch (\Throwable $e) {
		return false;
	}
	
	return $isTranslatable;
}

/**
 * Default translator (e.g. en/global.php)
 *
 * @param string|null $key
 * @param array $replace
 * @param string $file
 * @param string|null $locale
 * @return array|\Illuminate\Contracts\Translation\Translator|string|null
 */
function t(string $key = null, array $replace = [], string $file = 'global', string $locale = null)
{
	if (is_null($locale)) {
		$locale = config('app.locale');
	}
	
	return trans($file . '.' . $key, $replace, $locale);
}

/**
 * @param string|null $defaultIp
 * @return string
 */
function getIp(?string $defaultIp = ''): string
{
	return Ip::get($defaultIp);
}

/**
 * Get host (domain with subdomain)
 *
 * @param string|null $url
 * @return array|mixed|string
 */
function getHost(string $url = null)
{
	if (!empty($url)) {
		$host = parse_url($url, PHP_URL_HOST);
	} else {
		$host = (trim(request()->server('HTTP_HOST')) != '') ? request()->server('HTTP_HOST') : ($_SERVER['HTTP_HOST'] ?? '');
	}
	
	if ($host == '') {
		$host = parse_url(url()->current(), PHP_URL_HOST);
	}
	
	return $host;
}

/**
 * Get domain (host without a subdomain)
 *
 * @param string|null $url
 * @return string
 */
function getDomain(string $url = null): string
{
	if (!empty($url)) {
		$host = parse_url($url, PHP_URL_HOST);
	} else {
		$host = getHost();
	}
	
	$tmp = explode('.', $host);
	if (count($tmp) > 2) {
		$itemsToKeep = count($tmp) - 2;
		$tldArray = getTopLevelDomainRefList();
		if (isset($tmp[$itemsToKeep]) && isset($tldArray[$tmp[$itemsToKeep]])) {
			$itemsToKeep = $itemsToKeep - 1;
		}
		for ($i = 0; $i < $itemsToKeep; $i++) {
			Arr::forget($tmp, $i);
		}
		$domain = implode('.', $tmp);
	} else {
		$domain = @implode('.', $tmp);
	}
	
	return $domain;
}

/**
 * Get subdomain name
 *
 * NOTE:
 * The subdomains of the fetched subdomain are not retrieved
 * Example: xxx.yyy.zzz.foo.com, only "xxx" will be retrieved
 *
 * @return string
 */
function getSubDomainName(): string
{
	$host = getHost();
	
	return (substr_count($host, '.') > 1) ? trim(current(explode('.', $host))) : '';
}

/**
 * @return string
 */
function getCookieDomain(): string
{
	$host = getHost();
	$array = mb_parse_url($host);
	
	return (is_array($array) && !empty($array['path']))
		? $array['path']
		: $host;
}

/**
 * Get the URL (no query string) for the given URL or for the request
 *
 * @param string|null $url
 * @param bool|null $secure
 * @return string
 */
function getUrlWithoutQuery(?string $url, bool $secure = null): string
{
	if (empty($url)) {
		$url = request()->fullUrl();
		if (empty($url)) return '';
	} else {
		// Accepts URI|Path as URL
		$url = url()->to($url, [], $secure);
	}
	
	$url = preg_replace('/\?.*/ui', '', $url);
	
	return is_string($url) ? rtrim($url, '/') : '';
}

/**
 * Get query string from a given URL
 * NOTE: Possibility to except some query
 *
 * @param string|null $url
 * @param array|string|null $except
 * @return array
 */
function getUrlQuery(?string $url, $except = null): array
{
	if (empty($url)) {
		$url = request()->fullUrl();
		if (empty($url)) return [];
	}
	
	$queryArray = [];
	
	$parsedUrl = mb_parse_url($url);
	if (isset($parsedUrl['query'])) {
		mb_parse_str($parsedUrl['query'], $queryArray);
		
		if (!empty($except)) {
			if (is_array($except)) {
				foreach ($except as $item) {
					if (isset($queryArray[$item])) {
						unset($queryArray[$item]);
					}
				}
			}
			if (is_string($except) || is_numeric($except)) {
				if (isset($queryArray[$except])) {
					unset($queryArray[$except]);
				}
			}
		}
	}
	
	return $queryArray;
}

/**
 * @info Depreciated - This function will be removed in the next updated
 *
 * @param string|null $path
 * @param array|null $attributes
 * @param null $locale
 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\UrlGenerator|string
 */
function lurl(string $path = null, ?array $attributes = [], $locale = null)
{
	return url($path);
}

/**
 * Check local environment
 *
 * @param string|null $url
 * @return bool
 */
function isLocalEnv(string $url = null): bool
{
	if (empty($url)) {
		$url = config('app.url');
	}
	
	return (
		str_contains($url, '127.0.0.1')
		|| str_contains($url, '::1')
		|| (!str_contains($url, '.'))
		|| str_ends_with(getDomain($url), '.local')
		|| str_ends_with(getDomain($url), '.localhost')
	);
}

/**
 * Human-readable file size
 *
 * @param $bytes
 * @param int $decimals
 * @param string $system (metric OR binary)
 * @return string
 */
function readableBytes($bytes, int $decimals = 2, string $system = 'binary')
{
	if (!is_numeric($bytes)) {
		return $bytes;
	}
	
	$mod = ($system === 'binary') ? 1024 : 1000;
	
	$units = [
		'binary' => [
			'B',
			'KiB',
			'MiB',
			'GiB',
			'TiB',
			'PiB',
			'EiB',
			'ZiB',
			'YiB',
		],
		'metric' => [
			'B',
			'kB',
			'MB',
			'GB',
			'TB',
			'PB',
			'EB',
			'ZB',
			'YB',
		],
	];
	
	$factor = floor((strlen($bytes) - 1) / 3);
	$unit = $units[$system][$factor] ?? $units['binary'][$factor];
	$bytes = $bytes / pow($mod, $factor);
	
	$bytes = \App\Helpers\Number::format($bytes, $decimals);
	
	return $bytes . $unit;
}

/**
 * Check if value is uploaded file data
 *
 * @param $value
 * @return bool
 */
function isUploadedFile($value): bool
{
	if (
		($value instanceof UploadedFile)
		|| (is_string($value) && str_starts_with($value, 'data:image'))
	) {
		return true;
	}
	
	return false;
}

/**
 * Get the uploaded file mime type
 *
 * @param \Illuminate\Http\UploadedFile|string|null $value
 * @return string|null
 */
function getUploadedFileMimeType(UploadedFile|string|null $value): ?string
{
	$mimeType = null;
	
	if (!is_string($value)) {
		if ($value instanceof UploadedFile) {
			$mimeType = $value->getMimeType();
		}
	} else {
		if (str_starts_with($value, 'data:image')) {
			try {
				$mimeType = mime_content_type($value);
			} catch (\Throwable $e) {
			}
		}
		
		if (empty($mimeType)) {
			$mimeType = 'image/jpeg';
		}
	}
	
	return strtolower($mimeType);
}

/**
 * Get the uploaded file extension
 *
 * @param \Illuminate\Http\UploadedFile|string|null $value
 * @return string|null
 */
function getUploadedFileExtension(UploadedFile|string|null $value): ?string
{
	$extension = null;
	
	if (!is_string($value)) {
		if ($value instanceof UploadedFile) {
			$extension = $value->getClientOriginalExtension();
		}
	} else {
		if (str_starts_with($value, 'data:image')) {
			$matches = [];
			preg_match('#data:image/([^;]+);base64#', $value, $matches);
			$extension = !empty($matches[1]) ? $matches[1] : 'png';
		} else {
			$extension = file_extension($value);
		}
	}
	
	return strtolower($extension);
}

/**
 * Check tld is a valid tld
 *
 * @param string|null $url
 * @return bool
 */
function checkTld(?string $url): bool
{
	if (empty($url)) {
		return false;
	}
	
	$parsedUrl = parse_url($url);
	if ($parsedUrl === false) {
		return false;
	}
	
	$tldArray = getTopLevelDomainRefList();
	$patten = implode('|', array_keys($tldArray));
	
	$matched = preg_match('/\.(' . $patten . ')$/i', $parsedUrl['host']);
	
	return (bool)$matched;
}

/**
 * Json To Array
 * NOTE: Used for MySQL Json and Laravel array (casts) columns
 *
 * @param array|object|string $string
 * @return array|mixed
 */
function jsonToArray($string)
{
	if (is_array($string)) {
		return $string;
	}
	
	if (is_object($string)) {
		return Arr::fromObject($string);
	}
	
	if (isJson($string)) {
		$array = json_decode($string, true);
		// If the JSON was encoded in JSON by mistake
		if (!is_array($array)) {
			return jsonToArray($array);
		}
	} else {
		$array = [];
	}
	
	return $array;
}

/**
 * Check if variable contains (valid) JSON data
 *
 * @param $string
 * @return bool
 */
function isJson($string): bool
{
	return (is_string($string) && str($string)->isJson());
}

/**
 * Run artisan config cache
 *
 * @return mixed
 */
function artisanConfigCache()
{
	// Artisan config:cache generate the following two files
	// Since config:cache runs in the background
	// to determine if it is done, we just check if the files modified time have been changed
	$files = ['bootstrap/cache/config.php', 'bootstrap/cache/services.php'];
	
	// get the last modified time of the files
	$last = 0;
	foreach ($files as $file) {
		$path = base_path($file);
		if (file_exists($path)) {
			if (filemtime($path) > $last) {
				$last = filemtime($path);
			}
		}
	}
	
	// Prepare to run (5 seconds for $timeout)
	$timeout = 5;
	$start = time();
	
	// Actually call the Artisan command
	$exitCode = Artisan::call('config:cache');
	
	// Check if Artisan call is done
	while (true) {
		// Just finish if timeout
		if (time() - $start >= $timeout) {
			echo "Timeout\n";
			break;
		}
		
		// If any file is still missing, keep waiting
		// If any file is not updated, keep waiting
		// @todo: services.php file keeps unchanged after artisan config:cache
		foreach ($files as $file) {
			$path = base_path($file);
			if (!file_exists($path)) {
				sleep(1);
				continue;
			} else {
				if (filemtime($path) == $last) {
					sleep(1);
					continue;
				}
			}
		}
		
		// Just wait another extra 3 seconds before finishing
		sleep(3);
		break;
	}
	
	return $exitCode;
}

/**
 * Run artisan migrate
 *
 * @return mixed
 */
function artisanMigrate()
{
	return Artisan::call('migrate', ["--force" => true]);
}

/**
 * @return string
 */
function vTime(): string
{
	$timeStamp = '?v=' . time();
	if (app()->environment(['staging', 'production'])) {
		$timeStamp = '';
	}
	
	return $timeStamp;
}

/**
 * Check if a phone number is valid (for a given country)
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @param string|null $type
 * @return bool
 */
function isValidPhoneNumber(?string $phone, ?string $countryCode = null, ?string $type = null): bool
{
	if (empty($phone) || empty($countryCode)) {
		return false;
	}
	
	$phone = normalizePhoneNumber($phone);
	
	try {
		$validator = phone($phone, $countryCode);
		$isValid = $validator->isOfCountry($countryCode);
		if (!empty($type)) {
			$isValid = $validator->isOfType($type);
		}
	} catch (\Throwable $e) {
		$isValid = false;
	}
	
	return $isValid;
}

/**
 * Get Phone's National Format
 *
 * Example: BE: 012/34.56.78 => 012 34 56 78
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return string|null
 */
function phoneNational(?string $phone, ?string $countryCode = null): ?string
{
	$phone = normalizePhoneNumber($phone);
	
	try {
		$phone = phone($phone, $countryCode)->formatNational();
	} catch (\Throwable $e) {
		// Keep the default value
	}
	
	return $phone;
}

/**
 * Get Phone's E164 Format
 *
 * https://en.wikipedia.org/wiki/E.164
 * https://www.twilio.com/docs/glossary/what-e164
 *
 * Example: BE: 012 34 56 78 => +3212345678
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return string|null
 */
function phoneE164(?string $phone, ?string $countryCode = null): ?string
{
	$phone = normalizePhoneNumber($phone);
	
	try {
		$phone = phone($phone, $countryCode)->formatE164();
	} catch (\Throwable $e) {
		// Keep the default value
	}
	
	return $phone;
}

/**
 * Get Phone's International Format
 * Don't need to be saved in database
 *
 * Example: BE: 012 34 56 78 => +32 12 34 56 78
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return string|null
 */
function phoneIntl(?string $phone, ?string $countryCode = null): ?string
{
	$phone = normalizePhoneNumber($phone);
	
	try {
		$phone = phone($phone, $countryCode)->formatInternational();
	} catch (\Throwable $e) {
		// Keep the default value
	}
	
	return $phone;
}

/**
 * Get the script possible URL base
 *
 * @return string
 */
function getRawBaseUrl(): string
{
	// Get the Laravel App public path name
	$laravelPublicPath = trim(public_path(), '/');
	$laravelPublicPathLabel = last(explode('/', $laravelPublicPath));
	
	// Get Server Variables
	$httpHost = (trim(request()->server('HTTP_HOST')) != '') ? request()->server('HTTP_HOST') : ($_SERVER['HTTP_HOST'] ?? '');
	$requestUri = (trim(request()->server('REQUEST_URI')) != '') ? request()->server('REQUEST_URI') : ($_SERVER['REQUEST_URI'] ?? '');
	
	// Clear the Server Variables
	$httpHost = trim($httpHost, '/');
	$requestUri = trim($requestUri, '/');
	$requestUri = (mb_substr($requestUri, 0, strlen($laravelPublicPathLabel)) === $laravelPublicPathLabel) ? '/' . $laravelPublicPathLabel : '';
	
	// Get the Current URL
	$currentUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://') . $httpHost . strtok($requestUri, '?');
	$currentUrl = head(explode('/' . admin_uri(), $currentUrl));
	
	// Get the Base URL
	$baseUrl = head(explode('/install', $currentUrl));
	
	return rtrim($baseUrl, '/');
}

/**
 * Get the current request path by pattern
 *
 * @param string|null $pattern
 * @return string
 */
function getRequestPath(string $pattern = null): string
{
	if (empty($pattern)) {
		return request()->path();
	}
	
	$pattern = '#(' . $pattern . ')#ui';
	
	$matches = [];
	preg_match($pattern, request()->path(), $matches);
	
	return (!empty($matches[1])) ? $matches[1] : request()->path();
}

/**
 * Get random password
 *
 * @param int $length
 * @return string
 */
function getRandomPassword(int $length): string
{
	$allowedCharacters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!$%^&#!$%^&#';
	$random = str_shuffle($allowedCharacters);
	$password = substr($random, 0, $length);
	
	if (empty($password)) {
		$password = Str::random($length);
	}
	
	return $password;
}

/**
 * Get a unique code
 *
 * @param int $limit
 * @return string
 */
function uniqueCode(int $limit): string
{
	$uniqueCode = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
	
	if (empty($uniqueCode)) {
		$uniqueCode = Str::random($limit);
	}
	
	return $uniqueCode;
}

/**
 * Get locale without codeset|encoding
 * Examples: de_CH.UTF-8 => de_CH, en_GB.ISO8859-15 => en_GB, ...
 *
 * @param string|null $locale
 * @param bool $nullable
 * @return string|null
 */
function removeLocaleCodeset(?string $locale, bool $nullable = true): ?string
{
	$default = 'en_US';
	$default = getAsString(config('app.locale'), $default);
	$default = $nullable ? null : (!str_contains($default, '.') ? $default : 'en_US');
	
	if (empty($locale)) {
		return $default;
	}
	
	if (str_contains($locale, '.')) {
		$tmp = explode('.', $locale);
		$locale = current($tmp);
	}
	
	return getAsString($locale, $default);
}

/**
 * Get locale without country code
 * Examples: de_CH => de, en_GB => en, ...
 *
 * @param string|null $locale
 * @param bool $nullable
 * @return string|null
 */
function getPrimaryLocaleCode(?string $locale, bool $nullable = true): ?string
{
	$default = $nullable ? null : 'en';
	
	$locale = removeLocaleCodeset($locale, $nullable);
	if (extension_loaded('intl') && class_exists('\Locale')) {
		return \Locale::getPrimaryLanguage($locale);
	}
	if (isRegionalLocaleCode($locale)) {
		$locale = str($locale)->substr(0, -3)->toString();
	}
	
	return getAsString($locale, $default);
}

/**
 * Get a language name from its ISO code
 * Examples: de => German, ro => Romanian, ...
 *
 * Note: Use the getRegionalLocaleName() function instead for more options
 *
 * @param string|null $code
 * @param bool $nullable
 * @return string|null
 */
function getPrimaryLocaleName(?string $code, bool $nullable = true): ?string
{
	$default = $nullable ? null : (!empty($code) ? $code : 'English');
	
	if (empty($code)) {
		return $default;
	}
	
	// Get language list
	$languages = getLanguageRefList();
	
	$code = getPrimaryLocaleCode($code, $nullable);
	$name = $languages[$code] ?? $default;
	
	return getAsString($name, $default);
}

/**
 * Get a language regional locale from its ISO code (or from its locale)
 * Examples: de => de_DE, ro => ro_RO, ...
 *
 * @param string|null $locale
 * @param bool $nullable
 * @return string|null
 */
function getRegionalLocaleCode(?string $locale, bool $nullable = true): ?string
{
	$default = $nullable ? null : (!empty($locale) ? $locale : 'en_US');
	
	if (empty($locale)) {
		return $default;
	}
	
	if (isRegionalLocaleCode($locale)) {
		return $locale;
	}
	
	// Get languages linked to their main country
	$isoLanguageCountries = getLanguagesLinkedToTheirMainCountry();
	
	if (!empty($isoLanguageCountries[$locale]['locale'])) {
		$locale = $isoLanguageCountries[$locale]['locale'];
	}
	
	return getAsString($locale, $default);
}

/**
 * Get a language regional locale name from its ISO code (or from its locale)
 * Examples: de_CH => German (Switzerland), en_GB => English (United Kingdom), ...
 *
 * @param string|null $locale
 * @param bool $nullable
 * @return string|null
 */
function getRegionalLocaleName(?string $locale, bool $nullable = true): ?string
{
	if (!isRegionalLocaleCode($locale)) {
		return getPrimaryLocaleName($locale, $nullable);
	}
	
	// Get localized locale
	$locale = getRegionalLocaleCode($locale, $nullable);
	
	// Get locales with name
	$localesWithName = getLocalesWithName('merged', false);
	
	$name = $localesWithName[$locale] ?? $locale;
	
	return getAsString($name, $locale);
}

/**
 * Check if a language ISO code (or locale) is regional
 * Examples: de_CH => true, de => false, ...
 *
 * @param string|null $locale
 * @return bool
 */
function isRegionalLocaleCode(?string $locale): bool
{
	if (empty($locale)) {
		return false;
	}
	
	if (extension_loaded('intl') && class_exists('\Locale')) {
		return !empty(\Locale::getRegion($locale));
	}
	$countryCodeList = array_keys(getCountryRefList());
	$tmp = explode('_', $locale);
	$countryCode = end($tmp);
	
	return in_array($countryCode, $countryCodeList);
}

if (!function_exists('getLangTag')) {
	/**
	 * Get locale's language tag
	 * Example: en-US, pt-BR, fr-CA, ... (Usage of "-" instead of "_")
	 *
	 * The language tag syntax is defined by the IETF's BCP 47
	 * Info: https://www.w3.org/International/articles/language-tags/
	 *
	 * @param string|null $locale
	 * @param bool $nullable
	 * @return string|null
	 */
	function getLangTag(?string $locale = null, bool $nullable = true): ?string
	{
		$default = 'en-US';
		$default = getAsString(config('app.locale'), $default);
		$default = $nullable ? null : (!str_contains($default, '_') ? $default : 'en');
		
		if (empty($locale)) {
			return $default;
		}
		
		$locale = str_replace('_', '-', $locale);
		$locale = getAsString($locale, $default);
		
		return removeLocaleCodeset($locale, $nullable);
	}
}

/**
 * @param string $url
 * @param string $string
 * @param int $length
 * @param string $attributes
 * @return string
 */
function linkStrLimit(string $url, string $string, int $length = 0, string $attributes = ''): string
{
	if (!is_string($attributes)) {
		$attributes = '';
	}
	
	if (!empty($attributes)) {
		$attributes = ' ' . $attributes;
	}
	
	$tooltip = '';
	if (is_numeric($length) && $length > 0 && str($string)->length() > $length) {
		$tooltip = ' data-bs-toggle="tooltip" title="' . $string . '"';
	}
	
	$out = '<a href="' . $url . '"' . $attributes . $tooltip . '>';
	if ($length > 0) {
		$out .= str($string)->limit($length);
	} else {
		$out .= $string;
	}
	$out .= '</a>';
	
	return $out;
}

/**
 * Get Translation from Column (from Json, Array or String)
 *
 * @param $column
 * @param string|null $locale
 * @return string
 */
function getColumnTranslation($column, string $locale = null): string
{
	if (empty($locale)) {
		$locale = app()->getLocale();
	}
	
	if (!is_array($column)) {
		if (isJson($column)) {
			$column = json_decode($column, true);
		} else {
			$column = [$column];
		}
	}
	
	$fallbackLocale = config('app.fallback_locale');
	$translation = $column[$locale] ?? ($column[$fallbackLocale] ?? head($column));
	
	return getAsString($translation);
}

/**
 * Convert a full path to a relative path
 * Old name: relativeAppPath
 *
 * @param string|null $path
 * @return string
 */
function getRelativePath(?string $path): string
{
	$documentRoot = request()->server('DOCUMENT_ROOT');
	$path = str_replace($documentRoot, '', $path);
	
	$basePath = base_path();
	$path = str_replace($basePath, '', $path);
	
	return (!empty($path) && is_string($path)) ? $path : '/';
}

/**
 * Parse the HTTP Accept-Language header
 * NOTE: Get the preferred language: $firstKey = array_key_first($array);
 *
 * @param string|null $acceptLanguage
 * @return array
 */
function parseAcceptLanguageHeader(string $acceptLanguage = null): array
{
	if (empty($acceptLanguage)) {
		$acceptLanguage = request()->server('HTTP_ACCEPT_LANGUAGE');
	}
	
	$acceptLanguageTab = explode(',', $acceptLanguage);
	
	$array = [];
	if (!empty($acceptLanguageTab)) {
		foreach ($acceptLanguageTab as $key => $value) {
			$tmp = explode(';', $value);
			if (empty($tmp)) continue;
			
			if (isset($tmp[0]) && isset($tmp[1])) {
				$q = str_replace('q=', '', $tmp[1]);
				$array[$tmp[0]] = (double)$q;
			} else {
				$array[$tmp[0]] = 1;
			}
		}
	}
	arsort($array);
	
	return $array;
}

/**
 * Get Google Maps Embed URL
 * https://developers.google.com/maps/documentation/embed/get-started
 * https://developers.google.com/maps/documentation/embed/embedding-map
 *
 * @param string|null $apiKey
 * @param string|null $q
 * @param string|null $language
 * @return string
 */
function getGoogleMapsEmbedUrl(?string $apiKey, ?string $q, ?string $language = null): string
{
	$baseUrl = 'https://www.google.com/maps/embed/v1/place';
	
	$query = [
		'key'      => $apiKey,
		'q'        => $q,
		'zoom'     => 9,         // Values ranging from 0 (the whole world) to 21 (individual buildings)
		'maptype'  => 'roadmap', // roadmap (default) or satellite
		'language' => $language ?? config('app.locale', 'en'),
	];
	
	$url = $baseUrl . '?' . Arr::query($query);
	
	return html_entity_decode($url);
}

/**
 * During a cURL request (using the Laravel HTTP Client),
 * Should the request be retried?
 *
 * Note:
 * - The initial request encounters can be a ConnectionException, then the request can be retried.
 * - The request can also be retried, for GET request, when the exception error contains:
 *   "cURL error 28: Connection timed out after {x} milliseconds"
 * - Don't retry in the other cases
 * - More info: https://laravel.com/docs/master/http-client#retries
 *
 * @param \Exception $e
 * @param \Illuminate\Http\Client\PendingRequest $request
 * @param string|null $method
 * @return bool
 */
function shouldHttpRequestBeRetried(Exception $e, PendingRequest $request, ?string $method = null): bool
{
	// cURL error found
	$msg = $e->getMessage();
	$isHttpGetRequest = (!empty($method) && strtolower($method) == 'get');
	$isTimeoutError = (str_contains($msg, 'cURL') && str_contains($msg, 'Connection'));
	$isTimeoutError = ($isTimeoutError && $isHttpGetRequest);
	
	// Connection exception encountered
	$isConnectionException = ($e instanceof ConnectionException);
	
	return ($isConnectionException || $isTimeoutError);
}

/**
 * Parse and get error from HTTP client request's exception or response as string
 *
 * @param $exceptionOrResponse
 * @return string
 */
function parseHttpRequestError($exceptionOrResponse): string
{
	if (is_string($exceptionOrResponse)) {
		return $exceptionOrResponse;
	}
	
	$message = null;
	
	if (
		$exceptionOrResponse instanceof Throwable
		&& method_exists($exceptionOrResponse, 'getMessage')
	) {
		$message = $exceptionOrResponse->getMessage();
	}
	
	if ($exceptionOrResponse instanceof \Illuminate\Http\Client\Response) {
		$responseErrorMessage = null;
		
		if (method_exists($exceptionOrResponse, 'reason')) {
			try {
				$responseErrorMessage = $exceptionOrResponse->reason();
			} catch (\Exception $e) {
			}
		}
		if (empty($responseErrorMessage)) {
			if (method_exists($exceptionOrResponse, 'json')) {
				try {
					$responseErrorMessage = $exceptionOrResponse->json();
				} catch (\Exception $e) {
				}
			}
		}
		if (empty($responseErrorMessage)) {
			if (method_exists($exceptionOrResponse, 'body')) {
				try {
					$responseErrorMessage = $exceptionOrResponse->body();
				} catch (\Exception $e) {
				}
			}
		}
		if (!empty($responseErrorMessage)) {
			$message = $responseErrorMessage;
		}
	}
	
	if (is_array($message)) {
		$message = json_encode($message);
	}
	if (is_string($message)) {
		$message = strip_tags($message);
	}
	if (empty($message) || !is_string($message)) {
		$message = 'Failed to get the request\'s data.';
	}
	
	return $message;
}

/**
 * Deprecated
 *
 * @param $response
 * @return string
 */
function getCurlHttpError($response): string
{
	return parseHttpRequestError($response);
}

function isValidHttpStatus($code): bool
{
	$code = is_numeric($code) ? $code : 500;
	
	return array_key_exists($code, \Illuminate\Http\Response::$statusTexts);
}

/**
 * SystemLocale Object
 * Note: This is different to the app()->setLocale() and app()->getLocale() that are related to the app's locale
 *
 * @return \App\Helpers\SystemLocale
 */
function systemLocale(): SystemLocale
{
	return new SystemLocale();
}

/**
 * API Response Object
 *
 * @return \App\Helpers\Response\Api
 */
function apiResponse(): Api
{
	return new Api();
}

/**
 * AJAX Response Object
 *
 * @return \App\Helpers\Response\Ajax
 */
function ajaxResponse(): Ajax
{
	return new Ajax();
}

/**
 * @param string|null $charset
 * @return bool
 */
function isCharsetConflictFound(?string $charset = null): bool
{
	if (empty($charset)) {
		$charset = config('larapen.core.charset', 'utf-8');
	}
	
	$systemCharset = @ini_get('default_charset');
	$systemCharset = is_string($systemCharset) ? $systemCharset : '';
	
	return (strtolower($charset) != strtolower($systemCharset));
}

/**
 * Add Content-Type Header (Only if missing)
 *
 * @param string $type
 * @param array|null $headers
 * @return array
 */
function addContentTypeHeader(string $type, ?array $headers = []): array
{
	$headers = is_array($headers) ? $headers : [];
	
	$charset = config('larapen.core.charset', 'utf-8');
	$defaultHeaders = ['Content-Type' => $type . '; charset=' . strtoupper($charset)];
	
	return array_merge($defaultHeaders, $headers);
}

/**
 * @param array|null $referrers
 * @param bool $nullable
 * @return bool
 */
function isFromValidReferrer(?array $referrers = [], bool $nullable = false): bool
{
	if (empty($referrers)) {
		$referrers = [get_url_host(url('/'))];
	}
	
	$isFromValidReferrer = false;
	
	$httpReferrer = request()->server('HTTP_REFERER');
	if ($nullable && empty($httpReferrer)) {
		return true;
	}
	
	foreach ($referrers as $referrer) {
		$isPattern = (
			str_contains($referrer, 'https?')
			|| str_contains($referrer, '.*')
			|| str_contains($referrer, '\.')
		);
		
		// Check to see what the referrer is
		$isFromValidReferrer = $isPattern
			? preg_match('|' . $referrer . '|ui', $httpReferrer)
			: str_contains($httpReferrer, $referrer);
		if ($isFromValidReferrer) {
			break;
		}
	}
	
	return $isFromValidReferrer;
}
