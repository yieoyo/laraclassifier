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

namespace App\Models\Setting;

use App\Http\Controllers\Web\Install\Traits\Install\PhpTrait;

class CronSetting
{
	use PhpTrait;
	
	public static function getValues($value, $disk)
	{
		if (empty($value)) {
			
			$value['unactivated_listings_expiration'] = '30';
			$value['activated_listings_expiration'] = '30';
			$value['archived_listings_expiration'] = '7';
			$value['manually_archived_listings_expiration'] = '90';
			
		} else {
			
			if (!array_key_exists('unactivated_listings_expiration', $value)) {
				$value['unactivated_listings_expiration'] = '30';
			}
			if (!array_key_exists('activated_listings_expiration', $value)) {
				$value['activated_listings_expiration'] = '30';
			}
			if (!array_key_exists('archived_listings_expiration', $value)) {
				$value['archived_listings_expiration'] = '7';
			}
			if (!array_key_exists('manually_archived_listings_expiration', $value)) {
				$value['manually_archived_listings_expiration'] = '90';
			}
			
		}
		
		return $value;
	}
	
	public static function setValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName)
	{
		$fields = [];
		
		$phpBinaryDefaultPath = '/path/to/php';
		$phpBinaryPath = null;
		if (view()->exists('elements._cron_jobs')) {
			$phpTrait = new self();
			$phpBinaryPath = $phpTrait->getPhpBinaryPath();
			$requiredPhpVersion = $phpTrait->getComposerRequiredPhpVersion();
			$data = [
				'phpBinaryPath'      => !isDemoDomain() ? $phpBinaryPath : $phpBinaryDefaultPath,
				'requiredPhpVersion' => $requiredPhpVersion,
				'basePath'           => relativeAppPath(base_path()),
			];
			$cronJobInfo = view('elements._cron_jobs', $data)->render();
			$fields[] = [
				'name'  => 'cron_php_binary_info',
				'type'  => 'custom_html',
				'value' => $cronJobInfo,
			];
		} else {
			$fields[] = [
				'name'  => 'cron_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.cron_sep_value'),
			];
			$fields[] = [
				'name'  => 'cron_info_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.cron_info_sep_value'),
			];
		}
		
		$fields = array_merge($fields, [
			[
				'name'  => 'cron_listings_clear_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.cron_listings_clear_sep_value'),
			],
			[
				'name'  => 'cron_listings_clear_info',
				'type'  => 'custom_html',
				'value' => (isset($phpBinaryPath) && !empty($phpBinaryPath))
					? trans('admin.cron_listings_clear_info_value_real', [
						'phpBinaryPath' => !isDemoDomain() ? $phpBinaryPath : $phpBinaryDefaultPath,
						'basePath'      => rtrim(relativeAppPath(base_path()), '/'),
					])
					: trans('admin.cron_listings_clear_info_value'),
			],
			[
				'name'              => 'unactivated_listings_expiration',
				'label'             => trans('admin.unactivated_listings_expiration_label'),
				'type'              => 'number',
				'hint'              => trans('admin.unactivated_listings_expiration_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'activated_listings_expiration',
				'label'             => trans('admin.activated_listings_expiration_label'),
				'type'              => 'number',
				'hint'              => trans('admin.activated_listings_expiration_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'archived_listings_expiration',
				'label'             => trans('admin.archived_listings_expiration_label'),
				'type'              => 'number',
				'hint'              => trans('admin.archived_listings_expiration_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'manually_archived_listings_expiration',
				'label'             => trans('admin.manually_archived_listings_expiration_label'),
				'type'              => 'number',
				'hint'              => trans('admin.manually_archived_listings_expiration_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
		]);
		
		return $fields;
	}
}
