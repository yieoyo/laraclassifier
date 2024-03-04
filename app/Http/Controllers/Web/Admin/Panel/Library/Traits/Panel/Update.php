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


trait Update
{
	/*
	|--------------------------------------------------------------------------
	|                                   UPDATE
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Update a row in the database.
	 *
	 * @param $id
	 * @param $data
	 * @return mixed
	 */
	public function update($id, $data)
	{
		$item = $this->model->findOrFail($id);
		$valuesToStore = $this->compactFakeFields($data, 'update');
		$updated = $item->update($valuesToStore);
		
		if ($this->isEnabledSyncPivot()) {
			$this->syncPivot($item, $data, 'update');
		}
		
		return $item;
	}
	
	/**
	 * Get all fields needed for the EDIT ENTRY form.
	 *
	 * @param  [integer] The id of the entry that is being edited.
	 * @param int $id
	 *
	 * @return [array] The fields with attributes, fake attributes and values.
	 */
	/**
	 * @param $id
	 * @return array
	 */
	public function getUpdateFields($id)
	{
		$fields = (array)$this->updateFields;
		$entry = $this->getEntry($id);
		
		foreach ($fields as $key => $field) {
			// set the value
			if (!isset($fields[$key]['value'])) {
				if (isset($field['subfields'])) {
					$fields[$key]['value'] = [];
					foreach ($field['subfields'] as $k => $subfield) {
						$fields[$key]['value'][] = $entry->{$subfield['name']};
					}
				} else {
					$fields[$key]['value'] = $entry->{$field['name']};
					
					if (isset($entry->value) && is_array($entry->value) && array_key_exists($key, $entry->value)) {
						$fields[$key]['value'] = $entry->value[$key];
					}
				}
			}
		}
		
		// always have a hidden input for the entry id
		$fields['id'] = [
			'name'  => $entry->getKeyName(),
			'value' => $entry->getKey(),
			'type'  => 'hidden',
		];
		
		if ($this->model->translationEnabled()) {
			$fields['locale'] = [
				'name'  => 'locale',
				'type'  => 'hidden',
				'value' => request()->input('locale') ?? app()->getLocale(),
			];
		}
		
		return $fields;
	}
}
