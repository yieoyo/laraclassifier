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

use App\Http\Resources\EntityCollection;
use App\Http\Resources\SavedPostResource;
use App\Models\SavedPost;
use Illuminate\Http\Request;

/**
 * @group Saved Posts
 */
class SavedPostController extends BaseController
{
	/**
	 * List saved listings
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam country_code string required The code of the user's country. Example: US
	 * @queryParam embed string The Comma-separated list of the category relationships for Eager Loading - Possible values: post,city,pictures,user. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$user = auth('sanctum')->user();
		
		$countryCode = request()->query('country_code', config('country.code'));
		
		$savedPosts = SavedPost::query()
			->whereHas('post', fn ($query) => $query->inCountry($countryCode))
			->where('user_id', $user->id);
		
		$embed = explode(',', request()->query('embed'));
		
		if (in_array('user', $embed)) {
			$savedPosts->with('user');
		}
		
		if (in_array('post', $embed)) {
			$savedPosts->with('post')->with(['post.pictures', 'post.city', 'post.user']);
		}
		
		// Sorting
		$savedPosts = $this->applySorting($savedPosts, ['created_at']);
		
		$savedPosts = $savedPosts->paginate($this->perPage);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$savedPosts = setPaginationBaseUrl($savedPosts);
		
		$collection = new EntityCollection(class_basename($this), $savedPosts);
		
		$message = ($savedPosts->count() <= 0) ? t('no_saved_posts_found') : null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Store/Delete saved listing
	 *
	 * Save a post/listing in favorite, or remove it from favorite.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam post_id int required The post/listing's ID. Example: 2
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(Request $request): \Illuminate\Http\JsonResponse
	{
		$guard = 'sanctum';
		if (!auth($guard)->check()) {
			return apiResponse()->unauthorized();
		}
		
		$data = [
			'success' => false,
			'result'  => null,
		];
		
		// Get the 'post_id' field
		$postId = $request->input('post_id');
		if (empty($postId)) {
			$data['message'] = 'The "post_id" field need to be filled.';
			
			return apiResponse()->json($data, 400);
		}
		
		$data['success'] = true;
		
		$user = auth($guard)->user();
		
		$savedPost = SavedPost::where('user_id', $user->id)->where('post_id', $postId);
		if ($savedPost->count() > 0) {
			// Delete SavedPost
			$savedPost->delete();
			
			$data['message'] = t('Listing deleted from favorites successfully');
		} else {
			// Store SavedPost
			$savedPostArray = [
				'user_id' => $user->id,
				'post_id' => $postId,
			];
			$savedPost = new SavedPost($savedPostArray);
			$savedPost->save();
			
			$resource = new SavedPostResource($savedPost);
			
			$data['message'] = t('Listing saved in favorites successfully');
			$data['result'] = $resource;
		}
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Delete saved listing(s)
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @urlParam ids string required The ID or comma-separated IDs list of saved post/listing(s).
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): \Illuminate\Http\JsonResponse
	{
		$user = auth('sanctum')->user();
		
		$data = [
			'success' => false,
			'message' => t('no_deletion_is_done'),
			'result'  => null,
		];
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $postId) {
			$savedPost = SavedPost::query()
				->where('user_id', $user->id)
				->where('post_id', $postId)
				->first();
			
			if (!empty($savedPost)) {
				$res = $savedPost->delete();
			}
		}
		
		// Confirmation
		if ($res) {
			$data['success'] = true;
			
			$count = count($ids);
			if ($count > 1) {
				$data['message'] = t('x entities have been deleted successfully', ['entities' => t('listings'), 'count' => $count]);
			} else {
				$data['message'] = t('1 entity has been deleted successfully', ['entity' => t('listing')]);
			}
		}
		
		return apiResponse()->json($data);
	}
}
