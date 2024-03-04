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

use App\Http\Controllers\Api\Picture\MultiStepsPictures;
use App\Http\Controllers\Api\Picture\SingleStepPictures;
use App\Http\Requests\Front\PhotoRequest;
use App\Models\Picture;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PictureResource;

/**
 * @group Pictures
 */
class PictureController extends BaseController
{
	use MultiStepsPictures, SingleStepPictures;
	
	/**
	 * List pictures
	 *
	 * @queryParam embed string The list of the picture relationships separated by comma for Eager Loading. Possible values: post. Example: null
	 * @queryParam postId int List of pictures related to a listing (using the listing ID). Example: 1
	 * @queryParam latest boolean Get only the first picture after ordering (as object instead of collection). Possible value: 0 or 1. Example: 0
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: position, created_at.Example: -position
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		
		$pictures = Picture::query();
		
		if (in_array('post', $embed)) {
			$pictures->with('post');
		}
		
		if (request()->filled('postId')) {
			$pictures->where('post_id', request()->query('postId'));
		}
		
		// Sorting
		$pictures = $this->applySorting($pictures, ['position', 'created_at']);
		
		if (request()->query('latest') == 1) {
			$picture = $pictures->first();
			
			abort_if(empty($picture), 404, t('picture_not_found'));
			
			$resource = new PictureResource($picture);
			
			return apiResponse()->withResource($resource);
		} else {
			$pictures = $pictures->paginate($this->perPage);
			
			// If the request is made from the app's Web environment,
			// use the Web URL as the pagination's base URL
			$pictures = setPaginationBaseUrl($pictures);
			
			$resourceCollection = new EntityCollection(class_basename($this), $pictures);
			
			$message = ($pictures->count() <= 0) ? t('no_pictures_found') : null;
			
			return apiResponse()->withCollection($resourceCollection, $message);
		}
	}
	
	/**
	 * Get picture
	 *
	 * @queryParam embed string The list of the picture relationships separated by comma for Eager Loading. Example: null
	 *
	 * @urlParam id int required The picture's ID. Example: 298
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		
		$picture = Picture::query();
		
		if (in_array('post', $embed)) {
			$picture->with('post');
		}
		
		$picture = $picture->find($id);
		
		abort_if(empty($picture), 404, t('picture_not_found'));
		
		$resource = new PictureResource($picture);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Store picture
	 *
	 * Note: This endpoint is only available for the multi steps post edition.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam country_code string required The code of the user's country. Example: US
	 * @bodyParam count_packages int required The number of available packages. Example: 3
	 * @bodyParam count_payment_methods int required The number of available payment methods. Example: 1
	 * @bodyParam post_id int required The post's ID. Example: 2
	 *
	 * @bodyParam pictures file[] The files to upload.
	 *
	 * @param \App\Http\Requests\Front\PhotoRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(PhotoRequest $request): \Illuminate\Http\JsonResponse
	{
		// Check if the form type is 'Single-Step Form'
		$isSingleStepFormEnabled = (config('settings.single.publication_form_type') == '2');
		if ($isSingleStepFormEnabled) {
			abort(404);
		}
		
		return $this->multiStepsPicturesStore($request);
	}
	
	/**
	 * Reorder pictures
	 *
	 * Note: This endpoint is only available for the multi steps form edition.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 * @header X-Action bulk
	 *
	 * @bodyParam post_id int required The post's ID. Example: 2
	 *
	 * @bodyParam body string required Encoded json of the new pictures' positions array [['id' => 2, 'position' => 1], ['id' => 1, 'position' => 2], ...]
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function reorder(): \Illuminate\Http\JsonResponse
	{
		// Single-Step Form
		$isSingleStepFormEnabled = (config('settings.single.publication_form_type') == '2');
		if ($isSingleStepFormEnabled) {
			abort(404);
		}
		
		return $this->reorderMultiStepsPictures();
	}
	
	/**
	 * Delete picture
	 *
	 * Note: This endpoint is only available for the multi steps form edition.
	 * For newly created listings, the post's ID needs to be added in the request input with the key 'new_post_id'.
	 * The 'new_post_id' and 'new_post_tmp_token' fields need to be removed or unset during the listing edition steps.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam post_id int required The post's ID. Example: 2
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy($id): \Illuminate\Http\JsonResponse
	{
		// Check if the form type is 'Single-Step Form'
		$isSingleStepFormEnabled = (config('settings.single.publication_form_type') == '2');
		if ($isSingleStepFormEnabled) {
			// abort(404);
		}
		
		return $this->deleteMultiStepsPicture($id);
	}
}
