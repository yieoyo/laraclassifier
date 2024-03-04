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

use App\Models\Blacklist;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BlacklistDomainRule implements ValidationRule
{
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail(trans('validation.blacklist_domain_rule'));
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
		$value = strtolower($value);
		
		$value = str_replace(['http', 'https', 'ftp', 'sftp', '://', 'www.'], '', $value);
		if (str_contains($value, '/')) {
			$value = strstr($value, '/', true);
		}
		
		if (str_contains($value, '@')) {
			$value = strstr($value, '@');
			$value = str_replace('@', '', $value);
		}
		
		$blacklisted = Blacklist::ofType('domain')->where('entry', $value)->first();
		
		return empty($blacklisted);
	}
}
