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

use App\Models\Category;
use App\Models\CategoryField;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomFieldUniqueParentRule implements ValidationRule
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
				$message = trans('validation.custom_field_unique_parent_rule', [
					'field_1' => trans('admin.category'),
					'field_2' => trans('admin.custom field'),
				]);
			} else {
				$message = trans('validation.custom_field_unique_parent_rule_field', [
					'field_1' => trans('admin.custom field'),
					'field_2' => trans('admin.category'),
				]);
			}
			
			$fail($message);
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
		
		if (!isset($this->parameters[0]) || !isset($this->parameters[1])) {
			return false;
		}
		
		$categoryId = ($attribute == 'category_id') ? $value : $this->parameters[1];
		
		// Get the category
		$cat = Category::find($categoryId);
		
		// Check parents records
		return $this->checkIfFieldExistsInAllParents($cat, $attribute, $value);
	}
	
	/**
	 * @param $cat
	 * @param $attribute
	 * @param $value
	 * @return bool
	 */
	private function checkIfFieldExistsInAllParents($cat, $attribute, $value): bool
	{
		$doesNotExist = true;
		
		if (!empty($cat)) {
			if (!empty($cat->parent_id)) {
				$parentCatField = CategoryField::query();
				if ($attribute == 'category_id') {
					$parentCatField->where($this->parameters[0], $this->parameters[1])->where($attribute, $cat->parent_id);
				} else {
					$parentCatField->where($this->parameters[0], $cat->parent_id)->where($attribute, $value);
				}
				$parentCatField = $parentCatField->first();
				
				// If field exists in a parent of the current category & the 'disabled_in_subcategories' option is not set,
				// Then prevent to link this field to the current category.
				if (!empty($parentCatField) && $parentCatField->disabled_in_subcategories != 1) {
					$doesNotExist = false;
				}
				
				if ($doesNotExist && !empty($cat->parent)) {
					return $this->checkIfFieldExistsInAllParents($cat->parent, $attribute, $value);
				}
			}
		}
		
		return $doesNotExist;
	}
}
