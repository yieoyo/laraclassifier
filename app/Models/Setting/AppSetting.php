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

use App\Helpers\Files\Upload;
use Illuminate\Support\Facades\Storage;

class AppSetting
{
	public static function passedValidation($request)
	{
		$params = [
			[
				'attribute' => 'logo',
				'destPath'  => 'app/logo',
				'width'     => (int)config('settings.upload.img_resize_logo_width', 454),
				'height'    => (int)config('settings.upload.img_resize_logo_height', 80),
				'ratio'     => config('settings.upload.img_resize_logo_ratio', '1'),
				'upsize'    => config('settings.upload.img_resize_logo_upsize', '1'),
				'filename'  => 'logo-',
			],
			[
				'attribute' => 'favicon',
				'destPath'  => 'app/ico',
				'width'     => (int)config('larapen.core.picture.otherTypes.favicon.width', 32),
				'height'    => (int)config('larapen.core.picture.otherTypes.favicon.height', 32),
				'ratio'     => config('larapen.core.picture.otherTypes.favicon.ratio', '1'),
				'upsize'    => config('larapen.core.picture.otherTypes.favicon.upsize', '0'),
				'filename'  => 'ico-',
			],
			[
				'attribute' => 'logo_dark',
				'destPath'  => 'app/backend',
				'width'     => (int)config('larapen.core.picture.otherTypes.adminLogo.width', 300),
				'height'    => (int)config('larapen.core.picture.otherTypes.adminLogo.height', 40),
				'ratio'     => config('larapen.core.picture.otherTypes.adminLogo.ratio', '1'),
				'upsize'    => config('larapen.core.picture.otherTypes.adminLogo.upsize', '0'),
				'filename'  => 'logo-dark-',
			],
			[
				'attribute' => 'logo_light',
				'destPath'  => 'app/backend',
				'width'     => (int)config('larapen.core.picture.otherTypes.adminLogo.width', 300),
				'height'    => (int)config('larapen.core.picture.otherTypes.adminLogo.height', 40),
				'ratio'     => config('larapen.core.picture.otherTypes.adminLogo.ratio', '1'),
				'upsize'    => config('larapen.core.picture.otherTypes.adminLogo.upsize', '0'),
				'filename'  => 'logo-light-',
			],
		];
		
		foreach ($params as $param) {
			$file = $request->hasFile($param['attribute'])
				? $request->file($param['attribute'])
				: $request->input($param['attribute']);
			
			$request->request->set($param['attribute'], Upload::image($param['destPath'], $file, $param));
		}
		
		return $request;
	}
	
	public static function getValues($value, $disk)
	{
		if (empty($value)) {
			
			$value['purchase_code'] = env('PURCHASE_CODE', '');
			$value['name'] = config('app.name');
			$value['logo'] = config('larapen.core.logo');
			$value['favicon'] = config('larapen.core.favicon');
			$value['date_format'] = config('larapen.core.dateFormat.default');
			$value['datetime_format'] = config('larapen.core.datetimeFormat.default');
			$value['logo_dark'] = config('larapen.admin.logo.dark');
			$value['logo_light'] = config('larapen.admin.logo.light');
			$value['vector_charts_type'] = 'morris_bar';
			$value['show_countries_charts'] = '1';
			$value['general_settings_as_submenu_in_sidebar'] = '1';
			
		} else {
			
			/**
			 * @var $disk Storage
			 */
			
			foreach ($value as $key => $item) {
				if ($key == 'logo') {
					$value['logo'] = str_replace('uploads/', '', $value['logo']);
					if (empty($value['logo']) || !$disk->exists($value['logo'])) {
						$value[$key] = config('larapen.core.logo');
					}
				}
				
				if ($key == 'favicon') {
					if (empty($value['favicon']) || !$disk->exists($value['favicon'])) {
						$value[$key] = config('larapen.core.favicon');
					}
				}
				
				if ($key == 'logo_dark') {
					if (empty($value['logo_dark']) || !$disk->exists($value['logo_dark'])) {
						$value[$key] = config('larapen.admin.logo.dark');
					}
				}
				
				if ($key == 'logo_light') {
					if (empty($value['logo_light']) || !$disk->exists($value['logo_light'])) {
						$value[$key] = config('larapen.admin.logo.light');
					}
				}
			}
			
			// Required keys & values
			// If $value exists and these keys don't exist, then set their default values
			if (!array_key_exists('logo', $value)) {
				$value['logo'] = config('larapen.core.logo');
			}
			if (!array_key_exists('favicon', $value)) {
				$value['favicon'] = config('larapen.core.favicon');
			}
			if (!array_key_exists('logo_dark', $value)) {
				$value['logo_dark'] = config('larapen.admin.logo.dark');
			}
			if (!array_key_exists('logo_light', $value)) {
				$value['logo_light'] = config('larapen.admin.logo.light');
			}
			if (!array_key_exists('login_bg_image', $value)) {
				$value['login_bg_image'] = config('larapen.admin.login_bg_image');
			}
			
			if (!array_key_exists('purchase_code', $value)) {
				$value['purchase_code'] = env('PURCHASE_CODE', '');
			}
			if (!array_key_exists('name', $value)) {
				$value['name'] = config('app.name');
			}
			if (!array_key_exists('logo', $value)) {
				$value['logo'] = config('larapen.core.logo');
			}
			if (!array_key_exists('favicon', $value)) {
				$value['favicon'] = config('larapen.core.favicon');
			}
			if (!array_key_exists('date_format', $value)) {
				$value['date_format'] = config('larapen.core.dateFormat.default');
			}
			if (!array_key_exists('datetime_format', $value)) {
				$value['datetime_format'] = config('larapen.core.datetimeFormat.default');
			}
			if (!array_key_exists('vector_charts_type', $value)) {
				$value['vector_charts_type'] = 'morris_bar';
			}
			if (!array_key_exists('show_countries_charts', $value)) {
				$value['show_countries_charts'] = '1';
			}
			if (!array_key_exists('general_settings_as_submenu_in_sidebar', $value)) {
				$value['general_settings_as_submenu_in_sidebar'] = '1';
			}
			
		}
		
		// Append files URLs
		// logo_url
		$logo = $value['logo'] ?? config('larapen.core.logo', 'app/default/logo.png');
		$value['logo_url'] = imgUrl($logo, 'logo');
		
		// favicon_url
		$favicon = $value['favicon'] ?? config('larapen.core.favicon', 'app/default/ico/favicon.png');
		$value['favicon_url'] = imgUrl($favicon, 'favicon');
		
		// logo_dark_url
		$logoDark = $value['logo_dark'] ?? config('larapen.admin.logo.dark', '');
		$value['logo_dark_url'] = imgUrl($logoDark, 'adminLogo');
		
		// logo_light_url
		$logoLight = $value['logo_light'] ?? config('larapen.admin.logo.light', '');
		$value['logo_light_url'] = imgUrl($logoLight, 'adminLogo');
		
		return $value;
	}
	
	public static function setValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName)
	{
		$dateFormatHint = (config('settings.app.php_specific_date_format')) ? 'php_date_format_hint' : 'iso_date_format_hint';
		
		$fields = [
			[
				'name'  => 'separator_1',
				'type'  => 'custom_html',
				'value' => trans('admin.app_html_brand_info'),
			],
			[
				'name'  => 'purchase_code',
				'label' => trans('admin.Purchase Code'),
				'type'  => 'text',
				'hint'  => trans('admin.find_my_purchase_code'),
			],
			[
				'name'              => 'name',
				'label'             => trans('admin.App Name'),
				'type'              => 'text',
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'slogan',
				'label'             => trans('admin.App Slogan'),
				'type'              => 'text',
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'logo',
				'label'             => trans('admin.App Logo'),
				'type'              => 'image',
				'upload'            => 'true',
				'disk'              => $diskName,
				'default'           => config('larapen.core.logo'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'favicon',
				'label'             => trans('admin.Favicon'),
				'type'              => 'image',
				'upload'            => 'true',
				'disk'              => $diskName,
				'default'           => config('larapen.core.favicon'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'  => 'separator_clear_1',
				'type'  => 'custom_html',
				'value' => '<div style="clear: both;"></div>',
			],
			[
				'name'              => 'email',
				'label'             => trans('admin.Email'),
				'type'              => 'email',
				'hint'              => trans('admin.The email address that all emails from the contact form will go to'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'phone_number',
				'label'             => trans('admin.Phone number'),
				'type'              => 'text',
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			
			[
				'name'  => 'dates_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.dates_title'),
			],
			[
				'name'              => 'php_specific_date_format',
				'label'             => trans('admin.php_specific_date_format_label'),
				'type'              => 'checkbox_switch',
				'hint'              => trans('admin.php_specific_date_format_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-12',
				],
			],
			[
				'name'  => 'php_specific_date_format_info',
				'type'  => 'custom_html',
				'value' => trans('admin.php_specific_date_format_info'),
			],
			[
				'name'              => 'date_format',
				'label'             => trans('admin.date_format_label'),
				'type'              => 'text',
				'default'           => config('larapen.core.dateFormat.default'),
				'hint'              => trans('admin.' . $dateFormatHint, ['year' => date('Y')])
					. ' ' . trans('admin.app_date_format_hint_help'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'datetime_format',
				'label'             => trans('admin.datetime_format_label'),
				'type'              => 'text',
				'default'           => config('larapen.core.datetimeFormat.default'),
				'hint'              => trans('admin.' . $dateFormatHint, ['year' => date('Y')])
					. ' ' . trans('admin.app_date_format_hint_help'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'  => 'admin_date_format_info',
				'type'  => 'custom_html',
				'value' => trans('admin.admin_date_format_info', [
					'languagesUrl' => admin_url('languages'),
					'countriesUrl' => admin_url('countries'),
				]),
			],
			[
				'name'  => 'backend_title_separator',
				'type'  => 'custom_html',
				'value' => trans('admin.backend_title_separator'),
			],
			[
				'name'              => 'logo_dark',
				'label'             => trans('admin.logo_dark_label'),
				'type'              => 'image',
				'upload'            => 'true',
				'disk'              => $diskName,
				'default'           => config('larapen.admin.logo.dark'),
				'hint'              => trans('admin.logo_dark_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'logo_light',
				'label'             => trans('admin.logo_light_label'),
				'type'              => 'image',
				'upload'            => 'true',
				'disk'              => $diskName,
				'default'           => config('larapen.admin.logo.light'),
				'hint'              => trans('admin.logo_light_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'  => 'settings_app_dashboard_sep',
				'type'  => 'custom_html',
				'value' => trans('admin.settings_app_dashboard_sep'),
			],
			[
				'name'              => 'vector_charts_type',
				'label'             => trans('admin.vector_charts_type_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					'morris_bar'  => 'Morris - Bar Charts',
					'morris_line' => 'Morris - Line Charts',
				],
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'latest_entries_limit',
				'label'             => trans('admin.settings_app_latest_entries_limit_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					5  => '5',
					10 => '10',
					15 => '15',
					20 => '20',
					25 => '25',
				],
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'show_countries_charts',
				'label'             => trans('admin.show_countries_charts_label'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'general_settings_as_submenu_in_sidebar',
				'label'             => trans('admin.general_settings_as_submenu_in_sidebar_label'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
		];
		
		return $fields;
	}
}
