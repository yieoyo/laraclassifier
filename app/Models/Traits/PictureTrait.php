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

use App\Helpers\UrlGen;

trait PictureTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getFilenameHtml(): string
	{
		$imgUrl = imgUrl($this->filename, 'small');
		
		return '<img src="' . $imgUrl . '" class="img-rounded" style="width:auto; max-height:90px;">';
	}
	
	public function getPostTitleHtml(): string
	{
		if (!empty($this->post)) {
			// $postUrl = url(UrlGen::postUri($this->post));
			$postUrl = dmUrl($this->post->country_code, UrlGen::postPath($this->post));
			
			return '<a href="' . $postUrl . '" target="_blank">' . $this->post->title . '</a>';
		} else {
			return 'no-link';
		}
	}
	
	public function getCountryHtml(): string
	{
		$countryCode = $this?->post?->country_code ?? '--';
		$countryName = $this?->post?->country?->name ?? null;
		$countryName = (!empty($countryName)) ? $countryName : $countryCode;
		$countryFlagUrl = $this?->post?->country_flag_url ?? null;
		
		if (!empty($countryFlagUrl)) {
			$out = '<a href="' . dmUrl($countryCode, '/', true, true) . '" target="_blank">';
			$out .= '<img src="' . $countryFlagUrl . '" data-bs-toggle="tooltip" title="' . $countryName . '">';
			$out .= '</a>';
			
			return $out;
		} else {
			return $countryCode;
		}
	}
	
	public function editPostButton($xPanel = false): string
	{
		$out = '';
		
		if (!empty($this->post)) {
			$url = admin_url('posts/' . $this->post->id . '/edit');
			
			$msg = trans('admin.Edit the listing of this picture');
			$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
			
			$out .= '<a class="btn btn-xs btn-light" href="' . $url . '"' . $tooltip . '>';
			$out .= '<i class="fa fa-edit"></i> ';
			$out .= mb_ucfirst(trans('admin.Edit the listing'));
			$out .= '</a>';
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
