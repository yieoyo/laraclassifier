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

use App\Helpers\Localization\Helpers\Country as CountryHelper;
use App\Models\Language;
use Illuminate\Support\Facades\DB;

trait CountryTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getNameHtml(): string
	{
		$currentUrl = preg_replace('#/(search)$#', '', url()->current());
		$url = $currentUrl . '/' . $this->getKey() . '/edit';
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function getActiveHtml(): string
	{
		if (!isset($this->active)) return '';
		
		return installAjaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'active', $this->active);
	}
	
	public function adminDivisions1Button($xPanel = false): string
	{
		$url = admin_url('countries/' . $this->id . '/admins1');
		
		$msg = trans('admin.Admin Divisions 1 of country', ['country' => $this->name]);
		$toolTip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $toolTip . '>';
		$out .= '<i class="fa fa-eye"></i> ';
		$out .= mb_ucfirst(trans('admin.admin divisions 1'));
		$out .= '</a>';
		
		return $out;
	}
	
	public function citiesButton($xPanel = false): string
	{
		$url = admin_url('countries/' . $this->id . '/cities');
		
		$msg = trans('admin.Cities of country', ['country' => $this->name]);
		$toolTip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $toolTip . '>';
		$out .= '<i class="fa fa-eye"></i> ';
		$out .= mb_ucfirst(trans('admin.cities'));
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	/**
	 * Countries Batch Auto Translation
	 *
	 * @param bool $overwriteExistingTrans
	 * @return void
	 */
	public static function autoTranslation(bool $overwriteExistingTrans = false): void
	{
		$tableName = (new self())->getTable();
		
		$languages = DB::table((new Language())->getTable())->get();
		$oldEntries = DB::table($tableName)->get();
		
		if ($oldEntries->count() > 0) {
			$transCountry = new CountryHelper();
			foreach ($oldEntries as $oldEntry) {
				$newNames = [];
				
				foreach ($languages as $language) {
					if (isJson($oldEntry->name)) {
						$oldNames = json_decode($oldEntry->name, true);
					}
					
					$translationNotFound = (!isset($oldNames[$language->code]) || empty($oldNames[$language->code]));
					
					if ($overwriteExistingTrans || $translationNotFound) {
						if ($translationNotFound) {
							$newNames[$language->code] = getColumnTranslation($oldEntry->name);
						}
						if ($name = $transCountry->get($oldEntry->code, $language->code)) {
							$newNames[$language->code] = $name;
						}
					}
				}
				
				if (!empty($newNames)) {
					$name = json_encode($newNames, JSON_UNESCAPED_UNICODE);
					$affected = DB::table($tableName)
						->where('code', $oldEntry->code)
						->update(['name' => $name]);
				}
			}
		}
	}
}
