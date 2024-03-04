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

namespace App\Helpers\Search\Traits\Filters;

use Illuminate\Support\Facades\DB;

trait DateFilter
{
	protected function applyDateFilter(): void
	{
		if (!(isset($this->posts) && isset($this->postsTable))) {
			return;
		}
		
		$postedDate = request()->query('postedDate');
		$postedDate = (is_numeric($postedDate) || is_string($postedDate)) ? $postedDate : null;
		
		if (empty($postedDate)) {
			return;
		}
		
		$table = DB::getTablePrefix() . $this->postsTable;
		
		$this->posts->whereRaw($table . '.created_at BETWEEN DATE_SUB(NOW(), INTERVAL ? DAY) AND NOW()', [$postedDate]);
	}
}
