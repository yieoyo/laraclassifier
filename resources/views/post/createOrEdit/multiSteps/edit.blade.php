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
	$post ??= [];
	
	$postTypes ??= [];
	$countries ??= [];
	
	$postCatParentId = data_get($post, 'category.parent_id');
	$postCatParentId = (empty($postCatParentId)) ? data_get($post, 'category.id', 0) : $postCatParentId;
@endphp

@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
	<div class="main-container">
		<div class="container">
			<div class="row">
				
				@includeFirst([config('larapen.core.customizedViewPath') . 'post.inc.notification', 'post.inc.notification'])

				<div class="col-md-9 page-content">
					<div class="inner-box category-content" style="overflow: visible;">
						<h2 class="title-2">
							<strong><i class="fas fa-edit"></i> {{ t('update_my_listing') }}</strong>
							-&nbsp;<a href="{{ \App\Helpers\UrlGen::post($post) }}"
							   class="" data-bs-placement="top"
								data-bs-toggle="tooltip"
								title="{!! data_get($post, 'title') !!}"
							>{!! str(data_get($post, 'title'))->limit(45) !!}</a>
						</h2>
						
						<div class="row">
							<div class="col-12">
								
								<form class="form-horizontal" id="payableForm" method="POST" action="{{ url()->current() }}" enctype="multipart/form-data">
									{!! csrf_field() !!}
									<input name="_method" type="hidden" value="PUT">
									<input type="hidden" name="post_id" value="{{ data_get($post, 'id') }}">
									<fieldset>
										
										{{-- category_id --}}
										<?php $categoryIdError = (isset($errors) && $errors->has('category_id')) ? ' is-invalid' : ''; ?>
										<div class="row mb-3 required">
											<label class="col-md-3 col-form-label{{ $categoryIdError }}">{{ t('category') }} <sup>*</sup></label>
											<div class="col-md-8">
												<div id="catsContainer" class="rounded{{ $categoryIdError }}">
													<a href="#browseCategories" data-bs-toggle="modal" class="cat-link" data-id="0">
														{{ t('select_a_category') }}
													</a>
												</div>
											</div>
											<input type="hidden" name="category_id" id="categoryId" value="{{ old('category_id', data_get($post, 'category.id')) }}">
											<input type="hidden" name="category_type" id="categoryType" value="{{ old('category_type', data_get($post, 'category.type')) }}">
										</div>
										
										@if (config('settings.single.show_listing_type'))
											{{-- post_type_id --}}
											@php
												$postTypeIdError = (isset($errors) && $errors->has('post_type_id')) ? ' is-invalid' : '';
												$postTypeId = old('post_type_id', data_get($post, 'post_type_id'));
											@endphp
											<div id="postTypeBloc" class="row mb-3 required">
												<label class="col-md-3 col-form-label{{ $postTypeIdError }}">{{ t('type') }} <sup>*</sup></label>
												<div class="col-md-8">
													@foreach ($postTypes as $postType)
														<div class="form-check form-check-inline">
															<input name="post_type_id"
																   id="postTypeId-{{ data_get($postType, 'id') }}"
																   value="{{ data_get($postType, 'id') }}"
																   type="radio"
																   class="form-check-input{{ $postTypeIdError }}" @checked($postTypeId == data_get($postType, 'id'))
															>
															<label class="form-check-label mb-0" for="postTypeId-{{ data_get($postType, 'id') }}">
																{{ data_get($postType, 'name') }}
															</label>
														</div>
													@endforeach
													<div class="form-text text-muted">{{ t('post_type_hint') }}</div>
												</div>
											</div>
										@endif

										{{-- title --}}
										<?php $titleError = (isset($errors) && $errors->has('title')) ? ' is-invalid' : ''; ?>
										<div class="row mb-3 required">
											<label class="col-md-3 col-form-label{{ $titleError }}" for="title">{{ t('title') }} <sup>*</sup></label>
											<div class="col-md-8">
												<input id="title" name="title" placeholder="{{ t('listing_title') }}" class="form-control input-md{{ $titleError }}"
													   type="text" value="{{ old('title', data_get($post, 'title')) }}">
												<div class="form-text text-muted">{{ t('a_great_title_needs_at_least_60_characters') }}</div>
											</div>
										</div>

										{{-- description --}}
										@php
											$descriptionError = (isset($errors) && $errors->has('description')) ? ' is-invalid' : '';
											$postDescription = data_get($post, 'description');
											$descriptionErrorLabel = '';
											$descriptionColClass = 'col-md-8';
											if (config('settings.single.wysiwyg_editor') != 'none') {
												$descriptionColClass = 'col-md-12';
												$descriptionErrorLabel = $descriptionError;
											} else {
												$postDescription = strip_tags($postDescription);
											}
										@endphp
										<div class="row mb-3 required">
											<label class="col-md-3 col-form-label{{ $descriptionErrorLabel }}" for="description">
												{{ t('Description') }} <sup>*</sup>
											</label>
											<div class="{{ $descriptionColClass }}">
												<textarea
														class="form-control{{ $descriptionError }}"
														id="description"
														name="description"
														rows="15"
														style="height: 300px"
												>{{ old('description', $postDescription) }}</textarea>
												<div class="form-text text-muted">{{ t('describe_what_makes_your_listing_unique') }}</div>
                                            </div>
										</div>
										
										{{-- cfContainer --}}
										<div id="cfContainer"></div>

										{{-- price --}}
										@php
											$priceError = (isset($errors) && $errors->has('price')) ? ' is-invalid' : '';
											$currencySymbol = config('currency.symbol', 'X');
											$price = old('price', data_get($post, 'price'));
											$price = \App\Helpers\Number::format($price, 2, '.', '');
										@endphp
										<div id="priceBloc" class="row mb-3 required">
											<label class="col-md-3 col-form-label{{ $priceError }}" for="price">{{ t('price') }}</label>
											<div class="col-md-8">
												<div class="input-group">
													<span class="input-group-text">{!! $currencySymbol !!}</span>
													<input id="price"
														   name="price"
														   class="form-control{{ $priceError }}"
														   placeholder="{{ t('ei_price') }}"
														   type="number"
														   min="0"
														   step="{{ getInputNumberStep((int)config('currency.decimal_places', 2)) }}"
														   value="{!! $price !!}"
													>
													<span class="input-group-text">
														<input id="negotiable" name="negotiable" type="checkbox"
															   value="1" {{ (old('negotiable', data_get($post, 'negotiable'))=='1') ? 'checked="checked"' : '' }}>
														&nbsp;<small>{{ t('negotiable') }}</small>
													</span>
												</div>
												@if (config('settings.single.price_mandatory') != '1')
													<div class="form-text text-muted">{{ t('price_hint') }}</div>
												@endif
											</div>
										</div>
										
										{{-- country_code --}}
										<input id="countryCode" name="country_code"
											   type="hidden"
											   value="{{ data_get($post, 'country_code') ?? config('country.code') }}"
										>
										
										@php
											$adminType = config('country.admin_type', 0);
										@endphp
										@if (config('settings.single.city_selection') == 'select')
											@if (in_array($adminType, ['1', '2']))
												{{-- admin_code --}}
												<?php $adminCodeError = (isset($errors) && $errors->has('admin_code')) ? ' is-invalid' : ''; ?>
												<div id="locationBox" class="row mb-3 required">
													<label class="col-md-3 col-form-label{{ $adminCodeError }}" for="admin_code">
														{{ t('location') }} <sup>*</sup>
													</label>
													<div class="col-md-8">
														<select id="adminCode" name="admin_code" class="form-control large-data-selecter{{ $adminCodeError }}">
															<option value="0" @selected(empty(old('admin_code')))>
																{{ t('select_your_location') }}
															</option>
														</select>
													</div>
												</div>
											@endif
										@else
											@php
												$adminType = (in_array($adminType, ['0', '1', '2'])) ? $adminType : 0;
												$relAdminType = (in_array($adminType, ['1', '2'])) ? $adminType : 1;
												$adminCode = data_get($post, 'city.subadmin' . $relAdminType . '_code', 0);
												$adminCode = data_get($post, 'city.subAdmin' . $relAdminType . '.code', $adminCode);
												$adminName = data_get($post, 'city.subAdmin' . $relAdminType . '.name');
												$cityId = data_get($post, 'city.id', 0);
												$cityName = data_get($post, 'city.name');
												$fullCityName = !empty($adminName) ? $cityName . ', ' . $adminName : $cityName;
											@endphp
											<input type="hidden" id="selectedAdminType" name="selected_admin_type" value="{{ old('selected_admin_type', $adminType) }}">
											<input type="hidden" id="selectedAdminCode" name="selected_admin_code" value="{{ old('selected_admin_code', $adminCode) }}">
											<input type="hidden" id="selectedCityId" name="selected_city_id" value="{{ old('selected_city_id', $cityId) }}">
											<input type="hidden" id="selectedCityName" name="selected_city_name" value="{{ old('selected_city_name', $fullCityName) }}">
										@endif
									
										{{-- city_id --}}
										<?php $cityIdError = (isset($errors) && $errors->has('city_id')) ? ' is-invalid' : ''; ?>
										<div id="cityBox" class="row mb-3 required">
											<label class="col-md-3 col-form-label{{ $cityIdError }}" for="city_id">
												{{ t('city') }} <sup>*</sup>
											</label>
											<div class="col-md-8">
												<select id="cityId" name="city_id" class="form-control large-data-selecter{{ $cityIdError }}">
													<option value="0" @selected(empty(old('city_id')))>
														{{ t('select_a_city') }}
													</option>
												</select>
											</div>
										</div>
										
										{{-- tags --}}
										@php
											$tagsError = (isset($errors) && $errors->has('tags.*')) ? ' is-invalid' : '';
											$tags = old('tags', data_get($post, 'tags'));
										@endphp
										<div class="row mb-3">
											<label class="col-md-3 col-form-label{{ $tagsError }}" for="tags">{{ t('Tags') }}</label>
											<div class="col-md-8">
												<select id="tags" name="tags[]" class="form-control tags-selecter" multiple="multiple">
													@if (!empty($tags))
														@foreach($tags as $iTag)
															<option selected="selected">{{ $iTag }}</option>
														@endforeach
													@endif
												</select>
												<div class="form-text text-muted">
													{!! t('tags_hint', [
															'limit' => (int)config('settings.single.tags_limit', 15),
															'min'   => (int)config('settings.single.tags_min_length', 2),
															'max'   => (int)config('settings.single.tags_max_length', 30)
														]) !!}
												</div>
											</div>
										</div>
										
										{{-- is_permanent --}}
										@if (config('settings.single.permanent_listings_enabled') == '3')
											<input id="isPermanent" name="is_permanent" type="hidden" value="{{ old('is_permanent', data_get($post, 'is_permanent')) }}">
										@else
											<?php $isPermanentError = (isset($errors) && $errors->has('is_permanent')) ? ' is-invalid' : ''; ?>
											<div id="isPermanentBox" class="row mb-3 required hide">
												<label class="col-md-3 col-form-label"></label>
												<div class="col-md-8">
													<div class="form-check">
														<input id="isPermanent" name="is_permanent"
															   class="form-check-input mt-1{{ $isPermanentError }}"
															   value="1"
															   type="checkbox" @checked(old('is_permanent', data_get($post, 'is_permanent')) == '1')
														>
														<label class="form-check-label mt-0" for="is_permanent">
															{!! t('is_permanent_label') !!}
														</label>
													</div>
													<div class="form-text text-muted">{{ t('is_permanent_hint') }}</div>
													<div style="clear:both"></div>
												</div>
											</div>
										@endif


										<div class="content-subheading">
											<i class="fas fa-user"></i>
											<strong>{{ t('seller_information') }}</strong>
										</div>
										
										
										{{-- contact_name --}}
										<?php $contactNameError = (isset($errors) && $errors->has('contact_name')) ? ' is-invalid' : ''; ?>
										<div class="row mb-3 required">
											<label class="col-md-3 col-form-label{{ $contactNameError }}" for="contact_name">
												{{ t('your_name') }} <sup>*</sup>
											</label>
											<div class="col-md-9 col-lg-8 col-xl-6">
												<div class="input-group">
													<span class="input-group-text"><i class="far fa-user"></i></span>
													<input id="contactName" name="contact_name"
														   type="text"
														   placeholder="{{ t('your_name') }}"
														   class="form-control input-md{{ $contactNameError }}"
														   value="{{ old('contact_name', data_get($post, 'contact_name')) }}"
													>
												</div>
											</div>
										</div>
										
										{{-- auth_field (as notification channel) --}}
										@php
											$authFields = getAuthFields(true);
											$authFieldError = (isset($errors) && $errors->has('auth_field')) ? ' is-invalid' : '';
											$usersCanChooseNotifyChannel = isUsersCanChooseNotifyChannel();
											$authFieldValue = data_get($post, 'auth_field') ?? getAuthField();
											$authFieldValue = ($usersCanChooseNotifyChannel) ? (old('auth_field', $authFieldValue)) : $authFieldValue;
										@endphp
										@if ($usersCanChooseNotifyChannel)
											<div class="row mb-3 required">
												<label class="col-md-3 col-form-label" for="auth_field">{{ t('notifications_channel') }} <sup>*</sup></label>
												<div class="col-md-9">
													@foreach ($authFields as $iAuthField => $notificationType)
														<div class="form-check form-check-inline pt-2">
															<input name="auth_field"
																   id="{{ $iAuthField }}AuthField"
																   value="{{ $iAuthField }}"
																   class="form-check-input auth-field-input{{ $authFieldError }}"
																   type="radio" @checked($authFieldValue == $iAuthField)
															>
															<label class="form-check-label mb-0" for="{{ $iAuthField }}AuthField">
																{{ $notificationType }}
															</label>
														</div>
													@endforeach
													<div class="form-text text-muted">
														{{ t('notifications_channel_hint') }}
													</div>
												</div>
											</div>
										@else
											<input id="{{ $authFieldValue }}AuthField" name="auth_field" type="hidden" value="{{ $authFieldValue }}">
										@endif
										
										@php
											$forceToDisplay = isBothAuthFieldsCanBeDisplayed() ? ' force-to-display' : '';
										@endphp
										
										{{-- email --}}
										@php
											$emailError = (isset($errors) && $errors->has('email')) ? ' is-invalid' : '';
										@endphp
										<?php  ?>
										<div class="row mb-3 auth-field-item required{{ $forceToDisplay }}">
											<label class="col-md-3 col-form-label{{ $emailError }}" for="email">{{ t('email') }}
												@if (getAuthField() == 'email')
													<sup>*</sup>
												@endif
											</label>
											<div class="col-md-9 col-lg-8 col-xl-6">
												<div class="input-group">
													<span class="input-group-text"><i class="far fa-envelope"></i></span>
													<input id="email" name="email"
														   type="text"
														   class="form-control{{ $emailError }}"
														   placeholder="{{ t('email_address') }}"
														   value="{{ old('email', data_get($post, 'email')) }}"
													>
												</div>
											</div>
										</div>
										
										{{-- phone --}}
										@php
											$phoneError = (isset($errors) && $errors->has('phone')) ? ' is-invalid' : '';
											$phoneValue = data_get($post, 'phone');
											$phoneCountryValue = data_get($post, 'phone_country') ?? config('country.code');
											$phoneValue = phoneE164($phoneValue, $phoneCountryValue);
											$phoneValueOld = phoneE164(old('phone', $phoneValue), old('phone_country', $phoneCountryValue));
										@endphp
										<div class="row mb-3 auth-field-item required{{ $forceToDisplay }}">
											<label class="col-md-3 col-form-label{{ $phoneError }}" for="phone">{{ t('phone_number') }}
												@if (getAuthField() == 'phone')
													<sup>*</sup>
												@endif
											</label>
											<div class="col-md-9 col-lg-8 col-xl-6">
												<div class="input-group">
													<input id="phone" name="phone"
														   class="form-control input-md{{ $phoneError }}"
														   type="text"
														   value="{{ $phoneValueOld }}"
													>
													<span class="input-group-text iti-group-text">
														<input id="phoneHidden" name="phone_hidden" type="checkbox"
															   value="1" @checked(old('phone_hidden', data_get($post, 'phone_hidden'))=='1')>
														&nbsp;<small>{{ t('Hide') }}</small>
													</span>
												</div>
												<input name="phone_country" type="hidden" value="{{ old('phone_country', $phoneCountryValue) }}">
											</div>
										</div>

										{{-- Button --}}
										<div class="row mb-3 pt-3">
											<div class="col-md-12 text-center">
												<a href="{{ \App\Helpers\UrlGen::post($post) }}" class="btn btn-default btn-lg">{{ t('Back') }}</a>
												<button id="nextStepBtn" class="btn btn-primary btn-lg">{{ t('Update') }}</button>
											</div>
										</div>

									</fieldset>
								</form>
								
							</div>
						</div>
					</div>
				</div>
				<!-- /.page-content -->

				<div class="col-md-3 reg-sidebar">
					@includeFirst([config('larapen.core.customizedViewPath') . 'post.createOrEdit.inc.right-sidebar', 'post.createOrEdit.inc.right-sidebar'])
				</div>
				
			</div>
		</div>
	</div>
	@includeFirst([config('larapen.core.customizedViewPath') . 'post.createOrEdit.inc.category-modal', 'post.createOrEdit.inc.category-modal'])
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
	<script>
		defaultAuthField = '{{ old('auth_field', $authFieldValue ?? getAuthField()) }}';
		phoneCountry = '{{ old('phone_country', ($phoneCountryValue ?? '')) }}';
	</script>
@endsection

@includeFirst([config('larapen.core.customizedViewPath') . 'post.createOrEdit.inc.form-assets', 'post.createOrEdit.inc.form-assets'])
