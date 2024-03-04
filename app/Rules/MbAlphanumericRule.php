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

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MbAlphanumericRule implements ValidationRule
{
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail(trans('validation.mb_alphanumeric_rule'));
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$value = is_stringable($value) ? $value : '';
		$value = trim(strip_tags($value));
		
		// Regex - Unicode character properties
		// https://www.php.net/manual/en/regexp.reference.unicode.php
		$pattern = [
			'\p{N}', // Number
			'\p{M}', // Mark
			'\p{P}', // Punctuation
			'\p{S}', // Symbol
			'\p{Z}', // Separator
		];
		$pattern = implode('', $pattern);
		$pattern = '/[^' . $pattern . ']/ui';
		
		// Check if the string contains characters other than the regex anti-rule (^)
		if (preg_match($pattern, $value)) {
			return true;
		} else {
			return false;
		}
	}
}
