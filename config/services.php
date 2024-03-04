<?php

return [
	
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */
	
	/*
	 * Mail providers
	 */
    'mailgun' => [
        'domain'   => null,
        'secret'   => null,
		'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'guzzle' => [
            'verify' => false,
        ],
    ],
	
	'postmark' => [
		'token' => env('POSTMARK_TOKEN', ''),
	],
	
    'ses' => [
        'key'    => null,
        'secret' => null,
        'region' => null,
		'token'  => null, // To utilize AWS temporary credentials via a session token
    ],
	
    'sparkpost' => [
        'secret' => null,
        'guzzle' => [
            'verify' => false,
        ],
    ],
	
	/*
	 * Social Auth Providers
	 */
    'facebook' => [
        'client_id'     => null,
        'client_secret' => null,
        'redirect'      => env('APP_URL') . '/auth/facebook/callback',
    ],
	
	'linkedin-openid' => [
		'client_id'     => null,
		'client_secret' => null,
		'redirect'      => env('APP_URL') . '/auth/linkedin/callback',
	],
	
	// OAuth 2.0
    'twitter-oauth-2' => [
	    'client_id'       => null,
	    'client_secret'   => null,
	    'redirect'        => env('APP_URL') . '/auth/twitter-oauth-2/callback',
    ],
	
	// OAuth 1.0
	'twitter' => [
		'client_id'       => null,
		'client_secret'   => null,
		'redirect'        => env('APP_URL') . '/auth/twitter/callback',
	],
	
    'google' => [
        'client_id'     => null,
        'client_secret' => null,
        'redirect'      => env('APP_URL') . '/auth/google/callback',
    ],
	
	/*
	 * Payment gateways
	 * See payment plugins config files
	 */
	
	/*
	 * Other
	 */
	'googlemaps' => [
		'key' => null, //-> for Google Map JavaScript & Embeded
	],
	
];
