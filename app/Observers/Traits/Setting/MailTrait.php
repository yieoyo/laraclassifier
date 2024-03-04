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

namespace App\Observers\Traits\Setting;

use App\Models\Permission;
use App\Models\User;
use App\Notifications\ExampleMail;
use App\Providers\AppService\ConfigTrait\MailConfig;
use Illuminate\Support\Facades\Notification;
use Prologue\Alerts\Facades\Alert;

trait MailTrait
{
	use MailConfig;
	
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return bool
	 */
	public function mailUpdating($setting, $original)
	{
		$validateDriverParameters = $setting->value['validate_driver'] ?? false;
		if ($validateDriverParameters) {
			$this->updateMailConfig($setting->value);
			
			/*
			 * Send Example Email
			 *
			 * With the sendmail driver, in local environment,
			 * this test email cannot be found if you have not familiar with the sendmail configuration
			 */
			try {
				if (config('settings.app.email')) {
					Notification::route('mail', config('settings.app.email'))->notify(new ExampleMail());
				} else {
					$admins = User::permission(Permission::getStaffPermissions())->get();
					if ($admins->count() > 0) {
						Notification::send($admins, new ExampleMail());
					}
				}
			} catch (\Throwable $e) {
				$message = $e->getMessage();
				
				if (isAdminPanel()) {
					Alert::error($message)->flash();
				} else {
					flash($message)->error();
				}
				
				return false;
			}
		}
	}
}
