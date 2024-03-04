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

namespace App\Http\Controllers\Api\Post\Store;

use App\Models\CategoryField;
use App\Models\Post;
use App\Models\PostValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

trait StoreFieldValueTrait
{
	/**
	 * Create & Update for Custom Fields
	 *
	 * Note: Called when submitting Post's creation or edit forms
	 *
	 * @param \App\Models\Post|null $post
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function fieldsValuesStore(?Post $post, Request $request): \Illuminate\Http\JsonResponse
	{
		$postValues = [];
		
		if (empty($post)) {
			return apiResponse()->notFound(t('post_not_found'));
		}
		
		// Delete all old PostValue entries, if exist
		$oldPostValues = PostValue::with(['field'])->where('post_id', $post->id)->get();
		if ($oldPostValues->count() > 0) {
			foreach ($oldPostValues as $oldPostValue) {
				if ($oldPostValue->field->type == 'file') {
					if ($request->hasFile('cf.' . $oldPostValue->field->id)) {
						$oldPostValue->delete();
					}
				} else {
					$oldPostValue->delete();
				}
			}
		}
		
		// Get Category's Fields details
		$fields = CategoryField::getFields($request->input('category_id'));
		if ($fields->count() > 0) {
			foreach ($fields as $field) {
				if ($field->type == 'file') {
					$fileField = 'cf.' . $field->id;
					if ($request->hasFile($fileField) || $request->has($fileField)) {
						// Get file's destination path
						$destinationPath = 'files/' . strtolower($post->country_code) . '/' . $post->id;
						
						// Get the file
						$file = $request->file($fileField, $request->input($fileField));
						
						// Check if the file is valid
						if (!$file->isValid()) {
							continue;
						}
						
						// Get filename & file path
						$filename    = $file->getClientOriginalName();
						$extension   = $file->getClientOriginalExtension();
						$newFilename = md5($filename . time()) . '.' . $extension;
						$filePath    = $destinationPath . '/' . $newFilename;
						
						$postValueInfo = [
							'post_id'  => $post->id,
							'field_id' => $field->id,
							'value'    => $filePath,
						];
						
						$newPostValue = new PostValue($postValueInfo);
						$newPostValue->save();
						
						$this->disk->put($newPostValue->value, File::get($file->getrealpath()));
						
						$postValues[$newPostValue->id] = $newPostValue;
					}
				} else {
					if ($request->filled('cf.' . $field->id)) {
						// Get the input
						$input = $request->input('cf.' . $field->id);
						
						if (is_array($input)) {
							foreach ($input as $optionId => $optionValue) {
								$postValueInfo = [
									'post_id'   => $post->id,
									'field_id'  => $field->id,
									'option_id' => $optionId,
									'value'     => $optionValue,
								];
								
								$newPostValue = new PostValue($postValueInfo);
								$newPostValue->save();
								$postValues[$newPostValue->id][$optionId] = $newPostValue;
							}
						} else {
							$postValueInfo = [
								'post_id'  => $post->id,
								'field_id' => $field->id,
								'value'    => $input,
							];
							
							$newPostValue = new PostValue($postValueInfo);
							$newPostValue->save();
							$postValues[$newPostValue->id] = $newPostValue;
						}
					}
				}
			}
		}
		
		// Get Result's Data
		$data = [
			'success' => true,
			'result'  => $postValues,
		];
		
		return apiResponse()->json($data);
	}
}
