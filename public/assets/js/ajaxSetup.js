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

$(document).ready(function () {
	
	let xhrOptions = {
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
		},
		async: true,
		cache: true,
		xhrFields: {withCredentials: true},
		crossDomain: true
	};
	
	/* Ajax's calls should always have the CSRF token attached to them; otherwise they won't work */
	let token = $('meta[name="csrf-token"]').attr('content');
	if (token) {
		xhrOptions.headers['X-CSRF-TOKEN'] = token;
	}
	
	$.ajaxSetup(xhrOptions);
	
});
