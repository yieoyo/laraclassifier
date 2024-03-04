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

use App\Helpers\Arr;
use App\Helpers\UrlGen;

/**
 * @param string|null $path
 * @return string
 */
function admin_uri(?string $path = ''): string
{
	$path = str_replace(url(config('larapen.admin.route', 'admin')), '', $path);
	$path = ltrim($path, '/');
	
	if (!empty($path)) {
		$path = config('larapen.admin.route', 'admin') . '/' . $path;
	} else {
		$path = config('larapen.admin.route', 'admin');
	}
	
	return $path;
}

/**
 * @param string|null $path
 * @return string
 */
function admin_url(?string $path = ''): string
{
	return url(admin_uri($path));
}

/**
 * Checkbox Display
 *
 * @param $fieldValue
 * @param $id
 * @return string
 */
function checkboxDisplay($fieldValue, $id = null): string
{
	$attrId = !empty($id) ? 'id="' . $id . '"' : '';
	
	// fa-square-o | fa-check-square-o
	// fa-toggle-off | fa-toggle-on
	if (!empty($fieldValue)) {
		return '<i ' . $attrId . ' class="admin-single-icon fa fa-toggle-on" aria-hidden="true"></i>';
	} else {
		return '<i ' . $attrId . ' class="admin-single-icon fa fa-toggle-off" aria-hidden="true"></i>';
	}
}

/**
 * Ajax Checkbox Display
 *
 * @param $id
 * @param $table
 * @param $field
 * @param null $fieldValue
 * @return string
 */
function ajaxCheckboxDisplay($id, $table, $field, $fieldValue = null): string
{
	$lineId = $field . $id;
	$lineId = str_replace('.', '', $lineId); // fix JS bug (in admin layout)
	$data = 'data-table="' . $table . '"
			data-field="' . $field . '"
			data-line-id="' . $lineId . '"
			data-id="' . $id . '"
			data-value="' . $fieldValue . '"';
	
	// Decoration
	$html = checkboxDisplay($fieldValue, $lineId);
	
	return '<a href="" class="ajax-request" ' . $data . '>' . $html . '</a>';
}

/**
 * Advanced Ajax Checkbox Display
 *
 * @param $id
 * @param $table
 * @param $field
 * @param null $fieldValue
 * @return string
 */
function installAjaxCheckboxDisplay($id, $table, $field, $fieldValue = null): string
{
	$lineId = $field . $id;
	$lineId = str_replace('.', '', $lineId); // fix JS bug (in admin layout)
	$data = 'data-table="' . $table . '"
			data-field="' . $field . '"
			data-line-id="' . $lineId . '"
			data-id="' . $id . '"
			data-value="' . $fieldValue . '"';
	
	// Decoration
	$html = checkboxDisplay($fieldValue, $lineId);
	$html = '<a href="" class="ajax-request" ' . $data . '>' . $html . '</a>';
	
	// Install country's decoration
	$html .= ' &nbsp;';
	if ($fieldValue == 1) {
		$html .= '<a href="" id="install' . $id . '" class="ajax-request btn btn-xs btn-success text-white" ' . $data . '>';
		$html .= '<i class="fas fa-download"></i> ' . trans('admin.Installed');
		$html .= '</a>';
	} else {
		$html .= '<a href="" id="install' . $id . '" class="ajax-request btn btn-xs btn-light" ' . $data . '>';
		$html .= '<i class="fas fa-download"></i> ' . trans('admin.Install');
		$html .= '</a>';
	}
	
	return $html;
}

/**
 * Generate the Post's link from the Admin panel
 *
 * @param $post
 * @return string
 */
function getPostUrl($post): string
{
	// Get the listing's possible payment info
	$paymentInfo = '';
	if (!empty($post->payment)) {
		$info = ' (' . $post->payment->expiry_info . ')';
		$class = 'text-' . $post->payment->css_class_variant;
		$packageName = $post->payment->package?->short_name ?? t('unknown_package');
		
		$paymentInfo = ' <i class="fa fa-check-circle ' . $class . '"
                    data-bs-placement="bottom" data-bs-toggle="tooltip"
                    type="button" title="' . $packageName . $info . '">
                </i>';
	}
	
	// Get the listing link
	if (!is_null($post) && isset($post->country_code, $post->title)) {
		$url = dmUrl($post->country_code, UrlGen::postPath($post));
		$out = linkStrLimit($url, $post->title, 35, 'target="_blank"') . $paymentInfo;
	} else {
		$out = '--';
	}
	
	return $out;
}

/**
 * @param $entry
 * @param bool $withLink
 * @return string
 */
function getCountryFlag($entry, bool $withLink = false): string
{
	$out = '';
	
	$country = $entry->country ?? null;
	$countryCode = $country->code ?? $entry->country_code ?? null;
	
	if (!empty($countryCode)) {
		$countryName = $country->name ?? $countryCode;
		$countryFlagUrl = $country->flag_url ?? $entry->country_flag_url ?? null;
		
		if (!empty($countryFlagUrl)) {
			$out = ($withLink) ? '<a href="' . dmUrl($countryCode, '/', true, true) . '" target="_blank">' : '';
			$out .= '<img src="' . $countryFlagUrl . '" data-bs-toggle="tooltip" title="' . $countryName . '">';
			$out .= ($withLink) ? '</a>' : '';
			$out .= ' ';
		} else {
			$out .= $countryCode . ' ';
		}
	}
	
	return $out;
}

/**
 * Check if the Listing is verified
 *
 * @param $post
 * @return bool
 */
function isVerifiedPost($post): bool
{
	$post = (is_array($post)) ? Arr::toObject($post) : $post;
	
	if (
		!Arr::keyExists('email_verified_at', $post)
		|| !Arr::keyExists('phone_verified_at', $post)
		|| !Arr::keyExists('reviewed_at', $post)
	) {
		return false;
	}
	
	if (config('settings.single.listings_review_activation')) {
		$verified = (!empty($post->email_verified_at) && !empty($post->phone_verified_at) && !empty($post->reviewed_at));
	} else {
		$verified = (!empty($post->email_verified_at) && !empty($post->phone_verified_at));
	}
	
	return $verified;
}

/**
 * Check if the User is verified
 *
 * @param $user
 * @return bool
 */
function isVerifiedUser($user): bool
{
	$user = (is_array($user)) ? Arr::toObject($user) : $user;
	
	if (!Arr::keyExists('email_verified_at', $user) || !Arr::keyExists('phone_verified_at', $user)) {
		return false;
	}
	
	return (!empty($user->email_verified_at) && !empty($user->phone_verified_at));
}

/**
 * @return bool
 */
function userHasSuperAdminPermissions(): bool
{
	if (auth()->check()) {
		$permissions = \App\Models\Permission::getSuperAdminPermissions();
		
		// Remove the standard admin permission
		$permissions = collect($permissions)->reject(function ($value, $key) {
			return $value == 'dashboard-access';
		})->toArray();
		
		// Check if user has the super admin permissions
		if (auth()->user()->can($permissions)) {
			return true;
		}
	}
	
	return false;
}
