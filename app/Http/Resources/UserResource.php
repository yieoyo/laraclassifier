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

use App\Models\Post;
use App\Models\SavedPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
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
			'id'       => $this->id,
			'name'     => $this->name,
			'username' => $this->username,
		];
		
		$entity['updated_at'] = $this->updated_at ?? null;
		$entity['original_updated_at'] = $this->original_updated_at ?? null;
		$entity['original_last_activity'] = $this->original_last_activity ?? null;
		$entity['created_at_formatted'] = $this->created_at_formatted ?? null;
		$entity['photo_url'] = $this->photo_url ?? null;
		$entity['p_is_online'] = $this->p_is_online ?? null;
		$entity['country_flag_url'] = $this->country_flag_url ?? null;
		
		$embed = explode(',', request()->query('embed'));
		
		$authUser = auth('sanctum')->user();
		if (!empty($authUser)) {
			$isAuthUserData = ($this->id == $authUser->getAuthIdentifier());
			
			$columns = array_diff($this->getFillable(), $this->getHidden());
			foreach ($columns as $column) {
				$entity[$column] = $this->{$column};
			}
			
			if (array_key_exists('can_be_impersonate', $entity)) {
				unset($entity['can_be_impersonate']);
			}
			
			$entity['phone_intl'] = $this->phone_intl ?? null;
			if (isset($this->remaining_posts)) {
				$entity['remaining_posts'] = $this->remaining_posts;
			}
			
			if (in_array('country', $embed)) {
				$entity['country'] = new CountryResource($this->whenLoaded('country'));
			}
			if (in_array('userType', $embed)) {
				$entity['userType'] = new UserTypeResource($this->whenLoaded('userType'));
			}
			if (in_array('gender', $embed)) {
				$entity['gender'] = new GenderResource($this->whenLoaded('gender'));
			}
			
			// Logged User's Info
			if ($isAuthUserData) {
				if (in_array('payment', $embed)) {
					$entity['payment'] = new PaymentResource($this->whenLoaded('payment'));
				}
				if (in_array('possiblePayment', $embed)) {
					$entity['possiblePayment'] = new PaymentResource($this->whenLoaded('possiblePayment'));
				}
				
				// Mini Stats
				$count = [];
				if (in_array('postsInCountry', $embed)) {
					// $count['posts'] = Post::inCountry()->where('user_id', $this->id)->count();
					$count['posts'] = isset($this->postsInCountry) ? $this->postsInCountry->count() : 0;
				}
				if (in_array('countPostsViews', $embed)) {
					$countPostsViews = Post::query()
						->select('user_id', DB::raw('SUM(visits) as total_views'))
						->inCountry()
						->where('user_id', $this->id)
						->groupBy('user_id')
						->first();
					$count['postsViews'] = (int)($countPostsViews->total_views ?? 0);
				}
				if (in_array('countSavedPosts', $embed)) {
					$count['savedPosts'] = SavedPost::has('postsInCountry')->where('user_id', $this->id)->count();
				}
				if (!empty($count)) {
					$entity['count'] = $count;
				}
			} else {
				if (array_key_exists('email_token', $entity)) {
					unset($entity['email_token']);
				}
				if (array_key_exists('phone_token', $entity)) {
					unset($entity['phone_token']);
				}
			}
		}
		
		return $entity;
	}
}
