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

@section('wizard')
    @includeFirst([
		config('larapen.core.customizedViewPath') . 'post.createOrEdit.multiSteps.inc.wizard',
		'post.createOrEdit.multiSteps.inc.wizard'
	])
@endsection

@php
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
@endphp
@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
    <div class="main-container">
        <div class="container">
            <div class="row">
    
                @includeFirst([config('larapen.core.customizedViewPath') . 'post.inc.notification', 'post.inc.notification'])
                
                <div class="col-md-12 page-content">
                    <div class="inner-box">
						
                        <h2 class="title-2">
							<strong>
								@if (!empty($selectedPackage))
									<i class="fas fa-wallet"></i> {{ t('Payment') }}
								@else
									<i class="fas fa-tags"></i> {{ t('Pricing') }}
								@endif
							</strong>
	                        @php
		                        try {
									if (auth()->check()) {
										if (auth()->user()->can(\App\Models\Permission::getStaffPermissions())) {
											$postLink = '-&nbsp;<a href="' . \App\Helpers\UrlGen::post($post) . '"
													  class=""
													  data-bs-placement="top"
													  data-bs-toggle="tooltip"
													  title="' . $post->title . '"
											>' . str($post->title)->limit(45) . '</a>';
											
											echo $postLink;
										}
									}
								} catch (\Throwable $e) {}
	                        @endphp
						</h2>
						
                        <div class="row">
                            <div class="col-sm-12">
                                <form class="form" id="payableForm" method="POST" action="{{ url()->current() }}">
                                    {!! csrf_field() !!}
                                    <input type="hidden" name="payable_id" value="{{ $post->id }}">
                                    <fieldset>
										
										@if (!empty($selectedPackage))
											@includeFirst([
												config('larapen.core.customizedViewPath') . 'payment.packages.selected',
												'payment.packages.selected'
											])
										@else
											@includeFirst([
												config('larapen.core.customizedViewPath') . 'payment.packages',
												'payment.packages'
											])
                                        @endif
										
                                        <div class="row">
                                            <div class="col-md-12 text-center mt-4">
												<a id="skipBtn" href="{{ \App\Helpers\UrlGen::post($post) }}" class="btn btn-default btn-lg">
													{{ t('Skip') }}
												</a>
                                                <button id="submitPayableForm" class="btn btn-success btn-lg submitPayableForm"> {{ t('Pay') }} </button>
                                            </div>
                                        </div>
                                    
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.page-content -->
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.13.1/jquery.validate.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.payment/1.2.3/jquery.payment.min.js"></script>
    @php
	    $jqValidateLangFilePath = 'assets/plugins/forms/validation/localization/messages_'.config('app.locale').'.min.js';
    @endphp
    @if (file_exists(public_path() . '/' . $jqValidateLangFilePath))
        <script src="{{ url($jqValidateLangFilePath) }}" type="text/javascript"></script>
    @endif

    <script>
        @if ($packages->count() > 0 && $paymentMethods->count() > 0)
			
			var currentPackagePrice = {{ $currentPackagePrice ?? 0 }};
			var paymentIsActive = {{ $paymentIsActive ?? 0 }};
			var isCreationFormPage = {{ request()->segment(2) == 'create' ? 'true' : 'false' }};
            var forceDisplayPaymentMethods = {{ !empty($selectedPackage) ? 'true' : 'false' }};
			$(document).ready(function ()
			{
				let selectedPackageEl = $('input[name=package_id]:checked');
				let paymentMethodEl = $('#paymentMethodId');
				
				/* Get the selected package ID & info */
				var selectedPackage;
				
				if (selectedPackageEl.length > 0) {
					selectedPackage = selectedPackageEl.val();
				} else {
					if (hasQueryParameter('package')) {
						let urlWithoutPackage = removeURLParameter('package');
						redirect(urlWithoutPackage);
					}
				}
				
				/* Show price & Payment Methods */
				var packagePrice = getPackagePrice(selectedPackage);
				var packageCurrencySymbol = selectedPackageEl.data('currencysymbol');
				var packageCurrencyInLeft = selectedPackageEl.data('currencyinleft');
				var paymentMethod = paymentMethodEl.find('option:selected').data('name');
				showPaymentMethods(packagePrice, forceDisplayPaymentMethods);
				showAmount(packagePrice, packageCurrencySymbol, packageCurrencyInLeft);
				showPaymentSubmitButton(currentPackagePrice, packagePrice, paymentIsActive, paymentMethod, isCreationFormPage);
				
				/* Select a Package */
				$('.package-selection').click(function () {
					selectedPackage = $(this).val();
					packagePrice = getPackagePrice(selectedPackage);
					packageCurrencySymbol = $(this).data('currencysymbol');
					packageCurrencyInLeft = $(this).data('currencyinleft');
					showPaymentMethods(packagePrice);
					showAmount(packagePrice, packageCurrencySymbol, packageCurrencyInLeft);
					showPaymentSubmitButton(currentPackagePrice, packagePrice, paymentIsActive, paymentMethod, isCreationFormPage);
				});
				
				/* Select a Payment Method */
				paymentMethodEl.on('change', function () {
					paymentMethod = $(this).find('option:selected').data('name');
					showPaymentSubmitButton(currentPackagePrice, packagePrice, paymentIsActive, paymentMethod, isCreationFormPage);
				});
				
				/* Form Default Submission */
				$('#submitPayableForm').on('click', function (e) {
					e.preventDefault();
					
					if (packagePrice <= 0) {
						$('#payableForm').submit();
					}
					
					return false;
				});
			});
        
        @endif

		/* Show or Hide the Payment Submit Button */
		/* NOTE: Prevent Package's Downgrading */
		/* Hide the 'Skip' button if Package price > 0 */
		function showPaymentSubmitButton(currentPackagePrice, packagePrice, paymentIsActive, paymentMethod, isCreationFormPage = true)
		{
			let submitBtn = $('#submitPayableForm');
			let submitBtnLabel = {
				'pay': '{{ t('Pay') }}',
				'submit': '{{ t('submit') }}',
			};
			let skipBtn = $('#skipBtn');
			
			if (packagePrice > 0) {
				submitBtn.html(submitBtnLabel.pay).show();
				skipBtn.hide();
				
				if (currentPackagePrice > packagePrice) {
					submitBtn.hide().html(submitBtnLabel.submit);
				}
				if (currentPackagePrice === packagePrice) {
					if (paymentMethod === 'offlinepayment') {
						if (!isCreationFormPage && paymentIsActive !== 1) {
							submitBtn.hide().html(submitBtnLabel.submit);
							skipBtn.show();
						}
					}
				}
			} else {
				skipBtn.show();
				submitBtn.html(submitBtnLabel.submit);
			}
		}
    </script>
@endsection
