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

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		$entity = [
			'code' => $this->code,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column};
		}
		
		$entity['icode'] = $this->icode ?? null;
		$entity['flag_url'] = $this->flag_url ?? null;
		$entity['flag16_url'] = $this->flag16_url ?? null;
		$entity['flag24_url'] = $this->flag24_url ?? null;
		$entity['flag32_url'] = $this->flag32_url ?? null;
		$entity['flag48_url'] = $this->flag48_url ?? null;
		$entity['flag64_url'] = $this->flag64_url ?? null;
		$entity['background_image_url'] = $this->background_image_url ?? null;
		
		$embed = explode(',', request()->query('embed'));
		
		if (in_array('currency', $embed)) {
			$entity['currency'] = new CurrencyResource($this->whenLoaded('currency'));
		}
		
		return $entity;
	}
}
