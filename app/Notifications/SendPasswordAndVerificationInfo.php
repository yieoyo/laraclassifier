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

use App\Helpers\UrlGen;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class SendPasswordAndVerificationInfo extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $user;
	protected $randomPassword;
	
	public function __construct($user, $randomPassword)
	{
		$this->user = $user;
		$this->randomPassword = $randomPassword;
	}
	
	public function via($notifiable)
	{
		$authField = $this->user->auth_field ?? getAuthField();
		
		if ($authField == 'phone') {
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
		$path = 'users/verify/email/' . $this->user->email_token;
		$verificationUrl = (config('plugins.domainmapping.installed'))
			? dmUrl($this->user->country_code, $path)
			: url($path);
		
		$loginUrl = UrlGen::login();
		
		$mailMessage = (new MailMessage)
			->subject(trans('mail.generated_password_title'))
			->greeting(trans('mail.generated_password_content_1', ['userName' => $this->user->name]))
			->line(trans('mail.generated_password_content_2'));
		
		if (!isVerifiedUser($this->user)) {
			$mailMessage->line(trans('mail.generated_password_verify_content_3'))
				->action(trans('mail.generated_password_verify_action'), $verificationUrl);
		}
		
		$mailMessage->line(trans('mail.generated_password_content_4', ['randomPassword' => $this->randomPassword]));
		
		if (isVerifiedUser($this->user)) {
			$mailMessage->action(trans('mail.generated_password_login_action'), $loginUrl);
		}
		
		$mailMessage->line(trans('mail.generated_password_content_6', ['appName' => config('app.name')]))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
		
		return $mailMessage;
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
		$token = $this->user->phone_token;
		
		$path = 'users/verify/phone/' . $token;
		$tokenUrl = (config('plugins.domainmapping.installed'))
			? dmUrl($this->user->country_code, $path)
			: url($path);
		
		return trans('sms.generated_password_content', [
			'appName'        => config('app.name'),
			'randomPassword' => $this->randomPassword,
			'token'          => $token,
			'tokenUrl'       => $tokenUrl,
		]);
	}
}
