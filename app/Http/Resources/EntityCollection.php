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
use Illuminate\Http\Resources\Json\ResourceCollection;

class EntityCollection extends ResourceCollection
{
	public string $entityResource;
	
	/**
	 * EntityCollection constructor.
	 *
	 * @param $controllerName
	 * @param $resource
	 */
	public function __construct($controllerName, $resource)
	{
		parent::__construct($resource);
		
		$this->entityResource = str($controllerName)->replaceLast('Controller', 'Resource')->toString();
		if (!str_starts_with($this->entityResource, '\\')) {
			$this->entityResource = '\\' . __NAMESPACE__ . '\\' . $this->entityResource;
		}
	}
	
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		$collection = $this->collection->transform(function ($entity) {
			return new $this->entityResource($entity);
		});
		
		return [
			'data' => $collection,
		];
	}
}
