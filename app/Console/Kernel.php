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

namespace App\Console;

use App\Helpers\Date;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		\App\Console\Commands\Inspire::class,
		\App\Console\Commands\ListingsPurge::class,
	];
	
	/**
	 * @param \Illuminate\Contracts\Foundation\Application $app
	 * @param \Illuminate\Contracts\Events\Dispatcher $events
	 */
	public function __construct(Application $app, Dispatcher $events)
	{
		if (class_exists('\App\Console\Commands\DemoCleaner')) {
			$this->commands[] = \App\Console\Commands\DemoCleaner::class;
		}
		
		parent::__construct($app, $events);
	}
	
	/**
	 * Define the application's command schedule.
	 *
	 * @param \Illuminate\Console\Scheduling\Schedule $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		// Deleting Expired Tokens (Resetting Password)
		$schedule->command('auth:clear-resets')->everyFifteenMinutes();
		
		// Clear Listings
		$schedule->command('listings:purge')->hourly();
		
		// Backups
		setBackupConfig();
		$disableNotifications = (config('settings.backup.disable_notifications')) ? ' --disable-notifications' : '';
		
		// Taking Backups
		$takingBackup = config('settings.backup.taking_backup');
		if ($takingBackup != 'none') {
			$takingBackupAt = config('settings.backup.taking_backup_at');
			$takingBackupAt = ($takingBackupAt != '') ? $takingBackupAt : '00:00';
			
			if ($takingBackup == 'daily') {
				$schedule->command('backup:run' . $disableNotifications)->dailyAt($takingBackupAt);
			}
			if ($takingBackup == 'weekly') {
				$schedule->command('backup:run' . $disableNotifications)->weeklyOn(1, $takingBackupAt);
			}
			if ($takingBackup == 'monthly') {
				$schedule->command('backup:run' . $disableNotifications)->monthlyOn(1, $takingBackupAt);
			}
			if ($takingBackup == 'yearly') {
				$schedule->command('backup:run' . $disableNotifications)->yearlyOn(1, 1, $takingBackupAt);
			}
			
			// Cleaning Up Old Backups
			$schedule->command('backup:clean' . $disableNotifications)->daily();
		}
		
		// Clear Cache & Views
		if (!env('DISABLE_CACHE_AUTO_CLEAR') || (int)env('DISABLE_CACHE_AUTO_CLEAR', 0) != 1) {
			$schedule->command('cache:clear')->weeklyOn(7, '6:00');
			$schedule->command('cache:clear')->weeklyOn(7, '6:05'); // To prevent file lock issues (Optional)
			$schedule->command('view:clear')->weeklyOn(7, '6:00');
		}
	}
	
	/**
	 * Register the commands for the application.
	 *
	 * @return void
	 */
	protected function commands()
	{
		$this->load(__DIR__ . '/Commands');
		
		require base_path('routes/console.php');
	}
	
	/**
	 * Get the timezone that should be used by default for scheduled events.
	 *
	 * @return \DateTimeZone|string|null
	 */
	protected function scheduleTimezone()
	{
		// UTC
		return Date::getAppTimeZone();
	}
}
