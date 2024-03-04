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

namespace App\Http\Controllers\Api\User;

use App\Models\Payment;
use App\Models\Post;
use App\Models\SavedPost;
use App\Models\SavedSearch;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait Stats
{
	/**
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getStats($id): \Illuminate\Http\JsonResponse
	{
		$posts = [];
		$threads = [];
		$transactions = [];
		
		// posts (published)
		$posts['published'] = Post::query()
			->inCountry()->has('country')
			->where('user_id', $id)
			->verified()
			->unarchived()
			->reviewed()
			->count();
		
		// posts (pendingApproval)
		$posts['pendingApproval'] = Post::query()
			->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
			->inCountry()->has('country')
			->where('user_id', $id)
			->unverified()
			->count();
		
		// posts (archived)
		$posts['archived'] = Post::query()
			->inCountry()->has('country')
			->where('user_id', $id)
			->archived()
			->count();
		
		// posts (visits)
		$postsVisits = DB::table((new Post())->getTable())
			->select('user_id', DB::raw('SUM(visits) as totalVisits'))
			->where('country_code', config('country.code'))
			->where('user_id', $id)
			->groupBy('user_id')
			->first();
		$posts['visits'] = $postsVisits->totalVisits ?? 0;
		
		// posts (favourite)
		$posts['favourite'] = SavedPost::query()
			->whereHas('post', fn ($query) => $query->inCountry()->has('country'))
			->where('user_id', $id)
			->count();
		
		// savedSearch
		$savedSearch = SavedSearch::query()
			->inCountry()->has('country')
			->where('user_id', $id)
			->count();
		
		// threads (all)
		$threads['all'] = Thread::query()
			->whereHas('post', fn ($query) => $query->inCountry()->has('country')->unarchived())
			->forUser($id)->count();
		
		// threads (withNewMessage)
		$threads['withNewMessage'] = Thread::query()
			->whereHas('post', fn ($query) => $query->inCountry()->has('country')->unarchived())
			->forUserWithNewMessages($id)->count();
		
		// transactions (promotion)
		$promotion = Payment::query()
			->withoutGlobalScopes([ValidPeriodScope::class, StrictActiveScope::class])
			->whereHasMorph('payable', Post::class, function ($query) use ($id) {
				$query->inCountry()
					->has('country')
					->whereHas('user', fn ($query) => $query->where('user_id', $id));
			})->whereHas('package', fn ($query) => $query->has('currency'))
			->count();
		$transactions['promotion'] = ($promotion > 0) ? $promotion : -1; // Set -1 to hide it in the sidebar menu
		
		// transactions (subscription)
		$subscription = Payment::query()
			->withoutGlobalScopes([ValidPeriodScope::class, StrictActiveScope::class])
			->whereHasMorph('payable', User::class, fn ($query) => $query->where('id', $id))
			->whereHas('package', fn ($query) => $query->has('currency'))
			->count();
		$transactions['subscription'] = ($subscription > 0) ? $subscription : -1; // Set -1 to hide it in the sidebar menu
		
		// stats
		$stats = [
			'posts'        => $posts,
			'savedSearch'  => $savedSearch,
			'threads'      => $threads,
			'transactions' => $transactions,
		];
		
		$data = [
			'success' => true,
			'message' => null,
			'result'  => $stats,
		];
		
		return apiResponse()->json($data);
	}
}
