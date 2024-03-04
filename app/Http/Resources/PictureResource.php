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

class PictureResource extends JsonResource
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
			'id' => $this->id,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column};
		}
		
		$defaultPicture = config('larapen.core.picture.default');
		$defaultPictureUrl = imgUrl($defaultPicture);
		$entity['url'] = [
			'full'   => $this->filename_url ?? $defaultPictureUrl,
			'small'  => $this->filename_url_small ?? $defaultPictureUrl,
			'medium' => $this->filename_url_medium ?? $defaultPictureUrl,
			'big'    => $this->filename_url_big ?? $defaultPictureUrl,
		];
		
		$embed = explode(',', request()->query('embed'));
		
		if (in_array('post', $embed)) {
			$entity['post'] = new PostResource($this->whenLoaded('post'));
		}
		
		return $entity;
	}
}
