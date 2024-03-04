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

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use Illuminate\Support\Arr;

trait Read
{
	/*
	|--------------------------------------------------------------------------
	|                                   READ
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Find and retrieve an entry in the database or fail.
	 *
	 * @param $id
	 * @return mixed
	 */
	public function getEntry($id)
	{
		if (!$this->entry) {
			$this->entry = $this->model->findOrFail($id);
			$this->entry = $this->entry->withFakes();
		}
		
		return $this->entry;
	}
	
	/**
	 * Make the query JOIN all relationships used in the columns, too,
	 * so there will be less database queries overall.
	 */
	public function autoEagerLoadRelationshipColumns()
	{
		$relationships = $this->getColumnsRelationships();
		if (is_array($relationships) && count($relationships) > 0) {
			$this->with($relationships);
		}
	}
	
	/**
	 * Get all entries from the database.
	 *
	 * @return mixed
	 */
	public function getEntries()
	{
		$this->autoEagerLoadRelationshipColumns();
		
		$entries = $this->query->get();
		
		// add the fake columns for each entry
		foreach ($entries as $key => $entry) {
			$entry->addFakes($this->getFakeColumnsAsArray());
		}
		
		return $entries;
	}
	
	/**
	 * Get the fields for the create or update forms.
	 *
	 * @param $form
	 * @param bool $id
	 * @return mixed
	 */
	public function getFields($form, $id = false)
	{
		return match (strtolower($form)) {
			'update' => $this->getUpdateFields($id),
			default => $this->getCreateFields(),
		};
	}
	
	/**
	 * Check if the create/update form has upload fields.
	 * Upload fields are the ones that have "upload" => true defined on them.
	 *
	 * @param $form
	 * @param bool $id
	 * @return bool
	 */
	public function hasUploadFields($form, $id = false): bool
	{
		$fields = $this->getFields($form, $id);
		$uploadFields = Arr::where($fields, function ($value, $key) {
			return isset($value['upload']) && $value['upload'] == true;
		});
		
		return (is_array($uploadFields) && count($uploadFields) > 0);
	}
	
	/**
	 * Enable the DETAILS ROW functionality:.
	 *
	 * In the table view, show a plus sign next to each entry.
	 * When clicking that plus sign, an AJAX call will bring whatever content you want from the EntityCrudController::showDetailsRow($id) and show it to the user.
	 */
	public function enableDetailsRow()
	{
		$this->details_row = true;
	}
	
	/**
	 * Disable the DETAILS ROW functionality:.
	 */
	public function disableDetailsRow()
	{
		$this->details_row = false;
	}
	
	/**
	 * Set the number of rows that should be show on the table page (list view).
	 *
	 * @param $value
	 */
	public function setDefaultPageLength($value)
	{
		$this->default_page_length = $value;
	}
	
	/**
	 * Get the number of rows that should be show on the table page (list view).
	 *
	 * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|int|mixed
	 */
	public function getDefaultPageLength()
	{
		// return the custom value for this crud panel, if set using setPageLength()
		if ($this->default_page_length) {
			return $this->default_page_length;
		}
		
		// otherwise return the default value in the config file
		if (config('larapen.admin.default_page_length')) {
			return config('larapen.admin.default_page_length');
		}
		
		return 25;
	}
	
	public function enableParentEntity()
	{
		$this->parentEntity = true;
	}
	
	public function disableParentEntity()
	{
		$this->parentEntity = false;
	}
	
	public function hasParentEntity()
	{
		return $this->parentEntity;
	}
	
	public function enableSearchBar()
	{
		$this->hideSearchBar = false;
	}
	
	public function disableSearchBar()
	{
		$this->hideSearchBar = true;
	}
	
	/*
	|--------------------------------------------------------------------------
	|                                EXPORT BUTTONS
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Tell the list view to show the DataTables export buttons.
	 */
	public function enableExportButtons()
	{
		$this->exportButtons = true;
	}
	
	/**
	 * Check if export buttons are enabled for the table view.
	 * @return bool
	 */
	public function exportButtons()
	{
		return $this->exportButtons;
	}
}
