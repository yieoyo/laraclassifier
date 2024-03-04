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

use App\Models\Permission;
use App\Models\User;
use App\Notifications\ExampleSms;
use App\Providers\AppService\ConfigTrait\SmsConfig;
use Illuminate\Support\Facades\Notification;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Prologue\Alerts\Facades\Alert;

trait SmsTrait
{
	use SmsConfig;
	
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return bool
	 */
	public function smsUpdating($setting, $original)
	{
		$validateDriverParameters = $setting->value['validate_driver'] ?? false;
		if ($validateDriverParameters) {
			$this->updateSmsConfig($setting->value);
			
			/*
			 * Send Example SMS
			 */
			$driver = $setting->value['driver'] ?? null;
			try {
				if (config('settings.app.phone_number')) {
					Notification::route($driver, config('settings.app.phone_number'))->notify(new ExampleSms());
				} else {
					$admins = User::permission(Permission::getStaffPermissions())->get();
					if ($admins->count() > 0) {
						Notification::send($admins, new ExampleSms());
					}
				}
			} catch (\Throwable $e) {
				$message = $e->getMessage();
				
				if (isAdminPanel()) {
					Alert::error($message)->flash();
				} else {
					flash($message)->error();
				}
				
				return false;
			}
		}
		
		$this->saveParametersInEnvFile($setting);
	}
	
	/**
	 * Save SMS Settings in the /.env file
	 *
	 * @param $setting
	 */
	private function saveParametersInEnvFile($setting): void
	{
		$envFileHasChanged = false;
		
		if (
			!DotenvEditor::keyExists('VONAGE_KEY')
			&& !DotenvEditor::keyExists('VONAGE_SECRET')
			&& !DotenvEditor::keyExists('VONAGE_APPLICATION_ID')
			&& !DotenvEditor::keyExists('VONAGE_SMS_FROM')
			&& !DotenvEditor::keyExists('TWILIO_USERNAME')
			&& !DotenvEditor::keyExists('TWILIO_PASSWORD')
			&& !DotenvEditor::keyExists('TWILIO_AUTH_TOKEN')
			&& !DotenvEditor::keyExists('TWILIO_ACCOUNT_SID')
			&& !DotenvEditor::keyExists('TWILIO_FROM')
			&& !DotenvEditor::keyExists('TWILIO_ALPHA_SENDER')
			&& !DotenvEditor::keyExists('TWILIO_SMS_SERVICE_SID')
			&& !DotenvEditor::keyExists('TWILIO_DEBUG_TO')
		) {
			DotenvEditor::addEmpty();
			$envFileHasChanged = true;
		}
		
		if (array_key_exists('vonage_key', $setting->value)) {
			if (!empty($setting->value['vonage_key'])) {
				DotenvEditor::setKey('VONAGE_KEY', $setting->value['vonage_key']);
			} else {
				if (DotenvEditor::keyExists('VONAGE_KEY')) {
					DotenvEditor::deleteKey('VONAGE_KEY');
				}
			}
		}
		if (array_key_exists('vonage_secret', $setting->value)) {
			if (!empty($setting->value['vonage_secret'])) {
				DotenvEditor::setKey('VONAGE_SECRET', $setting->value['vonage_secret']);
			} else {
				if (DotenvEditor::keyExists('VONAGE_SECRET')) {
					DotenvEditor::deleteKey('VONAGE_SECRET');
				}
			}
		}
		if (array_key_exists('vonage_application_id', $setting->value)) {
			if (!empty($setting->value['vonage_application_id'])) {
				DotenvEditor::setKey('VONAGE_APPLICATION_ID', $setting->value['vonage_application_id']);
			} else {
				if (DotenvEditor::keyExists('VONAGE_APPLICATION_ID')) {
					DotenvEditor::deleteKey('VONAGE_APPLICATION_ID');
				}
			}
		}
		if (array_key_exists('vonage_from', $setting->value)) {
			if (!empty($setting->value['vonage_from'])) {
				DotenvEditor::setKey('VONAGE_SMS_FROM', $setting->value['vonage_from']);
			} else {
				if (DotenvEditor::keyExists('VONAGE_SMS_FROM')) {
					DotenvEditor::deleteKey('VONAGE_SMS_FROM');
				}
			}
		}
		if (array_key_exists('twilio_username', $setting->value)) {
			if (!empty($setting->value['twilio_username'])) {
				DotenvEditor::setKey('TWILIO_USERNAME', $setting->value['twilio_username']);
			} else {
				if (DotenvEditor::keyExists('TWILIO_USERNAME')) {
					DotenvEditor::deleteKey('TWILIO_USERNAME');
				}
			}
		}
		if (array_key_exists('twilio_password', $setting->value)) {
			if (!empty($setting->value['twilio_password'])) {
				DotenvEditor::setKey('TWILIO_PASSWORD', $setting->value['twilio_password']);
			} else {
				if (DotenvEditor::keyExists('TWILIO_PASSWORD')) {
					DotenvEditor::deleteKey('TWILIO_PASSWORD');
				}
			}
		}
		if (array_key_exists('twilio_auth_token', $setting->value)) {
			if (!empty($setting->value['twilio_auth_token'])) {
				DotenvEditor::setKey('TWILIO_AUTH_TOKEN', $setting->value['twilio_auth_token']);
			} else {
				if (DotenvEditor::keyExists('TWILIO_AUTH_TOKEN')) {
					DotenvEditor::deleteKey('TWILIO_AUTH_TOKEN');
				}
			}
		}
		if (array_key_exists('twilio_account_sid', $setting->value)) {
			if (!empty($setting->value['twilio_account_sid'])) {
				DotenvEditor::setKey('TWILIO_ACCOUNT_SID', $setting->value['twilio_account_sid']);
			} else {
				if (DotenvEditor::keyExists('TWILIO_ACCOUNT_SID')) {
					DotenvEditor::deleteKey('TWILIO_ACCOUNT_SID');
				}
			}
		}
		if (array_key_exists('twilio_from', $setting->value)) {
			if (!empty($setting->value['twilio_from'])) {
				DotenvEditor::setKey('TWILIO_FROM', $setting->value['twilio_from']);
			} else {
				if (DotenvEditor::keyExists('TWILIO_FROM')) {
					DotenvEditor::deleteKey('TWILIO_FROM');
				}
			}
		}
		if (array_key_exists('twilio_alpha_sender', $setting->value)) {
			if (!empty($setting->value['twilio_alpha_sender'])) {
				DotenvEditor::setKey('TWILIO_ALPHA_SENDER', $setting->value['twilio_alpha_sender']);
			} else {
				if (DotenvEditor::keyExists('TWILIO_ALPHA_SENDER')) {
					DotenvEditor::deleteKey('TWILIO_ALPHA_SENDER');
				}
			}
		}
		if (array_key_exists('twilio_sms_service_sid', $setting->value)) {
			if (!empty($setting->value['twilio_sms_service_sid'])) {
				DotenvEditor::setKey('TWILIO_SMS_SERVICE_SID', $setting->value['twilio_sms_service_sid']);
			} else {
				if (DotenvEditor::keyExists('TWILIO_SMS_SERVICE_SID')) {
					DotenvEditor::deleteKey('TWILIO_SMS_SERVICE_SID');
				}
			}
		}
		if (array_key_exists('twilio_debug_to', $setting->value)) {
			if (!empty($setting->value['twilio_debug_to'])) {
				DotenvEditor::setKey('TWILIO_DEBUG_TO', $setting->value['twilio_debug_to']);
			} else {
				if (DotenvEditor::keyExists('TWILIO_DEBUG_TO')) {
					DotenvEditor::deleteKey('TWILIO_DEBUG_TO');
				}
			}
		}
		
		if (
			array_key_exists('vonage_key', $setting->value)
			|| array_key_exists('vonage_secret', $setting->value)
			|| array_key_exists('vonage_from', $setting->value)
			|| array_key_exists('twilio_username', $setting->value)
			|| array_key_exists('twilio_password', $setting->value)
			|| array_key_exists('twilio_auth_token', $setting->value)
			|| array_key_exists('twilio_account_sid', $setting->value)
			|| array_key_exists('twilio_from', $setting->value)
			|| array_key_exists('twilio_alpha_sender', $setting->value)
			|| array_key_exists('twilio_sms_service_sid', $setting->value)
			|| array_key_exists('twilio_debug_to', $setting->value)
		) {
			$envFileHasChanged = true;
		}
		
		// Save the /.env file
		if ($envFileHasChanged) {
			DotenvEditor::save();
			
			// Some time of pause
			sleep(2);
		}
	}
}
