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

use App\Models\Language;
use App\Rules\BetweenRule;
use App\Rules\LocaleOfLanguageRule;

class LanguageRequest extends Request
{
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation()
	{
		$input = $this->all();
		
		if ($this->filled('code')) {
			$code = $this->input('code');
			
			// name
			$input['name'] = getRegionalLocaleName($code);
			
			// native
			if (!$this->filled('native')) {
				$input['native'] = $input['name'];
			}
			
			// locale
			if (!$this->filled('locale')) {
				$input['locale'] = getRegionalLocaleCode($code, false);
			}
		}
		
		// direction
		if (!$this->filled('direction')) {
			$input['direction'] = 'ltr';
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
		$code = $this->input('code');
		
		$rules = [
			'code'   => ['required', 'min:2', 'max:20'],
			'name'   => ['required', new BetweenRule(2, 255)],
			'native' => ['required', new BetweenRule(2, 255)],
			'locale' => ['required', 'min:2', 'max:25', new LocaleOfLanguageRule($code)],
		];
		
		if (in_array($this->method(), ['POST', 'CREATE'])) {
			$rules['code'][] = 'unique:languages,code';
		}
		
		if (in_array($this->method(), ['PUT', 'PATCH', 'UPDATE'])) {
			if (!empty($code)) {
				$language = Language::query()->where('code', $code)->first();
				
				$codeChanged = (!empty($language) && $this->input('code') != $language->code);
				if ($codeChanged) {
					$rules['code'][] = 'unique:languages,code';
				}
			}
		}
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		// code
		if ($this->filled('code')) {
			if (in_array($this->method(), ['POST', 'CREATE'])) {
				$messages['code.unique'] = t('language_code_unique_store');
			}
			if (in_array($this->method(), ['PUT', 'PATCH', 'UPDATE'])) {
				$messages['code.unique'] = t('language_code_unique_update');
			}
		}
		
		return $messages;
	}
}
