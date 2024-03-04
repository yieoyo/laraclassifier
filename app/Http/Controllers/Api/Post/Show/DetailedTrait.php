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

namespace App\Http\Controllers\Api\Post\Show;

use App\Events\PostWasVisited;
use App\Http\Resources\PostResource;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use Illuminate\Support\Facades\Event;

trait DetailedTrait
{
	/**
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function showDetailedPost($id): \Illuminate\Http\JsonResponse
	{
		// Lazy Loading Array
		$lazyLoadingArray = [
			'category',
			'category.parent',
			'postType',
			'city',
			'pictures',
			'user',
			'payment',
			'payment.package',
			'savedByLoggedUser',
		];
		
		$authUser = auth('sanctum')->user();
		if (!empty($authUser)) {
			// Get post's details even if it's not activated, not reviewed or archived
			$cacheId = 'post.withoutGlobalScopes.with.lazyLoading.' . $id . '.' . config('app.locale');
			$post = cache()->remember($cacheId, $this->cacheExpiration, function () use ($id, $lazyLoadingArray) {
				return Post::query()
					->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
					->withCountryFix()
					->where('id', $id)
					->with($lazyLoadingArray)
					->first();
			});
			
			// If the logged user is not an admin user...
			if (!$authUser->can(Permission::getStaffPermissions())) {
				// Then don't get post that is not from the user
				if (!empty($post) && $post->user_id != $authUser->getAuthIdentifier()) {
					$cacheId = 'post.with.lazyLoading.' . $id . '.' . config('app.locale');
					$post = cache()->remember($cacheId, $this->cacheExpiration, function () use ($id, $lazyLoadingArray) {
						return Post::withCountryFix()
							->unarchived()
							->where('id', $id)
							->with($lazyLoadingArray)
							->first();
					});
				}
			}
		} else {
			$cacheId = 'post.with.lazyLoading.' . $id . '.' . config('app.locale');
			$post = cache()->remember($cacheId, $this->cacheExpiration, function () use ($id, $lazyLoadingArray) {
				return Post::withCountryFix()
					->unarchived()
					->where('id', $id)
					->with($lazyLoadingArray)
					->first();
			});
		}
		// Preview Listing after activation
		if (request()->filled('preview') && request()->query('preview') == 1) {
			// Get the post's details even if it's not activated and reviewed
			$post = Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->withCountryFix()
				->where('id', $id)
				->with($lazyLoadingArray)
				->first();
		}
		
		// Listing isn't found
		if (empty($post) || empty($post->category) || empty($post->city)) {
			abort(404, t('post_not_found'));
		}
		
		// Increment the Listing's visits counter
		Event::dispatch(new PostWasVisited($post));
		
		// Get packages features
		$picturesLimit = (int)config('settings.single.pictures_limit');
		$picturesLimit = getUserSubscriptionFeatures($post->user, 'picturesLimit') ?? $picturesLimit;
		$picturesLimit = getPostPromotionFeatures($post, 'picturesLimit') ?? $picturesLimit;
		if ($post->pictures->count() > $picturesLimit) {
			$post->setRelation('pictures', $post->pictures->take($picturesLimit));
		}
		
		$data = [
			'success' => true,
			'result'  => new PostResource($post),
			'extra'   => [
				'fieldsValues' => $this->getFieldsValues($post->category->id, $post->id),
			],
		];
		
		return apiResponse()->json($data);
	}
}
