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

trait FakeFields
{
	/**
	 * Refactor the request array to something that can be passed to the model's create or update function.
	 * The resulting array will only include the fields that are stored in the database and their values,
	 * plus the '_token' and 'redirect_after_save' variables.
	 *
	 * @param array $request
	 * @param string $form
	 * @return array
	 */
	public function compactFakeFields($request, $form = 'create')
	{
		if (!is_array($request)) {
			$request = request()->all();
		}
		
		$fakeFieldColumnsToEncode = [];
		
		// get the right fields according to the form type (create/update)
		$fields = match (strtolower($form)) {
			'update' => $this->updateFields,
			default => $this->createFields,
		};
		
		// go through each defined field
		foreach ($fields as $k => $field) {
			if (isset($field['type']) && $field['type'] == 'custom_html') {
				continue;
			}
			// if it's a fake field
			if (isset($field['fake']) && $field['fake']) {
				// add it to the request in its appropriate variable - the one defined, if defined
				if (isset($field['store_in'])) {
					$request[$field['store_in']][$field['name']] = $request[$field['name']];
					
					// remove the fake field
					Arr::pull($request, $field['name']);
					
					if (!in_array($field['store_in'], $fakeFieldColumnsToEncode, true)) {
						$fakeFieldColumnsToEncode[] = $field['store_in'];
					}
				} else {
					// otherwise in the one defined in the $crud variable
					
					$request['extras'][$field['name']] = $request[$field['name']];
					
					// remove the fake field
					Arr::pull($request, $field['name']);
					
					if (!in_array('extras', $fakeFieldColumnsToEncode, true)) {
						$fakeFieldColumnsToEncode[] = 'extras';
					}
				}
			}
		}
		
		// json_encode all fake_value columns if applicable in the database, so they can be properly stored and interpreted
		if (is_array($fakeFieldColumnsToEncode) && count($fakeFieldColumnsToEncode) > 0) {
			foreach ($fakeFieldColumnsToEncode as $key => $value) {
				$isTranslatableModel = (
					property_exists($this->model, 'translatable')
					&& method_exists($this->model, 'getTranslatableAttributes')
					&& in_array($value, $this->model->getTranslatableAttributes(), true)
				);
				
				if (!$isTranslatableModel && $this->model->shouldEncodeFake($value)) {
					$request[$value] = json_encode($request[$value]);
				}
				
				if (!isJson($request[$value])) {
					if (is_array($request[$value])) {
						$request[$value] = json_encode($request[$value]);
					}
				}
			}
		}
		
		// if there are no fake fields defined, this will just return the original Request in full
		// since no modifications or additions have been made to $request
		return $request;
	}
}
