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
	$type ??= 'promotion';
	$isPromoting ??= true;
	$isSubscripting ??= false;
	
	$apiResult ??= [];
	$transactions = (array)data_get($apiResult, 'data');
	$totalTransactions = (int)data_get($apiResult, 'meta.total', 0);
	
	$stats ??= [];
	$countPromoTransactions = data_get($stats, 'transactions.promotion');
	$countSubsTransactions = data_get($stats, 'transactions.subscription');
	$isAllTypesOfTransactionExist = ($countPromoTransactions > 0 && $countSubsTransactions > 0);
@endphp
@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
	<div class="main-container">
		<div class="container">
			<div class="row">
				
				<div class="col-md-3 page-sidebar">
					@includeFirst([config('larapen.core.customizedViewPath') . 'account.inc.sidebar', 'account.inc.sidebar'])
				</div>
				
				<div class="col-md-9 page-content">
					<div class="inner-box">
						<h2 class="title-2"><i class="fas fa-coins"></i> {{ t('Transactions') }} </h2>
						
						@if ($isAllTypesOfTransactionExist)
							<div class="col-12 mb-4">
								<div class="row d-flex justify-content-center">
									<div class="col-sm-6 col-6 text-right pe-1">
										<a class="btn {{ $isPromoting ? 'btn-primary' : 'btn-default' }}"
										   href="{{ url('account/transactions/promotion') }}"
										>{{ t('promo_packages_tab') }}</a>
									</div>
									<div class="col-sm-6 col-6 text-left ps-1">
										<a class="btn {{ $isSubscripting ? 'btn-primary' : 'btn-default' }}"
										        href="{{ url('account/transactions/subscription') }}"
										>{{ t('subs_packages_tab') }}</a>
									</div>
								</div>
							</div>
						@endif
						
						<div class="table-responsive">
							<table class="table table-bordered">
								<thead>
								<tr>
									<th class="align-middle">
										<span data-bs-toggle="tooltip" title="{{ t('reference') }}">{{ t('ref') }}</span>
									</th>
									<th class="align-middle">{{ t('Description') }}</th>
									<th class="align-middle">{{ t('Payment Method') }}</th>
									<th class="align-middle">{{ t('amount') }}</th>
									<th class="align-middle">{{ t('Date') }}</th>
									<th class="align-middle">{{ t('Status') }}</th>
								</tr>
								</thead>
								<tbody>
								@if (!empty($transactions) && $totalTransactions > 0)
									@foreach($transactions as $key => $transaction)
										<tr>
											<td class="align-middle">{{ data_get($transaction, 'id') }}</td>
											<td class="align-middle">
												@if ($isPromoting)
													@php
														$postUrl = \App\Helpers\UrlGen::post(data_get($transaction, 'payable'));
													@endphp
													<strong>{{ t('Listing') }}</strong>
													<a href="{{ $postUrl }}">{{ data_get($transaction, 'payable.title') }}</a>
												@else
													<strong>{{ t('account') }}</strong> {{ data_get($transaction, 'payable.name') }}
												@endif
												<br><strong>{{ t('Package') }}</strong> {{ data_get($transaction, 'package.short_name') }}
												@if ($isSubscripting)
													@if (data_get($transaction, 'expired') != 1)
														<br><strong>{{ t('remaining_listings') }}</strong>
															{{ data_get($transaction, 'remaining_posts') }}
													@endif
												@endif
												<br>{{ data_get($transaction, 'starting_info') }}
												<br>{{ data_get($transaction, 'expiry_info') }}
											</td>
											<td class="align-middle">
												{{ data_get($transaction, 'paymentMethod.display_name', '--') }}
											</td>
											<td class="align-middle">
												{!! data_get($transaction, 'package.currency.symbol') . data_get($transaction, 'package.price') !!}
											</td>
											<td class="align-middle">
												{!! data_get($transaction, 'created_at_formatted') !!}
											</td>
											<td class="align-middle">
												@php
													$expiryInfo = data_get($transaction, 'expiry_info');
													$tooltip = ' data-bs-toggle="tooltip" title="' . $expiryInfo . '"';
													$cssClass = 'bg-' . data_get($transaction, 'css_class_variant');
												@endphp
												<span class="badge {{ $cssClass }}"{!! $tooltip !!}>
													{{ data_get($transaction, 'status_info') }}
												</span>
											</td>
										</tr>
									@endforeach
								@endif
								</tbody>
							</table>
						</div>
		
						<nav>
							@include('vendor.pagination.api.bootstrap-4')
						</nav>
						
						<div style="clear:both"></div>
					
					</div>
				</div>
				
			</div>
		</div>
	</div>
@endsection

@section('after_scripts')
@endsection
