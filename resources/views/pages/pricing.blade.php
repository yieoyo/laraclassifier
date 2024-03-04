{{--
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
--}}
@extends('layouts.master')

@php
	$promoPackages ??= [];
	$promoPackagesErrorMessage ??= '';
	
	$subsPackages ??= [];
	$subsPackagesErrorMessage ??= '';
	
	$isAllTypesOfPackageExist = (!empty($promoPackages) && !empty($subsPackages));
	$isAllTypesOfPackageNotExist = (empty($promoPackages) && empty($subsPackages));
	$errorMessage = t('no_packages_found');
	
	// Get the active tab
	$defaultPackageType = config('settings.single.default_package_type');
	$packageType = request()->query('type', $defaultPackageType);
	
	// Get the active tab (by checking if its packages exist)
	$packageType = ($packageType == 'promotion' && !empty($promoPackages)) ? 'promotion' : 'subscription';
	$packageType = ($packageType == 'subscription' && !empty($subsPackages)) ? 'subscription' : 'promotion';
	
	// Set the active tab classes
	$promoLinkClass = ($packageType == 'promotion') ? 'active' : '';
	$subsLinkClass = ($packageType == 'subscription') ? 'active' : '';
	$promoContentClass = !empty($promoLinkClass) ? 'show ' . $promoLinkClass : '';
	$subsContentClass = !empty($subsLinkClass) ? 'show ' . $subsLinkClass : '';
@endphp
@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
	<div class="main-container inner-page">
		<div class="container" id="pricing">
			
			<h1 class="text-center title-1" style="text-transform: none;">
				<strong>{{ t('Pricing') }}</strong>
			</h1>
			<hr class="center-block small mt-0">
			
			@if (!$isAllTypesOfPackageNotExist)
				@if ($isAllTypesOfPackageExist)
					<ul class="nav nav-pills justify-content-center mb-3" id="pills-tab" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link {{ $promoLinkClass }}"
							        id="pills-promotion-tab"
							        data-bs-toggle="pill"
							        data-bs-target="#pills-promotion"
							        type="button"
							        role="tab"
							        aria-controls="pills-promotion"
							        aria-selected="false"
							>{{ t('promo_packages_tab') }}</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link {{ $subsLinkClass }}"
							        id="pills-subscription-tab"
							        data-bs-toggle="pill"
							        data-bs-target="#pills-subscription"
							        type="button"
							        role="tab"
							        aria-controls="pills-subscription"
							        aria-selected="true"
							>{{ t('subs_packages_tab') }}</button>
						</li>
					</ul>
				@endif
				
				<div class="tab-content" id="pills-tabContent">
					@if (!empty($promoPackages))
						<div class="tab-pane fade {{ $promoContentClass }}"
						     id="pills-promotion"
						     role="tabpanel"
						     aria-labelledby="pills-promotion-tab"
						>
							@include('pages.pricing.promo-packages', [
								'packages' => $promoPackages,
								'message'  => $promoPackagesErrorMessage
							])
						</div>
					@endif
					@if (!empty($subsPackages))
						<div class="tab-pane fade {{ $subsContentClass }}"
						     id="pills-subscription"
						     role="tabpanel"
						     aria-labelledby="pills-subscription-tab"
						>
							@include('pages.pricing.subs-packages', [
								'packages' => $subsPackages,
								'message'  => $subsPackagesErrorMessage
							])
						</div>
					@endif
				</div>
			@else
				<div class="row mt-5 mb-md-5 justify-content-center">
					<div class="col-md-6 col-sm-12 text-center">
						<div class="card bg-light">
							<div class="card-body">
								{{ $errorMessage ?? null }}
							</div>
						</div>
					</div>
				</div>
			@endif
			
		</div>
	</div>
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
@endsection
