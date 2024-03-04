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

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class StrictActiveScope implements Scope
{
	/**
	 * Apply the scope to a given Eloquent query builder.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $builder
	 * @param \Illuminate\Database\Eloquent\Model $model
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function apply(Builder $builder, Model $model)
	{
		/*
		 * This scope is considered as strict except this condition,
		 * where we need to load all entries from some Admin panel Controllers:
		 */
		$areActiveOrNonActiveEntriesRequired = (
			str_contains(currentRouteAction(), 'Admin\PaymentController')
			|| str_contains(currentRouteAction(), 'Admin\AjaxController')
			|| str_contains(currentRouteAction(), 'Admin\InlineRequestController')
		);
		if ($areActiveOrNonActiveEntriesRequired) {
			return $builder;
		}
		
		// Load only activated entries for the rest of the website (Admin panel & Front)
		return $builder->where('active', 1);
	}
}
