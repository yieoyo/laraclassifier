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

namespace App\Http\Controllers\Web\Install;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../../Helpers/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

use App\Helpers\Cookie;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Install\Traits\Install\ApiTrait;
use App\Http\Controllers\Web\Install\Traits\Install\CheckerTrait;
use App\Http\Controllers\Web\Install\Traits\Install\DbTrait;
use App\Http\Controllers\Web\Install\Traits\Install\EnvTrait;
use App\Notifications\ExampleMail;
use App\Providers\AppService\ConfigTrait\MailConfig;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;

class InstallController extends Controller
{
	use ApiTrait, CheckerTrait, MailConfig, EnvTrait, DbTrait;
	
	public string $baseUrl;
	public string $installUrl;
	
	public function __construct()
	{
		$this->middleware(function ($request, $next) {
			$this->commonQueries();
			
			return $next($request);
		});
		
		// Create SQL destination path if not exists
		$countriesDataDir = storage_path('app/database/geonames/countries');
		if (!File::exists($countriesDataDir)) {
			File::makeDirectory($countriesDataDir, 0755, true);
		}
		
		// Base URL
		$this->baseUrl = getRawBaseUrl();
		view()->share('baseUrl', $this->baseUrl);
		config()->set('app.url', $this->baseUrl);
		
		// Installation URL
		$this->installUrl = $this->baseUrl . '/install';
		view()->share('installUrl', $this->installUrl);
	}
	
	/**
	 * Common Queries
	 *
	 * @return void
	 */
	public function commonQueries(): void
	{
		// Delete all front&back office sessions
		session()->forget('countryCode');
		session()->forget('timeZone');
		session()->forget('langCode');
		
		// Get country code by the user IP address
		// This method set its result in cookie,
		// that is used in the view instead of the local variable '$ipCountryCode'
		$ipCountryCode = $this->getCountryCodeFromIPAddr();
	}
	
	/**
	 * Checking for the current step
	 *
	 * @return int
	 */
	public function step(): int
	{
		$step = 0;
		
		$data = session('isCompatible');
		if (isset($data)) {
			$step = 1;
		} else {
			return $step;
		}
		
		$data = session('siteInfo');
		if (isset($data)) {
			$step = 3;
		} else {
			return $step;
		}
		
		$data = session('database');
		if (isset($data)) {
			$step = 4;
		} else {
			return $step;
		}
		
		$data = session('databaseImported');
		if (isset($data)) {
			$step = 5;
		} else {
			return $step;
		}
		
		$data = session('cronJobs');
		if (isset($data)) {
			$step = 6;
		} else {
			return $step;
		}
		
		return $step;
	}
	
	/**
	 * STEP 0 - Starting installation
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function starting()
	{
		Artisan::call('cache:clear');
		Artisan::call('config:clear');
		
		// Get possible query string
		$queryString = request()->getQueryString();
		$queryString = !empty($queryString) ? '?' . $queryString : '';
		
		return redirect()->to($this->installUrl . '/system_compatibility' . $queryString);
	}
	
	/**
	 * STEP 1 - Check System Compatibility
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function systemCompatibility()
	{
		session()->forget('isCompatible');
		
		// Check Components & Permissions
		$checkComponents = $this->checkComponents();
		$checkPermissions = $this->checkPermissions();
		$isCompatible = $checkComponents && $checkPermissions;
		
		// 1. Auto-Checking: Skip this step If the system is OK
		$isCompatibleWithAutoRedirect = $isCompatible && !$this->isManualCheckingAllowed();
		if ($isCompatibleWithAutoRedirect) {
			session()->put('isCompatible', ($isCompatible ? 1 : 0));
			
			// Get possible query string
			$queryString = !empty(request()->getQueryString()) ? '?' . request()->getQueryString() : '';
			
			return redirect()->to($this->installUrl . '/site_info' . $queryString);
		}
		
		// 2. Check the compatibilities manually: Retry if something does not work yet
		try {
			if ($isCompatible) {
				session()->put('isCompatible', 1);
			}
			
			return appView('install.compatibilities', [
				'components'       => $this->getComponents(),
				'permissions'      => $this->getPermissions(),
				'checkComponents'  => $checkComponents,
				'checkPermissions' => $checkPermissions,
				'step'             => $this->step(),
				'current'          => 1,
			]);
		} catch (\Throwable $e) {
			Artisan::call('cache:clear');
			Artisan::call('config:clear');
			
			return redirect()->to($this->installUrl . '/system_compatibility');
		}
	}
	
	/**
	 * STEP 2 - Set Site Info
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function siteInfo()
	{
		if ($this->step() < 1) {
			return redirect()->to($this->installUrl . '/system_compatibility');
		}
		
		// Remove the installed file (if it does exist)
		$installedFile = storage_path('installed');
		if (File::exists($installedFile)) {
			File::delete($installedFile);
		}
		
		// Unactivated all add-ons/plugins by removing their installed file
		$pluginsDir = storage_path('framework/plugins');
		$leaveFiles = ['.gitignore'];
		foreach (glob($pluginsDir . '/*') as $file) {
			if (!in_array(basename($file), $leaveFiles)) {
				@unlink($file);
			}
		}
		
		// Make sure the session is working
		$rules = [
			'site_name'       => 'required',
			'site_slogan'     => 'required',
			'name'            => 'required',
			'purchase_code'   => 'required',
			'email'           => 'required|email',
			'password'        => 'required',
			'default_country' => 'required',
		];
		
		// Check if the selected mail driver parameters need to be validated
		$validateDriverParameters = (request()->filled('driver') && request()->input('validate_driver'));
		
		// Mail Driver's Rules
		$mailRules = [];
		$mailRules['sendmail'] = [];
		if ($validateDriverParameters) {
			$mailRules['sendmail']['sendmail_path'] = 'required';
		}
		$mailRules['smtp'] = [
			'smtp_host'       => 'required',
			'smtp_port'       => 'required',
			// 'smtp_username'   => 'required',
			// 'smtp_password'   => 'required',
			// 'smtp_encryption' => 'required',
		];
		$mailRules['mailgun'] = [
			'mailgun_domain'     => 'required',
			'mailgun_secret'     => 'required',
			'mailgun_host'       => 'required',
			'mailgun_port'       => 'required',
			'mailgun_username'   => 'required',
			'mailgun_password'   => 'required',
			'mailgun_encryption' => 'required',
		];
		$mailRules['postmark'] = [
			'postmark_token'      => 'required',
			'postmark_host'       => 'required',
			'postmark_port'       => 'required',
			'postmark_username'   => 'required',
			'postmark_password'   => 'required',
			'postmark_encryption' => 'required',
		];
		$mailRules['ses'] = [
			'ses_key'        => 'required',
			'ses_secret'     => 'required',
			'ses_region'     => 'required',
			'ses_host'       => 'required',
			'ses_port'       => 'required',
			'ses_username'   => 'required',
			'ses_password'   => 'required',
			'ses_encryption' => 'required',
		];
		$mailRules['sparkpost'] = [
			'sparkpost_secret'     => 'required',
			'sparkpost_host'       => 'required',
			'sparkpost_port'       => 'required',
			'sparkpost_username'   => 'required',
			'sparkpost_password'   => 'required',
			'sparkpost_encryption' => 'required',
		];
		
		// Get Mail Driver
		$mailDriver = request()->input('driver');
		
		// Validate and save posted data
		if (request()->isMethod('POST')) {
			session()->forget('siteInfo');
			
			// Check purchase code
			$purchaseCodeData = $this->purchaseCodeChecker(request()->input('purchase_code'));
			
			$isValid = data_get($purchaseCodeData, 'valid');
			$doesPurchaseCodeIsValid = (is_bool($isValid) && $isValid == true);
			
			$messages = [];
			if (!$doesPurchaseCodeIsValid) {
				$errorMessage = data_get($purchaseCodeData, 'message');
				$errorMessage = !empty($errorMessage) ? ' ERROR: <strong>' . $errorMessage . '</strong>' : '';
				$errorMessage = 'The :attribute field is required.' . $errorMessage;
				
				$rules['purchase_code_valid'] = 'required';
				$messages['purchase_code_valid.required'] = $errorMessage;
			}
			
			// Merge all rules
			$rules = array_merge($rules, $mailRules[$mailDriver] ?? []);
			
			// Validate requirements
			$validatedData = request()->validate($rules, $messages);
			
			// Check mail sending parameters
			if ($validateDriverParameters) {
				// Set Mail Config
				$this->updateMailConfig(request()->all(), request()->input('site_name'));
				
				$rules = [];
				$messages = [];
				try {
					
					/*
					 * Send Example Email
					 *
					 * With the sendmail driver, in local environment,
					 * this test email cannot be found if you have not familiar with the sendmail configuration
					 */
					Notification::route('mail', request()->input('email'))->notify(new ExampleMail());
					
				} catch (\Throwable $e) {
					$ruleKey = $mailDriver . '_valid';
					$rules[$ruleKey] = 'required';
					
					$errorMsg = $e->getMessage();
					if (empty($errorMsg)) {
						$errorMsg = 'Error in the mail sending parameters.';
						$errorMsg .= ' Please contact your mail sending server\'s provider for more information.';
					}
					$messages = [$ruleKey . '.required' => $errorMsg];
				}
				
				// Validate requirements
				$validatedData = request()->validate($rules, $messages);
			}
			
			// Get unselected mail drivers parameters to avoid storing them in session
			$exceptInput = $this->getUnSelectedMailDriversParameters($mailRules, $mailDriver);
			
			// Save data in session
			session()->put('siteInfo', request()->except($exceptInput));
			
			return redirect()->to($this->installUrl . '/database');
		}
		
		$siteInfo = request()->old();
		$siteInfo = !empty($siteInfo) ? $siteInfo : session('siteInfo');
		
		return appView('install.site_info', [
			'siteInfo'  => $siteInfo,
			'rules'     => $rules,
			'mailRules' => $mailRules,
			'step'      => $this->step(),
			'current'   => 2,
		]);
	}
	
	/**
	 * STEP 3 - Database configuration
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function database()
	{
		if ($this->step() < 2) {
			return redirect()->to($this->installUrl . '/site_info');
		}
		
		// Check required fields
		$rules = [
			'host'     => 'required',
			'port'     => 'required',
			'username' => 'required',
			'database' => 'required',
		];
		
		// Validate and save posted data
		if (request()->isMethod('POST')) {
			session()->forget('database');
			
			// Validate requirements
			$validatedData = request()->validate($rules);
			
			// Check the Database Connection
			$messages = [];
			try {
				// Database Parameters
				$driver = config('database.connections.' . config('database.default') . '.driver', 'mysql');
				$charset = config('database.connections.' . config('database.default') . '.charset', 'utf8mb4');
				$port = (int)request()->input('port');
				$options = [
					\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
					\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
					\PDO::ATTR_EMULATE_PREPARES   => true,
					\PDO::ATTR_CURSOR             => \PDO::CURSOR_FWDONLY,
				];
				
				// Get Connexion's Host Info
				$hostInfo = (request()->filled('socket'))
					? 'unix_socket=' . request()->input('socket')
					: 'host=' . request()->input('host') . ';port=' . $port;
				
				// Get the Connexion's DSN
				$dsn = $driver . ':' . $hostInfo . ';dbname=' . request()->input('database') . ';charset=' . $charset;
				
				// Connect to the Database Server
				$pdo = new \PDO($dsn, request()->input('username'), request()->input('password'), $options);
				
			} catch (\PDOException $e) {
				$rules['database_connection'] = 'required';
				$messages = ['database_connection.required' => 'Can\'t connect to the database server. ERROR: <strong>' . $e->getMessage() . '</strong>'];
			} catch (\Throwable $e) {
				$rules['database_connection'] = 'required';
				$messages = ['database_connection.required' => 'The database connection failed. ERROR: <strong>' . $e->getMessage() . '</strong>'];
			}
			
			// Validate requirements
			$validatedData = request()->validate($rules, $messages);
			
			// Get database info and Save it in session
			session()->put('database', request()->all());
			
			// Write config file
			$this->writeEnv();
			
			// Return to Import Database page
			return redirect()->to($this->installUrl . '/database_import');
		}
		
		$database = request()->old();
		$database = !empty($database) ? $database : session('database');
		
		return appView('install.database', [
			'database' => $database,
			'rules'    => $rules,
			'step'     => $this->step(),
			'current'  => 3,
		]);
	}
	
	/**
	 * STEP 4 - Import Database
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function databaseImport()
	{
		if ($this->step() < 3) {
			return redirect()->to($this->installUrl . '/database');
		}
		
		// Get database connexion info & site info
		$database = session('database');
		$siteInfo = session('siteInfo');
		
		if (request()->query('action') == 'import') {
			session()->forget('databaseImported');
			
			$this->submitDatabaseImport($siteInfo, $database);
			
			// The database is now imported!
			session()->put('databaseImported', 1);
			
			session()->flash('alert-success', trans('messages.install.database_import.success'));
			
			return redirect()->to($this->installUrl . '/cron_jobs');
		}
		
		return appView('install.database_import', [
			'database' => $database,
			'step'     => $this->step(),
			'current'  => 3,
		]);
	}
	
	/**
	 * STEP 5 - Set Cron Jobs
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function cronJobs()
	{
		if ($this->step() < 5) {
			return redirect()->to($this->installUrl . '/database');
		}
		
		session()->put('cronJobs', 1);
		
		$phpBinaryPath = $this->getPhpBinaryPath();
		$requiredPhpVersion = $this->getComposerRequiredPhpVersion();
		
		return appView('install.cron_jobs', [
			'phpBinaryPath'      => $phpBinaryPath,
			'requiredPhpVersion' => $requiredPhpVersion,
			'basePath'           => base_path(),
			'step'               => $this->step(),
			'current'            => 5,
		]);
	}
	
	/**
	 * STEP 6 - Finish
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function finish()
	{
		if ($this->step() < 6) {
			return redirect()->to($this->installUrl . '/database');
		}
		
		// Create the "installed" file
		createTheInstalledFile(true);
		
		// Declare the installation as complete
		session()->put('installFinished', 1);
		session()->save();
		
		// Delete all front & back office cookies
		Cookie::forget('ipCountryCode');
		
		// Clear all the cache
		Artisan::call('cache:clear');
		sleep(2);
		Artisan::call('view:clear');
		sleep(1);
		File::delete(File::glob(storage_path('logs') . DIRECTORY_SEPARATOR . '*.log'));
		
		// Rendering final Info
		return appView('install.finish', [
			'step'    => $this->step(),
			'current' => 6,
		]);
	}
	
	// PRIVATE METHODS
	// Check out Traits
}
