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

trait PayableTrait
{
	/**
	 * - Update the 'featured' column of the payable (posts|users) table
	 * - Add or delete payment using the OfflinePayment plugin
	 *
	 * @param $model
	 * @param $column
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function updatePayableData($model, $column): \Illuminate\Http\JsonResponse
	{
		$opTool = '\extras\plugins\offlinepayment\app\Helpers\OpTools';
		$isOfflinePaymentInstalled = (config('plugins.offlinepayment.installed') && class_exists($opTool));
		
		$isValidCondition = (
			in_array($this->table, ['posts', 'users'])
			&& $column == 'featured'
			&& !empty($model)
			&& $isOfflinePaymentInstalled
		);
		
		if (!$isValidCondition) {
			$error = trans('admin.inline_req_condition', ['table' => $this->table, 'column' => $column]);
			
			return $this->responseError($error, 400);
		}
		
		// Save data
		if ($model->{$column} == 1) {
			$result = $opTool::deleteFeatured($model);
		} else {
			$result = $opTool::createFeatured($model);
		}
		
		$this->message = data_get($result, 'message', $this->message);
		
		if (!data_get($result, 'success')) {
			return $this->responseError($this->message);
		}
		
		return $this->responseSuccess($model, $column);
	}
}
