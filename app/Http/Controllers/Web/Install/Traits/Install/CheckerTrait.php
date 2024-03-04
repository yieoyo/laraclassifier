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

namespace App\Http\Controllers\Web\Install\Traits\Install;

use Illuminate\Support\Facades\File;

trait CheckerTrait
{
	use PhpTrait;
	
	/**
	 * Is Manual Checking Allowed
	 *
	 * @return bool
	 */
	protected function isManualCheckingAllowed(): bool
	{
		return (request()->has('mode') && request()->query('mode') == 'manual');
	}
	
	/**
	 * @return bool
	 */
	protected function checkComponents(): bool
	{
		$components = $this->getComponents();
		
		$success = true;
		foreach ($components as $component) {
			if ($component['required'] && !$component['isOk']) {
				$success = false;
			}
		}
		
		return $success;
	}
	
	/**
	 * @return bool
	 */
	protected function checkPermissions(): bool
	{
		$permissions = $this->getPermissions();
		
		$success = true;
		foreach ($permissions as $permission) {
			if ($permission['required'] && !$permission['isOk']) {
				$success = false;
			}
		}
		
		return $success;
	}
	
	/**
	 * @return array[]
	 */
	protected function getComponents(): array
	{
		$requiredPhpVersion = $this->getComposerRequiredPhpVersion();
		$phpBinaryVersion = $this->getPhpBinaryVersion();
		
		$components = [
			[
				'type'              => 'component',
				'name'              => 'PHP (CGI/FPM) version',
				'required'          => true,
				'isOk'              => version_compare(PHP_VERSION, $requiredPhpVersion, '>='),
				'permanentChecking' => false,
				'warning'           => 'PHP (CGI/FPM) <code>' . $requiredPhpVersion . '</code> or higher is required.',
				'success'           => 'The PHP (CGI/FPM) version <code>' . PHP_VERSION . '</code> is valid.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP-CLI version',
				'required'          => false,
				'isOk'              => version_compare($phpBinaryVersion, $requiredPhpVersion, '>='),
				'permanentChecking' => false,
				'warning'           => 'PHP-CLI <code>' . $requiredPhpVersion . '</code> or higher is required.',
				'success'           => 'The PHP-CLI version <code>' . $phpBinaryVersion . '</code> is valid.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP pdo extension',
				'required'          => true,
				'isOk'              => extension_loaded('pdo'),
				'permanentChecking' => true,
				'warning'           => 'PHP pdo extension is required.',
				'success'           => 'PHP pdo extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP pdo_mysql extension',
				'required'          => true,
				'isOk'              => extension_loaded('pdo_mysql'),
				'permanentChecking' => true,
				'warning'           => 'PHP pdo_mysql extension is required.',
				'success'           => 'PHP pdo_mysql extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'MySQL driver for PHP pdo extension',
				'required'          => true,
				'isOk'              => (
					class_exists('\PDO')
					&& method_exists(\PDO::class, 'getAvailableDrivers')
					&& in_array('mysql', \PDO::getAvailableDrivers())
				),
				'permanentChecking' => true,
				'warning'           => 'MySQL driver for PHP pdo extension is required.',
				'success'           => 'MySQL driver for PHP pdo extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP openssl extension',
				'required'          => true,
				'isOk'              => extension_loaded('openssl'),
				'permanentChecking' => true,
				'warning'           => 'PHP openssl extension is required.',
				'success'           => 'PHP openssl extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP tokenizer extension',
				'required'          => true,
				'isOk'              => extension_loaded('tokenizer'),
				'permanentChecking' => true,
				'warning'           => 'PHP tokenizer extension is required.',
				'success'           => 'PHP tokenizer extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP mbstring extension',
				'required'          => true,
				'isOk'              => extension_loaded('mbstring'),
				'permanentChecking' => true,
				'warning'           => 'PHP mbstring extension is required.',
				'success'           => 'PHP mbstring extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP json extension',
				'required'          => true,
				'isOk'              => extension_loaded('json'),
				'permanentChecking' => true,
				'warning'           => 'PHP json extension is required.',
				'success'           => 'PHP json extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP xml extension',
				'required'          => true,
				'isOk'              => extension_loaded('xml'),
				'permanentChecking' => true,
				'warning'           => 'PHP xml extension is required.',
				'success'           => 'PHP xml extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP fileinfo extension',
				'required'          => true,
				'isOk'              => extension_loaded('fileinfo'),
				'permanentChecking' => true,
				'warning'           => 'PHP fileinfo extension is required.',
				'success'           => 'PHP fileinfo extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP curl extension',
				'required'          => true,
				'isOk'              => extension_loaded('curl'),
				'permanentChecking' => true,
				'warning'           => 'PHP curl extension is required.',
				'success'           => 'PHP curl extension is installed.',
			],
		];
		
		$gdIsEnabled = (extension_loaded('gd') && function_exists('gd_info'));
		$gdChecker = [
			'type'              => 'component',
			'name'              => 'PHP gd extension',
			'required'          => true,
			'isOk'              => $gdIsEnabled,
			'permanentChecking' => true,
			'warning'           => 'PHP gd extension is required.',
			'success'           => 'PHP gd extension is installed.',
		];
		
		$imagickIsEnabled = (extension_loaded('imagick') && class_exists('\Imagick'));
		$imagickChecker = [
			'type'              => 'component',
			'name'              => 'PHP imagick extension',
			'required'          => true,
			'isOk'              => $imagickIsEnabled,
			'permanentChecking' => true,
			'warning'           => 'PHP imagick extension is required.',
			'success'           => 'PHP imagick extension is installed.',
		];
		
		if (!($gdIsEnabled && $imagickIsEnabled)) {
			$components[] = $gdChecker;
		} else {
			if ($gdIsEnabled) {
				$components[] = $gdChecker;
			}
			if ($imagickIsEnabled) {
				$components[] = $imagickChecker;
			}
		}
		
		$otherComponents = [
			[
				'type'              => 'component',
				'name'              => 'PHP intl extension',
				'required'          => false,
				'isOk'              => (
					extension_loaded('intl')
					&& class_exists('\Locale')
					&& class_exists('\NumberFormatter')
					&& class_exists('\Collator')
				),
				'permanentChecking' => false,
				'warning'           => 'PHP intl extension is required.',
				'success'           => 'PHP intl extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP zip extension',
				'required'          => false,
				'isOk'              => (extension_loaded('zip') && class_exists('\ZipArchive')),
				'permanentChecking' => false,
				'warning'           => 'PHP zip extension is required.',
				'success'           => 'PHP zip extension is installed.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP open_basedir setting',
				'required'          => false,
				'isOk'              => empty(ini_get('open_basedir')),
				'permanentChecking' => false,
				'warning'           => 'The PHP <code>open_basedir</code> setting must be disabled.',
				'success'           => 'The PHP <code>open_basedir</code> setting is disabled.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP escapeshellarg() function',
				'required'          => true,
				'isOk'              => phpFuncEnabled('escapeshellarg'),
				'permanentChecking' => true,
				'warning'           => 'The PHP <code>escapeshellarg()</code> function must be enabled.',
				'success'           => 'The PHP <code>escapeshellarg()</code> function is enabled.',
			],
			[
				'type'              => 'component',
				'name'              => 'PHP exec() function',
				'required'          => true,
				'isOk'              => phpFuncEnabled('exec'),
				'permanentChecking' => true,
				'warning'           => 'The PHP <code>exec()</code> function must be enabled.',
				'success'           => 'The PHP <code>exec()</code> function is enabled.',
			],
		];
		
		return array_merge($components, $otherComponents);
	}
	
	/**
	 * @return array[]
	 */
	protected function getPermissions(): array
	{
		$warning = 'The directory must be writable by the web server (0755).';
		$rWarning = 'The directory must be writable (recursively) by the web server (0755).';
		$message = 'The directory is writable with the right permissions.';
		$rMessage = 'The directory is writable (recursively) with the right permissions.';
		
		$permissions = [
			[
				'type'              => 'permission',
				'name'              => base_path('bootstrap/cache'),
				'required'          => true,
				'isOk'              => (
					file_exists(base_path('bootstrap/cache'))
					&& is_dir(base_path('bootstrap/cache'))
					&& is_writable(base_path('bootstrap/cache'))
					&& get_perms(base_path('bootstrap/cache')) >= 755
				),
				'permanentChecking' => true,
				'warning'           => $warning,
				'success'           => $message,
			],
			[
				'type'              => 'permission',
				'name'              => config_path(),
				'required'          => true,
				'isOk'              => (
					file_exists(config_path())
					&& is_dir(config_path())
					&& is_writable(config_path())
					&& get_perms(config_path()) >= 755
				),
				'permanentChecking' => true,
				'warning'           => $warning,
				'success'           => $message,
			],
			[
				'type'              => 'permission',
				'name'              => public_path(),
				'required'          => true,
				'isOk'              => (
					file_exists(public_path())
					&& is_dir(public_path())
					&& is_writable(public_path())
					&& get_perms(public_path()) >= 755
				),
				'permanentChecking' => true,
				'warning'           => $warning,
				'success'           => $message,
			],
			[
				'type'              => 'permission',
				'name'              => lang_path(),
				'required'          => true,
				'isOk'              => $this->checkResourcesLangPermissions(),
				'permanentChecking' => true,
				'warning'           => $rWarning,
				'success'           => $rMessage,
			],
			[
				'type'              => 'permission',
				'name'              => storage_path(),
				'required'          => true,
				'isOk'              => $this->checkStoragePermissions(),
				'permanentChecking' => true,
				'warning'           => $rWarning,
				'success'           => $rMessage,
			],
		];
		
		// Check and load Watermark plugin
		if (plugin_exists('watermark')) {
			$watermarkPath = plugin_path('watermark');
			$permissions[] = [
				'type'              => 'permission',
				'name'              => $watermarkPath,
				'required'          => false,
				'isOk'              => (
					file_exists($watermarkPath)
					&& is_dir($watermarkPath)
					&& is_writable($watermarkPath)
					&& get_perms($watermarkPath) >= 755
				),
				'permanentChecking' => false,
				'warning'           => $warning,
				'success'           => $message,
			];
		}
		
		return $permissions;
	}
	
	/**
	 * @return bool
	 */
	private function checkResourcesLangPermissions(): bool
	{
		$permissions = $this->getResourcesLangPermissions();
		
		$success = true;
		foreach ($permissions as $path => $permission) {
			if (!$permission) {
				$success = false;
			}
		}
		
		return $success;
	}
	
	/**
	 * @return bool
	 */
	private function checkStoragePermissions(): bool
	{
		$permissions = $this->getStoragePermissions();
		
		$success = true;
		foreach ($permissions as $path => $permission) {
			if (!$permission) {
				$success = false;
			}
		}
		
		return $success;
	}
	
	/**
	 * @return array
	 */
	private function getResourcesLangPermissions(): array
	{
		$resourceLangPath = rtrim(lang_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$paths = array_filter(glob($resourceLangPath . '*'), 'is_dir');
		
		$permissions = [];
		
		// Insert the $resourceLangPath at the beginning of the array paths
		array_unshift($paths, $resourceLangPath);
		
		foreach ($paths as $fullPath) {
			// Create path if it does not exist
			if (!File::exists($fullPath)) {
				try {
					File::makeDirectory($fullPath, 0777, true);
				} catch (\Throwable $e) {
				}
			}
			
			// Get the path permission
			$permissions[$fullPath] = (
				file_exists($fullPath)
				&& is_dir($fullPath)
				&& is_writable($fullPath)
				&& get_perms($fullPath) >= 755
			);
		}
		
		return $permissions;
	}
	
	/**
	 * @return array
	 */
	private function getStoragePermissions(): array
	{
		$paths = [
			'/',
			'app/public/app',
			'app/public/app/categories/custom',
			'app/public/app/logo',
			'app/public/app/page',
			'app/public/files',
			'app/public/temporary',
			'framework',
			'framework/cache',
			'framework/plugins',
			'framework/sessions',
			'framework/views',
			'logs',
		];
		
		$permissions = [];
		
		foreach ($paths as $path) {
			$fullPath = storage_path($path);
			
			// Create path if it does not exist
			if (!File::exists($fullPath)) {
				try {
					File::makeDirectory($fullPath, 0777, true);
				} catch (\Throwable $e) {
				}
			}
			
			// Get the path permission
			$permissions[$fullPath] = (
				file_exists($fullPath)
				&& is_dir($fullPath)
				&& is_writable($fullPath)
				&& get_perms($fullPath) >= 755
			);
		}
		
		return $permissions;
	}
}
