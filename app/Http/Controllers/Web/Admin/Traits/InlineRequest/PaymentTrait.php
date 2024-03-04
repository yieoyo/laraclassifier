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

use App\Helpers\Payment as PaymentHelper;
use App\Models\Payment;

trait PaymentTrait
{
	/**
	 * Update the 'active' column of the payment table
	 *
	 * @param $model
	 * @param $column
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function updatePaymentData($model, $column): \Illuminate\Http\JsonResponse
	{
		$isValidCondition = ($this->table == 'payments' && $column == 'active' && !empty($model));
		if (!$isValidCondition) {
			$error = trans('admin.inline_req_condition', ['table' => $this->table, 'column' => $column]);
			
			return $this->responseError($error, 400);
		}
		
		$payableModel = '\\' . $model->payable_type;
		$isPromoting = (str_ends_with($model->payable_type, 'Post'));
		$isSubscripting = (str_ends_with($model->payable_type, 'User'));
		
		if (!$isPromoting && !$isSubscripting) {
			return $this->responseError(t('payable_type_not_found'), 400);
		}
		
		$payable = $payableModel::find($model->payable_id);
		if (empty($payable)) {
			$error = $isPromoting ? t('post_not_found') : t('user_not_found');
			
			return $this->responseError($error);
		}
		
		// Save data
		if ($model->{$column} != 1) {
			if ($model->id == $payable->paymentEndingLater?->id) {
				$periodStart = now();
				$periodEnd = ($model->interval > 0) ? now()->addDays($model->interval) : now();
			} else {
				$daysLeft = PaymentHelper::getDaysLeftBeforePayablePaymentsExpire($payable, $model->period_start);
				$periodStart = PaymentHelper::periodDate($model->period_start, $daysLeft);
				$periodEnd = PaymentHelper::periodDate($model->period_end, $daysLeft);
			}
			
			$model->period_start = $periodStart->startOfDay();
			$model->period_end = $periodEnd->endOfDay();
			$model->{$column} = 1;
		} else {
			$model->{$column} = 0;
		}
		$model->save();
		
		/*
		 * Used by the OfflinePayment plugin
		 * Update the 'featured' fields of the related payable (Post|User)
		 * And update the 'reviewed' fields of the related payable (Post)
		 */
		if ($model->{$column} == 1) {
			if ($isPromoting) {
				$payable->reviewed_at = now();
			}
			$payable->featured = 1;
			$payable->save();
		} else {
			$payableActivePayments = Payment::query()
				->where('payable_type', $model->payable_type)
				->where('payable_id', $model->payable_id)
				->where('id', '!=', $model->id)
				->valid()
				->active();
			
			if ($payableActivePayments->count() <= 0) {
				if ($isPromoting) {
					$payable->reviewed_at = null;
				}
				$payable->featured = 0;
				$payable->save();
			}
		}
		
		return $this->responseSuccess($model, $column);
	}
}
