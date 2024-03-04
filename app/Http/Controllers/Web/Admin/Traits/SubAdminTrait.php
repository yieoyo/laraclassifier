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

namespace App\Http\Controllers\Web\Admin\Traits;

use App\Models\Scopes\ActiveScope;

trait SubAdminTrait
{
	/**
	 * Increment new Entries Codes
	 *
	 * @param string|null $prefix
	 * @return string
	 */
	public function autoIncrementCode(?string $prefix = null): string
	{
		// Init.
		$startAt = 0;
		$customPrefix = config('larapen.core.locationCodePrefix', 'Z');
		$customPrefix = is_string($customPrefix) ? $customPrefix : 'Z';
		$zeroLead = 3;
		
		// Get the latest Entry
		$latestAddedEntry = $this->xPanel->model->withoutGlobalScope(ActiveScope::class)
			->where('country_code', $this->countryCode)
			->where('code', 'LIKE', $prefix . $customPrefix . '%')
			->orderByDesc('code')
			->first();
		
		if (!empty($latestAddedEntry)) {
			$codeTab = explode($prefix, $latestAddedEntry->code);
			$latestAddedId = $codeTab[1] ?? null;
			if (!empty($latestAddedId)) {
				if (is_numeric($latestAddedId)) {
					$newId = $latestAddedId + 1;
				} else {
					$newId = $this->alphanumericToUniqueIncrementation($latestAddedId, $startAt, $zeroLead, $customPrefix);
				}
			} else {
				$newId = $customPrefix . zeroLead($startAt + 1, $zeroLead);
			}
		} else {
			$newId = $customPrefix . zeroLead($startAt + 1, $zeroLead);
		}
		
		// Full new ID
		return $prefix . $newId;
	}
	
	/**
	 * Increment existing alphanumeric value by Transforming the given value
	 * e.g. AB => ZZ001 => ZZ002 => ZZ003 ...
	 *
	 * @param string|null $value
	 * @param int $startAt
	 * @param int $zeroLead
	 * @param string|null $customPrefix
	 * @return string
	 */
	private function alphanumericToUniqueIncrementation(?string $value, int $startAt, int $zeroLead, ?string $customPrefix): string
	{
		if (!empty($value)) {
			// Numeric value
			if (is_numeric($value)) {
				
				$value = $customPrefix . zeroLead($value + 1);
				
			} // NOT numeric value
			else {
				
				// Value contains the Custom Prefix
				if (str_starts_with($value, $customPrefix)) {
					
					$prefixLoop = '';
					$partOfValue = '';
					
					$tmp = explode($customPrefix, $value);
					if (count($tmp) > 0) {
						foreach ($tmp as $item) {
							if (!empty($item)) {
								$partOfValue = $item;
								break;
							} else {
								$prefixLoop .= $customPrefix;
							}
						}
					}
					
					if (!empty($partOfValue)) {
						if (is_numeric($partOfValue)) {
							$tmpValue = zeroLead($partOfValue + 1, $zeroLead);
						} else {
							// If the part of the value is not numeric, Get a (sub-)new unique code
							$tmpValue = $this->alphanumericToUniqueIncrementation($partOfValue, $startAt, $zeroLead, $customPrefix);
						}
					} else {
						$tmpValue = zeroLead($startAt + 1, $zeroLead);
					}
					
					$value = $prefixLoop . $tmpValue;
					
				} // Value DOESN'T contain the Custom Prefix
				else {
					$value = $customPrefix . zeroLead($startAt + 1, $zeroLead);
				}
			}
			
		} else {
			$value = $customPrefix . zeroLead($startAt + 1, $zeroLead);
		}
		
		return $value;
	}
}
