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
use App\Models\Post;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PostReviewed extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $post;
	
	public function __construct(Post $post)
	{
		$this->post = $post;
	}
	
	public function via($notifiable)
	{
		// Is email can be sent?
		$emailNotificationCanBeSent = (
			config('settings.mail.confirmation') == '1'
			&& !empty($this->post->email)
			&& !empty($this->post->email_verified_at)
		);
		if (config('settings.single.listings_review_activation') == '1') {
			$emailNotificationCanBeSent = ($emailNotificationCanBeSent && !empty($this->post->reviewed_at));
		}
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			config('settings.sms.enable_phone_as_auth_field') == '1'
			&& config('settings.sms.confirmation') == '1'
			&& $this->post->auth_field == 'phone'
			&& !empty($this->post->phone)
			&& !empty($this->post->phone_verified_at)
			&& !isDemoDomain()
		);
		if (config('settings.single.listings_review_activation') == '1') {
			$smsNotificationCanBeSent = ($smsNotificationCanBeSent && !empty($this->post->reviewed_at));
		}
		
		// Get the notification channel
		$channels = [];
		
		if ($emailNotificationCanBeSent) {
			$channels[] = 'mail';
		}
		
		if ($smsNotificationCanBeSent) {
			if (config('settings.sms.driver') == 'twilio') {
				$channels[] = TwilioChannel::class;
			}
			if (config('settings.sms.driver') == 'vonage') {
				$channels[] = 'vonage';
			}
		}
		
		return $channels;
	}
	
	public function toMail($notifiable)
	{
		$postUrl = UrlGen::post($this->post);
		
		return (new MailMessage)
			->subject(trans('mail.post_reviewed_title', ['title' => str($this->post->title)->limit(50)]))
			->greeting(trans('mail.post_reviewed_content_1'))
			->line(trans('mail.post_reviewed_content_2', [
				'postUrl' => $postUrl,
				'title'   => $this->post->title,
			]))
			->line(trans('mail.post_reviewed_content_3', ['appName' => config('app.name')]))
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
	
	// PRIVATE
	
	private function smsMessage()
	{
		return trans('sms.post_reviewed_content', ['appName' => config('app.name'), 'title' => $this->post->title]);
	}
}
