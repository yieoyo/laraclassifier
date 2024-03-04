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

namespace App\Http\Controllers\Web\Public\Post\Traits;

use App\Helpers\UrlGen;

trait CatBreadcrumbTrait
{
	/**
	 * Get ordered category breadcrumb
	 *
	 * @param $cat
	 * @param int $position
	 * @return array
	 */
	private function getCatBreadcrumb($cat, int $position = 0): array
	{
		$array = $this->getUnorderedCatBreadcrumb($cat, $position);
		
		return $this->reorderCatBreadcrumbItemsPositions($array);
	}
	
	/**
	 * Get unordered category breadcrumb
	 *
	 * @param $cat
	 * @param int $position
	 * @param array $tab
	 * @return array
	 */
	private function getUnorderedCatBreadcrumb($cat, int &$position = 0, array &$tab = []): array
	{
		$isFromCatModel = (
			array_key_exists('parent_id', (array)$cat)
			&& array_key_exists('seo_title', (array)$cat)
		);
		
		if (empty($cat) || !$isFromCatModel) {
			return $tab;
		}
		
		if (empty($tab)) {
			$tab[] = [
				'name'     => data_get($cat, 'name'),
				'url'      => UrlGen::category($cat),
				'position' => $position,
			];
		}
		
		if (!empty(data_get($cat, 'parent'))) {
			$tab[] = [
				'name'     => data_get($cat, 'parent.name'),
				'url'      => UrlGen::category(data_get($cat, 'parent')),
				'position' => $position + 1,
			];
			
			if (!empty(data_get($cat, 'parent.parent'))) {
				$position = $position + 1;
				
				return $this->getUnorderedCatBreadcrumb(data_get($cat, 'parent'), $position, $tab);
			}
		}
		
		return $tab;
	}
	
	/**
	 * Reorder the items' positions
	 * And transform each item from array to collection
	 *
	 * @param array|null $array
	 * @return array
	 */
	private function reorderCatBreadcrumbItemsPositions(?array $array = []): array
	{
		if (!is_array($array)) {
			return [];
		}
		
		$countItems = count($array);
		if ($countItems > 0) {
			$tmp = $array;
			$j = $countParents = $countItems - 1;
			for ($i = 0; $i <= $countParents; $i++) {
				if (isset($array[$i]) && $tmp[$j]) {
					$array[$i]['position'] = $tmp[$j]['position'];
					
					// Transform the item from array to collection
					$array[$i] = collect($array[$i]);
				}
				$j--;
			}
			unset($tmp);
			$array = array_reverse($array);
		}
		
		return $array;
	}
}
