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

@section('content')
	@if (!(isset($paddingTopExists) && $paddingTopExists))
		<div class="p-0 mt-lg-4 mt-md-3 mt-3"></div>
	@endif
	<div class="main-container">
		<div class="container">
			<div class="row">

				@if (isset($errors) && $errors->any())
					<div class="col-12">
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<ul class="list list-check">
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					</div>
				@endif

				@if (session()->has('flash_notification'))
					<div class="col-12">
						@include('flash::message')
					</div>
				@endif

				<div class="col-lg-5 col-md-8 col-sm-10 col-12 login-box mt-2">
					<div class="card card-default">
						
						<div class="panel-intro">
							<div class="d-flex justify-content-center">
								<h2 class="logo-title">{{ t('reset_password') }}</h2>
							</div>
						</div>
						
						@php
							$passwordReset ??= [];
						@endphp
						
						<div class="card-body">
							<form method="POST" action="{{ url('password/reset') }}">
								{!! csrf_field() !!}
								@honeypot
								<input type="hidden" name="token" value="{{ $token }}">
								
								{{-- email --}}
								@php
									$emailError = (isset($errors) && $errors->has('email')) ? ' is-invalid' : '';
								@endphp
								<div class="mb-3 auth-field-item">
									<div class="row">
										@php
											$col = (config('settings.sms.enable_phone_as_auth_field') == '1') ? 'col-6' : 'col-12';
										@endphp
										<label class="form-label {{ $col }} m-0 py-2 text-left" for="email">{{ t('email') }}:</label>
										@if (config('settings.sms.enable_phone_as_auth_field') == '1')
											<div class="col-6 py-2 text-right">
												<a href="" class="auth-field" data-auth-field="phone">{{ t('use_phone') }}</a>
											</div>
										@endif
									</div>
									<div class="input-group">
										<span class="input-group-text"><i class="far fa-envelope"></i></span>
										<input id="email" name="email"
											   type="text"
											   class="form-control{{ $emailError }}"
											   value="{{ old('email', data_get($passwordReset, 'email')) }}"
											   placeholder="{{ t('email_address') }}"
										>
									</div>
								</div>
								
								{{-- phone --}}
								@if (config('settings.sms.enable_phone_as_auth_field') == '1')
									@php
										$phoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
										$phoneValue = data_get($passwordReset, 'phone');
										$phoneCountryValue = data_get($passwordReset, 'phone_country', config('country.code'));
										$phoneValue = phoneE164($phoneValue, $phoneCountryValue);
										$phoneValueOld = phoneE164(old('phone', $phoneValue), old('phone_country', $phoneCountryValue));
									@endphp
									<div class="mb-3 auth-field-item">
										<div class="row">
											<label class="form-label col-6 m-0 py-2 text-left" for="phone">{{ t('phone_number') }}:</label>
											<div class="col-6 py-2 text-right">
												<a href="" class="auth-field" data-auth-field="email">{{ t('use_email') }}</a>
											</div>
										</div>
										<input id="phone" name="phone"
											   type="tel"
											   class="form-control{{ $phoneError }}"
											   value="{{ $phoneValueOld }}"
										>
										<input name="phone_country" type="hidden" value="{{ old('phone_country', $phoneCountryValue) }}">
									</div>
								@endif
								
								{{-- auth_field --}}
								<input name="auth_field" type="hidden" value="{{ old('auth_field', getAuthField()) }}">
								
								{{-- password --}}
								<?php $passwordError = (isset($errors) && $errors->has('password')) ? ' is-invalid' : ''; ?>
								<div class="mb-3">
									<label class="form-label" for="password">{{ t('password') }}:</label>
									<input type="password" name="password" placeholder="" class="form-control email{{ $passwordError }}" autocomplete="new-password">
								</div>
								
								{{-- password_confirmation --}}
								<?php $passwordError = (isset($errors) && $errors->has('password')) ? ' is-invalid' : ''; ?>
								<div class="mb-3">
									<label class="form-label" for="password_confirmation">{{ t('Password Confirmation') }}:</label>
									<input type="password" name="password_confirmation" placeholder="" class="form-control email{{ $passwordError }}">
								</div>
							
								@include('layouts.inc.tools.captcha', ['noLabel' => true])
								
								{{-- Submit --}}
								<div class="mb-3">
									<button type="submit" class="btn btn-primary btn-lg btn-block">{{ t('Reset the Password') }}</button>
								</div>
							</form>
						</div>
						
						<div class="card-footer text-center">
							<a href="{{ \App\Helpers\UrlGen::login() }}"> {{ t('back_to_the_log_in_page') }} </a>
						</div>
					</div>
					<div class="login-box-btm text-center">
						<p>
							{{ t('do_not_have_an_account') }} <br>
							<a href="{{ \App\Helpers\UrlGen::register() }}"><strong>{{ t('sign_up_') }}</strong></a>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('after_scripts')
	<script>
		phoneCountry = '{{ old('phone_country', ($phoneCountryValue ?? '')) }}';
		
		$(document).ready(function () {
			
			$(document).on('click', '#pwdBtn', function (e) {
				e.preventDefault();
				$("#pwdForm").submit();
				
				return false;
			});
			
		});
	</script>
@endsection
