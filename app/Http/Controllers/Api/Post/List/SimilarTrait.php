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

namespace App\Http\Controllers\Api\Post\List;

use App\Http\Controllers\Api\Post\List\Search\CategoryTrait;
use App\Http\Controllers\Api\Post\List\Search\LocationTrait;
use App\Http\Controllers\Api\Post\List\Search\SidebarTrait;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;

trait SimilarTrait
{
	use CategoryTrait, LocationTrait, SidebarTrait;
	
	/**
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSimilarPosts(): \Illuminate\Http\JsonResponse
	{
		$postId = request()->query('postId');
		
		// Create the MySQL Distance Calculation function If it doesn't exist
		$distanceCalculationFormula = config('settings.list.distance_calculation_formula', 'haversine');
		if (!DistanceHelper::checkIfDistanceCalculationFunctionExists($distanceCalculationFormula)) {
			DistanceHelper::createDistanceCalculationFunction($distanceCalculationFormula);
		}
		
		// similar
		$posts = collect();
		
		if (!empty($postId)) {
			// $embed = ['user', 'category', 'parent', 'postType', 'city', 'savedByLoggedUser', 'pictures', 'payment', 'package'];
			$embed = ['user', 'savedByLoggedUser', 'pictures', 'payment', 'package'];
			if (!config('settings.list.hide_post_type')) {
				$embed[] = 'postType';
			}
			if (!config('settings.list.hide_category')) {
				$embed[] = 'category';
				$embed[] = 'parent';
			}
			if (!config('settings.list.hide_location')) {
				$embed[] = 'city';
			}
			request()->query->add(['embed' => implode(',', $embed)]);
			
			$distance = request()->query('distance');
			$res = $this->getSimilarPostsData($postId, $distance);
			$posts = $res['posts'] ?? collect();
			$post = $res['post'] ?? null;
			
			$postResource = new PostResource($post);
			$postApiResult = apiResponse()->withResource($postResource)->getData(true);
			$post = data_get($postApiResult, 'result');
		}
		
		$postsCollection = new EntityCollection(class_basename($this), $posts);
		$postsResult = $postsCollection->toResponse(request())->getData(true);
		
		$totalPosts = $posts->count();
		$message = ($totalPosts <= 0) ? t('no_posts_found') : null;
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => $postsResult, // $postsCollection,
			'extra'   => [
				'count' => [$totalPosts],
			],
		];
		if (!empty($post)) {
			$data['extra']['preSearch'] = ['post' => $post];
		}
		
		return apiResponse()->json($data);
	}
	
	/**
	 * @param int|null $postId
	 * @param int|null $distance
	 * @return array
	 */
	protected function getSimilarPostsData(?int $postId, ?int $distance = 50): array
	{
		$cacheLocaleId = '.' . config('app.locale');
		
		// Get the current listing
		$cacheId = 'post.withoutGlobalScopes.' . $postId . $cacheLocaleId;
		$post = cache()->remember($cacheId, $this->cacheExpiration, function () use ($postId) {
			return Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->with(['category', 'city'])
				->where('id', $postId)
				->first();
		});
		
		$posts = [];
		
		if (empty($post)) {
			return $posts;
		}
		
		// Get the similar listings
		$postsLimit = (int)config('settings.single.similar_listings_limit', 10);
		$cachePostId = '.post.' . $post->id;
		$cacheLimitId = '.limit.' . $postsLimit;
		
		if (config('settings.single.similar_listings') == '1') {
			$cacheId = 'posts.similar.category.' . $post->category_id . $cachePostId . $cacheLocaleId . $cacheLimitId;
			$posts = cache()->remember($cacheId, $this->cacheExpiration, function () use ($post, $postsLimit) {
				return $post->getSimilarByCategory($postsLimit);
			});
		}
		
		if (config('settings.single.similar_listings') == '2') {
			$distance = $distance ?? 50; // km OR miles
			$cacheId = 'posts.similar.city.' . $post->city_id . $cachePostId . $cacheLocaleId . $cacheLimitId;
			$posts = cache()->remember($cacheId, $this->cacheExpiration, function () use ($post, $distance, $postsLimit) {
				return $post->getSimilarByLocation($distance, $postsLimit);
			});
		}
		
		return ['post' => $post, 'posts' => $posts];
	}
}
