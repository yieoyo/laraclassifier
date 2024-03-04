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

namespace App\Exceptions\Traits;

use App\Notifications\ExceptionOccurred;
use Illuminate\Support\Facades\Notification;
use Throwable;

trait NotificationTrait
{
	/**
	 * @param \Throwable $e
	 * @return void
	 */
	public function sendNotification(Throwable $e): void
	{
		if ($this->isFullMemoryException($e)) {
			die($this->getFullMemoryMessage($e));
		}
		
		if (str_contains(currentRouteAction(), 'InstallController')) {
			return;
		}
		
		if (!config('larapen.core.sendNotificationOnError')) {
			return;
		}
		
		try {
			$fullUrl = request()->fullUrl();
			$isFromApi = (isFromApi() || str_contains($fullUrl, '/api/'));
			
			$content = [];
			// The request
			$content['method'] = request()->getMethod();
			if ($isFromApi) {
				$content['endpoint'] = $fullUrl;
				if (request()->hasHeader('X-WEB-REQUEST-URL')) {
					$content['url'] = request()->header('X-WEB-REQUEST-URL');
				}
				if (request()->hasHeader('X-IP')) {
					$content['ip'] = request()->header('X-IP');
				}
			} else {
				$content['url'] = $fullUrl;
				$content['ip'] = request()->ip();
			}
			$content['userAgent'] = request()->server('HTTP_USER_AGENT');
			$content['referer'] = request()->server('HTTP_REFERER');
			$content['body'] = request()->all();
			
			// The error
			$content['message'] = $e->getMessage();
			$content['file'] = $e->getFile();
			$content['line'] = $e->getLine();
			$content['trace'] = $e->getTrace();
			
			// Send notification
			Notification::route('mail', config('settings.app.email'))->notify(new ExceptionOccurred($content));
			
		} catch (Throwable $e) {
			// dd($e); // debug
		}
	}
}
