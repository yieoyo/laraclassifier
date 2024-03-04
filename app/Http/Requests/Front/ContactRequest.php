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

namespace App\Http\Requests\Front;

use App\Http\Requests\Request;
use App\Rules\BetweenRule;

class ContactRequest extends Request
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = [
			'first_name' => ['required', 'string', new BetweenRule(2, 100)],
			'last_name'  => ['required', 'string', new BetweenRule(2, 100)],
			'email'      => ['required'],
			'message'    => ['required', 'string', new BetweenRule(5, 500)],
		];
		
		$rules = $this->validEmailRules('email', $rules);
		
		if (isFromApi()) {
			$rules['country_code'] = ['required'];
			$rules['country_name'] = ['required'];
		}
		
		return $this->captchaRules($rules);
	}
}
