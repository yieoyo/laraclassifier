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
@extends('errors.layouts.master')

@section('title', t('Forbidden'))

@section('search')
    @parent
    @include('errors.layouts.inc.search')
@endsection

@section('content')
    @include('common.spacer')
    <div class="main-container inner-page">
        <div class="container">
            <div class="section-content">
                <div class="row">

                    <div class="col-md-12 page-content">
    
                        <div class="error-page mt-5 mb-5 ms-0 me-0 pt-5">
                            <h1 class="headline text-center" style="font-size: 180px;">403</h1>
                            <div class="text-center m-l-0 mt-5">
                                <h3 class="m-t-0 color-danger">
                                    <i class="fas fa-exclamation-triangle"></i> {{ t('Forbidden') }}
                                </h3>
                                <p>
                                    @php
                                        $defaultErrorMessage = t('Meanwhile, you may return to homepage', ['url' => url('/')]);
										$extractedMessage = null;
										
										if (isset($exception)) {
											if (is_object($exception) && method_exists($exception, 'getMessage')) {
												$extractedMessage = $exception->getMessage();
												$extractedMessage = str_replace(base_path(), '', $extractedMessage);
											}
										}
										
										echo !empty($extractedMessage) ? $extractedMessage : $defaultErrorMessage;
                                    @endphp
                                </p>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
