@php
	$captcha = config('settings.security.captcha');
	$reCaptchaVersion = config('recaptcha.version', 'v2');
	$isReCaptchaEnabled = (
		$captcha == 'recaptcha'
		&& !empty(config('recaptcha.site_key'))
		&& !empty(config('recaptcha.secret_key'))
		&& in_array($reCaptchaVersion, ['v2', 'v3'])
	);
@endphp
@if ($isReCaptchaEnabled)
	@if ($reCaptchaVersion == 'v3')
		<input type="hidden" name="g-recaptcha-response" id="gRecaptchaResponse">
	@endif
	@if ($reCaptchaVersion == 'v2')
		{{-- recaptcha --}}
		@php
			$recaptchaError = (isset($errors) && $errors->has('g-recaptcha-response')) ? ' is-invalid' : '';
		@endphp
		@if (isAdminPanel())
			
			<div class="form-group mb-3 required{{ $recaptchaError }}">
				<div class="no-label">
					{!! recaptchaHtmlFormSnippet() !!}
				</div>
				
				@if ($errors->has('g-recaptcha-response'))
					<div class="invalid-feedback{{ $recaptchaError }}">
						{{ $errors->first('g-recaptcha-response') }}
					</div>
				@endif
			</div>
			
		@else
			
			@if (isset($colLeft) && isset($colRight))
				<div class="row mb-3 required{{ $recaptchaError }}">
					<label class="{{ $colLeft }} col-form-label" for="g-recaptcha-response">
						@if (isset($label) && $label == true)
							{{ t('captcha_label') }}
						@endif
					</label>
					<div class="{{ $colRight }}">
						{!! recaptchaHtmlFormSnippet() !!}
					</div>
				</div>
			@else
				@if (isset($label) && $label == true)
					<div class="row mb-3 required{{ $recaptchaError }}">
						<label class="control-label" for="g-recaptcha-response">{{ t('captcha_label') }}</label>
						<div>
							{!! recaptchaHtmlFormSnippet() !!}
						</div>
					</div>
				@elseif (isset($noLabel) && $noLabel == true)
					<div class="row mb-3 required{{ $recaptchaError }}">
						<div class="no-label">
							{!! recaptchaHtmlFormSnippet() !!}
						</div>
					</div>
				@else
					<div class="row mb-3 required{{ $recaptchaError }}">
						<div>
							{!! recaptchaHtmlFormSnippet() !!}
						</div>
					</div>
				@endif
			@endif
			
		@endif
		
	@endif
@endif

@section('recaptcha_head')
	@if ($isReCaptchaEnabled)
		<style>
			.is-invalid .g-recaptcha iframe,
			.has-error .g-recaptcha iframe {
				border: 1px solid #f85359;
			}
		</style>
		@if ($reCaptchaVersion == 'v3')
			<script type="text/javascript">
				function myCustomValidation(token) {
					/* read HTTP status */
					/* console.log(token); */
					let gRecaptchaResponseEl = $('#gRecaptchaResponse');
					if (gRecaptchaResponseEl.length) {
						gRecaptchaResponseEl.val(token);
					}
				}
			</script>
			{!! recaptchaApiV3JsScriptTag([
				'action' 		    => request()->path(),
				'custom_validation' => 'myCustomValidation'
			]) !!}
		@else
			{!! recaptchaApiJsScriptTag() !!}
		@endif
	@endif
@endsection
