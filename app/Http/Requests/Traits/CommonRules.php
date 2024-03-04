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

namespace App\Http\Requests\Traits;

use App\Rules\BlacklistDomainRule;
use App\Rules\BlacklistEmailRule;
use App\Rules\BlacklistPhoneRule;
use App\Rules\EmailRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait CommonRules
{
	/**
	 * Valid Email Address Rules
	 *
	 * @param string $field
	 * @param array $rules
	 * @return array
	 */
	protected function validEmailRules(string $field, array $rules = []): array
	{
		if ($this->filled($field)) {
			$rules[$field][] = new EmailRule();
			$rules[$field][] = 'max:100';
			$rules[$field][] = new BlacklistEmailRule();
			$rules[$field][] = new BlacklistDomainRule();
			
			$params = [];
			if (config('settings.security.email_validator_rfc')) {
				$params[] = 'rfc';
			}
			if (config('settings.security.email_validator_strict')) {
				$params[] = 'strict';
			}
			if (extension_loaded('intl')) {
				if (config('settings.security.email_validator_dns')) {
					$params[] = 'dns';
				}
				if (config('settings.security.email_validator_spoof')) {
					$params[] = 'spoof';
				}
			}
			if (config('settings.security.email_validator_filter')) {
				$params[] = 'filter';
			}
			if (!empty($params)) {
				$rules[$field][] = 'email:' . implode(',', $params);
			}
		}
		
		return $rules;
	}
	
	/**
	 * Valid Phone Number Rules
	 *
	 * @param string $field
	 * @param array $rules
	 * @return array
	 */
	protected function validPhoneNumberRules(string $field, array $rules = []): array
	{
		if ($this->filled($field)) {
			$rules[$field][] = new BlacklistPhoneRule();
			
			$smsSendingIsRequired = (config('settings.sms.phone_verification') == 1);
			if ($smsSendingIsRequired) {
				// $rules[$field][] = 'phone:' . getPhoneCountry() . ',mobile';
				$rules[$field][] = Rule::phone()->country([getPhoneCountry()])->type('mobile');
			} else {
				// $rules[$field][] = 'phone:' . getPhoneCountry();
				$rules[$field][] = Rule::phone()->country([getPhoneCountry()]);
			}
		}
		
		return $rules;
	}
	
	/**
	 * Valid Password Rules
	 *
	 * @param string $field
	 * @param array $rules
	 * @return array
	 */
	protected function validPasswordRules(string $field, array $rules = []): array
	{
		if ($this->filled($field)) {
			$rule = Password::min(config('settings.security.password_min_length', 6));
			if (config('settings.security.password_letters_required')) {
				$rule->letters();
			}
			if (config('settings.security.password_mixedCase_required')) {
				$rule->mixedCase();
			}
			if (config('settings.security.password_numbers_required')) {
				$rule->numbers();
			}
			if (config('settings.security.password_symbols_required')) {
				$rule->symbols();
			}
			if (config('settings.security.password_uncompromised_required')) {
				$rule->uncompromised(config('settings.security.password_uncompromised_threshold', 0));
			}
			$rules[$field][] = $rule;
			
			$rules[$field][] = 'max:' . config('settings.security.password_max_length', 60);
		}
		
		return $rules;
	}
	
	/**
	 * CAPTCHA Rules
	 *
	 * @param array $rules
	 * @return array
	 */
	protected function captchaRules(array $rules = []): array
	{
		if (empty(config('settings.security.captcha'))) {
			return $rules;
		}
		
		if (config('settings.security.captcha') == 'recaptcha') {
			// reCAPTCHA
			if (config('recaptcha.site_key') && config('recaptcha.secret_key')) {
				if (!isFromApi()) {
					$rules['g-recaptcha-response'] = ['recaptcha'];
				}
			}
		} else {
			// CAPTCHA
			if (config('captcha.option') && !empty(config('captcha.option'))) {
				if (isFromApi()) {
					if (!doesRequestIsFromWebApp()) {
						if ($this->filled('captcha_key')) {
							$rules['captcha'] = [
								'required',
								'captcha_api:' . $this->get('captcha_key') . ',' . config('settings.security.captcha'),
							];
						}
					}
				} else {
					$rules['captcha'] = ['required', 'captcha'];
				}
			}
		}
		
		return $rules;
	}
}
