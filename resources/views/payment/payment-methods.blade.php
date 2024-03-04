@php
	$paymentMethods ??= collect();
	$payment ??= [];
@endphp
<div class="row mb-3 mb-0">
	@php
		$paymentMethodIdError = (isset($errors) && $errors->has('payment_method_id')) ? ' is-invalid' : '';
	@endphp
	<div class="col-md-10 col-sm-12 p-0">
		<select class="form-control selecter{{ $paymentMethodIdError }}" name="payment_method_id" id="paymentMethodId">
			@foreach ($paymentMethods as $paymentMethod)
				@if (view()->exists('payment::' . $paymentMethod->name))
					@php
						$paymentMethodCheckedAttr = (
								old(
									'payment_method_id',
									data_get($payment, 'paymentMethod.id', 0)
								) == $paymentMethod->id
							)
								? 'selected="selected"'
								: '';
					@endphp
					<option value="{{ $paymentMethod->id }}" data-name="{{ $paymentMethod->name }}"{{ $paymentMethodCheckedAttr }}>
						@if ($paymentMethod->name == 'offlinepayment')
							{{ trans('offlinepayment::messages.offline_payment') }}
						@else
							{{ $paymentMethod->display_name }}
						@endif
					</option>
				@endif
			@endforeach
		</select>
	</div>
</div>
