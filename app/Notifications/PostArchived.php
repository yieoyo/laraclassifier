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

use App\Helpers\Date;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Post;
use Illuminate\Support\Carbon;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PostArchived extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $post;
	protected $archivedPostsExpiration;
	
	public function __construct(Post $post, $archivedPostsExpiration)
	{
		$this->post = $post;
		$this->archivedPostsExpiration = $archivedPostsExpiration;
	}
	
	public function via($notifiable)
	{
		// Is email can be sent?
		$emailNotificationCanBeSent = (
			config('settings.mail.confirmation') == '1'
			&& !empty($this->post->email)
			&& !empty($this->post->archived_at)
		);
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			config('settings.sms.enable_phone_as_auth_field') == '1'
			&& config('settings.sms.confirmation') == '1'
			&& $this->post->auth_field == 'phone'
			&& !empty($this->post->phone)
			&& !empty($this->post->archived_at)
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
		$path = 'account/posts/archived/' . $this->post->id . '/repost';
		$repostUrl = (config('plugins.domainmapping.installed'))
			? dmUrl($this->post->country_code, $path)
			: url($path);
		
		return (new MailMessage)
			->subject(trans('mail.post_archived_title', ['title' => $this->post->title]))
			->greeting(trans('mail.post_archived_content_1'))
			->line(trans('mail.post_archived_content_2', [
				'title'   => $this->post->title,
				'now'     => Date::format(Carbon::now(Date::getAppTimeZone())),
				'appName' => config('app.name'),
			]))
			->line(trans('mail.post_archived_content_3', ['repostUrl' => $repostUrl]))
			->line(trans('mail.post_archived_content_4', [
				'dateDel' => Date::format($this->post->archived_at->addDays($this->archivedPostsExpiration)),
			]))
			->line(trans('mail.post_archived_content_5'))
			->line('<br>')
			->line(trans('mail.post_archived_content_6'))
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
		return trans('sms.post_archived_content', [
			'appName' => config('app.name'),
			'title'   => $this->post->title,
			'dateDel' => Date::format($this->post->archived_at->addDays($this->archivedPostsExpiration)),
		]);
	}
}
