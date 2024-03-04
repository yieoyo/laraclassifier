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

namespace App\Http\Controllers\Api;

use App\Http\Resources\GenderResource;
use App\Http\Resources\EntityCollection;
use App\Models\Gender;

/**
 * @group Users
 */
class GenderController extends BaseController
{
	/**
	 * List genders
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$genders = Gender::query()->get();
		
		$resourceCollection = new EntityCollection(class_basename($this), $genders);
		
		$message = ($genders->count() <= 0) ? t('no_genders_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get gender
	 *
	 * @urlParam id int required The gender's ID. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		$gender = Gender::query()->where('id', $id);
		
		$gender = $gender->first();
		
		abort_if(empty($gender), 404, t('gender_not_found'));
		
		$resource = new GenderResource($gender);
		
		return apiResponse()->withResource($resource);
	}
}
