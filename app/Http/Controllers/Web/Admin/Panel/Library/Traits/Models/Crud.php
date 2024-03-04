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

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models;

trait Crud
{
	use HasRelationshipFields;
	use HasUploadFields;
	use HasFakeFields;
	use HasTranslatableFields;
	
	/*
    |--------------------------------------------------------------------------
    | Translation Methods
    |--------------------------------------------------------------------------
    */
	
	
	/*
	|--------------------------------------------------------------------------
	| Methods for ALL models
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Check if the attribute exists
	 *
	 * @param string $attr
	 * @return bool
	 */
	public function hasAttribute(string $attr): bool
	{
		return array_key_exists($attr, $this->attributes);
	}
	
	/**
	 * @param bool $xPanel
	 * @return string
	 */
	public function bulkDeletionButton($xPanel = false): string
	{
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.delete_selection') . '"';
		
		// Button
		$out = '<button name="deletion" class="bulk-action btn btn-danger shadow"' . $tooltip . '>';
		$out .= '<i class="fas fa-times"></i> ';
		$out .= trans('admin.delete');
		$out .= '</button>';
		
		return $out;
	}
	
	/**
	 * @param $xPanel
	 * @return string|null
	 */
	public function bulkActivationButton($xPanel = false): ?string
	{
		if (!isset($xPanel->model) || !in_array('active', $xPanel->model->getFillable())) {
			return null;
		}
		
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.activate_selection') . '"';
		
		// Button
		$out = '<button name="activation" class="bulk-action btn btn-outline-secondary shadow"' . $tooltip . '>';
		$out .= '<i class="fa fa-toggle-on"></i> ';
		$out .= trans('admin.activate');
		$out .= '</button>';
		
		return $out;
	}
	
	/**
	 * @param $xPanel
	 * @return string|null
	 */
	public function bulkDeactivationButton($xPanel = false): ?string
	{
		if (!isset($xPanel->model) || !in_array('active', $xPanel->model->getFillable())) {
			return null;
		}
		
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.disable_selection') . '"';
		
		// Button
		$out = '<button name="deactivation" class="bulk-action btn btn-outline-secondary shadow"' . $tooltip . '>';
		$out .= '<i class="fa fa-toggle-off"></i> ';
		$out .= trans('admin.disable');
		$out .= '</button>';
		
		return $out;
	}
	
	/**
	 * @param $xPanel
	 * @return string|null
	 */
	public function bulkApprovalBtn($xPanel = false): ?string
	{
		if (
			!isset($xPanel->model)
			|| !in_array('reviewed_at', $xPanel->model->getFillable())
			|| !config('settings.single.listings_review_activation')
		) {
			return null;
		}
		
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.approve_selection') . '"';
		
		// Button
		$out = '<button name="approval" class="bulk-action btn btn-outline-secondary shadow"' . $tooltip . '>';
		$out .= '<i class="fa fa-toggle-on"></i> ';
		$out .= trans('admin.approve');
		$out .= '</button>';
		
		return $out;
	}
	
	/**
	 * @param $xPanel
	 * @return string|null
	 */
	public function bulkDisapprovalBtn($xPanel = false): ?string
	{
		if (
			!isset($xPanel->model)
			|| !in_array('reviewed_at', $xPanel->model->getFillable())
			|| !config('settings.single.listings_review_activation')
		) {
			return null;
		}
		
		$tooltip = ' data-bs-toggle="tooltip" title="' . trans('admin.disapprove_selection') . '"';
		
		// Button
		$out = '<button name="disapproval" class="bulk-action btn btn-outline-secondary shadow"' . $tooltip . '>';
		$out .= '<i class="fa fa-toggle-off"></i> ';
		$out .= trans('admin.disapprove');
		$out .= '</button>';
		
		return $out;
	}
}
