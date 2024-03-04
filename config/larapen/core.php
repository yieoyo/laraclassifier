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
     |-----------------------------------------------------------------------------------------------
     | The item's ID on CodeCanyon
     |-----------------------------------------------------------------------------------------------
     |
     */
    'itemId'   => '16458425',
    'itemSlug' => 'laraclassifier',
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Purchase code checker URL
     |-----------------------------------------------------------------------------------------------
     |
     */
    'purchaseCodeCheckerUrl' => 'https://api.bedigit.com/envato.php?purchase_code=',
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Purchase Code
     |-----------------------------------------------------------------------------------------------
     |
     */
	'purchaseCode' => env('PURCHASE_CODE', ''),
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Demo Website Info
     |-----------------------------------------------------------------------------------------------
     |
     */
    'demo' => [
    	'domain' => 'laraclassifier.com',
		'hosts'  => [
			'laraclassified.bedigit.com',
			'demo.laraclassifier.com',
		],
	],
	
	/*
     |-----------------------------------------------------------------------------------------------
     | App's Charset
     |-----------------------------------------------------------------------------------------------
	 | It is very important to not change this value
	 | because the UTF-8 charset is more universal and easier to use.
	 | Unless you know what you're doing.
	 |
     */
	'charset' => env('CHARSET', 'utf-8'),
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Database Charset & Collation
     |-----------------------------------------------------------------------------------------------
	 | utf8mb4 & utf8mb4_unicode_ci => MySQL v5.5.3 or greater
	 |
     */
	'database' => [
		'charset'   => [
			'default'     => 'utf8mb4',
			'recommended' => ['utf8', 'utf8mb4'],
		],
		'collation' => [
			'default'     => 'utf8mb4_unicode_ci',
			'recommended' => ['utf8_unicode_ci', 'utf8mb4_unicode_ci'],
		],
	],
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Default Logo
     |-----------------------------------------------------------------------------------------------
     |
     */
    'logo' => 'app/default/logo.png',
	'logoSize' => [
		'default' => [
			'width'  => 216,
			'height' => 40,
		],
		'max' => [
			'width'  => 430,
			'height' => 80,
		],
	],
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Default Favicon
     |-----------------------------------------------------------------------------------------------
     |
     */
    'favicon' => 'app/default/ico/favicon.png',
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Default Skins
     |-----------------------------------------------------------------------------------------------
     |
     */
	'skins' => [
		'default' => [
			'name'  => 'Default',
			'color' => null,
		],
		'blue' => [
			'name'  => 'Blue (~French Blue)',
			'color' => '#4682B4', // lcBlue (#4682B4) | frenchBlue (#0072B5)
		],
		'blueViolet' => [
			'name'  => 'Blue Violet',
			'color' => '#4D87E7',
		],
		'deepPurple' => [
			'name'  => 'Deep Purple',
			'color' => '#673AB7', // purple
		],
		'blueIzis' => [
			'name'  => 'Blue Izis',
			'color' => '#5B5EA6',
		],
		'green' => [
			'name'  => 'Green (~Emerald)',
			'color' => '#028e7b', // Emerald (green)
		],
		'red' => [
			'name'  => 'Red (Grape Fruit)',
			'color' => '#DA4453', // Grape Fruit (red)
		],
		'yellow' => [
			'name'  => 'Yellow (~Sun Flower)',
			'color' => '#FCBB42', // Sun Flower (yellow)
		],
		'riverside' => [
			'name'  => 'Riverside',
			'color' => '#4C6A92', // gray-violet (light)
		],
		'sargassoSea' => [
			'name'  => 'Sargasso Sea',
			'color' => '#485167', // gray-violet
		],
		'indigo' => [
			'name'  => 'Indigo',
			'color' => '#3F51B5', // purple-blue
		],
		'blueJeans' => [
			'name'  => 'Blue Jeans',
			'color' => '#4A89DC', // blue-jeans
		],
		'pink' => [
			'name'  => 'Pink',
			'color' => '#E91E63', // red-purple
		],
		'custom' => [
			'name'  => 'Custom',
			'color' => null,
		],
	],
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Default user profile picture
     |-----------------------------------------------------------------------------------------------
     |
     */
    'avatar' => [
		'default' => 'app/default/user.png',
	],
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Default listings picture & Default listings pictures sizes
     |-----------------------------------------------------------------------------------------------
     |
     */
	'picture' => [
		'default'   => 'app/default/picture.jpg',
		'versioned' => env('PICTURE_VERSIONED', false),
		'version'   => env('PICTURE_VERSION', 1),
		// Other types of picture (Not available in the 'Upload' options in the Admin Panel)
		'otherTypes' => [
			'favicon'   => [
				'width'     => 32,
				'height'    => 32,
			],
			'adminLogo' => [
				'width'  => 300,
				'height' => 40,
			],
			'user'  => [
				'width'  => 800,
				'height' => 800,
			],
			'company'  => [
				'width'  => 800,
				'height' => 800,
			],
			'bgHeader'  => [
				'width'  => 2000,
				'height' => 1000,
			],
			'bgBody'    => [
				'width'  => 2500,
				'height' => 2500,
			],
		],
	],
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Bootstrap-FileInput
     |-----------------------------------------------------------------------------------------------
     |
     */
	'fileinput' => ['theme' => 'bs5'],
	
	/*
     |-----------------------------------------------------------------------------------------------
     | TextToImage settings (Used to convert phone numbers to image)
     |-----------------------------------------------------------------------------------------------
     |
	 | format         : IMAGETYPE_JPEG, IMAGETYPE_PNG or IMAGETYPE_GIF
	 | color          : RGB (Example RGB: #FFFFFF = White)
	 | backgroundColor: RGBA or RGB (Examples RGBA: rgba(0,0,0,0.0) = Transparent)
	 | fontFamily     : Fonts Path: /packages/larapen/texttoimage/src/Libraries/font/
	 |
	 | NOTE: Transparent value is only available for PNG format.
	 |
     */
	'textToImage' => [
		'format'          => IMAGETYPE_PNG,
		'color'           => '#FFFFFF',
		'backgroundColor' => 'rgba(0,0,0,0.0)',
		'fontFamily'      => 'FiraSans-Regular.ttf',
		'fontSize'        => 12,
		'padding'         => 0,
		'quality'         => 100,
	],
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Countries SVG maps folder & URL base
     |-----------------------------------------------------------------------------------------------
     |
     */
    'maps' => [
        'path'    => public_path('images/maps') . DIRECTORY_SEPARATOR,
        'urlBase' => 'images/maps/',
    ],
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Optimize your URLs for SEO (for International website)
     |-----------------------------------------------------------------------------------------------
     |
     | You have to set the variables below in the /.env file:
     |
     | MULTI_COUNTRY_URLS=true (to enable the multi-country URLs optimization)
     | HIDE_DEFAULT_LOCALE_IN_URL=false (to show the default language code in the URLs)
     |
     */
    'multiCountryUrls' => env('MULTI_COUNTRY_URLS', false),
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Force links to use the HTTPS protocol
     |-----------------------------------------------------------------------------------------------
     |
     */
    'forceHttps' => env('FORCE_HTTPS', false),
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Headers - No Cache during redirect (Prevent Browser cache)
     |-----------------------------------------------------------------------------------------------
     | 'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', // IE.
	 |
     */
	'noCacheHeaders' => [
		'Cache-Control' => 'no-store, no-cache, must-revalidate', // HTTP 1.1.
		'Pragma'        => 'no-cache', // HTTP 1.0.
		'Expires'       => 'Sun, 02 Jan 1990 05:00:00 GMT', // Proxies. (Date in the past)
		'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
	],
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Performance (preventLazyLoading & httpRequestTimeout) & Debug Bar
     |-----------------------------------------------------------------------------------------------
	 | preventLazyLoading:
	 | Disable lazy loading (completely).
	 | Errors will be occurred if the Eloquent queries are not optimized.
	 | NOTE: Don't apply that on production to prevent exception errors.
	 |
	 | httpRequestTimeout: in seconds (1 recommended)
	 | Fire action when HTTP request running duration is more than specified value
	 |
	 | debugBar:
	 | In addition to this option, the Debug Bar will be enabled when APP_DEBUG is true
	 |
     */
	'performance' => [
		'preventLazyLoading' => env('PREVENT_LAZY_LOADING', false),
		'httpRequestTimeout' => env('HTTP_REQUEST_TIMEOUT', 1),
	],
	'debugBar' => env('DEBUG_BAR', false),
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Storing user's selected|preferred language in: cookie or session
     |-----------------------------------------------------------------------------------------------
     | Possible value: cookie, session
	 |
     */
	'storingUserSelectedLang' => 'cookie',
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Plugins Path & Namespace
     |-----------------------------------------------------------------------------------------------
     |
     */
    'plugin' => [
        'path'      => base_path('extras/plugins') . DIRECTORY_SEPARATOR,
        'namespace' => '\\extras\plugins\\',
    ],
	
	// Available only when the Multi-Domain plugin is installed
	'dmCountriesListAsHomepage' => env('DM_COUNTRIES_LIST_AS_HOMEPAGE'),
	
	'customizedViewPath' => 'views.',
	
    /*
     |-----------------------------------------------------------------------------------------------
     | Managing User's Fields
     |-----------------------------------------------------------------------------------------------
     | Disable (or not) these fields on the user's creation form
     | and on the listing's creation form when users are not logged in.
     |
     */
    'disable' => [
		'username' => env('DISABLE_USERNAME', true),
    ],

	/*
     |-----------------------------------------------------------------------------------------------
     | Display both auth fields
     |-----------------------------------------------------------------------------------------------
     | IMPORTANT:
	 | - The both auth fields (email and phone) cannot be displayed when both these fields need
	 |   to be verified. So to make work this option, you have to disable the email verification
	 |   or the phone verification option from the Admin panel.
	 | - By setting the option bellow to 'false', and since it's not possible to disable email field
	 |   to be auth field, it will be always possible to fill the email field in the related forms.
	 |   It's not the case for the phone field that can be disabled as auth field from the Admin panel.
     |
     */
	'displayBothAuthFields' => env('DISPLAY_BOTH_AUTH_FIELDS', true),
    
    /*
     |-----------------------------------------------------------------------------------------------
     | Custom Prefix for the new locations (Administrative Divisions) Codes
     |-----------------------------------------------------------------------------------------------
     |
     */
    'locationCodePrefix' => 'Z',
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Send Notifications On Error Exceptions
     |-----------------------------------------------------------------------------------------------
	 | This option will allow mail sending when error exceptions occurred.
	 | Note: The notifications will be sent in the email set in the:
	 |       "Settings -> General -> Application -> Email"
     |
     */
	'sendNotificationOnError' => env('SEND_NOTIFICATION_ON_ERROR', false),
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Date & Datetime Format
	 |-----------------------------------------------------------------------------------------------
     | Accepted formats:
     | - ISO Format: https://carbon.nesbot.com/docs/#api-localization
     | - PHP-specific dates formats
     |     |- DateTimeInterface::format():https://www.php.net/manual/en/datetime.format.php
     |     |- strftime(): https://www.php.net/manual/en/function.strftime.php
	 |
	 | Worldwide Date and Time Formats: https://www.timeandunits.com/time-and-date-format.html
	 |
     */
	'dateFormat' => [
		'default' => 'YYYY-MM-DD',
		'php'     => 'Y-m-d',
	],
	'datetimeFormat' => [
		'default' => 'YYYY-MM-DD HH:mm',
		'php'     => 'Y-m-d H:i',
	],

	/*
     |-----------------------------------------------------------------------------------------------
     | Permalinks & Extensions
     |-----------------------------------------------------------------------------------------------
     |
     */
	'permalink' => [
		'post' => [
			'{slug}-{hashableId}',
			'{slug}/{hashableId}',
			'{slug}_{hashableId}',
			'{hashableId}-{slug}',
			'{hashableId}/{slug}',
			'{hashableId}_{slug}',
			'{hashableId}',
		],
	],
	'permalinkExt' => [
		'',
		'.html',
		'.htm',
		'.php',
		'.asp',
		'.aspx',
		'.jsp',
	],
	'hashableIdPrefix' => '',
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Maintenance Mode IP Whitelist
     |-----------------------------------------------------------------------------------------------
	 |
	 | Add the MAINTENANCE_IP_ADDRESSES="" variable in the /.env file,
	 | with IP addresses separated by commas
	 |
	 | example: MAINTENANCE_IP_ADDRESSES="127.0.0.1, ::1, 175.12.103.14"
     |
     */
	'maintenanceIpAddresses' => array_map('trim', explode(',', env('MAINTENANCE_IP_ADDRESSES') ?? '')),
	
	/*
     |-----------------------------------------------------------------------------------------------
     | IP Address Link Creation Base
     |-----------------------------------------------------------------------------------------------
	 |
	 | example: https://whatismyipaddress.com/ip/127.0.0.1
     |
     */
	'ipLinkBase' => 'https://whatismyipaddress.com/ip/',
	
	/*
     |-----------------------------------------------------------------------------------------------
     | API Parameters
     |-----------------------------------------------------------------------------------------------
	 |
	 | api.token: Token to authenticate each HTTP request
	 | api.client: Type of HTTP requests (can be: none, curl)
	 | - none: To consume the API by making internal HTTP requests (using Laravel sub requests)
	 | - curl: To consume the API by making external HTTP requests (using CURL)
	 | api.timeout: HTTP timeout during API calls (in seconds)
	 | api.retry.sleep: HTTP retry times during API calls
	 | api.retry.sleep: HTTP retry sleep during API calls (in milliseconds)
	 |
     */
	'api' => [
		'token'   => env('APP_API_TOKEN'),
		'client'  => env('APP_HTTP_CLIENT', 'none'),
		'timeout' => env('APP_API_TIMEOUT', 60),
		'retry' => [
			'times' => env('APP_API_RETRY_TIMES', 3),
			'sleep' => env('APP_API_RETRY_SLEEP', 2000),
		],
		// Optional
		// Pattern: host:port (or) ip:port - Example: proxy.example.tld:3128
		'proxy' => env('APP_API_PROXY'),
	],
	
	/*
     |-----------------------------------------------------------------------------------------------
     | Packages Options
     |-----------------------------------------------------------------------------------------------
	 |
	 | package.type.promotion: The promotion packages are about Post
	 | package.type.promotion: The subscription packages are about User
	 |
     */
    'package' => [
	    'type' => [
		    'promotion'    => 'App\Models\Post',
		    'subscription' => 'App\Models\User',
	    ],
    ],
	
];
