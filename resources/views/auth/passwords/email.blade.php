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
	@if (!(isset($paddingTopExists) and $paddingTopExists))
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
				
				@if (session()->has('status'))
					<div class="col-12">
						<div class="alert alert-success alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<p class="mb-0">{{ session('status') }}</p>
						</div>
					</div>
				@endif
				
				@if (session()->has('email'))
					<div class="col-12">
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<p class="mb-0">{{ session('email') }}</p>
						</div>
					</div>
				@endif
				
				@if (session()->has('phone'))
					<div class="col-12">
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<p class="mb-0">{{ session('phone') }}</p>
						</div>
					</div>
				@endif
				
				@if (session()->has('login'))
					<div class="col-12">
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<p class="mb-0">{{ session('login') }}</p>
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
						
						<div class="panel-intro text-center">
							<div class="d-flex justify-content-center">
								<h2 class="logo-title">{{ t('password') }}</h2>
							</div>
						</div>
						
						<div class="card-body">
							<form id="pwdForm" role="form" method="POST" action="{{ url('password/email') }}">
								{!! csrf_field() !!}
								@honeypot
								
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
											   placeholder="{{ t('email_address') }}"
											   class="form-control{{ $emailError }}"
											   value="{{ old('email') }}"
										>
										<div class="form-text">
											{{ t('forgot_password_info_email') }}
										</div>
									</div>
								</div>
								
								{{-- phone --}}
								@if (config('settings.sms.enable_phone_as_auth_field') == '1')
									@php
										$phoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
										$phoneCountryValue = config('country.code');
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
											   value="{{ phoneE164(old('phone'), old('phone_country', $phoneCountryValue)) }}"
										>
										<input name="phone_country" type="hidden" value="{{ old('phone_country', $phoneCountryValue) }}">
										<div class="form-text">
											{{ t('forgot_password_info_phone') }}
										</div>
									</div>
								@endif
								
								{{-- auth_field --}}
								<input name="auth_field" type="hidden" value="{{ old('auth_field', getAuthField()) }}">
								
								@include('layouts.inc.tools.captcha', ['noLabel' => true])
								
								{{-- Submit --}}
								<div class="mb-3">
									<button id="pwdBtn" type="submit" class="btn btn-primary btn-lg btn-block">{{ t('submit') }}</button>
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
		$(document).ready(function () {
			$("#pwdBtn").click(function () {
				$("#pwdForm").submit();
				return false;
			});
		});
	</script>
@endsection
