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

namespace App\Http\Controllers\Web\Install\Traits\Install\Db;

use App\Models\Country;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait MigrationsTrait
{
	/**
	 * Import from Laravel Migrations
	 * php artisan migrate --path=/database/migrations --force
	 * php artisan migrate --path=/vendor/laravel/sanctum/database/migrations --force
	 *
	 * Rollback & Re-runs all the Migrations
	 * php artisan migrate:refresh --path=/database/migrations --force
	 *
	 * Drop All Tables & Migrate
	 * php artisan migrate:fresh --path=/database/migrations --force
	 */
	protected function runMigrations(): void
	{
		Artisan::call('migrate', [
			'--path'  => '/database/migrations',
			'--force' => true,
		]);
		
		// Run Sanctum Migrations
		Artisan::call('migrate', [
			'--path'  => '/vendor/laravel/sanctum/database/migrations',
			'--force' => true,
		]);
		
		// sleep(2);
	}
	
	/**
	 * Import from Laravel Seeders
	 * php artisan db:seed --force
	 */
	protected function runSeeders(): void
	{
		Artisan::call('db:seed', ['--force' => true]);
		
		// sleep(2);
	}
	
	/**
	 * Insert & Update the Site Information
	 *
	 * @param $siteInfo
	 */
	protected function runSiteInfoSeeder($siteInfo): void
	{
		try {
			
			// Disable foreign key constraints (Temporarily)
			DB::statement('SET FOREIGN_KEY_CHECKS=0;');
			
			// USERS - Insert default superuser
			$usersTable = (new User())->getTable();
			DB::table($usersTable)->truncate();
			$user = [
				'country_code'      => $siteInfo['default_country'],
				'user_type_id'      => 1,
				'gender_id'         => 1,
				'name'              => $siteInfo['name'],
				'about'             => 'Administrator',
				'email'             => $siteInfo['email'],
				'password'          => Hash::make($siteInfo['password']),
				'is_admin'          => 1,
				'email_verified_at' => now(),
				'phone_verified_at' => now(),
				'created_at'        => now(),
				'updated_at'        => now(),
			];
			DB::table($usersTable)->insert($user);
			
			// Setup ACL system
			$this->setupAclSystem();
			
			// COUNTRIES - Activate default country
			$countriesTable = (new Country())->getTable();
			DB::table($countriesTable)->where('code', $siteInfo['default_country'])->update(['active' => 1]);
			
			// SETTINGS - Update settings
			$settingsTable = (new Setting())->getTable();
			
			// App
			$app = [
				'purchase_code' => $siteInfo['purchase_code'] ?? '',
				'name'          => $siteInfo['site_name'] ?? '',
				'slogan'        => $siteInfo['site_slogan'] ?? '',
				'email'         => $siteInfo['email'] ?? '',
			];
			DB::table($settingsTable)->where('key', 'app')->update(['value' => json_encode($app)]);
			
			// Localization
			$localization = [
				'default_country_code' => $siteInfo['default_country'] ?? '',
			];
			DB::table($settingsTable)->where('key', 'localization')->update(['value' => json_encode($localization)]);
			
			// Mail
			$driver = (!empty($siteInfo['driver'])) ? $siteInfo['driver'] : 'sendmail';
			
			$mail = [];
			$mail['driver'] = $driver;
			if ($driver == 'sendmail') {
				$mail['sendmail_path'] = $siteInfo['sendmail_path'] ?? '/usr/sbin/sendmail -bs';
			}
			if ($driver == 'smtp') {
				// ...
			}
			if ($driver == 'mailgun') {
				$mail['mailgun_domain'] = $siteInfo['mailgun_domain'] ?? '';
				$mail['mailgun_secret'] = $siteInfo['mailgun_secret'] ?? '';
				$mail['mailgun_endpoint'] = $siteInfo['mailgun_endpoint'] ?? 'api.mailgun.net';
			}
			if ($driver == 'postmark') {
				$mail['postmark_token'] = $siteInfo['postmark_token'] ?? '';
			}
			if ($driver == 'ses') {
				$mail['ses_key'] = $siteInfo['ses_key'] ?? '';
				$mail['ses_secret'] = $siteInfo['ses_secret'] ?? '';
				$mail['ses_region'] = $siteInfo['ses_region'] ?? '';
				$mail['ses_token'] = $siteInfo['ses_token'] ?? '';
			}
			if ($driver == 'sparkpost') {
				$mail['sparkpost_secret'] = $siteInfo['sparkpost_secret'] ?? '';
			}
			
			// Exclude 'sendmail' that is the only driver that doesn't have SMTP parameters
			if ($driver != 'sendmail') {
				$smtpHost = $driver . '_host';
				$smtpPort = $driver . '_port';
				$smtpEncryption = $driver . '_encryption';
				$smtpUsername = $driver . '_username';
				$smtpPassword = $driver . '_password';
				
				$mail[$smtpHost] = $siteInfo[$smtpHost] ?? '';
				$mail[$smtpPort] = $siteInfo[$smtpPort] ?? '';
				$mail[$smtpEncryption] = $siteInfo[$smtpEncryption] ?? '';
				$mail[$smtpUsername] = $siteInfo[$smtpUsername] ?? '';
				$mail[$smtpPassword] = $siteInfo[$smtpPassword] ?? '';
			}
			
			$appEmail = $siteInfo['email'] ?? '';
			$emailSender = $driver . '_email_sender';
			$mail[$emailSender] = (!empty($siteInfo[$emailSender])) ? $siteInfo[$emailSender] : $appEmail;
			
			DB::table($settingsTable)->where('key', 'mail')->update(['value' => json_encode($mail)]);
			
			// Re-Enable back foreign key constraints
			DB::statement('SET FOREIGN_KEY_CHECKS=1;');
			
		} catch (\PDOException $e) {
			dd($e->getMessage());
		} catch (\Throwable $e) {
			dd($e->getMessage());
		}
	}
}
