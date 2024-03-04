<div class="modal fade" id="quickLogin" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			
			<div class="modal-header px-3">
				<h4 class="modal-title"><i class="fas fa-sign-in-alt"></i> {{ t('log_in') }} </h4>
				
				<button type="button" class="close" data-bs-dismiss="modal">
					<span aria-hidden="true">&times;</span>
					<span class="sr-only">{{ t('Close') }}</span>
				</button>
			</div>
			
			<form role="form" method="POST" action="{{ \App\Helpers\UrlGen::login() }}">
				<div class="modal-body">
					<div class="row">
						<div class="col-12">
							
							{!! csrf_field() !!}
							<input type="hidden" name="language_code" value="{{ config('app.locale') }}">
							
							@if (isset($errors) && $errors->any() && old('quickLoginForm')=='1')
								<div class="alert alert-danger alert-dismissible">
									<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
									<ul class="list list-check">
										@foreach($errors->all() as $error)
											<li>{!! $error !!}</li>
										@endforeach
									</ul>
								</div>
							@endif
							
							@includeFirst([config('larapen.core.customizedViewPath') . 'auth.login.inc.social', 'auth.login.inc.social'], ['socialCol' => 12])
							<?php $mtAuth = !socialLoginIsEnabled() ? ' mt-3' : ''; ?>
							
							{{-- email --}}
							@php
								$emailError = (isset($errors) && $errors->has('email')) ? ' is-invalid' : '';
								$emailValue = (session()->has('email')) ? session('email') : old('email');
							@endphp
							<div class="mb-3 auth-field-item{{ $mtAuth }}">
								<div class="row">
									@php
										$col = (config('settings.sms.enable_phone_as_auth_field') == '1') ? 'col-6' : 'col-12';
									@endphp
									<label class="form-label {{ $col }} m-0 py-2 text-left" for="email">{{ t('email') }}:</label>
									@if (config('settings.sms.enable_phone_as_auth_field') == '1')
										<div class="col-6 py-2 text-right">
											<a href="" class="auth-field" data-auth-field="phone">{{ t('login_with_phone') }}</a>
										</div>
									@endif
								</div>
								<div class="input-group">
									<span class="input-group-text"><i class="fas fa-user"></i></span>
									<input id="mEmail" name="email"
										   type="text"
										   placeholder="{{ t('email_or_username') }}"
										   class="form-control{{ $emailError }}"
										   value="{{ $emailValue }}"
									>
								</div>
							</div>
							
							{{-- phone --}}
							@if (config('settings.sms.enable_phone_as_auth_field') == '1')
								@php
									$phoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
									$phoneValue = (session()->has('phone')) ? session('phone') : old('phone');
									$phoneCountryValue = config('country.code');
								@endphp
								<div class="mb-3 auth-field-item{{ $mtAuth }}">
									<div class="row">
										<label class="form-label col-6 m-0 py-2 text-left" for="phone">{{ t('phone_number') }}:</label>
										<div class="col-6 py-2 text-right">
											<a href="" class="auth-field" data-auth-field="email">{{ t('login_with_email') }}</a>
										</div>
									</div>
									<input id="mPhone" name="phone"
										   type="tel"
										   class="form-control m-phone{{ $phoneError }}"
										   value="{{ phoneE164($phoneValue, old('phone_country', $phoneCountryValue)) }}"
									>
									<input name="phone_country" type="hidden" value="{{ old('phone_country', $phoneCountryValue) }}">
								</div>
							@endif
							
							{{-- auth_field --}}
							<input name="auth_field" type="hidden" value="{{ old('auth_field', getAuthField()) }}">
							
							{{-- password --}}
							<?php $passwordError = (isset($errors) && $errors->has('password')) ? ' is-invalid' : ''; ?>
							<div class="mb-3">
								<label for="password" class="control-label">{{ t('password') }}</label>
								<div class="input-group show-pwd-group">
									<span class="input-group-text"><i class="fas fa-lock"></i></span>
									<input id="mPassword" name="password"
										   type="password"
										   class="form-control{{ $passwordError }}"
										   placeholder="{{ t('password') }}"
										   autocomplete="new-password"
									>
									<span class="icon-append show-pwd">
										<button type="button" class="eyeOfPwd">
											<i class="far fa-eye-slash"></i>
										</button>
									</span>
								</div>
							</div>
							
							{{-- remember --}}
							<?php $rememberError = (isset($errors) && $errors->has('remember')) ? ' is-invalid' : ''; ?>
							<div class="mb-3">
								<label class="checkbox form-check-label float-start mt-2" style="font-weight: normal;">
									<input type="checkbox" value="1" name="remember_me" id="rememberMe2" class="{{ $rememberError }}"> {{ t('keep_me_logged_in') }}
								</label>
								<p class="float-end mt-2">
									<a href="{{ url('password/reset') }}">
										{{ t('lost_your_password') }}
									</a> / <a href="{{ \App\Helpers\UrlGen::register() }}">
										{{ t('register') }}
									</a>
								</p>
								<div style=" clear:both"></div>
							</div>
							
							@include('layouts.inc.tools.captcha', ['label' => true])
							
							<input type="hidden" name="quickLoginForm" value="1">
							
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary float-end">{{ t('log_in') }}</button>
					<button type="button" class="btn btn-default" data-bs-dismiss="modal">{{ t('Cancel') }}</button>
				</div>
			</form>
			
		</div>
	</div>
</div>
