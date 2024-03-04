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

trait MailConfig
{
	/**
	 * Get un-selected mail drivers parameters to avoid to store them in session
	 *
	 * @param array|null $mailRules
	 * @param string|null $mailDriver
	 * @return array
	 */
	private function getUnSelectedMailDriversParameters(?array $mailRules, ?string $mailDriver = null): array
	{
		$exceptInput = [];
		$mailUnnecessaryInput = $mailRules;
		if (isset($mailUnnecessaryInput[$mailDriver])) {
			unset($mailUnnecessaryInput[$mailDriver]);
		}
		if (!empty($mailUnnecessaryInput)) {
			foreach ($mailUnnecessaryInput as $iRules) {
				$exceptInput = array_merge($exceptInput, array_keys($iRules));
			}
		}
		
		return $exceptInput;
	}
	
	private function updateMailConfig(?array $settings = [], ?string $appName = null): void
	{
		if (empty($settings)) {
			return;
		}
		
		// Mail
		config()->set('mail.default', env('MAIL_MAILER', env('MAIL_DRIVER', $settings['driver'] ?? null)));
		config()->set('mail.from.name', env('MAIL_FROM_NAME', config('settings.app.name', $appName)));
		
		// Old Config
		$mailHost = $settings['host'] ?? null;
		$mailPort = $settings['port'] ?? null;
		$mailEncryption = $settings['encryption'] ?? null;
		$mailUsername = $settings['username'] ?? null;
		$mailPassword = $settings['password'] ?? null;
		$mailSender = $settings['email_sender'] ?? null;
		
		// Default Mail Sender (from Installer)
		if (empty($mailSender)) {
			$mailSender = $settings['email'] ?? null;
		}
		
		// SMTP
		if (config('mail.default') == 'smtp') {
			config()->set('mail.mailers.smtp.host', env('MAIL_HOST', $settings['smtp_host'] ?? ($mailHost ?? null)));
			config()->set('mail.mailers.smtp.port', env('MAIL_PORT', $settings['smtp_port'] ?? ($mailPort ?? null)));
			config()->set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', $settings['smtp_encryption'] ?? ($mailEncryption ?? null)));
			config()->set('mail.mailers.smtp.username', env('MAIL_USERNAME', $settings['smtp_username'] ?? ($mailUsername ?? null)));
			config()->set('mail.mailers.smtp.password', env('MAIL_PASSWORD', $settings['smtp_password'] ?? ($mailPassword ?? null)));
			config()->set('mail.from.address', env('MAIL_FROM_ADDRESS', $settings['smtp_email_sender'] ?? ($mailSender ?? null)));
		}
		
		// Sendmail
		if (config('mail.default') == 'sendmail') {
			config()->set('mail.mailers.sendmail.path', env('MAIL_SENDMAIL', $settings['sendmail_path'] ?? null));
			config()->set('mail.from.address', env('MAIL_FROM_ADDRESS', $settings['sendmail_email_sender'] ?? ($mailSender ?? null)));
		}
		
		// Mailgun
		if (config('mail.default') == 'mailgun') {
			config()->set('services.mailgun.domain', env('MAILGUN_DOMAIN', $settings['mailgun_domain'] ?? null));
			config()->set('services.mailgun.secret', env('MAILGUN_SECRET', $settings['mailgun_secret'] ?? null));
			config()->set('services.mailgun.endpoint', env('MAILGUN_ENDPOINT', $settings['mailgun_endpoint'] ?? ('api.mailgun.net' ?? null)));
			config()->set('mail.mailers.smtp.host', env('MAIL_HOST', $settings['mailgun_host'] ?? ($mailHost ?? null)));
			config()->set('mail.mailers.smtp.port', env('MAIL_PORT', $settings['mailgun_port'] ?? ($mailPort ?? null)));
			config()->set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', $settings['mailgun_encryption'] ?? ($mailEncryption ?? null)));
			config()->set('mail.mailers.smtp.username', env('MAIL_USERNAME', $settings['mailgun_username'] ?? ($mailUsername ?? null)));
			config()->set('mail.mailers.smtp.password', env('MAIL_PASSWORD', $settings['mailgun_password'] ?? ($mailPassword ?? null)));
			config()->set('mail.from.address', env('MAIL_FROM_ADDRESS', $settings['mailgun_email_sender'] ?? ($mailSender ?? null)));
		}
		
		// Postmark
		if (config('mail.default') == 'postmark') {
			config()->set('services.postmark.token', env('POSTMARK_TOKEN', $settings['postmark_token'] ?? null));
			config()->set('mail.mailers.smtp.host', env('MAIL_HOST', $settings['postmark_host'] ?? ($mailHost ?? null)));
			config()->set('mail.mailers.smtp.port', env('MAIL_PORT', $settings['postmark_port'] ?? ($mailPort ?? null)));
			config()->set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', $settings['postmark_encryption'] ?? ($mailEncryption ?? null)));
			config()->set('mail.mailers.smtp.username', env('MAIL_USERNAME', $settings['postmark_username'] ?? ($mailUsername ?? null)));
			config()->set('mail.mailers.smtp.password', env('MAIL_PASSWORD', $settings['postmark_password'] ?? ($mailPassword ?? null)));
			config()->set('mail.from.address', env('MAIL_FROM_ADDRESS', $settings['postmark_email_sender'] ?? ($mailSender ?? null)));
		}
		
		// Amazon SES
		if (config('mail.default') == 'ses') {
			config()->set('services.ses.key', env('SES_KEY', $settings['ses_key'] ?? null));
			config()->set('services.ses.secret', env('SES_SECRET', $settings['ses_secret'] ?? null));
			config()->set('services.ses.region', env('SES_REGION', $settings['ses_region'] ?? null));
			config()->set('services.ses.token', env('SES_SESSION_TOKEN', $settings['ses_token'] ?? null));
			config()->set('mail.mailers.smtp.host', env('MAIL_HOST', $settings['ses_host'] ?? ($mailHost ?? null)));
			config()->set('mail.mailers.smtp.port', env('MAIL_PORT', $settings['ses_port'] ?? ($mailPort ?? null)));
			config()->set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', $settings['ses_encryption'] ?? ($mailEncryption ?? null)));
			config()->set('mail.mailers.smtp.username', env('MAIL_USERNAME', $settings['ses_username'] ?? ($mailUsername ?? null)));
			config()->set('mail.mailers.smtp.password', env('MAIL_PASSWORD', $settings['ses_password'] ?? ($mailPassword ?? null)));
			config()->set('mail.from.address', env('MAIL_FROM_ADDRESS', $settings['ses_email_sender'] ?? ($mailSender ?? null)));
		}
		
		// Sparkpost
		if (config('mail.default') == 'sparkpost') {
			config()->set('services.sparkpost.secret', env('SPARKPOST_SECRET', $settings['sparkpost_secret'] ?? null));
			config()->set('mail.mailers.smtp.host', env('MAIL_HOST', $settings['sparkpost_host'] ?? ($mailHost ?? null)));
			config()->set('mail.mailers.smtp.port', env('MAIL_PORT', $settings['sparkpost_port'] ?? ($mailPort ?? null)));
			config()->set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION', $settings['sparkpost_encryption'] ?? ($mailEncryption ?? null)));
			config()->set('mail.mailers.smtp.username', env('MAIL_USERNAME', $settings['sparkpost_username'] ?? ($mailUsername ?? null)));
			config()->set('mail.mailers.smtp.password', env('MAIL_PASSWORD', $settings['sparkpost_password'] ?? ($mailPassword ?? null)));
			config()->set('mail.from.address', env('MAIL_FROM_ADDRESS', $settings['sparkpost_email_sender'] ?? ($mailSender ?? null)));
		}
	}
}
