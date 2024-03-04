@extends('admin.layouts.auth')

@section('content')
	
	@if (isset($errors) && $errors->any())
        <div class="alert alert-danger ms-0 me-0 mb-5">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
	@endif
    
    @if (session('status'))
        <div class="alert alert-success ms-0 me-0 mb-5">
            {{ session('status') }}
        </div>
    @endif
    
    <div id="loginform">
        
        <div class="logo">
            <h3 class="box-title mb-3">{{ trans('admin.login') }}</h3>
        </div>
        
        <div class="row">
            <div class="col-12">
                
                <form class="form-horizontal mt-3" id="loginform" action="{{ admin_url('login') }}" method="post">
                    {!! csrf_field() !!}
                    @honeypot
                    
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
                                <a href="" class="auth-field text-muted" data-auth-field="phone">{{ t('login_with_phone') }}</a>
                            </div>
                        @endif
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input id="email" name="email"
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
                                <a href="" class="auth-field text-muted" data-auth-field="email">{{ t('login_with_email') }}</a>
                            </div>
                            <div class="">
                                <input id="phone" name="phone"
                                       type="tel"
                                       class="form-control{{ $phoneRowError ?? '' }}"
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
                    
                    {{-- password --}}
                    @php
                        if (isset($errors)) {
							$pwdHasError = $errors->has('phone');
							$pwdRowError = $pwdHasError ? ' has-danger' : '';
							$pwdFieldError = $pwdHasError ? ' form-control-danger' : '';
							$pwdError = $errors->first('email');
						}
                    @endphp
                    <div class="row mb-3{{ $pwdRowError ?? '' }}">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input id="password" name="password"
                                   type="password"
                                   class="form-control{{ $pwdFieldError ?? '' }}"
                                   placeholder="{{ trans('admin.password') }}"
                                   autocomplete="new-password"
                            >
                        </div>
                        @if (isset($pwdHasError) && $pwdHasError)
                            <div class="invalid-feedback">{{ $pwdError ?? '' }}</div>
                        @endif
                    </div>
                    
                    {{-- captcha --}}
                    @include('layouts.inc.tools.captcha')
                    
                    {{-- remember me & password recover --}}
                    <div class="row mb-3">
                        <div class="d-flex">
                            <div class="checkbox checkbox-info pt-0">
                                <input type="checkbox" name="remember_me" id="rememberMe" class="material-inputs chk-col-indigo">
                                <label for="rememberMe"> {{ trans('admin.remember_me') }} </label>
                            </div>
                            <div class="ms-auto">
                                <a href="javascript:void(0)" id="to-recover" class="text-muted float-end">
                                    <i class="fa fa-lock me-1"></i> {{ trans('admin.forgot_your_password') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    {{-- button --}}
                    <div class="row mb-3 text-center mt-4">
                        <div class="col-12 d-grid">
                            <button class="btn btn-primary btn-lg waves-effect waves-light" type="submit">{{ trans('admin.login') }}</button>
                        </div>
                    </div>
                </form>
                
            </div>
        </div>
        
    </div>
    
    @include('admin.auth.passwords.inc.recover-form')
    
@endsection
