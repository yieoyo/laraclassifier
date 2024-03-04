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

trait HomeSectionTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function resetHomepageReOrderButton($xPanel = false): string
	{
		$url = admin_url('homepage/reset_reorder');
		
		$msg = trans('admin.Reset the homepage sections reorder');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-warning text-white shadow" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fas fa-sort-amount-up"></i> ';
		$out .= trans('admin.Reset sections reorganization');
		$out .= '</a>';
		
		return $out;
	}
	
	public function resetHomepageSettingsButton($xPanel = false): string
	{
		$url = admin_url('homepage/reset_settings');
		
		$msg = trans('admin.Reset all the homepage settings');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-danger shadow" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fas fa-industry"></i> ';
		$out .= trans('admin.Return to factory settings');
		$out .= '</a>';
		
		return $out;
	}
	
	public function getNameHtml(): string
	{
		$currentUrl = preg_replace('#/(search)$#', '', url()->current());
		$url = $currentUrl . '/' . $this->getKey() . '/edit';
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function configureButton($xPanel = false): string
	{
		$url = admin_url('homepage/' . $this->id . '/edit');
		
		$msg = trans('admin.configure_entity', ['entity' => $this->name]);
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-primary" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fas fa-cog"></i> ';
		$out .= mb_ucfirst(trans('admin.Configure'));
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
