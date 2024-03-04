<?php
/*
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
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Default URIs
    |--------------------------------------------------------------------------
    |
    | 'default_uri' => Homepage
    | 'countries_list_uri' => Page that show the countries list
    */
    
    'default_uri' => '/',
    'countries_list_uri' => 'countries',
    
    
    /*
    |--------------------------------------------------------------------------
    | Cache and Cookies Expiration (Unused)
    |--------------------------------------------------------------------------
    | Value in seconds
    |
    | InMinute = 60; InHour = 3600; InDay = 86400; InWeek = 604800; InMonth = 2592000;
    */
    
    'cache_expire' => 3600,
    'cookie_expire' => 2592000,
    
    
    /*
    |--------------------------------------------------------------------------
    | Default Country (Unused)
    |--------------------------------------------------------------------------
    |
    | Use the countries ISO Code
    | E.g. Use 'BJ' for Benin.
    | Let this value empty to allow user to select a country if her IP not found or if her IP belong a banned country.
    */
    
    'default_country' => '',
    'show_country_flag' => true,

];
