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

namespace App\Helpers\Search\Traits\Relations;

use App\Helpers\Date;
use App\Models\Package;
use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait PaymentRelation
{
	protected function setPaymentRelation(): void
	{
		if (!(isset($this->posts) && isset($this->postsTable) && isset($this->groupBy))) {
			abort(500, 'Fatal Error: Payment relation cannot be applied.');
		}
		
		// payment
		$this->posts->with('payment', fn ($query) => $query->with('package'));
		
		// payment (Can be used in orderBy)
		$tablesPrefix = DB::getTablePrefix();
		
		$select = [];
		$select[] = $tablesPrefix . 'tPackage.lft';
		if (self::$dbModeStrict) {
			$this->groupBy[] = 'tPackage.lft';
		}
		
		$this->posts->addSelect(DB::raw(implode(', ', $select)));
		
		$paymentsTable = (new Payment())->getTable();
		$packagesTable = (new Package())->getTable();
		
		$paymentBuilder = $this->getPaymentBuilder($tablesPrefix, $paymentsTable);
		
		$op = data_get($this->input, 'op');
		
		if ($op == 'premium') {
			$displayFreeInPremium = (config('settings.list.free_listings_in_premium') == '1');
			if ($displayFreeInPremium) {
				$this->setRelationForPremiumFirst($paymentsTable, $packagesTable, $paymentBuilder);
			} else {
				$this->setRelationForPremium($paymentsTable, $packagesTable, $paymentBuilder);
			}
		} else if ($op == 'latest') {
			$this->setRelationForLatest($paymentsTable, $packagesTable, $paymentBuilder);
		} else if ($op == 'free') {
			$this->setRelationForFree();
		} else if ($op == 'premiumFirst') {
			$this->setRelationForPremiumFirst($paymentsTable, $packagesTable, $paymentBuilder);
		} else {
			// For op == 'search' and others
			$displayPremiumFirst = (
				(config('settings.list.premium_first') == '1' && empty($this->cat) && empty($this->city))
				|| (config('settings.list.premium_first_category') == '1' && !empty($this->cat))
				|| (config('settings.list.premium_first_location') == '1' && !empty($this->city))
			);
			
			if ($displayPremiumFirst) {
				$this->setRelationForPremiumFirst($paymentsTable, $packagesTable, $paymentBuilder);
			} else {
				$this->setRelationForLatest($paymentsTable, $packagesTable, $paymentBuilder);
			}
		}
	}
	
	/*
	 * Get (a possible) valid & active payment builder
	 */
	private function getPaymentBuilder($tablesPrefix, $paymentsTable): \Illuminate\Database\Query\Builder
	{
		// Get the current Datetime
		$today = Carbon::now(Date::getAppTimeZone());
		
		$isValid = function ($query) use ($today) {
			$query->where('period_start', '<=', $today)->where('period_end', '>=', $today);
		};
		$isNotCanceled = function ($query) {
			$query->whereNull('canceled_at');
		};
		$isNotRefunded = function ($query) {
			$query->whereNull('refunded_at');
		};
		
		return DB::table($paymentsTable, 'ap')
			->select(DB::raw('MAX(' . $tablesPrefix . 'ap.id) as apId'), 'ap.payable_id as post_id')
			->where('ap.payable_type', 'LIKE', '%Post')
			->where($isValid)
			->where($isNotCanceled)
			->where($isNotRefunded)
			->where('ap.active', 1)
			->groupBy('ap.payable_id');
	}
	
	/*
	 * The standard way:
	 * Select the premium listings first (sorted by their package order)
	 */
	private function setRelationForPremiumFirst($paymentsTable, $packagesTable, $paymentBuilder): void
	{
		$this->posts->leftJoinSub($paymentBuilder, 'tmpAp', function ($join) {
			$join->on('tmpAp.post_id', '=', $this->postsTable . '.id')->where('featured', 1);
		});
		$this->posts->leftJoin($paymentsTable . ' as payment', 'payment.id', '=', 'tmpAp.apId');
		$this->posts->leftJoin($packagesTable . ' as tPackage', 'tPackage.id', '=', 'payment.package_id');
		
		// Priority to the Premium Listings.
		// Push the Package Position order onto the beginning of an array
		// Check out the orderBy items positions in the OrderBy file
		$this->orderBy = Arr::prepend($this->orderBy, 'tPackage.lft DESC');
	}
	
	/*
	 * Select only the premium listings (sorted by their package order)
	 */
	private function setRelationForPremium($paymentsTable, $packagesTable, $paymentBuilder): void
	{
		$this->posts->joinSub($paymentBuilder, 'tmpAp', function ($join) {
			$join->on('tmpAp.post_id', '=', $this->postsTable . '.id')->where('featured', 1);
		});
		$this->posts->join($paymentsTable . ' as payment', 'payment.id', '=', 'tmpAp.apId');
		$this->posts->join($packagesTable . ' as tPackage', 'tPackage.id', '=', 'payment.package_id');
		
		// Priority to the Premium Listings
		// Push the Package Position order onto the beginning of an array
		$this->orderBy = Arr::prepend($this->orderBy, 'tPackage.lft DESC');
	}
	
	/*
	 * Select the latest listings (including premium & normal listings)
	 * Sorted by the listings' creation date: 'created_at'
	 */
	private function setRelationForLatest($paymentsTable, $packagesTable, $paymentBuilder): void
	{
		$this->posts->leftJoinSub($paymentBuilder, 'tmpAp', function ($join) {
			$join->on('tmpAp.post_id', '=', $this->postsTable . '.id')->where('featured', 1);
		});
		$this->posts->leftJoin($paymentsTable . ' as payment', 'payment.id', '=', 'tmpAp.apId');
		$this->posts->leftJoin($packagesTable . ' as tPackage', 'tPackage.id', '=', 'payment.package_id');
	}
	
	/*
	 * Free (Not premium) listings
	 */
	private function setRelationForFree(): void
	{
		$this->posts->where('featured', '!=', 1);
	}
}
