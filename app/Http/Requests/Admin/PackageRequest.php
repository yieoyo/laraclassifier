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

namespace App\Http\Requests\Admin;

use App\Models\Package;
use App\Models\Scopes\ActiveScope;

class PackageRequest extends Request
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$type = $this->input('type');
		$isPromoPackage = ($type == 'promotion');
		$isSubsPackage = ($type == 'subscription');
		
		$rules = [
			'name'          => ['required', 'min:2', 'max:255'],
			'short_name'    => ['required', 'min:2', 'max:255'],
			'price'         => ['required', 'numeric'],
			'currency_code' => ['required'],
		];
		
		if ($isSubsPackage) {
			$rules['interval'] = ['required'];
			$rules['listings_limit'] = ['required', 'numeric', 'gte:' . config('settings.single.listings_limit', 5)];
		}
		if ($this->filled('pictures_limit')) {
			$rules['pictures_limit'] = ['numeric', 'gte:' . config('settings.single.pictures_limit', 5)];
		}
		if ($this->filled('expiration_time')) {
			$rules['expiration_time'] = ['numeric', 'gte:' . config('settings.cron.activated_listings_expiration', 30)];
		}
		
		$isFromEditForm = in_array($this->method(), ['PUT', 'PATCH', 'UPDATE']);
		$currentPackageId = $this->segment(4);
		
		$countBasicPackage = Package::query()
			->withoutGlobalScopes([ActiveScope::class])
			->when($isPromoPackage, fn ($query) => $query->promotion())
			->when($isSubsPackage, fn ($query) => $query->subscription())
			->when($isFromEditForm, fn ($query) => $query->where('id', '!=', $currentPackageId))
			->applyCurrency()
			->columnIsEmpty('price')
			->count();
		
		$doesBasicPackageExist = ($countBasicPackage >= 1);
		if ($doesBasicPackageExist) {
			$rules['price'] = ['gt:0'];
		}
		
		return $rules;
	}
}
