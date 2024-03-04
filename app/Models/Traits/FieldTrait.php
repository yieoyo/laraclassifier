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

trait FieldTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getNameHtml(): string
	{
		$currentUrl = preg_replace('#/(search)$#', '', url()->current());
		$url = $currentUrl . '/' . $this->id . '/edit';
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function getTypeHtml()
	{
		$types = self::fieldTypes();
		
		return (isset($types[$this->type])) ? $types[$this->type] : $this->type;
	}
	
	public function optionsBtn($xPanel = false): string
	{
		$out = '';
		
		if (isset($this->type) && in_array($this->type, ['select', 'radio', 'checkbox_multiple'])) {
			$url = admin_url('custom_fields/' . $this->id . '/options');
			
			$out .= '<a class="btn btn-xs btn-info" href="' . $url . '">';
			$out .= '<i class="fa fa-cog"></i> ';
			$out .= mb_ucfirst(trans('admin.options'));
			$out .= '</a>';
		}
		
		return $out;
	}
	
	public function addToCategoryBtn($xPanel = false): string
	{
		$url = admin_url('custom_fields/' . $this->id . '/categories/create');
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '">';
		$out .= '<i class="fa fa-plus"></i> ';
		$out .= trans('admin.Add to a Category');
		$out .= '</a>';
		
		return $out;
	}
	
	public function getRequiredHtml()
	{
		if (!isset($this->required)) return false;
		
		return checkboxDisplay($this->required);
	}
	
	// ===| OTHER METHODS |===
	
	public static function fieldTypes(): array
	{
		return [
			'text'              => 'Text',
			'textarea'          => 'Textarea',
			'checkbox'          => 'Checkbox',
			'checkbox_multiple' => 'Checkbox (Multiple)',
			'select'            => 'Select Box',
			'radio'             => 'Radio',
			'file'              => 'File',
			'url'               => 'URL',
			'number'            => 'Number',
			'date'              => 'Date',
			'date_time'         => 'Date Time',
			'date_range'        => 'Date Range',
			'video'             => 'Video (Youtube, Vimeo)',
		];
	}
}
