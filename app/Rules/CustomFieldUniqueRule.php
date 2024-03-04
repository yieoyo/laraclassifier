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

use App\Models\CategoryField;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomFieldUniqueRule implements ValidationRule
{
	public array $parameters = [];
	
	public function __construct($parameters)
	{
		$this->parameters = $parameters;
	}
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			if ($attribute == 'category_id') {
				$message = trans('validation.custom_field_unique_rule', [
					'field_1' => trans('admin.category'),
					'field_2' => trans('admin.custom field'),
				]);
			} else {
				$message = trans('validation.custom_field_unique_rule_field', [
					'field_1' => trans('admin.custom field'),
					'field_2' => trans('admin.category'),
				]);
			}
			
			$fail($message);
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 * Prevent duplicate content (Category & Custom Field) in 'category_field' table
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$value = is_stringable($value) ? $value : '';
		
		if (!isset($this->parameters[0]) || !isset($this->parameters[1])) {
			return false;
		}
		
		$categoryFields = CategoryField::query()
			->where($this->parameters[0], $this->parameters[1])
			->where($attribute, $value);
		
		return ($categoryFields->count() <= 0);
	}
}
