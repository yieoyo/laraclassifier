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

namespace App\Http\Controllers\Web\Admin\Traits\InlineRequest;

trait HomeSectionTrait
{
	/**
	 * Update the 'active' column of the home sections table
	 *
	 * @param $model
	 * @param $column
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function updateHomeSectionData($model, $column): \Illuminate\Http\JsonResponse
	{
		$isValidCondition = ($this->table == 'home_sections' && $column == 'active' && !empty($model));
		if (!$isValidCondition) {
			$error = trans('admin.inline_req_condition', ['table' => $this->table, 'column' => $column]);
			
			return $this->responseError($error, 400);
		}
		
		// Update the 'active' column
		// See the "app/Observers/HomeSectionObserver.php" file for complete operation
		$model->{$column} = ($model->{$column} != 1) ? 1 : 0;
		
		// Update the 'active' option in the 'value' column
		$valueColumnValue = $model->value;
		$valueColumnValue[$column] = $model->{$column};
		$model->value = $valueColumnValue;
		
		$model->save();
		
		return $this->responseSuccess($model, $column);
	}
}
