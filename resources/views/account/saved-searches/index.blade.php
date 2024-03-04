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
	$apiResult ??= [];
	$savedSearches = (array)data_get($apiResult, 'data');
	$totalSavedSearches = (int)data_get($apiResult, 'meta.total');
@endphp

@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
	<div class="main-container">
		<div class="container">
			<div class="row">

				@if (session()->has('flash_notification'))
					<div class="col-xl-12">
						<div class="row">
							<div class="col-xl-12">
								@include('flash::message')
							</div>
						</div>
					</div>
				@endif

				<div class="col-md-3 page-sidebar">
					@includeFirst([config('larapen.core.customizedViewPath') . 'account.inc.sidebar', 'account.inc.sidebar'])
				</div>

				<div class="col-md-9 page-content">
					<div class="inner-box">
						<h2 class="title-2"><i class="fas fa-bell"></i> {{ t('Saved searches') }} </h2>
						<div class="row">
							<div class="col-md-12">
								@if (!empty($savedSearches) && $totalSavedSearches > 0)
									<div class="row row-cols-lg-3 row-cols-md-2 row-cols-sm-2 row-cols-2 g-2 g-lg-2">
										@foreach ($savedSearches as $search)
											@php
												$isSelected = (request()->query('q') == data_get($search, 'keyword'));
											@endphp
											<div class="col border border-1 rounded-2 bg-white {{ $isSelected ? 'active' : '' }}" style="height: 40px;">
												<div class="float-start d-flex align-items-center" style="height: 100%;">
													<a href="{{ url('account/saved-searches/' . data_get($search, 'id')) }}">
														<span> {{ str(data_get($search, 'keyword'))->headline()->limit(20) }} </span>
														<span class="badge badge-pill bg-warning" id="{{ data_get($search, 'id') }}">
															{{ data_get($search, 'count') }}+
														</span>
													</a>
												</div>
												<div class="float-end">
													<a href="{{ url('account/saved-searches/' . data_get($search, 'id') . '/delete') }}" class="confirm-simple-action">&times;</a>
												</div>
											</div>
										@endforeach
									</div>
								@else
									<div class="text-center mt10 mb30">
										{{ $apiMessage ?? t('You have no saved search') }}
									</div>
								@endif
								
								<div style="clear:both;"></div>
								
								<div class="pagination-bar text-center">
									@include('vendor.pagination.api.bootstrap-4')
								</div>
							</div>
						</div>
					</div>
				</div>
				
			</div>
		</div>
	</div>
@endsection

@section('after_scripts')
@endsection
