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

class SystemLocale
{
	/**
	 * Set locale for the system (Override the PHP setlocale() function)
	 *
	 * USAGE: Call this method as soon as the app\'s locale is retrieved
	 *
	 * IMPORTANT: Prevent issue in Laravel Blade
	 * The Blade @if(...) statement doesn't convert to <?php if(...): ?> in Turkish language (for example).
	 *
	 * RESOURCES
	 * - https://www.php.net/manual/en/function.setlocale.php
	 * - https://stackoverflow.com/questions/43589501/if-statements-not-working-correctly-on-laravel-blade
	 * - https://docs.moodle.org/dev/Table_of_locales
	 * - https://stackoverflow.com/questions/3191664/list-of-all-locales-and-their-short-codes
	 * - https://github.com/umpirsky/locale-list
	 * - Get locales list in Terminal on MAC/Linux: locale -a
	 *
	 * NOTE: $category can be:
	 * LC_ALL for all of the below
	 * LC_COLLATE for string comparison, see strcoll()
	 * LC_CTYPE for character classification and conversion, for example strtoupper()
	 * LC_MONETARY for localeconv()
	 * LC_NUMERIC for decimal separator (See also localeconv())
	 * LC_TIME for date and time formatting with strftime()
	 * LC_MESSAGES for system responses (available if PHP was compiled with libintl)
	 *
	 * @param string|null $locale
	 * @param int|null $category
	 * @return string|null
	 */
	public static function setLocale(?string $locale, int $category = null): ?string
	{
		if (empty($locale)) {
			return null;
		}
		
		$categories = array_keys(self::getCategories());
		if (empty($category) || !in_array($category, $categories)) {
			$category = LC_ALL;
		}
		
		// Note from \Carbon\Carbon:
		// isoFormat() use ISO format rather than PHP-specific format
		// and use inner translations rather than language packages you need to install
		// on every machine where you deploy your application.
		if ($category == LC_TIME) {
			if (!config('settings.app.php_specific_date_format')) {
				Carbon::setLocale($locale);
				
				return $locale;
			}
		}
		
		$localeFound = false;
		
		// Get available locales from the server
		// $localeList = getLocales();
		$localeList = getLocales('installed');
		
		if (in_array($locale, $localeList)) {
			$definedLocales = setlocale($category, $locale);
			if ($definedLocales !== false) {
				Carbon::setLocale($locale);
				$localeFound = true;
			}
		}
		
		// If not found, try to use locale with codeset (If it exists)
		if (!$localeFound) {
			foreach ($localeList as $sysLocale) {
				/*
				 * Check if $locale exists on the server with a codeset (locale.codeset)
				 * e.g. tr_TR.UTF-8, ru_RU.UTF-8, ru_RU.ISO8859-5, fr_CH.ISO8859-15, ...
				 * More Info: https://stackoverflow.com/a/24355529
				 */
				$locale = removeLocaleCodeset($locale);
				if (str_starts_with($sysLocale, $locale)) {
					$definedLocales = setlocale($category, $sysLocale);
					if ($definedLocales !== false) {
						Carbon::setLocale($sysLocale);
						$localeFound = true;
						$locale = $sysLocale;
					}
				}
				
				if (!$localeFound) {
					$regionalLocale = getRegionalLocaleCode(getPrimaryLocaleCode($locale));
					if (empty($regionalLocale)) {
						continue;
					}
					
					if (in_array($regionalLocale, $localeList)) {
						$definedLocales = setlocale($category, $regionalLocale);
						if ($definedLocales !== false) {
							Carbon::setLocale($regionalLocale);
							$localeFound = true;
							$locale = $regionalLocale;
						}
					}
					
					if (!$localeFound) {
						if (str_starts_with($sysLocale, $regionalLocale)) {
							$definedLocales = setlocale($category, $regionalLocale);
							if ($definedLocales !== false) {
								Carbon::setLocale($regionalLocale);
								$localeFound = true;
								$locale = $sysLocale;
							}
						}
					}
				}
			}
		}
		
		// Reset the decimal separator
		if ($category == LC_ALL) {
			$definedNumericLocale = self::getLocale(LC_NUMERIC);
			if ($definedNumericLocale !== false) {
				if (!in_array($definedNumericLocale, ['C', 'en_US'])) {
					self::resetLcNumeric();
				}
			} else {
				self::resetLcNumeric();
			}
		}
		
		return $locale;
	}
	
	/**
	 * Get the defined locale related to a category
	 *
	 * Example:
	 * echo systemLocale()->getLocale(LC_ALL);
	 * LC_CTYPE=en_US.UTF-8;LC_NUMERIC=C;LC_TIME=C;LC_COLLATE=C;LC_MONETARY=C;LC_MESSAGES=C;LC_PAPER=C;LC_NAME=C;LC_ADDRESS=C;LC_TELEPHONE=C;LC_MEASUREMENT=C;LC_IDENTIFICATION=C
	 *
	 * echo systemLocale()->getLocale(LC_CTYPE);
	 * en_US.UTF-8
	 *
	 * setlocale(LC_ALL, "en_US.UTF-8");
	 * echo getLocale(LC_ALL);
	 * en_US.UTF-8
	 *
	 * @param int $category
	 * @return string|null
	 */
	public static function getLocale(int $category): ?string
	{
		$currentLocales = setlocale($category, 0);
		
		return ($currentLocales !== false) ? $currentLocales : null;
	}
	
	/**
	 * Get all the available categories
	 *
	 * @return array
	 */
	public static function getCategories(): array
	{
		$categories = [
			LC_ALL      => 'for all of the below',
			LC_COLLATE  => 'for string comparison, see strcoll()',
			LC_CTYPE    => 'for character classification and conversion, for example ctype_alpha()',
			LC_MONETARY => 'for localeconv()',
			LC_NUMERIC  => 'for decimal separator (See also localeconv())',
			LC_TIME     => 'for date and time formatting with strftime()',
		];
		
		if (defined('LC_MESSAGES')) {
			$categories[LC_MESSAGES] = 'for system responses (available if PHP was compiled with libintl)';
		}
		
		return $categories;
	}
	
	/**
	 * Fix for float number with incorrect decimal separator.
	 * e.g. Reset the decimal separator after having set the locale
	 *
	 * NOTE
	 * This reset to 'C' locale for numerical values tell the operating system and PHP,
	 * that the C/C++ type of decimal separator has to be used which is defined as '.' for programming languages.
	 * Now you get your locale based month names if you are using the ordinary PHP date() function,
	 * but you get also correct calculated and converted numerical values (but you lose your locale known decimal sep char).
	 *
	 * RESOURCES
	 * - https://stackoverflow.com/a/26649200
	 *
	 * @return void
	 */
	public static function resetLcNumeric(): void
	{
		$definedLocale = setlocale(LC_NUMERIC, 'C');
		if ($definedLocale === false) {
			setlocale(LC_NUMERIC, 'en_US');
		}
	}
}
