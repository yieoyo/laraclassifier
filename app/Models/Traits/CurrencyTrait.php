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

trait CurrencyTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getNameHtml(): string
	{
		$currentUrl = preg_replace('#/(search)$#', '', url()->current());
		$url = $currentUrl . '/' . $this->getKey() . '/edit';
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function getSymbolHtml(): string
	{
		return html_entity_decode($this->symbol);
	}
	
	public function getPositionHtml(): string
	{
		if ($this->in_left == 1) {
			return '<i class="admin-single-icon fa fa-toggle-on" aria-hidden="true"></i>';
		} else {
			return '<i class="admin-single-icon fa fa-toggle-off" aria-hidden="true"></i>';
		}
	}
	
	public function getRateHtml(): string
	{
		$out = '-';
		
		if (!empty($this->rate)) {
			$driver = config('currencyexchange.default');
			$currencyBase = config('currencyexchange.drivers.' . $driver . '.currencyBase', 'XXX');
			$info = '1 ' . $currencyBase . ' =';
			
			$tooltip = ' data-bs-toggle="tooltip" title="' . $info . '"';
			
			$out = '<span' . $tooltip . '>' . $this->rate . '</span>';
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
