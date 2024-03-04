@php
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
@endphp
@if ($paymentMethods->count() > 0)
	@if (!empty($selectedPackage))
		
		<div class="content-subheading">
			<i class="fas fa-wallet"></i>
			<strong>{{ t('Payment') }}</strong>
		</div>
		
		<div class="col-md-12 page-content mb-4">
			<div class="inner-box">
				
				<div class="row">
					<div class="col-sm-12">
						
						<div class="form-group mb-0">
							<fieldset>
								
								@includeFirst([
									config('larapen.core.customizedViewPath') . 'payment.packages.selected',
									'payment.packages.selected'
								])
							
							</fieldset>
						</div>
					
					</div>
				</div>
			</div>
		</div>
		
	@else
	
		@if ($packages->count() > 0)
			<div class="content-subheading">
				<i class="fas fa-tags"></i>
				<strong>{{ t('Packages') }}</strong>
			</div>
			
			<div class="col-md-12 page-content mb-4">
				<div class="inner-box">
					
					<div class="row">
						<div class="col-sm-12">
							<fieldset>
								
								@includeFirst([
									config('larapen.core.customizedViewPath') . 'payment.packages',
									'payment.packages'
								])
							
							</fieldset>
							
						</div>
					</div>
				</div>
			</div>
		@endif
		
	@endif
@endif

@section('after_styles')
	@parent
@endsection

@section('after_scripts')
	@parent
	<script>
		@if ($packages->count() > 0 && $paymentMethods->count() > 0)
		
			var currentPackagePrice = {{ $currentPackagePrice ?? 0 }};
			var paymentIsActive = {{ $paymentIsActive ?? 0 }};
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
				showPaymentSubmitButton(currentPackagePrice, packagePrice, paymentIsActive, paymentMethod);
				
				/* Select a Package */
				$('.package-selection').click(function () {
					selectedPackage = $(this).val();
					packagePrice = getPackagePrice(selectedPackage);
					packageCurrencySymbol = $(this).data('currencysymbol');
					packageCurrencyInLeft = $(this).data('currencyinleft');
					showPaymentMethods(packagePrice);
					showAmount(packagePrice, packageCurrencySymbol, packageCurrencyInLeft);
					showPaymentSubmitButton(currentPackagePrice, packagePrice, paymentIsActive, paymentMethod);
				});
				
				/* Select a Payment Method */
				paymentMethodEl.on('change', function () {
					paymentMethod = $(this).find('option:selected').data('name');
					showPaymentSubmitButton(currentPackagePrice, packagePrice, paymentIsActive, paymentMethod);
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
		function showPaymentSubmitButton(currentPackagePrice, packagePrice, paymentIsActive, paymentMethod)
		{
			/* This feature is related to the Multi-Steps Form */
			return false;
		}
	</script>
@endsection
