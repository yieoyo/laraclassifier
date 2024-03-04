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

namespace App\Observers\Traits\Setting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Prologue\Alerts\Facades\Alert;

trait SecurityTrait
{
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return false|void
	 */
	public function securityUpdating($setting, $original)
	{
		// Honeypot
		$allFormsFields = $this->getAllFormsFields();
		
		// honeypot_name_field_name
		$nameFieldName = $setting->value['honeypot_name_field_name'] ?? null;
		if (!empty($nameFieldName)) {
			if (in_array($nameFieldName, $allFormsFields)) {
				$message = trans('admin.honeypot_reserved_field_name_error_message', [
					'attribute'      => trans('admin.honeypot_name_field_name_label'),
					'attributeValue' => $nameFieldName,
				]);
				Alert::error($message)->flash();
				
				return false;
			}
		} else {
			$message = trans('admin.honeypot_field_name_required_message', [
				'attribute' => trans('admin.honeypot_name_field_name_label'),
			]);
			Alert::error($message)->flash();
			
			return false;
		}
		
		// honeypot_valid_from_field_name
		$validFromFieldName = $setting->value['honeypot_valid_from_field_name'] ?? null;
		if (!empty($validFromFieldName)) {
			if (in_array($validFromFieldName, $allFormsFields)) {
				$message = trans('admin.honeypot_reserved_field_name_error_message', [
					'attribute'      => trans('admin.honeypot_valid_from_field_name_label'),
					'attributeValue' => $validFromFieldName,
				]);
				Alert::error($message)->flash();
				
				return false;
			}
		} else {
			$message = trans('admin.honeypot_field_name_required_message', [
				'attribute' => trans('admin.honeypot_valid_from_field_name_label'),
			]);
			Alert::error($message)->flash();
			
			return false;
		}
		
		// password length
		$passwordMinLength = $setting->value['password_min_length'] ?? 6;
		$passwordMaxLength = $setting->value['password_max_length'] ?? 60;
		if ($passwordMinLength > $passwordMaxLength) {
			$message = trans('admin.min_max_error_message', ['attribute' => trans('admin.password_length')]);
			Alert::error($message)->flash();
			
			return false;
		}
		
		// Check if the PHP intl extension is installed
		// to use DNS or Spoof in the email validator.
		$emailValidatorDns = $setting->value['email_validator_dns'] ?? false;
		$emailValidatorSpoof = $setting->value['email_validator_spoof'] ?? false;
		if (($emailValidatorDns || $emailValidatorSpoof) && !extension_loaded('intl')) {
			$message = trans('admin.intl_extension_missing_error_message_for_email_validation');
			Alert::error($message)->flash();
			
			return false;
		}
	}
	
	private function getAllFormsFields(): array
	{
		$fields = [];
		
		try {
			$dbColumns = $this->getAllDbColumns();
			$contactFields = ['first_name', 'last_name', 'company_name', 'email', 'message'];
			$reportFields = ['report_type_id', 'email', 'message', 'post_id', 'abuseForm'];
			$sendByEmailFields = ['recipient_email', 'post_id', 'sendByEmailForm'];
			$otherFields = ['_method', '_token', 'captcha', 'g-recaptcha-response'];
			
			$fields = array_merge($fields, $dbColumns);
			$fields = array_merge($fields, $contactFields);
			$fields = array_merge($fields, $reportFields);
			$fields = array_merge($fields, $sendByEmailFields);
			$fields = array_merge($fields, $otherFields);
			
			$fields = collect($fields)->unique()->toArray();
		} catch (\Throwable $e) {
		}
		
		return $fields;
	}
	
	private function getAllDbColumns(): array
	{
		$columns = [];
		
		$tables = $this->getAllDbTables(withPrefix: false);
		foreach ($tables as $table) {
			$tableColumns = Schema::getColumnListing($table);
			if (is_array($tableColumns)) {
				$columns = array_merge($columns, $tableColumns);
			}
		}
		
		return collect($columns)->unique()->toArray();
	}
	
	private function getAllDbTables(bool $withPrefix = true): array
	{
		$connection = config('database.default');
		$database = config('database.connections.' . $connection . '.database');
		
		$tables = [];
		try {
			$prefix = DB::getTablePrefix();
			$results = DB::select('SHOW TABLES');
			
			foreach ($results as $table) {
				$tableName = $table->{'Tables_in_' . $database};
				if (!$withPrefix) {
					$tableName = str_replace($prefix, '', $tableName);
				}
				$tables[] = $tableName;
			}
		} catch (\Throwable $e) {
		}
		
		return $tables;
	}
}
