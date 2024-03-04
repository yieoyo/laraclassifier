@php
	$paymentMethods ??= collect();
@endphp
@if ($paymentMethods->count() > 0)
	{{-- Payment Plugins --}}
	@php
		$hasCcBox = 0;
	@endphp
	@foreach($paymentMethods as $paymentMethod)
		@if (view()->exists('payment::' . $paymentMethod->name))
			@include('payment::' . $paymentMethod->name, [$paymentMethod->name . 'PaymentMethod' => $paymentMethod])
		@endif
		@php
			$hasCcBox = ($paymentMethod->has_ccbox == 1 && $hasCcBox == 0) ? 1 : $hasCcBox;
		@endphp
	@endforeach
@endif
