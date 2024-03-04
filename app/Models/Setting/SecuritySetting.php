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

namespace App\Models\Setting;

class SecuritySetting
{
	public static function getValues($value, $disk)
	{
		if (empty($value)) {
			
			$value['honeypot_enabled'] = '1';
			$value['honeypot_name_field_name'] = 'entity_field';
			$value['honeypot_valid_from_field_name'] = 'valid_field';
			$value['honeypot_amount_of_seconds'] = '3';
			$value['honeypot_respond_to_spam_with'] = 'blank_page';
			
			$value['captcha_delay'] = '1000';
			$value['recaptcha_version'] = 'v2';
			
			$value['login_open_in_modal'] = '1';
			$value['login_max_attempts'] = '5';
			$value['login_decay_minutes'] = '15';
			
			$value['password_min_length'] = '6';
			$value['password_max_length'] = '60';
			$value['password_letters_required'] = '0';
			$value['password_mixedCase_required'] = '0';
			$value['password_numbers_required'] = '0';
			$value['password_symbols_required'] = '0';
			$value['password_uncompromised_required'] = '0';
			$value['password_uncompromised_threshold'] = '0';
			
			$value['email_validator_rfc'] = '1';
			$value['email_validator_strict'] = '0';
			$value['email_validator_dns'] = '0';
			$value['email_validator_spoof'] = '0';
			$value['email_validator_filter'] = '0';
			
		} else {
			
			if (!array_key_exists('honeypot_enabled', $value)) {
				$value['honeypot_enabled'] = '1';
			}
			if (!array_key_exists('honeypot_name_field_name', $value)) {
				$value['honeypot_name_field_name'] = 'entity_field';
			}
			if (!array_key_exists('honeypot_valid_from_field_name', $value)) {
				$value['honeypot_valid_from_field_name'] = 'valid_field';
			}
			if (!array_key_exists('honeypot_amount_of_seconds', $value)) {
				$value['honeypot_amount_of_seconds'] = '3';
			}
			if (!array_key_exists('honeypot_respond_to_spam_with', $value)) {
				$value['honeypot_respond_to_spam_with'] = 'blank_page';
			}
			
			if (!array_key_exists('captcha_delay', $value)) {
				$value['captcha_delay'] = '1000';
			}
			if (!array_key_exists('recaptcha_version', $value)) {
				$value['recaptcha_version'] = 'v2';
			}
			
			// Get reCAPTCHA old config values
			if (isset($value['recaptcha_public_key'])) {
				$value['recaptcha_v2_site_key'] = $value['recaptcha_public_key'];
			}
			if (isset($value['recaptcha_private_key'])) {
				$value['recaptcha_v2_secret_key'] = $value['recaptcha_private_key'];
			}
			
			if (!array_key_exists('login_open_in_modal', $value)) {
				$value['login_open_in_modal'] = '1';
			}
			if (!array_key_exists('login_max_attempts', $value)) {
				$value['login_max_attempts'] = '5';
			}
			if (!array_key_exists('login_decay_minutes', $value)) {
				$value['login_decay_minutes'] = '15';
			}
			
			if (!array_key_exists('password_min_length', $value)) {
				$value['password_min_length'] = '6';
			}
			if (!array_key_exists('password_max_length', $value)) {
				$value['password_max_length'] = '60';
			}
			if (!array_key_exists('password_letters_required', $value)) {
				$value['password_letters_required'] = '0';
			}
			if (!array_key_exists('password_mixedCase_required', $value)) {
				$value['password_mixedCase_required'] = '0';
			}
			if (!array_key_exists('password_numbers_required', $value)) {
				$value['password_numbers_required'] = '0';
			}
			if (!array_key_exists('password_symbols_required', $value)) {
				$value['password_symbols_required'] = '0';
			}
			if (!array_key_exists('password_uncompromised_required', $value)) {
				$value['password_uncompromised_required'] = '0';
			}
			if (!array_key_exists('password_uncompromised_threshold', $value)) {
				$value['password_uncompromised_threshold'] = '0';
			}
			
			if (!array_key_exists('email_validator_rfc', $value)) {
				$value['email_validator_rfc'] = '1';
			}
			if (!array_key_exists('email_validator_strict', $value)) {
				$value['email_validator_strict'] = '0';
			}
			if (!array_key_exists('email_validator_dns', $value)) {
				$value['email_validator_dns'] = '0';
			}
			if (!array_key_exists('email_validator_spoof', $value)) {
				$value['email_validator_spoof'] = '0';
			}
			if (!array_key_exists('email_validator_filter', $value)) {
				$value['email_validator_filter'] = '0';
			}
			
		}
		
		return $value;
	}
	
	public static function setValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName)
	{
		$fields = [
			[
				'name'  => 'csrf_protection_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.csrf_protection_title'),
			],
			[
				'name'  => 'csrf_protection',
				'label' => trans('admin.csrf_protection_label'),
				'type'  => 'checkbox_switch',
				'hint'  => trans('admin.csrf_protection_hint'),
			],
			
			[
				'name'  => 'honeypot_title',
				'type'  => 'custom_html',
				'value' => trans('admin.honeypot_title'),
			],
			[
				'name'       => 'honeypot_enabled',
				'label'      => trans('admin.honeypot_enabled_label'),
				'type'       => 'checkbox_switch',
				'attributes' => [
					'id'       => 'honeypot',
					'onchange' => 'enableHoneypot(this)',
				],
				'hint'       => trans('admin.honeypot_enabled_hint'),
			],
			[
				'name'              => 'honeypot_name_field_name',
				'label'             => trans('admin.honeypot_name_field_name_label'),
				'type'              => 'text',
				'hint'              => trans('admin.honeypot_name_field_name_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 honeypot-el',
				],
			],
			[
				'name'              => 'honeypot_respond_to_spam_with',
				'label'             => trans('admin.honeypot_respond_to_spam_with_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					'blank_page'     => 'Blank Page',
					'http_error_500' => 'HTTP Error 500',
				],
				'wrapperAttributes' => [
					'class' => 'col-md-6 honeypot-el',
				],
				'hint'              => trans('admin.honeypot_respond_to_spam_with_hint'),
			],
			[
				'name'              => 'honeypot_separator_1',
				'type'              => 'custom_html',
				'value'             => '<div style="clear: both;"></div>',
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended honeypot-el',
				],
			],
			[
				'name'              => 'honeypot_randomize_name_field_name',
				'label'             => trans('admin.honeypot_randomize_name_field_name_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.honeypot_randomize_name_field_name_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 honeypot-el',
				],
			],
			[
				'name'              => 'honeypot_valid_from_timestamp',
				'label'             => trans('admin.honeypot_valid_from_timestamp_label'),
				'type'              => 'checkbox_switch',
				'attributes'        => [
					'id'       => 'validFromTimestamp',
					'onchange' => 'enableValidFromTimestamp(this)',
				],
				'hint'              => trans('admin.honeypot_valid_from_timestamp_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 honeypot-el',
				],
			],
			[
				'name'              => 'honeypot_valid_from_field_name',
				'label'             => trans('admin.honeypot_valid_from_field_name_label'),
				'type'              => 'text',
				'hint'              => trans('admin.honeypot_valid_from_field_name_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 honeypot-el honeypot-timestamp-el',
				],
			],
			[
				'name'              => 'honeypot_amount_of_seconds',
				'label'             => trans('admin.honeypot_amount_of_seconds_label'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 1,
					'max'  => 3600,
					'step' => 1,
				],
				'hint'              => trans('admin.honeypot_amount_of_seconds_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 honeypot-el honeypot-timestamp-el',
				],
			],
			
			[
				'name'  => 'captcha_title',
				'type'  => 'custom_html',
				'value' => trans('admin.captcha_title'),
			],
			[
				'name'              => 'captcha',
				'label'             => trans('admin.captcha_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					''          => 'Disabled',
					'default'   => 'Simple Captcha (Default)',
					'math'      => 'Simple Captcha (Math)',
					'flat'      => 'Simple Captcha (Flat)',
					'mini'      => 'Simple Captcha (Mini)',
					'inverse'   => 'Simple Captcha (Inverse)',
					'custom'    => 'Simple Captcha (Custom)',
					'recaptcha' => 'Google reCAPTCHA',
				],
				'attributes'        => [
					'id'       => 'captcha',
					'onchange' => 'getCaptchaFields(this)',
				],
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
				'hint'              => trans('admin.captcha_hint'),
			],
			[
				'name'              => 'captcha_delay',
				'label'             => trans('admin.captcha_delay_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					1000 => '1000ms',
					1100 => '1100ms',
					1200 => '1200ms',
					1300 => '1300ms',
					1400 => '1400ms',
					1500 => '1500ms',
					1600 => '1600ms',
					1700 => '1700ms',
					1800 => '1800ms',
					1900 => '1900ms',
					2000 => '2000ms',
					2500 => '2500ms',
					3000 => '3000ms',
				],
				'wrapperAttributes' => [
					'class' => 'col-md-6 s-captcha',
				],
				'hint'              => trans('admin.captcha_delay_hint'),
			],
			[
				'name'              => 'captcha_custom',
				'type'              => 'custom_html',
				'value'             => trans('admin.captcha_custom'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_custom_info',
				'type'              => 'custom_html',
				'value'             => trans('admin.captcha_custom_info'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_width',
				'label'             => trans('admin.captcha_width_label', ['max' => 300]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 100,
					'max'  => 300,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_height',
				'label'             => trans('admin.captcha_height_label', ['max' => 150]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 30,
					'max'  => 150,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_length',
				'label'             => trans('admin.captcha_length_label', ['max' => 8]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 3,
					'max'  => 8,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_quality',
				'label'             => trans('admin.captcha_quality_label', ['max' => 100]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_bgImage',
				'label'             => trans('admin.captcha_bgImage_label'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-12 s-captcha s-captcha-custom',
				],
				'hint'              => trans('admin.captcha_bgImage_hint'),
			],
			[
				'name'                => 'captcha_bgColor',
				'label'               => trans('admin.captcha_bgColor_label'),
				'type'                => 'color_picker',
				'colorpicker_options' => [
					'customClass' => 'custom-class',
				],
				'attributes'          => [
					'placeholder' => '',
				],
				'wrapperAttributes'   => [
					'class' => 'col-md-6 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_lines',
				'label'             => trans('admin.captcha_lines_label', ['max' => 20]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'max'  => 20,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_angle',
				'label'             => trans('admin.captcha_angle_label', ['max' => 180]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'max'  => 180,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_sharpen',
				'label'             => trans('admin.captcha_sharpen_label', ['max' => 20]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'max'  => 20,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_blur',
				'label'             => trans('admin.captcha_blur_label', ['max' => 20]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'max'  => 20,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_contrast',
				'label'             => trans('admin.captcha_contrast_label', ['max' => 50]),
				'type'              => 'number',
				'attributes'        => [
					'min'  => -50,
					'max'  => 50,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_expire',
				'label'             => trans('admin.captcha_expire_label'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
				],
				'wrapperAttributes' => [
					'class' => 'col-md-3 s-captcha s-captcha-custom',
				],
			],
			[
				'name'              => 'captcha_math',
				'label'             => trans('admin.captcha_math_label'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-6 s-captcha s-captcha-custom',
				],
				'hint'              => trans('admin.captcha_math_hint'),
			],
			[
				'name'              => 'captcha_encrypt',
				'label'             => trans('admin.captcha_encrypt_label'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-6 s-captcha s-captcha-custom',
				],
				'hint'              => trans('admin.captcha_encrypt_hint'),
			],
			[
				'name'              => 'captcha_sensitive',
				'label'             => trans('admin.captcha_sensitive_label'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-6 s-captcha s-captcha-custom',
				],
				'hint'              => trans('admin.captcha_sensitive_hint'),
			],
			[
				'name'              => 'captcha_invert',
				'label'             => trans('admin.captcha_invert_label'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-6 s-captcha s-captcha-custom',
				],
				'hint'              => trans('admin.captcha_invert_hint'),
			],
			
			// ==========
			
			[
				'name'              => 'recaptcha_sep_info',
				'type'              => 'custom_html',
				'value'             => trans('admin.recaptcha_sep_info_value'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 recaptcha',
				],
			],
			[
				'name'              => 'recaptcha_version',
				'label'             => trans('admin.recaptcha_version_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					'v2' => 'v2 (Checkbox)',
					'v3' => 'v3',
				],
				'attributes'        => [
					'id'       => 'recaptchaVersion',
					'onchange' => 'getReCaptchaFields(this)',
				],
				'hint'              => trans('admin.recaptcha_version_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 recaptcha',
				],
			],
			[
				'name'              => 'separator_clear_recaptcha',
				'type'              => 'custom_html',
				'value'             => '<div style="clear: both;"></div>',
				'wrapperAttributes' => [
					'class' => 'col-md-12 recaptcha',
				],
			],
			[
				'name'              => 'recaptcha_v2_site_key',
				'label'             => trans('admin.recaptcha_v2_site_key_label'),
				'type'              => 'text',
				'wrapperAttributes' => [
					'class' => 'col-md-6 recaptcha recaptcha-v2',
				],
			],
			[
				'name'              => 'recaptcha_v2_secret_key',
				'label'             => trans('admin.recaptcha_v2_secret_key_label'),
				'type'              => 'text',
				'wrapperAttributes' => [
					'class' => 'col-md-6 recaptcha recaptcha-v2',
				],
			],
			[
				'name'              => 'recaptcha_v3_site_key',
				'label'             => trans('admin.recaptcha_v3_site_key_label'),
				'type'              => 'text',
				'wrapperAttributes' => [
					'class' => 'col-md-6 recaptcha recaptcha-v3',
				],
			],
			[
				'name'              => 'recaptcha_v3_secret_key',
				'label'             => trans('admin.recaptcha_v3_secret_key_label'),
				'type'              => 'text',
				'wrapperAttributes' => [
					'class' => 'col-md-6 recaptcha recaptcha-v3',
				],
			],
			[
				'name'              => 'recaptcha_skip_ips',
				'label'             => trans('admin.recaptcha_skip_ips_label'),
				'type'              => 'textarea',
				'hint'              => trans('admin.recaptcha_skip_ips_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 recaptcha',
				],
			],
			
			// ==========
			
			[
				'name'  => 'login_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.login_sep_value'),
			],
			[
				'name'  => 'login_open_in_modal',
				'label' => trans('admin.Open In Modal'),
				'type'  => 'checkbox_switch',
				'hint'  => trans('admin.Open the top login link into Modal'),
			],
			[
				'name'              => 'login_max_attempts',
				'label'             => trans('admin.Max Attempts'),
				'type'              => 'select2_from_array',
				'options'           => [
					30 => '30',
					20 => '20',
					10 => '10',
					5  => '5',
					4  => '4',
					3  => '3',
					2  => '2',
					1  => '1',
				],
				'hint'              => trans('admin.The maximum number of attempts to allow'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'login_decay_minutes',
				'label'             => trans('admin.Decay Minutes'),
				'type'              => 'select2_from_array',
				'options'           => [
					1440 => '1440',
					720  => '720',
					60   => '60',
					30   => '30',
					20   => '20',
					15   => '15',
					10   => '10',
					5    => '5',
					4    => '4',
					3    => '3',
					2    => '2',
					1    => '1',
				],
				'hint'              => trans('admin.The number of minutes to throttle for'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			
			[
				'name'  => 'password_validator_title',
				'type'  => 'custom_html',
				'value' => trans('admin.password_validator_title_value'),
			],
			[
				'name'              => 'password_min_length',
				'label'             => trans('admin.password_min_length_label'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
					'max'  => 100,
				],
				'hint'              => trans('admin.password_min_length_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'password_max_length',
				'label'             => trans('admin.password_max_length_label'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
					'max'  => 100,
				],
				'hint'              => trans('admin.password_max_length_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'password_letters_required',
				'label'             => trans('admin.password_letters_required_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.password_letters_required_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'password_mixedCase_required',
				'label'             => trans('admin.password_mixedCase_required_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.password_mixedCase_required_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'password_numbers_required',
				'label'             => trans('admin.password_numbers_required_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.password_numbers_required_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'password_symbols_required',
				'label'             => trans('admin.password_symbols_required_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.password_symbols_required_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'password_uncompromised_required',
				'label'             => trans('admin.password_uncompromised_required_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.password_uncompromised_required_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'password_uncompromised_threshold',
				'label'             => trans('admin.password_uncompromised_threshold_label'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
					'max'  => 10,
				],
				'hint'              => trans('admin.password_uncompromised_threshold_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 mt-4',
				],
			],
			
			[
				'name'  => 'email_validator_title',
				'type'  => 'custom_html',
				'value' => trans('admin.email_validator_title_value'),
			],
			[
				'name'              => 'email_validator_rfc',
				'label'             => trans('admin.email_validator_rfc_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.email_validator_rfc_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'email_validator_strict',
				'label'             => trans('admin.email_validator_strict_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.email_validator_strict_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'email_validator_dns',
				'label'             => trans('admin.email_validator_dns_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.email_validator_dns_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'email_validator_spoof',
				'label'             => trans('admin.email_validator_spoof_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.email_validator_spoof_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'email_validator_filter',
				'label'             => trans('admin.email_validator_filter_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.email_validator_filter_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			
			// ==========
			
			[
				'name'  => 'javascript',
				'type'  => 'custom_html',
				'value' => '<script>
docReady(function() {
	/* honeypot */
	let honeypotEl = document.querySelector("#honeypot");
	enableHoneypot(honeypotEl);
	
	/* captcha */
	let captchaEl = document.querySelector("#captcha");
	getCaptchaFields(captchaEl);
	
	let recaptchaVersionEl = document.querySelector("#recaptchaVersion");
	getReCaptchaFields(recaptchaVersionEl);
});

function enableHoneypot(honeypotEl) {
	if (honeypotEl.checked) {
		showEl(document.querySelectorAll(".honeypot-el"));
		
		let validFromTimestampEl = document.querySelector("#validFromTimestamp");
		enableValidFromTimestamp(validFromTimestampEl);
	} else {
		hideEl(document.querySelectorAll(".honeypot-el"));
	}
}
function enableValidFromTimestamp(validFromTimestampEl) {
	if (validFromTimestampEl.checked) {
		showEl(document.querySelectorAll(".honeypot-timestamp-el"));
	} else {
		hideEl(document.querySelectorAll(".honeypot-timestamp-el"));
	}
}
function getCaptchaFields(captchaEl) {
	let captchaElValue = captchaEl.value;
	
	if (captchaElValue === "") {
		hideEl(document.querySelectorAll(".s-captcha"));
		hideEl(document.querySelectorAll(".recaptcha"));
	}
	if (
		captchaElValue === "default"
		|| captchaElValue === "math"
		|| captchaElValue === "flat"
		|| captchaElValue === "mini"
		|| captchaElValue === "inverse"
	) {
		hideEl(document.querySelectorAll(".recaptcha"));
		hideEl(document.querySelectorAll(".s-captcha-custom"));
		showEl(document.querySelectorAll(".s-captcha:not(.s-captcha-custom)"));
	}
	if (captchaElValue === "custom") {
		hideEl(document.querySelectorAll(".recaptcha"));
		showEl(document.querySelectorAll(".s-captcha"));
	}
	if (captchaElValue === "recaptcha") {
		hideEl(document.querySelectorAll(".s-captcha"));
		showEl(document.querySelectorAll(".recaptcha"));
		
		let recaptchaVersionEl = document.querySelector("#recaptchaVersion");
		getReCaptchaFields(recaptchaVersionEl);
	}
}

function getReCaptchaFields(recaptchaVersionEl) {
	let recaptchaVersionElValue = recaptchaVersionEl.value;
	
	let captchaEl = document.querySelector("#captcha");
	let captchaElValue = captchaEl.value;
	
	if (captchaElValue === "recaptcha") {
		hideEl(document.querySelectorAll(".s-captcha"));
		showEl(document.querySelectorAll(".recaptcha"));
		
		if (recaptchaVersionElValue === "v3") {
			hideEl(document.querySelectorAll(".recaptcha-v2"));
			showEl(document.querySelectorAll(".recaptcha-v3"));
		} else {
			hideEl(document.querySelectorAll(".recaptcha-v3"));
			showEl(document.querySelectorAll(".recaptcha-v2"));
		}
	} else {
		hideEl(document.querySelectorAll(".recaptcha"));
	}
}
</script>',
			],
		];
		
		return $fields;
	}
}
