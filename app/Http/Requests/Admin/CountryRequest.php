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

use App\Rules\CurrenciesCodesAreValidRule;

class CountryRequest extends Request
{
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation()
	{
		$input = $this->all();
		
		// admin_type
		$adminTypeList = array_keys(enumCountryAdminTypes());
		$adminType = $this->filled('admin_type') ? $this->input('admin_type') : '0';
		$input['admin_type'] = in_array($adminType, $adminTypeList) ? $adminType : '0';
		
		// currencies
		if ($this->filled('currencies')) {
			// Add new data field before it gets sent to the validator
			$currencies = explode(',', $this->input('currencies'));
			$currenciesCodes = collect($currencies)
				->map(fn ($value) => trim($value))
				->reject(fn ($value) => empty($value))
				->toArray();
			
			$input['currencies'] = @implode(',', $currenciesCodes);
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
		$rules = [
			'code'           => ['required', 'min:2', 'max:2'],
			'name'           => ['required', 'min:2', 'max:255'],
			'continent_code' => ['required'],
			'currency_code'  => ['required'],
			'phone'          => ['required'],
			'languages'      => ['required'],
		];
		
		if ($this->filled('currencies')) {
			$rules['currencies'] = [new CurrenciesCodesAreValidRule()];
		}
		
		return $rules;
	}
}
