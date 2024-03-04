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
use App\Http\Resources\ThreadMessageResource;
use App\Models\ThreadMessage;

/**
 * @group Threads
 */
class ThreadMessageController extends BaseController
{
	/**
	 * List messages
	 *
	 * Get all thread's messages
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string Comma-separated list of the post relationships for Eager Loading - Possible values: user. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @urlParam threadId int required The thread's ID. Example: 293
	 *
	 * @param $threadId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index($threadId): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		
		$authUser = auth('sanctum')->user();
		
		// All threads messages
		$threadMessages = ThreadMessage::whereHas('thread', function ($query) use ($threadId, $authUser) {
			$query->where('thread_id', $threadId)->forUser($authUser->id);
		});
		
		if (in_array('user', $embed)) {
			$threadMessages->with('user');
		}
		
		// Sorting
		$threadMessages = $this->applySorting($threadMessages, ['created_at']);
		
		// Get rows & paginate
		$threadMessages = $threadMessages->paginate($this->perPage);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$threadMessages = setPaginationBaseUrl($threadMessages);
		
		$collection = new EntityCollection(class_basename($this), $threadMessages);
		
		$message = ($threadMessages->count() <= 0) ? t('no_messages_found') : null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Get message
	 *
	 * Get a thread's message (owned by the logged user) details
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string Comma-separated list of the post relationships for Eager Loading - Possible values: thread,user. Example: null
	 *
	 * @urlParam threadId int required The thread's ID. Example: 293
	 * @urlParam id int required The thread's message's ID. Example: 3545
	 *
	 * @param $threadId
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($threadId, $id): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		
		$authUser = auth('sanctum')->user();
		
		$threadMessage = ThreadMessage::whereHas('thread', function ($query) use ($threadId, $authUser) {
			$query->where('thread_id', $threadId)->forUser($authUser->id);
		});
		
		if (in_array('thread', $embed)) {
			$threadMessage->with('thread');
		}
		
		if (in_array('user', $embed)) {
			$threadMessage->with('user');
		}
		
		$threadMessage = $threadMessage->where('id', $id)->first();
		
		abort_if(empty($threadMessage), 404, t('message_not_found'));
		
		$resource = new ThreadMessageResource($threadMessage);
		
		return apiResponse()->withResource($resource);
	}
}
