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

namespace App\Http\Controllers\Api\Thread;

use App\Models\Thread;

trait UpdateByTypeTrait
{
	/**
	 * @param $ids
	 * @param $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updateByType($ids, $user): \Illuminate\Http\JsonResponse
	{
		if (!isset($user->id)) {
			return apiResponse()->forbidden();
		}
		
		if (request()->query('type') == 'markAsRead') {
			return $this->markAsRead($ids, $user);
		}
		if (request()->query('type') == 'markAsUnread') {
			return $this->markAsUnread($ids, $user);
		}
		if (request()->query('type') == 'markAsImportant') {
			return $this->markAsImportant($ids, $user);
		}
		if (request()->query('type') == 'markAsNotImportant') {
			return $this->markAsNotImportant($ids, $user);
		}
		if (request()->query('type') == 'markAllAsRead') {
			return $this->markAllAsRead($user);
		}
		
		return apiResponse()->forbidden();
	}
	
	/**
	 * @param $ids
	 * @param $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function markAsRead($ids, $user): \Illuminate\Http\JsonResponse
	{
		foreach ($ids as $id) {
			// Get the Thread
			$thread = $this->findThread($id);
			
			if (!empty($thread)) {
				$thread->markTheThreadAsRead($user->id);
			}
		}
		
		$count = count($ids);
		if ($count > 1) {
			$msg = t('x entities have been marked as action successfully', [
				'entities' => t('messages'),
				'count'    => $count,
				'action'   => mb_strtolower(t('read')),
			]);
		} else {
			$msg = t('1 entity has been marked as action successfully', [
				'entity' => t('message'),
				'action' => mb_strtolower(t('read')),
			]);
		}
		
		return apiResponse()->success($msg);
	}
	
	/**
	 * @param $ids
	 * @param $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function markAsUnread($ids, $user): \Illuminate\Http\JsonResponse
	{
		foreach ($ids as $id) {
			// Get the Thread
			$thread = $this->findThread($id);
			
			if (!empty($thread)) {
				$thread->markTheThreadAsUnread($user->id);
			}
		}
		
		$count = count($ids);
		if ($count > 1) {
			$msg = t('x entities have been marked as action successfully', [
				'entities' => t('messages'),
				'count'    => $count,
				'action'   => mb_strtolower(t('unread')),
			]);
		} else {
			$msg = t('1 entity has been marked as action successfully', [
				'entity' => t('message'),
				'action' => mb_strtolower(t('unread')),
			]);
		}
		
		return apiResponse()->success($msg);
	}
	
	/**
	 * @param $ids
	 * @param $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function markAsImportant($ids, $user): \Illuminate\Http\JsonResponse
	{
		foreach ($ids as $id) {
			// Get the Thread
			$thread = $this->findThread($id);
			
			if (!empty($thread)) {
				$thread->markAsImportant($user->id);
			}
		}
		
		$count = count($ids);
		if ($count > 1) {
			$msg = t('x entities have been marked as action successfully', [
				'entities' => t('messages'),
				'count'    => $count,
				'action'   => mb_strtolower(t('important')),
			]);
		} else {
			$msg = t('1 entity has been marked as action successfully', [
				'entity' => t('message'),
				'action' => mb_strtolower(t('important')),
			]);
		}
		
		return apiResponse()->success($msg);
	}
	
	/**
	 * @param $ids
	 * @param $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function markAsNotImportant($ids, $user): \Illuminate\Http\JsonResponse
	{
		foreach ($ids as $id) {
			// Get the Thread
			$thread = $this->findThread($id);
			
			if (!empty($thread)) {
				$thread->markAsNotImportant($user->id);
			}
		}
		
		$count = count($ids);
		if ($count > 1) {
			$msg = t('x entities have been marked as action successfully', [
				'entities' => t('messages'),
				'count'    => $count,
				'action'   => mb_strtolower(t('not important')),
			]);
		} else {
			$msg = t('1 entity has been marked as action successfully', [
				'entity' => t('message'),
				'action' => mb_strtolower(t('not important')),
			]);
		}
		
		return apiResponse()->success($msg);
	}
	
	/**
	 * Mark all Threads as read
	 *
	 * @param $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function markAllAsRead($user): \Illuminate\Http\JsonResponse
	{
		// Get all Threads with New Messages
		$threadsWithNewMessage = Thread::whereHas('post', function ($query) {
			$query->inCountry();
		})->forUserWithNewMessages($user->id);
		
		// Count all Threads
		$countThreadsWithNewMessage = $threadsWithNewMessage->count();
		
		if ($countThreadsWithNewMessage > 0) {
			foreach ($threadsWithNewMessage->cursor() as $thread) {
				$thread->markTheThreadAsRead($user->id);
			}
			$msg = t('x entities have been marked as action successfully', [
				'entities' => t('messages'),
				'count'    => $countThreadsWithNewMessage,
				'action'   => mb_strtolower(t('read')),
			]);
			
			return apiResponse()->success($msg);
		} else {
			$msg = t('This action could not be done');
			
			return apiResponse()->error($msg);
		}
	}
	
	/* PRIVATE */
	
	/**
	 * @param $id
	 * @return mixed
	 */
	private function findThread($id)
	{
		$user = auth('sanctum')->user();
		
		return Thread::where((new Thread)->getTable() . '.id', $id)
			->forUser($user->id)
			->first();
	}
}
