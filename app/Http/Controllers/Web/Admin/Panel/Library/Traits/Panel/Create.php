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


trait Create
{
	/*
	|--------------------------------------------------------------------------
	|                                   CREATE
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Insert a row in the database.
	 *
	 * @param $data
	 * @return mixed
	 */
	public function create($data)
	{
		$valuesToStore = $this->compactFakeFields($data, 'create');
		$item = $this->model->create($valuesToStore);
		
		// if there are any relationships available, also sync those
		$this->syncPivot($item, $data);
		
		return $item;
	}
	
	/**
	 * Get all fields needed for the ADD NEW ENTRY form.
	 *
	 * @return mixed
	 */
	public function getCreateFields()
	{
		return $this->createFields;
	}
	
	/**
	 * Get all fields with relation set (model key set on field).
	 *
	 * @param string $form
	 * @return array
	 */
	public function getRelationFields($form = 'create')
	{
		if ($form == 'create') {
			$fields = $this->createFields;
		} else {
			$fields = $this->updateFields;
		}
		
		$relationFields = [];
		
		foreach ($fields as $field) {
			if (isset($field['model'])) {
				array_push($relationFields, $field);
			}
			
			if (isset($field['subfields']) &&
				is_array($field['subfields']) &&
				count($field['subfields'])) {
				foreach ($field['subfields'] as $subfield) {
					array_push($relationFields, $subfield);
				}
			}
		}
		
		return $relationFields;
	}
	
	/**
	 * @param $model
	 * @param $data
	 * @param string $form
	 */
	public function syncPivot($model, $data, $form = 'create')
	{
		$fieldsWithRelationships = $this->getRelationFields($form);
		
		foreach ($fieldsWithRelationships as $key => $field) {
			if (isset($field['pivot']) && $field['pivot']) {
				$values = isset($data[$field['name']]) ? $data[$field['name']] : [];
				$model->{$field['name']}()->sync($values);
				
				if (isset($field['pivotFields'])) {
					foreach ($field['pivotFields'] as $pivotField) {
						foreach ($data[$pivotField] as $pivot_id => $field) {
							$model->{$field['name']}()->updateExistingPivot($pivot_id, [$pivotField => $field]);
						}
					}
				}
			}
			
			if (isset($field['morph']) && $field['morph']) {
				$values = isset($data[$field['name']]) ? $data[$field['name']] : [];
				if ($model->{$field['name']}) {
					$model->{$field['name']}()->update($values);
				} else {
					$model->{$field['name']}()->create($values);
				}
			}
		}
	}
}
