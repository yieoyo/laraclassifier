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

namespace App\Http\Controllers\Api\Post\Show;

use App\Helpers\VideoEmbedding;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PostFieldResource;
use App\Models\CategoryField;
use App\Models\FieldOption;

trait ShowFieldValueTrait
{
	/**
	 * Get Post's Custom Fields Values
	 *
	 * Note: Called when displaying the Post's details
	 *
	 * @param $categoryId
	 * @param $postId
	 * @return array|\Illuminate\Http\JsonResponse
	 */
	public function getFieldsValues($categoryId, $postId): array|\Illuminate\Http\JsonResponse
	{
		// Get the Post's Custom Fields by its Parent Category
		$customFields = CategoryField::getFields($categoryId, $postId);
		
		// Get the Post's Custom Fields that have a value
		$fieldValues = [];
		if ($customFields->count() > 0) {
			foreach ($customFields as $key => $field) {
				if (empty($field->default_value)) {
					continue;
				}
				
				$iField = [
					'id'    => $field->id,
					'name'  => $field->name,
					'type'  => $field->type,
					'value' => $field->default_value,
				];
				
				if (in_array($field->type, ['radio', 'select'])) {
					if (is_numeric($field->default_value)) {
						$option = FieldOption::find($field->default_value);
						if (!empty($option)) {
							$iField['value'] = $option->value;
						}
					}
				}
				
				if (!is_array($field->default_value)) {
					if ($field->type == 'checkbox') {
						$iField['value'] = ($field->default_value == 1) ? t('Yes') : t('No');
					}
					
					if ($field->type == 'video') {
						$iField['value'] = VideoEmbedding::embedVideo($field->default_value);
					}
					
					if ($field->type == 'file') {
						$iField['value'] = privateFileUrl($field->default_value, null);
					}
					
					if ($field->type == 'url') {
						$iField['value'] = addHttp($field->default_value);
					}
				} else {
					if ($field->type == 'checkbox_multiple') {
						$fValues = [];
						foreach ($field->default_value as $iKey => $iValue) {
							if (empty($iValue)) continue;
							$fValues[$iKey] = $iValue->value;
						}
						$iField['value'] = $fValues;
					}
				}
				
				$fieldValues[$key] = $iField;
			}
		}
		
		$fieldValues = collect($fieldValues);
		
		// api/categories/{id}/fields/post/{postId}
		$endpointResponseIsNeed = (
			getRequestSegment(2) == 'categories'
			&& getRequestSegment(4) == 'fields'
			&& getRequestSegment(5) == 'post'
		);
		
		if ($endpointResponseIsNeed) {
			/*
			$data = [
				'success' => true,
				'result'  => new PostFieldResource($fieldValues),
			];
			
			return apiResponse()->json($data);
			*/
			
			$resourceCollection = new EntityCollection('PostFieldResource', $fieldValues);
			$message = ($fieldValues->count() <= 0) ? t('no_post_fields_found') : null;
			
			return apiResponse()->withCollection($resourceCollection, $message);
		} else {
			// Get Result's Data
			return $fieldValues->toArray();
		}
	}
}
