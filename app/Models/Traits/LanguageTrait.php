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

namespace App\Models\Traits;

trait LanguageTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function syncFilesLinesButton($xPanel = false): string
	{
		$url = admin_url('languages/sync_files');
		
		$msg = trans('admin.Fill the missing lines in all languages files from the master language');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-success shadow" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fas fa-exchange-alt"></i> ';
		$out .= trans('admin.Sync Languages Files Lines');
		$out .= '</a>';
		
		return $out;
	}
	
	public function filesLinesEditionButton($xPanel = false): string
	{
		$url = admin_url('languages/texts');
		
		$msg = trans('admin.site_texts');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-primary shadow" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fa fa-language"></i> ';
		$out .= trans('admin.translate') . ' ' . mb_strtolower(trans('admin.site_texts'));
		$out .= '</a>';
		
		return $out;
	}
	
	public function getNameHtml(): string
	{
		$currentUrl = preg_replace('#/(search)$#', '', url()->current());
		$url = $currentUrl . '/' . $this->getKey() . '/edit';
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function getDefaultHtml(): string
	{
		return checkboxDisplay($this->default);
	}
	
	// ===| OTHER METHODS |===
	
	/**
	 * @return array
	 */
	public static function getActiveLanguagesArray(): array
	{
		$cacheExpiration = config('settings.optimization.cache_expiration', 86400);
		$activeLanguages = cache()->remember('languages.active.array', $cacheExpiration, function () {
			return self::where('active', 1)->get();
		});
		
		return collect($activeLanguages)->keyBy('code')->toArray();
	}
}
