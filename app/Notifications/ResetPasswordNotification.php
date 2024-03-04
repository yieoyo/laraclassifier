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

namespace App\Notifications;

use Carbon\CarbonInterval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $user;
	protected $token;
	protected $field;
	
	protected ?string $expireTimeString;
	
	public function __construct($user, $token, $field)
	{
		$this->user = $user;
		$this->token = $token;
		$this->field = $field;
		
		// Password Timeout String
		// Convert seconds into days hours minutes
		$passwordTimeout = (int)config('auth.password_timeout', 10800);
		$passwordTimeout = ($passwordTimeout < 1) ? 1 : $passwordTimeout;
		try {
			$this->expireTimeString = CarbonInterval::seconds($passwordTimeout)->cascade()->forHumans();
		} catch (\Throwable $e) {
			$this->expireTimeString = $passwordTimeout . ' minute(s)';
		}
	}
	
	public function via($notifiable)
	{
		if ($this->field == 'phone') {
			if (config('settings.sms.driver') == 'twilio') {
				return [TwilioChannel::class];
			}
			
			return ['vonage'];
		} else {
			return ['mail'];
		}
	}
	
	public function toMail($notifiable)
	{
		$path = 'password/reset/' . $this->token;
		$resetPasswordUrl = (config('plugins.domainmapping.installed'))
			? dmUrl(config('country.code'), $path)
			: url($path);
		
		return (new MailMessage)
			->subject(trans('mail.reset_password_title'))
			->line(trans('mail.reset_password_content_1'))
			->action(trans('mail.reset_password_action'), $resetPasswordUrl)
			->line(trans('mail.reset_password_content_2', ['expireTimeString' => $this->expireTimeString]))
			->line(trans('mail.reset_password_content_3'))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
	
	public function toVonage($notifiable)
	{
		return (new VonageMessage())->content($this->smsMessage())->unicode();
	}
	
	public function toTwilio($notifiable)
	{
		return (new TwilioSmsMessage())->content($this->smsMessage());
	}
	
	protected function smsMessage()
	{
		return trans('sms.reset_password_content', [
			'appName'          => config('app.name'),
			'token'            => $this->token,
			'expireTimeString' => $this->expireTimeString,
		]);
	}
}
