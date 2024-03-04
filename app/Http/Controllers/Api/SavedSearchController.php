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

use App\Helpers\Search\PostQueries;
use App\Http\Controllers\Api\Post\List\Search\CategoryTrait;
use App\Http\Controllers\Api\Post\List\Search\LocationTrait;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\SavedSearchResource;
use App\Models\SavedSearch;
use Illuminate\Http\Request;

/**
 * @group Saved Searches
 */
class SavedSearchController extends BaseController
{
	use CategoryTrait, LocationTrait;
	
	/**
	 * List saved searches
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string The Comma-separated list of the category relationships for Eager Loading - Possible values: user,country. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$user = auth('sanctum')->user();
		
		$countryCode = request()->query('country_code', config('country.code'));
		
		// Get Saved Searches
		$savedSearches = SavedSearch::inCountry($countryCode)
			->where('user_id', $user->id);
		
		$embed = explode(',', request()->query('embed'));
		
		if (in_array('user', $embed)) {
			$savedSearches->with('user');
		}
		
		if (in_array('country', $embed)) {
			$savedSearches->with('country');
		}
		
		// Sorting
		$orderBy = request()->query('orderBy');
		if (request()->request->has('sort')) {
			request()->request->replace(['sort' => $orderBy]);
		} else {
			request()->request->add(['sort' => $orderBy]);
		}
		$savedSearches = $this->applySorting($savedSearches, ['created_at']);
		
		$savedSearches = $savedSearches->paginate($this->perPage);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$savedSearches = setPaginationBaseUrl($savedSearches);
		
		$message = ($savedSearches->count() <= 0) ? t('no_saved_searches_found') : null;
		
		$resourceCollection = new EntityCollection(class_basename($this), $savedSearches);
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get saved search
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string The Comma-separated list of the category relationships for Eager Loading - Possible values: user,country,pictures,postType,category,city,country. Example: null
	 *
	 * @urlParam id int required The ID of the saved search. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		$user = auth('sanctum')->user();
		
		$countryCode = request()->query('country_code', config('country.code'));
		
		// Get Saved Searches
		$savedSearch = SavedSearch::inCountry($countryCode)
			->where('user_id', $user->id)
			->where('id', $id);
		
		$embed = explode(',', request()->query('embed'));
		
		if (in_array('user', $embed)) {
			$savedSearch->with('user');
		}
		
		if (in_array('country', $embed)) {
			$savedSearch->with('country');
		}
		
		$savedSearch = $savedSearch->first();
		
		abort_if(empty($savedSearch), 404, t('saved_search_not_found'));
		
		$resource = new SavedSearchResource($savedSearch);
		$resource = $resource->toArray(request());
		
		// ...
		
		// Parse saved query string to array
		parse_str($savedSearch->query, $query);
		
		// Add query to request
		if (isset($query['q'])) {
			request()->query->add(['q' => $query['q']]);
		}
		if (isset($query['c'])) {
			request()->query->add(['c' => $query['c']]);
			if (isset($query['sc'])) {
				request()->query->add(['sc' => $query['sc']]);
			}
		}
		if (isset($query['l'])) {
			request()->query->add(['l' => $query['l']]);
		}
		if (isset($query['location'])) {
			request()->query->add(['location' => $query['location']]);
		}
		if (isset($query['r'])) {
			request()->query->add(['r' => $query['r']]);
		}
		
		// Get the listings type parameter
		$allowedFilters = ['search', 'premium'];
		$filterBy = $query['filterBy'] ?? null;
		$filterBy = in_array($filterBy, $allowedFilters) ? $filterBy : 'search';
		
		// Get the saved search order
		$orderBy = $query['orderBy'] ?? null;
		$orderBy = ($orderBy != 'random') ? $orderBy : null;
		
		$input = [
			'op'      => $filterBy,
			'perPage' => $this->perPage,
			'orderBy' => $orderBy,
		];
		
		// PreSearch
		$location = $this->getLocation();
		$preSearch = [
			'cat'   => $this->getCategory(),
			'city'  => $location['city'] ?? null,
			'admin' => $location['admin'] ?? null,
		];
		
		// Search
		$queriesToRemove = array_merge(['embed', 'sort'], array_keys($query));
		$searchData = (new PostQueries($input, $preSearch))->fetch($queriesToRemove);
		
		$preSearch = $searchData['preSearch'] ?? [];
		$preSearch['query'] = $query;
		
		$posts = [
			'success' => true,
			'message' => $searchData['message'] ?? null,
			'result'  => $searchData['posts'] ?? [],
			'extra'   => [
				'count'     => $searchData['count'] ?? [],
				'preSearch' => $preSearch,
				'sidebar'   => [],
				'tags'      => $searchData['tags'] ?? [],
			],
		];
		
		$resource['posts'] = $posts;
		
		// Result
		$data = [
			'success' => true,
			'message' => null,
			'result'  => $resource,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Store/Delete saved search
	 *
	 * Save a search result in favorite, or remove it from favorite.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam url string required Search URL to save. Example: https://demo.laraclassifier.com/search/?q=test&l=
	 * @bodyParam count_posts int required The number of posts found for the URL. Example: 29
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
		
		// Get the 'url' field
		$queryUrl = $request->input('url');
		if (empty($queryUrl)) {
			$data['message'] = 'The "url" field need to be filled.';
			
			return apiResponse()->json($data, 400);
		}
		
		// Extract the keyword by extracting the 'q' parameter of the filled 'url'
		$tmp = parse_url($queryUrl);
		$query = $tmp['query'];
		parse_str($query, $tab);
		$keyword = $tab['q'];
		
		// Get the 'count_posts' field
		$countPosts = $request->input('count_posts');
		if ($keyword == '') {
			$data['message'] = 'The "count_posts" field need to be filled.';
			
			return apiResponse()->json($data, 400);
		}
		
		$data['success'] = true;
		
		$user = auth($guard)->user();
		
		$savedSearch = SavedSearch::where('user_id', $user->id)->where('keyword', $keyword)->where('query', $query);
		if ($savedSearch->count() > 0) {
			// Delete SavedSearch
			$savedSearch->delete();
			
			$data['message'] = t('Search deleted successfully');
		} else {
			// Store SavedSearch
			$savedSearchArray = [
				'country_code' => config('country.code'),
				'user_id'      => $user->id,
				'keyword'      => $keyword,
				'query'        => $query,
				'count'        => $countPosts,
			];
			$savedSearch = new SavedSearch($savedSearchArray);
			$savedSearch->save();
			
			$resource = new SavedSearchResource($savedSearch);
			
			$data['message'] = t('Search saved successfully');
			$data['result'] = $resource;
		}
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Delete saved search(es)
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @urlParam ids string required The ID or comma-separated IDs list of saved search(es).
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): \Illuminate\Http\JsonResponse
	{
		$authUser = auth('sanctum')->user();
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		$data = [
			'success' => false,
			'message' => t('no_deletion_is_done'),
			'result'  => null,
		];
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $id) {
			$savedSearch = SavedSearch::query()
				->where('user_id', $authUser->getAuthIdentifier())
				->where('id', $id)
				->first();
			
			if (!empty($savedSearch)) {
				$res = $savedSearch->delete();
			}
		}
		
		// Confirmation
		if ($res) {
			$data['success'] = true;
			
			$count = count($ids);
			if ($count > 1) {
				$data['message'] = t('x entities have been deleted successfully', ['entities' => t('saved searches'), 'count' => $count]);
			} else {
				$data['message'] = t('1 entity has been deleted successfully', ['entity' => t('saved search')]);
			}
		}
		
		return apiResponse()->json($data);
	}
}
