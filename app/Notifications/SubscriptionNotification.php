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
use App\Models\PaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriptionNotification extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $payment;
	protected $user;
	protected $package;
	protected $paymentMethod;
	
	public function __construct($payment, $user)
	{
		$this->payment = $payment;
		$this->user = $user;
		$this->package = Package::find($payment->package_id);
		$this->paymentMethod = PaymentMethod::find($payment->payment_method_id);
	}
	
	public function via($notifiable)
	{
		return ['mail'];
	}
	
	public function toMail($notifiable)
	{
		return (new MailMessage)
			->subject(trans('mail.subscription_notification_title'))
			->greeting(trans('mail.subscription_notification_content_1'))
			->line(trans('mail.subscription_notification_content_2', [
				'userName'    => $this->user->name,
				'packageName' => (!empty($this->package->short_name))
					? $this->package->short_name
					: $this->package->name,
			]))
			->line('<br>')
			->line(trans('mail.subscription_notification_content_3', [
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
}
