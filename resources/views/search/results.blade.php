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
	$apiExtra ??= [];
	$count = (array)data_get($apiExtra, 'count');
	$posts = (array)data_get($apiResult, 'data');
	$totalPosts = (int)data_get($apiResult, 'meta.total', 0);
	$tags = (array)data_get($apiExtra, 'tags');
	
	$postTypes ??= [];
	$orderByOptions ??= [];
	$displayModes ??= [];
@endphp

@section('search')
	@parent
	@includeFirst([config('larapen.core.customizedViewPath') . 'search.inc.form', 'search.inc.form'])
@endsection

@section('content')
	<div class="main-container">
		
		@if (session()->has('flash_notification'))
			@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
			<?php $paddingTopExists = true; ?>
			<div class="container">
				<div class="row">
					<div class="col-12">
						@include('flash::message')
					</div>
				</div>
			</div>
		@endif
		
		@includeFirst([config('larapen.core.customizedViewPath') . 'search.inc.breadcrumbs', 'search.inc.breadcrumbs'])
		
		@if (config('settings.list.show_cats_in_top'))
			@if (isset($cats) && !empty($cats))
				<div class="container mb-2 hide-xs">
					<div class="row p-0 m-0">
						<div class="col-12 p-0 m-0 border border-bottom-0 bg-light"></div>
					</div>
				</div>
			@endif
			@includeFirst([config('larapen.core.customizedViewPath') . 'search.inc.categories', 'search.inc.categories'])
		@endif
		
		@if (isset($topAdvertising) && !empty($topAdvertising))
			@includeFirst([
				config('larapen.core.customizedViewPath') . 'layouts.inc.advertising.top',
				'layouts.inc.advertising.top'
			], ['paddingTopExists' => true])
			@php
				$paddingTopExists = false;
			@endphp
		@else
			@php
				if (isset($paddingTopExists) && $paddingTopExists) {
					$paddingTopExists = false;
				}
			@endphp
		@endif
		
		<div class="container">
			<div class="row">
				
				{{-- Sidebar --}}
                @if (config('settings.list.left_sidebar'))
                    @includeFirst([config('larapen.core.customizedViewPath') . 'search.inc.sidebar', 'search.inc.sidebar'])
					@php
						$contentColSm = 'col-md-9';
					@endphp
                @else
					@php
						$contentColSm = 'col-md-12';
					@endphp
                @endif

				{{-- Content --}}
				<div class="{{ $contentColSm }} page-content col-thin-left mb-4">
					<div class="category-list {{ config('settings.list.display_mode', 'make-grid') }}{{ ($contentColSm == 'col-md-12') ? ' noSideBar' : '' }}">
						<div class="tab-box">

							{{-- Nav tabs --}}
							<ul id="postType" class="nav nav-tabs add-tabs tablist" role="tablist">
								@php
									$aClass = '';
									$spanClass = 'alert-danger';
									if (config('settings.single.show_listing_type')) {
										if (!request()->filled('type') || request()->query('type') == '') {
											$aClass = ' active';
											$spanClass = 'bg-danger';
										}
									} else {
										$aClass = ' active';
										$spanClass = 'bg-danger';
									}
								@endphp
								<li class="nav-item">
									<a href="{!! request()->fullUrlWithoutQuery(['page', 'type']) !!}" class="nav-link{{ $aClass }}">
										{{ t('All Listings') }} <span class="badge badge-pill {!! $spanClass !!}">{{ data_get($count, '0') }}</span>
									</a>
								</li>
								@if (config('settings.single.show_listing_type'))
									@if (!empty($postTypes))
										@foreach ($postTypes as $postType)
											@php
												$postTypeUrl = request()->fullUrlWithQuery(['type' => data_get($postType, 'id'), 'page' => null]);
												$postTypeCount = data_get($count, data_get($postType, 'id')) ?? 0;
											@endphp
											@if (request()->filled('type') && request()->query('type') == data_get($postType, 'id'))
												<li class="nav-item">
													<a href="{!! $postTypeUrl !!}" class="nav-link active">
														{{ data_get($postType, 'name') }}
														<span class="badge badge-pill bg-danger">
															{{ $postTypeCount }}
														</span>
													</a>
												</li>
											@else
												<li class="nav-item">
													<a href="{!! $postTypeUrl !!}" class="nav-link">
														{{ data_get($postType, 'name') }}
														<span class="badge badge-pill alert-danger">
															{{ $postTypeCount }}
														</span>
													</a>
												</li>
											@endif
										@endforeach
									@endif
								@endif
							</ul>
							
							<div class="tab-filter pb-2">
								{{-- OrderBy Desktop --}}
								<select id="orderBy" title="sort by" class="niceselecter select-sort-by small" data-style="btn-select" data-width="auto">
									@if (!empty($orderByOptions))
										@foreach($orderByOptions as $option)
											@if (data_get($option, 'condition'))
												@php
													$optionUrl = request()->fullUrlWithQuery((array)data_get($option, 'query'));
												@endphp
												<option @selected(data_get($option, 'isSelected')) value="{!! $optionUrl !!}">
													{{ data_get($option, 'label') }}
												</option>
											@endif
										@endforeach
									@endif
								</select>
							</div>

						</div>
						
						<div class="listing-filter">
							<div class="float-start col-md-9 col-sm-8 col-12">
								<h1 class="h6 pb-0 breadcrumb-list">
									{!! (isset($htmlTitle)) ? $htmlTitle : '' !!}
								</h1>
                                <div style="clear:both;"></div>
							</div>
							
							{{-- Display Modes --}}
							@if (!empty($posts) && $totalPosts > 0)
								@php
									$currDisplay = config('settings.list.display_mode');
								@endphp
								<div class="float-end col-md-3 col-sm-4 col-12 text-end listing-view-action">
									@if (!empty($displayModes))
										@foreach($displayModes as $displayMode => $value)
											<span class="grid-view{{ ($currDisplay == $displayMode) ? ' active' : '' }}">
												@if ($currDisplay == $displayMode)
													<i class="{{ data_get($value, 'icon') }}"></i>
												@else
													@php
														$displayModeUrl = request()->fullUrlWithQuery((array)data_get($value, 'query'));
													@endphp
													<a href="{!! $displayModeUrl !!}" rel="nofollow">
														<i class="{{ data_get($value, 'icon') }}"></i>
													</a>
												@endif
											</span>
										@endforeach
									@endif
								</div>
							@endif
							
							<div style="clear:both"></div>
						</div>
						
						{{-- Mobile Filter Bar --}}
						<div class="mobile-filter-bar col-xl-12">
							<ul class="list-unstyled list-inline no-margin no-padding">
								@if (config('settings.list.left_sidebar'))
									<li class="filter-toggle">
										<a class=""><i class="fas fa-bars"></i> {{ t('Filters') }}</a>
									</li>
								@endif
								<li>
									{{-- OrderBy Mobile --}}
									<div class="dropdown">
										<a class="dropdown-toggle" data-bs-toggle="dropdown">{{ t('Sort by') }}</a>
										<ul class="dropdown-menu">
											@if (!empty($orderByOptions))
												@foreach($orderByOptions as $option)
													@if (data_get($option, 'condition'))
														@php
															$optionUrl = request()->fullUrlWithQuery((array)data_get($option, 'query'));
														@endphp
														<li>
															<a href="{!! $optionUrl !!}" rel="nofollow">
																{{ data_get($option, 'label') }}
															</a>
														</li>
													@endif
												@endforeach
											@endif
										</ul>
									</div>
								</li>
							</ul>
						</div>
						<div class="menu-overly-mask"></div>
						{{-- Mobile Filter bar End--}}
						
						<div class="tab-content" id="myTabContent">
							<div class="tab-pane fade show active" id="contentAll" role="tabpanel" aria-labelledby="tabAll">
								<div id="postsList" class="category-list-wrapper posts-wrapper row no-margin">
									@if (config('settings.list.display_mode') == 'make-list')
										@includeFirst([
											config('larapen.core.customizedViewPath') . 'search.inc.posts.template.list',
											'search.inc.posts.template.list'
										])
									@elseif (config('settings.list.display_mode') == 'make-compact')
										@includeFirst([
											config('larapen.core.customizedViewPath') . 'search.inc.posts.template.compact',
											'search.inc.posts.template.compact'
										])
									@else
										@includeFirst([
											config('larapen.core.customizedViewPath') . 'search.inc.posts.template.grid',
											'search.inc.posts.template.grid'
										])
									@endif
								</div>
							</div>
						</div>
						
						@if (request()->filled('q') && request()->query('q') != '' && data_get($count, '0') > 0)
							<div class="tab-box save-search-bar text-center">
								<a id="saveSearch"
								   data-name="{!! request()->fullUrlWithoutQuery(['_token', 'location']) !!}"
								   data-count="{{ data_get($count, '0') }}"
								>
									<i class="far fa-bell"></i> {{ t('Save Search') }}
								</a>
							</div>
						@endif
					</div>
					
					<nav class="mt-3 mb-0 pagination-sm" aria-label="">
						@include('vendor.pagination.api.bootstrap-4')
					</nav>
					
				</div>
			</div>
		</div>
		
		{{-- Advertising --}}
		@includeFirst([
			config('larapen.core.customizedViewPath') . 'layouts.inc.advertising.bottom',
			'layouts.inc.advertising.bottom'
		])
		
		{{-- Promo Listing Button --}}
		<div class="container mb-3">
			<div class="card border-light text-dark bg-light mb-3">
				<div class="card-body text-center">
					<h2>{{ t('do_you_have_anything') }}</h2>
					<h5>{{ t('sell_products_and_services_online_for_free') }}</h5>
					@if (!auth()->check() && config('settings.single.guest_can_submit_listings') != '1')
						<a href="#quickLogin" class="btn btn-border btn-post btn-listing" data-bs-toggle="modal">
							{{ t('start_now') }}
						</a>
					@else
						<a href="{{ \App\Helpers\UrlGen::addPost() }}" class="btn btn-border btn-post btn-listing">
							{{ t('start_now') }}
						</a>
					@endif
				</div>
			</div>
		</div>
		
		{{-- Category Description --}}
		@if (isset($cat) && !empty(data_get($cat, 'description')))
			@if (!(bool)data_get($cat, 'hide_description'))
				<div class="container mb-3">
					<div class="card border-light text-dark bg-light mb-3">
						<div class="card-body">
							{!! data_get($cat, 'description') !!}
						</div>
					</div>
				</div>
			@endif
		@endif
		
		{{-- Show Posts Tags --}}
		@if (config('settings.list.show_listings_tags'))
			@if (!empty($tags))
				<div class="container">
					<div class="card mb-3">
						<div class="card-body">
							<h2 class="card-title"><i class="fas fa-tags"></i> {{ t('Tags') }}:</h2>
							@foreach($tags as $iTag)
								<span class="d-inline-block border border-inverse bg-light rounded-1 py-1 px-2 my-1 me-1">
									<a href="{{ \App\Helpers\UrlGen::tag($iTag) }}">
										{{ $iTag }}
									</a>
								</span>
							@endforeach
						</div>
					</div>
				</div>
			@endif
		@endif
		
	</div>
@endsection

@section('modal_location')
	@includeFirst([
		config('larapen.core.customizedViewPath') . 'layouts.inc.modal.location',
		'layouts.inc.modal.location'
	])
@endsection

@section('after_scripts')
	<script>
		$(document).ready(function () {
			$('#postType a').click(function (e) {
				e.preventDefault();
				let goToUrl = $(this).attr('href');
				redirect(goToUrl);
			});
			$('#orderBy').change(function () {
				let goToUrl = $(this).val();
				redirect(goToUrl);
			});
		});
	</script>
@endsection
