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

use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class SubscriptionApproved extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $payment;
	protected $user;
	protected $package;
	protected $paymentMethod;
	
	public function __construct(Payment $payment, User $user)
	{
		$this->payment = $payment;
		$this->user = $user;
		$this->package = Package::find($payment->package_id);
		$this->paymentMethod = PaymentMethod::find($payment->payment_method_id);
	}
	
	public function via($notifiable)
	{
		if ($this->payment->active != 1) {
			return [];
		}
		
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
			->subject(trans('mail.subscription_approved_title'))
			->greeting(trans('mail.subscription_approved_content_1'))
			->line(trans('mail.subscription_approved_content_2', [
				'packageName' => (!empty($this->package->short_name))
					? $this->package->short_name
					: $this->package->name,
			]))
			->line(trans('mail.subscription_approved_content_3'))
			->line(trans('mail.subscription_approved_content_4', [
				'packageName'       => (!empty($this->package->short_name))
					? $this->package->short_name
					: $this->package->name,
				'userName'          => $this->user->name,
				'userId'            => $this->user->id,
				'amount'            => $this->package->price,
				'currency'          => $this->package->currency_code,
				'paymentMethodName' => $this->paymentMethod->display_name,
			]))
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
		return trans('sms.subscription_approved_content', [
			'appName'     => config('app.name'),
			'packageName' => (!empty($this->package->short_name))
				? $this->package->short_name
				: $this->package->name,
		]);
	}
}
