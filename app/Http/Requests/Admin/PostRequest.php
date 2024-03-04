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

namespace App\Http\Requests\Admin;

use App\Helpers\Number;
use App\Rules\BetweenRule;
use Illuminate\Validation\Rule;

class PostRequest extends Request
{
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation()
	{
		$input = $this->all();
		
		// price
		if ($this->has('price')) {
			if ($this->filled('price')) {
				$input['price'] = $this->input('price');
				// If field's value contains only numbers and dot,
				// Then decimal separator is set as dot.
				if (preg_match('/^[0-9.]*$/', $input['price'])) {
					$input['price'] = Number::formatForDb($input['price'], '.');
				} else {
					if ($this->filled('currency_decimal_separator')) {
						$input['price'] = Number::formatForDb($input['price'], $this->input('currency_decimal_separator'));
					}
				}
			} else {
				$input['price'] = null;
			}
		}
		
		// currency_code (Not implemented)
		if ($this->filled('currency_code')) {
			$input['currency_code'] = $this->input('currency_code');
		}
		
		// auth_field
		$input['auth_field'] = getAuthField();
		
		// phone
		if ($this->filled('phone')) {
			$input['phone'] = phoneE164($this->input('phone'), getPhoneCountry());
			$input['phone_national'] = phoneNational($this->input('phone'), getPhoneCountry());
		} else {
			$input['phone'] = null;
			$input['phone_national'] = null;
		}
		
		// tags
		if ($this->filled('tags')) {
			$input['tags'] = tagCleaner($this->input('tags'));
		}
		
		request()->merge($input); // Required!
		$this->merge($input);
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$rules = [];
		
		$authFields = array_keys(getAuthFields());
		
		$rules['category_id'] = ['required', 'not_in:0'];
		if (config('settings.single.show_listing_type')) {
			$rules['post_type_id'] = ['required', 'not_in:0'];
		}
		$rules['title'] = [
			'required',
			new BetweenRule(
				(int)config('settings.single.title_min_length', 2),
				(int)config('settings.single.title_max_length', 150)
			),
		];
		$rules['description'] = [
			'required',
			new BetweenRule(
				(int)config('settings.single.description_min_length', 5),
				(int)config('settings.single.description_max_length', 6000)
			),
		];
		$rules['contact_name'] = ['required', new BetweenRule(2, 200)];
		$rules['auth_field'] = ['required', Rule::in($authFields)];
		$rules['phone'] = ['max:30'];
		$rules['phone_country'] = ['required_with:phone'];
		
		$phoneIsEnabledAsAuthField = (config('settings.sms.enable_phone_as_auth_field') == '1');
		$phoneNumberIsRequired = ($phoneIsEnabledAsAuthField && $this->input('auth_field') == 'phone');
		
		// email
		$emailIsRequired = (!$phoneNumberIsRequired);
		if ($emailIsRequired) {
			$rules['email'][] = 'required';
		}
		$rules = $this->validEmailRules('email', $rules);
		
		// phone
		if ($phoneNumberIsRequired) {
			$rules['phone'][] = 'required';
		}
		$rules = $this->validPhoneNumberRules('phone', $rules);
		
		// Tags
		if ($this->filled('tags')) {
			$rules['tags.*'] = ['regex:' . tag_regex_pattern()];
		}
		
		return $rules;
	}
	
	/**
	 * Get custom attributes for validator errors.
	 *
	 * @return array
	 */
	public function attributes(): array
	{
		$attributes = [];
		
		if ($this->filled('tags')) {
			foreach ($this->input('tags') as $key => $tag) {
				$attributes['tags.' . $key] = t('tag X', ['key' => ($key + 1)]);
			}
		}
		
		return $attributes;
	}
}
