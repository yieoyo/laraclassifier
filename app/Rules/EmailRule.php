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

class EmailRule implements ValidationRule
{
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail(trans('validation.email'));
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 *
	 * NOTE: Laravel's email validator says that *@* is valid e-mail address
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$value = is_stringable($value) ? $value : '';
		
		return $this->simpleEmailAddressCheck(strtolower($value));
	}
	
	/**
	 * Custom email address validation
	 *
	 * NOTE: The 'filter_var($value, FILTER_VALIDATE_EMAIL)' function doesn't validate "not latin" domains.
	 *
	 * @param $value
	 * @return bool
	 */
	private function simpleEmailAddressCheck($value): bool
	{
		$posAtSign = strpos($value, '@');
		$posDot = strrpos($value, '.'); // Get the latest dot (.) position.
		
		return ($posAtSign !== false && $posDot !== false && $posDot > $posAtSign);
	}
}
