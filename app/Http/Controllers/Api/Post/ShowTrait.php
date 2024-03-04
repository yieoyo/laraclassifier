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

namespace App\Http\Controllers\Api\Post;

use App\Events\PostWasVisited;
use App\Http\Controllers\Api\Post\Show\DetailedTrait;
use App\Http\Controllers\Api\Post\Show\ShowFieldValueTrait;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\VerifiedScope;
use Illuminate\Support\Facades\Event;

trait ShowTrait
{
	use DetailedTrait;
	use ShowFieldValueTrait;
	
	/**
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function showPost($id): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->query('embed'));
		$countryCode = request()->query('countryCode');
		$isUnactivatedIncluded = (request()->filled('unactivatedIncluded') && request()->integer('unactivatedIncluded') == 1);
		$isBelongLoggedUser = (request()->filled('belongLoggedUser') && request()->integer('belongLoggedUser') == 1);
		
		// Cache control
		$this->updateCachingParameters();
		
		// Cache ID
		$cacheEmbedId = request()->filled('embed') ? '.embed.' . request()->query('embed') : '';
		$cacheFiltersId = '.filters' . '.unactivatedIncluded:' . (int)$isUnactivatedIncluded . '.auth:' . (int)$isBelongLoggedUser;
		$cacheId = 'post' . $cacheEmbedId . $cacheFiltersId . '.id:' . $id . '.' . config('app.locale');
		$cacheId = md5($cacheId);
		
		// Cached Query
		$post = cache()->remember($cacheId, $this->cacheExpiration, function () use (
			$countryCode,
			$isUnactivatedIncluded,
			$id,
			$embed,
			$isBelongLoggedUser
		) {
			$post = Post::query();
			
			if ($isUnactivatedIncluded) {
				$post->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class]);
			}
			
			if (in_array('country', $embed)) {
				$post->with('country');
			}
			if (in_array('user', $embed)) {
				if (in_array('possiblePayment', $embed)) {
					// For the pictures limit
					if (in_array('package', $embed)) {
						$post->with(['user' => function ($query) {
							$query->with(['possiblePayment', 'possiblePayment.package']);
						}]);
					} else {
						$post->with(['user' => function ($query) {
							$query->with(['possiblePayment']);
						}]);
					}
				} else {
					$post->with('user');
				}
			}
			if (in_array('category', $embed) || in_array('fieldsValues', $embed)) {
				$post->with('category');
			}
			if (in_array('postType', $embed)) {
				$post->with('postType');
			}
			if (in_array('city', $embed)) {
				$post->with('city');
				if (in_array('subAdmin1', $embed)) {
					$post->with('city.subAdmin1');
				}
				if (in_array('subAdmin2', $embed)) {
					$post->with('city.subAdmin2');
				}
			}
			if (in_array('payment', $embed)) {
				$post->with(['payment' => function ($query) {
					$query->withoutGlobalScope(StrictActiveScope::class);
				}]);
				if (in_array('package', $embed)) {
					$post->with('payment.package');
				}
			}
			if (in_array('possiblePayment', $embed)) {
				$post->with(['possiblePayment']);
				if (in_array('package', $embed)) {
					$post->with('possiblePayment.package');
				}
			}
			if (in_array('savedByLoggedUser', $embed)) {
				$post->with('savedByLoggedUser');
			}
			if (in_array('pictures', $embed)) {
				if (!in_array('user', $embed)) {
					$post->with('user');
				}
				$post->with('pictures');
			}
			
			if (!empty($countryCode)) {
				$post->inCountry($countryCode)->has('country');
			}
			if ($isBelongLoggedUser) {
				$guard = 'sanctum';
				$userId = (auth($guard)->check()) ? auth($guard)->user()->getAuthIdentifier() : '-1';
				$post->where('user_id', $userId);
			}
			
			return $post->where('id', $id)->first();
		});
		
		// Reset caching parameters
		$this->resetCachingParameters();
		
		abort_if(empty($post), 404, t('post_not_found'));
		
		// Increment the Listing's visits counter
		Event::dispatch(new PostWasVisited($post));
		
		$fieldsValues = [];
		if (in_array('fieldsValues', $embed)) {
			if (isset($post->category)) {
				$fieldsValues = $this->getFieldsValues($post->category->id, $post->id);
			}
		}
		
		if (in_array('pictures', $embed)) {
			// Get packages features
			$picturesLimit = (int)config('settings.single.pictures_limit');
			$picturesLimit = getUserSubscriptionFeatures($post->user, 'picturesLimit') ?? $picturesLimit;
			$picturesLimit = getPostPromotionFeatures($post, 'picturesLimit') ?? $picturesLimit;
			if ($post->pictures->count() > $picturesLimit) {
				$post->setRelation('pictures', $post->pictures->take($picturesLimit));
			}
		}
		
		$data = [
			'success' => true,
			'result'  => new PostResource($post),
			'extra'   => [
				'fieldsValues' => $fieldsValues,
			],
		];
		
		return apiResponse()->json($data);
	}
}
