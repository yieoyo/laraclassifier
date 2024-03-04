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

namespace App\Providers\AppService\ConfigTrait;

trait SmsConfig
{
	private function updateSmsConfig(?array $settings = [], ?string $appName = null): void
	{
		if (empty($settings)) {
			return;
		}
		
		$driver = $settings['driver'] ?? null;
		
		// Vonage
		if ($driver == 'vonage') {
			config()->set('vonage.api_key', $settings['vonage_key'] ?? null);
			config()->set('vonage.api_secret', $settings['vonage_secret'] ?? null);
			config()->set('vonage.application_id', $settings['vonage_application_id'] ?? null);
			config()->set('vonage.sms_from', $settings['vonage_from'] ?? null);
			config()->set('vonage.app.name', env('VONAGE_APP_NAME', $appName ?? config('app.name')));
			config()->set('vonage.app.version', env('VONAGE_APP_VERSION', config('version.app')));
		}
		
		// Twilio
		if ($driver == 'twilio') {
			config()->set('twilio-notification-channel.username', $settings['twilio_username'] ?? null);
			config()->set('twilio-notification-channel.password', $settings['twilio_password'] ?? null);
			config()->set('twilio-notification-channel.auth_token', $settings['twilio_auth_token'] ?? null);
			config()->set('twilio-notification-channel.account_sid', $settings['twilio_account_sid'] ?? null);
			config()->set('twilio-notification-channel.from', $settings['twilio_from'] ?? null);
			config()->set('twilio-notification-channel.alphanumeric_sender', $settings['twilio_alpha_sender'] ?? null);
			config()->set('twilio-notification-channel.sms_service_sid', $settings['twilio_sms_service_sid'] ?? null);
			config()->set('twilio-notification-channel.debug_to', $settings['twilio_debug_to'] ?? null);
		}
	}
}
