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
	$posts = (array)data_get($apiResult, 'data');
	$totalPosts = (int)data_get($apiResult, 'meta.total', 0);
	$pagePath ??= null;
	
	$pageTitles = [
		'list' => [
			'icon'  => 'fas fa-bullhorn',
			'title' => t('my_listings'),
		],
		'archived' => [
			'icon'  => 'fas fa-calendar-times',
			'title' => t('archived_listings'),
		],
		'favourite' => [
			'icon'  => 'fas fa-bookmark',
			'title' => t('favourite_listings'),
		],
		'pending-approval' => [
			'icon'  => 'fas fa-hourglass-half',
			'title' => t('pending_approval'),
		],
	];
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
						<h2 class="title-2">
							<i class="{{ $pageTitles[$pagePath]['icon'] ?? 'fas fa-bullhorn' }}"></i>
							{{ $pageTitles[$pagePath]['title'] ?? t('posts') }}
						</h2>
						
						<div class="table-responsive">
							<form name="listForm" method="POST" action="{{ url('account/posts/' . $pagePath . '/delete') }}">
								{!! csrf_field() !!}
								<div class="table-action">
									<div class="btn-group hidden-sm" role="group">
										<button type="button" class="btn btn-sm btn-default pb-0">
											<input type="checkbox" id="checkAll" class="from-check-all">
										</button>
										<button type="button" class="btn btn-sm btn-default from-check-all">
											{{ t('Select') }}: {{ t('All') }}
										</button>
									</div>
									
									<button type="submit" class="btn btn-sm btn-default confirm-simple-action">
										<i class="fa fa-trash"></i> {{ t('Delete') }}
									</button>
									
									<div class="table-search float-end col-sm-7">
										<div class="row">
											<label class="col-5 form-label text-end">{{ t('search') }} <br>
												<a title="clear filter" class="clear-filter" href="#clear">[{{ t('clear') }}]</a>
											</label>
											<div class="col-7 searchpan px-3">
												<input type="text" class="form-control" id="filter">
											</div>
										</div>
									</div>
								</div>
								
								<table id="addManageTable"
									   class="table table-striped table-bordered add-manage-table table demo"
									   data-filter="#filter"
									   data-filter-text-only="true"
								>
									<thead>
									<tr>
										<th data-type="numeric" data-sort-initial="true"></th>
										<th>{{ t('Photo') }}</th>
										<th data-sort-ignore="true">{{ t('listing_details') }}</th>
										<th data-type="numeric">--</th>
										<th>{{ t('Option') }}</th>
									</tr>
									</thead>
									<tbody>
									
									@if (!empty($posts) && $totalPosts > 0)
										@foreach($posts as $key => $post)
											<tr>
												<td style="width:2%" class="add-img-selector">
													<div class="checkbox">
														<label><input type="checkbox" name="entries[]" value="{{ data_get($post, 'id') }}"></label>
													</div>
												</td>
												<td style="width:20%" class="add-img-td">
													<a href="{{ \App\Helpers\UrlGen::post($post) }}">
														<img class="img-thumbnail img-fluid" src="{{ data_get($post, 'picture.url.medium') }}" alt="img">
													</a>
												</td>
												<td style="width:52%" class="items-details-td">
													<div>
														<p>
															<strong>
																<a href="{{ \App\Helpers\UrlGen::post($post) }}" title="{{ data_get($post, 'title') }}">
																	{{ str(data_get($post, 'title'))->limit(40) }}
																</a>
															</strong>
															@if (in_array($pagePath, ['list', 'archived', 'pending-approval']))
																@if (!empty(data_get($post, 'payment')) && !empty(data_get($post, 'payment.package')))
																	@php
																		if (data_get($post, 'featured') == 1) {
																			$color = data_get($post, 'payment.package.ribbon');
																			$packageInfo = '';
																		} else {
																			$color = '#ddd';
																			$packageInfo = ' (' . t('expired') . ')';
																		}
																	@endphp
																	<i class="fa fa-check-circle"
																		style="color: {{ $color }};"
																		data-bs-placement="bottom"
																		data-bs-toggle="tooltip"
																		title="{{ data_get($post, 'payment.package.short_name') . $packageInfo }}"
																	></i>
																@endif
															@endif
														</p>
														<p>
															<strong>
																<i class="far fa-clock" title="{{ t('Posted On') }}"></i>
															</strong>&nbsp;{!! data_get($post, 'created_at_formatted') !!}
														</p>
														<p>
															<strong><i class="far fa-eye" title="{{ t('Visitors') }}"></i></strong> {{ data_get($post, 'visits') ?? 0 }}
															<strong><i class="bi bi-geo-alt" title="{{ t('Located In') }}"></i></strong> {{ data_get($post, 'city.name') ?? '-' }}
															<img src="{{ data_get($post, 'country_flag_url') }}" data-bs-toggle="tooltip" title="{{ data_get($post, 'country.name') }}">
														</p>
													</div>
												</td>
												<td style="width:16%" class="price-td">
													<div>
														<strong>
															{!! data_get($post, 'price_formatted') !!}
														</strong>
													</div>
												</td>
												<td style="width:10%" class="action-td">
													<div>
														@if (
																in_array($pagePath, ['list', 'pending-approval'])
																&& data_get($post, 'user_id') == $authUser->id
																&& empty(data_get($post, 'archived_at'))
															)
															<p>
																<a class="btn btn-primary btn-sm" href="{{ \App\Helpers\UrlGen::editPost($post) }}">
																	<i class="fa fa-edit"></i> {{ t('Edit') }}
																</a>
															</p>
														@endif
														@if ($pagePath == 'list' && isVerifiedPost($post) && empty(data_get($post, 'archived_at')))
															<p>
																<a class="btn btn-warning btn-sm confirm-simple-action"
																   href="{{ url('account/posts/'.$pagePath.'/'.data_get($post, 'id').'/offline') }}"
																>
																	<i class="fas fa-eye-slash"></i> {{ t('Offline') }}
																</a>
															</p>
														@endif
														@if (
															$pagePath == 'archived'
															&& data_get($post, 'user_id') == $authUser->id
															&& !empty(data_get($post, 'archived_at'))
														)
															<p>
																<a class="btn btn-info btn-sm confirm-simple-action"
																	href="{{ url('account/posts/' . $pagePath . '/' . data_get($post, 'id') . '/repost') }}"
																>
																	<i class="fa fa-recycle"></i> {{ t('Repost') }}
																</a>
															</p>
														@endif
														<p>
															<a class="btn btn-danger btn-sm confirm-simple-action"
																href="{{ url('account/posts/' . $pagePath . '/' . data_get($post, 'id') . '/delete') }}"
															>
																<i class="fa fa-trash"></i> {{ t('Delete') }}
															</a>
														</p>
													</div>
												</td>
											</tr>
										@endforeach
									@endif
									</tbody>
								</table>
							</form>
						</div>
						
						<nav>
							@include('vendor.pagination.api.bootstrap-4')
						</nav>
						
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('after_styles')
	<style>
		.action-td p {
			margin-bottom: 5px;
		}
	</style>
@endsection

@section('after_scripts')
	<script src="{{ url('assets/js/footable.js?v=2-0-1') }}" type="text/javascript"></script>
	<script src="{{ url('assets/js/footable.filter.js?v=2-0-1') }}" type="text/javascript"></script>
	<script type="text/javascript">
		$(function () {
			$('#addManageTable').footable().bind('footable_filtering', function (e) {
				let selected = $('.filter-status').find(':selected').text();
				if (selected && selected.length > 0) {
					e.filter += (e.filter && e.filter.length > 0) ? ' ' + selected : selected;
					e.clear = !e.filter;
				}
			});

			$('.clear-filter').click(function (e) {
				e.preventDefault();
				$('.filter-status').val('');
				$('table.demo').trigger('footable_clear_filter');
			});

			$('.from-check-all').click(function () {
				checkAll(this);
			});
		});
	</script>
	{{-- include custom script for listings table [select all checkbox]  --}}
	<script>
		function checkAll(bx) {
			if (bx.type !== 'checkbox') {
				bx = document.getElementById('checkAll');
				bx.checked = !bx.checked;
			}
			
			var chkinput = document.getElementsByTagName('input');
			for (var i = 0; i < chkinput.length; i++) {
				if (chkinput[i].type === 'checkbox') {
					chkinput[i].checked = bx.checked;
				}
			}
		}
	</script>
@endsection
