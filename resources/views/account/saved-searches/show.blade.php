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
	$savedSearch ??= [];
	
	$apiMessage = $apiMessagePosts ?? null;
	$apiResult = $apiResultPosts ?? [];
	$posts = (array)data_get($apiResult, 'data');
	$totalPosts = (int)data_get($apiResult, 'meta.total');
	
	$apiExtraPosts ??= [];
	$query = (array)data_get($apiExtraPosts, 'preSearch.query');
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
						<h2 class="title-2"><i class="fas fa-bell"></i> {{ t('Saved search') }} #{{ data_get($savedSearch, 'id') }} </h2>
						
						<div class="mb30" style="float: right; padding-right: 5px;">
							&laquo; <a href="{{ url('account/saved-searches') }}">{{ t('Saved searches') }}</a>
						</div>
						<div style="clear: both;"></div>
						
						<div class="row">
							
							<div class="col-md-12 mb-3">
								@php
									$searchLink = \App\Helpers\UrlGen::search($query, ['page']);
								@endphp
								<strong>{{ t('search') }}:</strong> <a href="{{ $searchLink }}" target="_blank">{{ $searchLink }}</a>
							</div>
							
							<div class="col-md-12">
								<div class="posts-wrapper category-list">
									@if (!empty($posts) && $totalPosts > 0)
										@foreach($posts as $key => $post)
											@continue(empty(data_get($post, 'city')))
											<div class="item-list">
												<div class="row">
													<div class="col-md-2 no-padding photobox">
														<div class="add-image">
															<span class="photo-count">
																<i class="fa fa-camera"></i> {{ data_get($post, 'count_pictures') }}
															</span>
															<a href="{{ \App\Helpers\UrlGen::post($post) }}">
																<img class="img-thumbnail no-margin" src="{{ data_get($post, 'picture.url.medium') }}" alt="img">
															</a>
														</div>
													</div>
													
													<div class="col-md-8 add-desc-box">
														<div class="items-details">
															<h5 class="add-title">
																<a href="{{ \App\Helpers\UrlGen::post($post) }}">{{ data_get($post, 'title') }}</a>
															</h5>
															
															<span class="info-row">
																@if (!empty(data_get($post, 'postType')))
																	<span class="add-type business-posts"
																		  data-bs-toggle="tooltip"
																		  data-bs-placement="right"
																		  title="{{ data_get($post, 'postType.name') }}"
																	>
																		{{ strtoupper(mb_substr(data_get($post, 'postType.name'), 0, 1)) }}
																	</span>
																@endif
																<span class="date">
																	<i class="far fa-clock"></i> {!! data_get($post, 'created_at_formatted') !!}
																</span>
																@if (!empty(data_get($post, 'category')))
																	&nbsp;<span class="category">
																		<i class="bi bi-folder"></i> {{ data_get($post, 'category.name') }}
																	</span>
																@endif
																@if (!empty(data_get($post, 'city')))
																	&nbsp;<span class="item-location">
																		<i class="bi bi-geo-alt"></i> {{ data_get($post, 'city.name') }}
																	</span>
																@endif
															</span>
														</div>
													</div>
													
													<div class="col-md-2 text-end text-center-xs price-box">
														<h4 class="item-price">
															{!! data_get($post, 'price_formatted') !!}
														</h4>
													</div>
												</div>
											</div>
										@endforeach
									@else
										<div class="text-center mt10 mb30">
											{{ $apiPostsMessage ?? t('Please select a saved search to show the result') }}
										</div>
									@endif
								</div>
								
								<div style="clear:both;"></div>
								
								<nav class="pagination-bar mb-4" aria-label="">
									@include('vendor.pagination.api.bootstrap-4')
								</nav>
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
