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

namespace App\Helpers;

use Illuminate\Support\Carbon;

/*
 * The system locale needs to be set in the 'AppServiceProvider'
 * by calling this method: systemLocale()->setLocale($locale);
 */

class Date
{
	/**
	 * Get Time Zone List
	 *
	 * @param null $countryCode
	 * @return array
	 */
	public static function getTimeZones($countryCode = null): array
	{
		$timeZones = [];
		
		try {
			$timeZones = !empty($countryCode)
				? \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $countryCode)
				: \DateTimeZone::listIdentifiers();
		} catch (\Throwable $e) {
		}
		
		if (empty($timeZones)) {
			$timeZones = getTimeZoneRefList();
		}
		
		return collect($timeZones)
			->mapWithKeys(fn ($tz) => [$tz => $tz])
			->toArray();
	}
	
	/**
	 * Get the App's current Time Zone
	 *
	 * @return string
	 */
	public static function getAppTimeZone(): string
	{
		$tz = config('ipCountry.time_zone', config('country.time_zone'));
		$tz = isAdminPanel() ? config('app.timezone') : $tz;
		
		$guard = isFromApi() ? 'sanctum' : null;
		$authUser = auth($guard)->user();
		
		if (!empty($authUser)) {
			$tz = !empty($authUser->time_zone) ? $authUser->time_zone : $tz;
		}
		
		$tz = self::isValidTimeZone($tz) ? $tz : 'UTC';
		
		return getAsString($tz);
	}
	
	/**
	 * Format the instance with the current locale. You can set the current
	 * locale using setlocale() https://www.php.net/manual/en/function.setlocale.php
	 *
	 * @param $value
	 * @param string $dateType
	 * @return \Illuminate\Support\Carbon|string
	 */
	public static function format($value, string $dateType = 'date')
	{
		if ($value instanceof Carbon) {
			$dateFormat = self::getAppDateFormat($dateType);
			
			try {
				if (self::isIsoFormat($dateFormat)) {
					$value = $value->isoFormat($dateFormat);
				} else {
					$value = $value->translatedFormat($dateFormat);
				}
			} catch (\Throwable $e) {
			}
		}
		
		return $value;
	}
	
	/**
	 * @param $value
	 * @return \Illuminate\Support\Carbon|string
	 */
	public static function formatFormNow($value)
	{
		if (!$value instanceof Carbon) {
			return $value;
		}
		
		$formattedDate = self::format($value, 'datetime');
		
		$isFromPostsList = (
			config('settings.list.elapsed_time_from_now')
			&& (
				(
					isFromApi()
					&& (
						str_contains(currentRouteAction(), 'Api\PostController@index')
						|| str_contains(currentRouteAction(), 'Api\HomeSectionController')
						|| str_contains(currentRouteAction(), 'Api\SavedSearchController')
					)
				)
				|| (
					!isFromApi()
					&& (
						str_contains(currentRouteAction(), 'Search\\')
						|| str_contains(currentRouteAction(), 'HomeController')
						|| str_contains(currentRouteAction(), 'Account\\')
					)
				)
			)
		);
		
		$isFromPostDetails = (
			config('settings.single.elapsed_time_from_now')
			&& (
				(isFromApi() && (str_contains(currentRouteAction(), 'Api\PostController@show')))
				|| (!isFromApi() && (str_contains(currentRouteAction(), 'Post\ShowController')))
			)
		);
		
		try {
			if ($isFromPostsList) {
				if (doesRequestIsFromWebApp()) {
					$popover = ' data-bs-container="body"';
					$popover .= ' data-bs-toggle="popover"';
					$popover .= ' data-bs-trigger="hover"';
					$popover .= ' data-bs-placement="bottom"';
					$popover .= ' data-bs-content="' . $formattedDate . '"';
					
					if (config('lang.direction') == 'rtl') {
						$popover = ' data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . $formattedDate . '"';
					}
					
					$out = '<span style="cursor: help;"' . $popover . '>';
					$out .= $value->fromNow();
					$out .= '</span>';
					
					$value = $out;
				} else {
					$value = $value->fromNow();
				}
			} else if ($isFromPostDetails) {
				if (doesRequestIsFromWebApp()) {
					$popover = ' data-bs-container="body"';
					$popover .= ' data-bs-toggle="popover"';
					$popover .= ' data-bs-trigger="hover"';
					$popover .= ' data-bs-placement="bottom"';
					$popover .= ' data-bs-content="' . $formattedDate . '"';
					
					if (config('lang.direction') == 'rtl') {
						$popover = ' data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . $formattedDate . '"';
					}
					
					$out = '<span style="cursor: help;"' . $popover . '>';
					$out .= $value->fromNow();
					$out .= '</span>';
					
					$value = $out;
				} else {
					$value = $value->fromNow();
				}
			} else {
				$value = $formattedDate;
			}
		} catch (\Throwable $e) {
		}
		
		return $value;
	}
	
	/**
	 * Check if a time zone is valid for PHP
	 *
	 * @param $timeZoneId
	 * @return bool
	 */
	private static function isValidTimeZone($timeZoneId): bool
	{
		$timeZones = self::getTimeZones();
		
		return !empty($timeZones[$timeZoneId]);
	}
	
	/**
	 * Get the App Date Format
	 *
	 * @param string $dateType
	 * @return string
	 */
	private static function getAppDateFormat(string $dateType = 'date'): string
	{
		$adminDateFormat = ($dateType == 'datetime')
			? config('settings.app.datetime_format', config('larapen.core.datetimeFormat.default'))
			: config('settings.app.date_format', config('larapen.core.dateFormat.default'));
		
		$langFrontDateFormat = ($dateType == 'datetime') ? config('lang.datetime_format') : config('lang.date_format');
		$frontDateFormat = !empty($langFrontDateFormat) ? $langFrontDateFormat : $adminDateFormat;
		
		$countryFrontDateFormat = ($dateType == 'datetime') ? config('country.datetime_format') : config('country.date_format');
		$frontDateFormat = !empty($countryFrontDateFormat) ? $countryFrontDateFormat : $frontDateFormat;
		
		$dateFormat = isAdminPanel() ? $adminDateFormat : $frontDateFormat;
		
		if (empty($dateFormat)) {
			$dateFormat = ($dateType == 'datetime') ? config('larapen.core.datetimeFormat.default') : config('larapen.core.dateFormat.default');
		}
		
		// For stats short dates
		if ($dateType == 'stats') {
			$dateFormat = !config('settings.app.php_specific_date_format') ? 'MMM DD' : '%b %d';
		}
		
		// For backup dates
		if ($dateType == 'backup') {
			$dateFormat = !config('settings.app.php_specific_date_format') ? 'DD MMMM YYYY, HH:mm' : '%d %B %Y, %H:%M';
		}
		
		if (str_contains($dateFormat, '%')) {
			$dateFormat = self::strftimeToDateFormat($dateFormat);
		}
		
		if (!is_string($dateFormat)) {
			$dateFormat = !config('settings.app.php_specific_date_format')
				? (($dateType == 'datetime') ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD')
				: (($dateType == 'datetime') ? '%Y-%m-%d %H:%M' : '%Y-%m-%d');
		}
		
		return $dateFormat;
	}
	
	/**
	 * Equivalent to `date_format_to( $format, 'date' )`
	 *
	 * @param string $strfFormat A `strftime()` date/time format
	 * @return bool|string
	 */
	private static function strftimeToDateFormat(string $strfFormat): bool|string
	{
		return self::dateFormatTo($strfFormat, 'date');
	}
	
	/**
	 * Equivalent to `convert_datetime_format_to( $format, 'strf' )`
	 *
	 * @param string $dateFormat A `date()` date/time format
	 * @return bool|string
	 */
	private static function dateToStrftimeFormat(string $dateFormat): bool|string
	{
		return self::dateFormatTo($dateFormat, 'strf');
	}
	
	/**
	 * Convert date/time format between `date()` and `strftime()`
	 *
	 * Timezone conversion is done for Unix. Windows users must exchange %z and %Z.
	 *
	 * Unsupported date formats : S, n, t, L, B, G, u, e, I, P, Z, c, r
	 * Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x
	 *
	 * @param string $format The format to parse.
	 * @param string $syntax The format's syntax. Either 'strf' for `strtime()` or 'date' for `date()`.
	 * @return bool|string Returns a string formatted according $syntax using the given $format or `false`.
	 * @link http://php.net/manual/en/function.strftime.php#96424
	 *
	 * @example Convert `%A, %B %e, %Y, %l:%M %P` to `l, F j, Y, g:i a`, and vice versa for "Saturday, March 10, 2001, 5:16 pm"
	 */
	private static function dateFormatTo(string $format, string $syntax): bool|string
	{
		// http://php.net/manual/en/function.strftime.php
		$strfSyntax = [
			// Day - no strf eq : S (created one called %O)
			'%O', '%d', '%a', '%e', '%A', '%u', '%w', '%j',
			// Week - no date eq : %U, %W
			'%V',
			// Month - no strf eq : n, t
			'%B', '%m', '%b', '%-m',
			// Year - no strf eq : L; no date eq : %C, %g
			'%G', '%Y', '%y',
			// Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
			'%P', '%p', '%l', '%I', '%H', '%M', '%S',
			// Timezone - no strf eq : e, I, P, Z
			'%z', '%Z',
			// Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
			'%s',
		];
		
		// http://php.net/manual/en/function.date.php
		$dateSyntax = [
			'S', 'd', 'D', 'j', 'l', 'N', 'w', 'z',
			'W',
			'F', 'm', 'M', 'n',
			'o', 'Y', 'y',
			'a', 'A', 'g', 'h', 'H', 'i', 's',
			'O', 'T',
			'U',
		];
		
		switch ($syntax) {
			case 'date':
				$from = $strfSyntax;
				$to = $dateSyntax;
				break;
			
			case 'strf':
				$from = $dateSyntax;
				$to = $strfSyntax;
				break;
			
			default:
				return false;
		}
		
		$pattern = array_map(
			function ($s) {
				return '/(?<!\\\\|\%)' . $s . '/';
			},
			$from
		);
		
		$format = preg_replace($pattern, $to, $format);
		
		return getAsString($format);
	}
	
	/**
	 * Check if the format is a valid ISO format
	 *
	 * @param $format
	 * @return bool
	 */
	public static function isIsoFormat($format): bool
	{
		$isIsoFormat = false;
		
		$splitChars = preg_split('/( |-|\/|\.|,|:|;)/', $format);
		$splitChars = array_filter($splitChars);
		
		if (!empty($splitChars)) {
			foreach ($splitChars as $char) {
				if (in_array($char, self::diffBetweenIsoAndDateTimeFormats())) {
					$isIsoFormat = true;
					break;
				}
			}
		}
		
		return $isIsoFormat;
	}
	
	/**
	 * Difference between the ISO and the DateTime formats
	 *
	 * @return array
	 */
	private static function diffBetweenIsoAndDateTimeFormats(): array
	{
		return array_diff(self::isoFormatReplacement(), self::dateTimeFormatReplacement());
	}
	
	/**
	 * Date ISO format replacement
	 * https://carbon.nesbot.com/docs/#api-localization
	 *
	 * @return string[]
	 */
	private static function isoFormatReplacement(): array
	{
		return [
			'OD', 'OM', 'OY', 'OH', 'Oh', 'Om', 'Os', 'D', 'DD', 'Do',
			'd', 'dd', 'ddd', 'dddd', 'DDD', 'DDDD', 'DDDo', 'e', 'E',
			'H', 'HH', 'h', 'hh', 'k', 'kk', 'm', 'mm', 'a', 'A', 's', 'ss', 'S', 'SS', 'SSS', 'SSSS', 'SSSSS', 'SSSSSS', 'SSSSSSS', 'SSSSSSSS', 'SSSSSSSSS',
			'M', 'MM', 'MMM', 'MMMM', 'Mo', 'Q', 'Qo',
			'G', 'GG', 'GGG', 'GGGG', 'GGGGG', 'g', 'gg', 'ggg', 'gggg', 'ggggg', 'W', 'WW', 'Wo', 'w', 'ww', 'wo',
			'x', 'X',
			'Y', 'YY', 'YYYY', 'YYYYY',
			'z', 'zz', 'Z', 'ZZ',
			// Macro-formats
			'LT', 'LTS', 'L', 'l', 'LL', 'll', 'LLL', 'lll', 'LLLL', 'llll',
		];
	}
	
	/**
	 * DateTime format replacement
	 * https://www.php.net/manual/en/datetime.format.php
	 *
	 * @return string[]
	 */
	private static function dateTimeFormatReplacement(): array
	{
		return [
			// Day
			'd', 'D', 'j', 'l', 'N', 'S', 'w', 'z',
			// Week
			'W',
			// Month
			'F', 'm', 'M', 'n', 't',
			// Year
			'L', 'o', 'Y', 'y',
			// Time
			'a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u', 'v',
			// Timezone
			'e', 'I', 'O', 'P', 'p', 'T', 'Z',
			// Full Date/Time
			'c', 'r', 'U',
		];
	}
	
	/**
	 * strftime format replacement
	 * https://www.php.net/manual/en/function.strftime.php
	 *
	 * @return string[]
	 */
	private static function strftimeFormatReplacement(): array
	{
		return [
			// Day
			'%a', '%A', '%d', '%e', '%j', '%u', '%w',
			// Week
			'%U', '%V', '%W',
			// Month
			'%b', '%B', '%h', '%m',
			// Year
			'%C', '%g', '%G', '%y', '%Y',
			// Time
			'%H', '%k', '%I', '%l', '%M', '%p', '%P', '%r', '%R', '%S', '%T', '%X', '%z', '%Z',
			// Time and Date Stamps
			'%c', '%D', '%F', '%s', '%x',
			// Miscellaneous
			'%n', '%t', '%%',
		];
	}
}
