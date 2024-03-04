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

/*
 * For some methods of this class,
 * the system locale need to be set in the 'AppServiceProvider'
 * by calling this method: systemLocale()->setLocale($locale);
 */

class Number
{
	/**
	 * Converts a number into a short version, eg: 1000 -> 1K
	 *
	 * @param float|null $value
	 * @param int $precision
	 * @param bool $roundThousands
	 * @return string|null
	 */
	public static function short(float|null $value, int $precision = 1, bool $roundThousands = true): ?string
	{
		if (empty($value)) return '0';
		
		$format = fn($value, $suffix = '') => self::localeFormat($value, $precision) . $suffix;
		
		// Hundred (or less) | 0 - 900
		$limitFrom = ($roundThousands) ? 900 : 1000;
		if ($value < $limitFrom) {
			return $format($value);
		}
		
		// Thousands | 0.9K - 850K
		$limitFrom = ($roundThousands) ? 900000 : 1000000;
		if ($value < $limitFrom) {
			return $format($value / 1000, 'K');
		}
		
		// Millions | 0.9M - 850M
		$limitFrom = ($roundThousands) ? 900000000 : 1000000000;
		if ($value < $limitFrom) {
			return $format($value / 1000000, 'M');
		}
		
		// Billions | 0.9B - 850B
		$limitFrom = ($roundThousands) ? 900000000000 : 1000000000000;
		if ($value < $limitFrom) {
			return $format($value / 1000000000, 'B');
		}
		
		// Trillion | 0.9T - ...
		return $format($value / 1000000000000, 'T');
	}
	
	/**
	 * @param $value
	 * @param int $decimals
	 * @param bool $removeZeroAsDecimal
	 * @return string|null
	 */
	public static function localeFormat($value, int $decimals = 2, bool $removeZeroAsDecimal = true): ?string
	{
		// Convert string to numeric
		$value = self::getFloatRawFormat($value);
		
		if (!is_numeric($value)) return null;
		
		// Set locale for LC_NUMERIC (This is reset below)
		systemLocale()->setLocale(app()->getLocale(), LC_NUMERIC);
		
		// Get numeric formatting information & format '$value'
		$localeInfo = localeconv();
		$decPoint = $localeInfo['decimal_point'] ?? '.';
		$thousandsSep = $localeInfo['thousands_sep'] ?? ',';
		$value = number_format($value, $decimals, $decPoint, $thousandsSep);
		
		if ($removeZeroAsDecimal) {
			$value = self::removeZeroAsDecimal($value, $decimals, $decPoint);
		}
		
		systemLocale()->resetLcNumeric();
		
		return $value;
	}
	
	/**
	 * Transform the given number to display it using the Currency format settings
	 * NOTE: Transform non-numeric value
	 *
	 * @param $value
	 * @param int|null $decimals
	 * @param string|null $decPoint
	 * @param string|null $thousandsSep
	 * @param bool $removeZeroAsDecimal
	 * @return string|string[]|null
	 */
	public static function format($value, int $decimals = null, string $decPoint = null, string $thousandsSep = null, bool $removeZeroAsDecimal = true)
	{
		// Convert string to numeric
		$value = self::getFloatRawFormat($value);
		
		if (!is_numeric($value)) return null;
		
		$defaultCurrency = config('selectedCurrency', config('currency'));
		if (is_null($decimals)) {
			$decimals = (int)data_get($defaultCurrency, 'decimal_places', 2);
		}
		if (is_null($decPoint)) {
			$decPoint = data_get($defaultCurrency, 'decimal_separator', '.');
		}
		if (is_null($thousandsSep)) {
			$thousandsSep = data_get($defaultCurrency, 'thousand_separator', ',');
		}
		
		// Currency format - Ex: USD 100,234.56 | EUR 100 234,56
		$value = number_format($value, $decimals, $decPoint, $thousandsSep);
		
		if ($removeZeroAsDecimal) {
			$value = self::removeZeroAsDecimal($value, $decimals, $decPoint);
		}
		
		return $value;
	}
	
	/**
	 * Format a number before insert it in MySQL database
	 * NOTE: The DB column need to be decimal (or float)
	 *
	 * @param $value
	 * @param string $decPoint
	 * @param bool $canSaveZero
	 * @return int|string|string[]|null
	 */
	public static function formatForDb($value, string $decPoint = '.', bool $canSaveZero = true)
	{
		$value = preg_replace('/^[0\s]+(.+)$/', '$1', $value);  // 0123 => 123 | 00 123 => 123
		$value = preg_replace('/^[.]+/', '0.', $value);         // .123 => 0.123
		
		if ($canSaveZero) {
			$value = ($value == 0 && strlen(trim($value)) > 0) ? 0 : $value;
			if ($value === 0) {
				return $value;
			} else {
				if (empty($value)) {
					return $value;
				}
			}
		}
		
		if ($decPoint == '.') {
			// For string ending by '.000' like 'XX.000',
			// Replace the '.000' by ',000' like 'XX,000' before removing the thousands separator
			$value = preg_replace('/\.\s?(0{3}+)$/', ',$1', $value);
			
			// Remove eventual thousands separator
			$value = str_replace(',', '', $value);
		}
		if ($decPoint == ',') {
			// Remove eventual thousands separator
			$value = str_replace('.', '', $value);
			
			// Always save in DB decimals with dot (.) instead of comma (,)
			$value = str_replace(',', '.', $value);
		}
		
		// Skip only numeric and dot characters
		$value = preg_replace('/[^\d.]/', '', $value);
		
		// Use the first dot as decimal point (All the next dots will be ignored)
		$tmp = explode('.', $value);
		if (!empty($tmp)) {
			$value = $tmp[0] . (isset($tmp[1]) ? '.' . $tmp[1] : '');
		}
		
		if (empty($value)) {
			return null;
		}
		
		return $value;
	}
	
	/**
	 * Get Float Raw Format
	 *
	 * @param $value
	 * @return float|int|string|null
	 */
	public static function getFloatRawFormat($value)
	{
		if (is_numeric($value)) return $value;
		if (!is_string($value)) return null;
		
		$value = trim($value);
		$value = strtr($value, [' ' => '']);
		$value = preg_replace('/ +/', '', $value);
		$value = str_replace(',', '.', $value);
		$value = preg_replace('/[^\d.]/', '', $value);
		
		if (empty($value)) return null;
		
		return getAsString($value);
	}
	
	/**
	 * @param $value
	 * @param array|null $itemCurrency
	 * @return string
	 */
	public static function money($value, ?array $itemCurrency = []): string
	{
		$value = self::applyCurrencyRate($value, $itemCurrency);
		
		if (config('settings.other.decimals_superscript')) {
			return static::moneySuperscript($value);
		}
		
		$currency = !empty($itemCurrency) ? $itemCurrency : config('selectedCurrency', config('currency'));
		
		$decimals = (int)data_get($currency, 'decimal_places', 2);
		$decPoint = data_get($currency, 'decimal_separator', '.');
		$thousandsSep = data_get($currency, 'thousand_separator', ',');
		
		$value = self::format($value, $decimals, $decPoint, $thousandsSep);
		
		// In line current
		if (data_get($currency, 'in_left') == 1) {
			$value = data_get($currency, 'symbol') . $value;
		} else {
			$value = $value . ' ' . data_get($currency, 'symbol');
		}
		
		return getAsString($value);
	}
	
	/**
	 * @param $value
	 * @param array|null $itemCurrency
	 * @return string
	 */
	public static function moneySuperscript($value, ?array $itemCurrency = []): string
	{
		$value = self::format($value);
		$currency = !empty($itemCurrency) ? $itemCurrency : config('selectedCurrency', config('currency'));
		
		$decPoint = data_get($currency, 'decimal_separator', '.');
		$tmp = explode($decPoint, $value);
		
		if (!empty($tmp[1])) {
			if (data_get($currency, 'in_left') == 1) {
				$value = data_get($currency, 'symbol') . $tmp[0] . '<sup>' . $tmp[1] . '</sup>';
			} else {
				$value = $tmp[0] . '<sup>' . data_get($currency, 'symbol') . $tmp[1] . '</sup>';
			}
		} else {
			if (data_get($currency, 'in_left') == 1) {
				$value = data_get($currency, 'symbol') . $value;
			} else {
				$value = $value . ' ' . data_get($currency, 'symbol');
			}
		}
		
		return getAsString($value);
	}
	
	/**
	 * Remove decimal value if it's null
	 *
	 * Note:
	 * Remove unnecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
	 * Intentionally does not affect partials, eg "1.50" -> "1.50"
	 *
	 * @param $value
	 * @param int|null $decimals
	 * @param string|null $decPoint
	 * @return string|string[]
	 */
	public static function removeZeroAsDecimal($value, ?int $decimals = null, ?string $decPoint = null)
	{
		if ((int)$decimals <= 0) return $value;
		
		$decPoint ??= '.';
		
		$defaultDecimal = str_pad('', $decimals, '0');
		
		return str_replace($decPoint . $defaultDecimal, '', $value);
	}
	
	/**
	 * @param $value
	 * @param array|null $itemCurrency
	 * @return float|int|mixed|string
	 */
	public static function applyCurrencyRate($value, ?array $itemCurrency = [])
	{
		if (!is_numeric($value)) return $value;
		$currency = !empty($itemCurrency) ? $itemCurrency : config('selectedCurrency', config('currency'));
		
		try {
			$value = $value * data_get($currency, 'rate', 1);
		} catch (\Throwable $e) {
			// Debug
		}
		
		return $value;
	}
	
	/**
	 * Clean Float Value
	 * Fixed: MySQL don't accept the comma format number
	 *
	 * This function takes the last comma or dot (if any) to make a clean float,
	 * ignoring thousands separator, currency or any other letter.
	 *
	 * Example:
	 * $num = '1.999,369€';
	 * var_dump(Number::toFloat($num)); // float(1999.369)
	 * $otherNum = '126,564,789.33 m²';
	 * var_dump(Number::toFloat($otherNum)); // float(126564789.33)
	 *
	 * @param $value
	 * @return float
	 */
	public static function toFloat($value)
	{
		// Check negative numbers
		$isNegative = false;
		if (str_starts_with(trim($value), '-')) {
			$isNegative = true;
		}
		
		$dotPos = strrpos($value, '.');
		$commaPos = strrpos($value, ',');
		$sepPos = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
		
		if (!$sepPos) {
			$value = preg_replace('/\D/', '', $value);
			$value = floatval($value);
			
			if ($isNegative) {
				$value = '-' . $value;
			}
			
			return $value;
		}
		
		$integer = preg_replace('/\D/', '', substr($value, 0, $sepPos));
		$decimal = preg_replace('/\D/', '', substr($value, $sepPos + 1, strlen($value)));
		$decimal = rtrim($decimal, '0');
		
		if (intval($decimal) == 0) {
			$value = intval($integer);
		} else {
			$value = intval($integer) . '.' . $decimal;
		}
		
		if ($isNegative) {
			$value = '-' . $value;
		}
		
		return $value;
	}
}
