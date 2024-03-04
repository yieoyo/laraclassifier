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

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class SubscriptionPurchased extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $payment;
	protected $user;
	protected $packageName;
	
	public function __construct(Payment $payment, User $user)
	{
		$this->payment = $payment;
		$this->user = $user;
		
		$this->packageName = null;
		if (!empty($payment->package)) {
			$this->packageName = !empty($payment->package->short_name)
				? $payment->package->short_name
				: $payment->package->name;
		}
	}
	
	public function via($notifiable)
	{
		// Is email can be sent?
		$emailNotificationCanBeSent = (config('settings.mail.confirmation') == '1' && !empty($this->user->email));
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			config('settings.sms.enable_phone_as_auth_field') == '1'
			&& config('settings.sms.confirmation') == '1'
			&& $this->user->auth_field == 'phone'
			&& !empty($this->user->phone)
			&& !isDemoDomain()
		);
		
		if ($emailNotificationCanBeSent) {
			return ['mail'];
		}
		
		if ($smsNotificationCanBeSent) {
			if (config('settings.sms.driver') == 'twilio') {
				return [TwilioChannel::class];
			}
			
			return ['vonage'];
		}
		
		return [];
	}
	
	public function toMail($notifiable)
	{
		return (new MailMessage)
			->subject(trans('mail.subscription_purchased_title'))
			->greeting(trans('mail.subscription_purchased_content_1'))
			->line(trans('mail.subscription_purchased_content_2', [
				'packageName' => $this->packageName,
			]))
			->line(trans('mail.subscription_purchased_content_3'))
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
		return trans('sms.subscription_purchased_content', [
			'appName'     => config('app.name'),
			'packageName' => $this->packageName,
		]);
	}
}
