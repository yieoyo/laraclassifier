<div id="recoverform">
	<div class="logo">
		<h3 class="fw-medium mb-3">{{ trans('admin.reset_password') }}</h3>
		{{--<span class="text-muted">{{ trans('admin.reset_password_info') }}</span>--}}
	</div>
	
	<div class="row mt-3">
		<form class="col-12" action="{{ url('password/email') }}" method="post">
			{!! csrf_field() !!}
			@honeypot
			<input type="hidden" name="language_code" value="{{ config('app.locale') }}">
			
			{{-- email --}}
			@php
				if (isset($errors)) {
					$emailHasError = $errors->has('email');
					$emailRowError = $emailHasError ? ' has-danger' : '';
					$emailFieldError = $emailHasError ? ' form-control-danger' : '';
					$emailError = $errors->first('email');
				}
			@endphp
			<div class="row mb-3 auth-field-item{{ $emailRowError ?? '' }}">
				@if (config('settings.sms.enable_phone_as_auth_field') == '1')
					<div class="col-12 pb-1">
						<a href="" class="auth-field text-muted" data-auth-field="phone">{{ t('use_phone') }}</a>
					</div>
				@endif
				<div class="input-group">
					<span class="input-group-text"><i class="fas fa-user"></i></span>
					<input id="mEmail" name="email"
						   type="text"
						   placeholder="{{ trans('admin.email_address') }}"
						   class="form-control{{ $emailFieldError ?? '' }}"
						   value="{{ old('email') }}"
					>
				</div>
				@if (isset($emailHasError) && $emailHasError)
					<div class="invalid-feedback">{{ $emailError ?? '' }}</div>
				@endif
			</div>
			
			{{-- phone --}}
			@if (config('settings.sms.enable_phone_as_auth_field') == '1')
				@php
					if (isset($errors)) {
						$phoneHasError = $errors->has('phone');
						$phoneRowError = $emailHasError ? ' has-danger' : '';
						$phoneFieldError = $emailHasError ? ' form-control-danger' : '';
						$phoneError = $errors->first('email');
					}
				@endphp
				<div class="row mb-3 auth-field-item{{ $phoneHasError ?? '' }}">
					<div class="col-12 pb-1">
						<a href="" class="auth-field text-muted" data-auth-field="email">{{ t('use_email') }}</a>
					</div>
					<div class="">
						<input id="mPhone" name="phone"
							   type="tel"
							   class="form-control m-phone{{ $phoneRowError ?? '' }}"
							   value="{{ phoneE164(old('phone'), old('phone_country', 'us')) }}"
						>
						<input name="phone_country" type="hidden" value="{{ old('phone_country', 'us') }}">
					</div>
					@if (isset($phoneHasError) && $phoneHasError)
						<div class="invalid-feedback">{{ $phoneError ?? '' }}</div>
					@endif
				</div>
			@endif
			
			{{-- auth_field --}}
			<input name="auth_field" type="hidden" value="{{ old('auth_field', getAuthField()) }}">
			
			@include('layouts.inc.tools.captcha')
			
			{{-- remember me & password recover --}}
			<div class="row mb-3">
				<div class="d-flex">
					<div class="ms-auto">
						<a href="javascript:void(0)" id="to-login" class="text-muted float-end">
							<i class="fas fa-sign-in-alt me-1"></i> {{ trans('admin.login') }}
						</a>
					</div>
				</div>
			</div>
			
			{{-- button --}}
			<div class="row mb-3 text-center mt-4">
				<div class="col-12 d-grid">
					<button class="btn btn-lg btn-primary" type="submit" name="action">{{ trans('admin.reset') }}</button>
				</div>
			</div>
		</form>
	</div>
</div>
