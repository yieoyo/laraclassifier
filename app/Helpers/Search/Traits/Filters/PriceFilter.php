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

trait PriceFilter
{
	protected function applyPriceFilter(): void
	{
		// The 'calculatedPrice' is a calculated column, so HAVING clause is required
		if (!isset($this->having)) {
			return;
		}
		
		$minPrice = request()->filled('minPrice') ? request()->query('minPrice') : null;
		$maxPrice = request()->filled('maxPrice') ? request()->query('maxPrice') : null;
		
		$minPrice = (is_numeric($minPrice)) ? $minPrice : null;
		$maxPrice = (is_numeric($maxPrice)) ? $maxPrice : null;
		
		if (!is_null($minPrice) && !is_null($maxPrice)) {
			if ($maxPrice > $minPrice) {
				$this->having[] = 'calculatedPrice >= ' . $minPrice;
				$this->having[] = 'calculatedPrice <= ' . $maxPrice;
			}
		} else {
			if (!is_null($minPrice)) {
				$this->having[] = 'calculatedPrice >= ' . $minPrice;
			}
			if (!is_null($maxPrice)) {
				$this->having[] = 'calculatedPrice <= ' . $maxPrice;
			}
		}
	}
}
