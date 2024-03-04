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

/* Polyfill (https://en.wikipedia.org/wiki/Polyfill_(programming)) */
/* Array.isArray() */
if (!Array.isArray) {
	Array.isArray = function(arg) {
		return Object.prototype.toString.call(arg) === '[object Array]';
	};
}
/* Number.isNaN() */
Number.isNaN = Number.isNaN || function(value) {
	return typeof value === 'number' && isNaN(value);
}
/* Number.isInteger() */
Number.isInteger = Number.isInteger || function(value) {
	return typeof value === 'number' && isFinite(value) && Math.floor(value) === value;
};
/* Number.isSafeInteger() */
if (!Number.MAX_SAFE_INTEGER) {
	Number.MAX_SAFE_INTEGER = 9007199254740991; /* Math.pow(2, 53) - 1; */
}
Number.isSafeInteger = Number.isSafeInteger || function (value) {
	return Number.isInteger(value) && Math.abs(value) <= Number.MAX_SAFE_INTEGER;
};
/* str.endsWith() */
if (!String.prototype.endsWith) {
	String.prototype.endsWith = function(searchString, position) {
		var subjectString = this.toString();
		if (typeof position !== 'number' || !isFinite(position) || Math.floor(position) !== position || position > subjectString.length) {
			position = subjectString.length;
		}
		position -= searchString.length;
		var lastIndex = subjectString.lastIndexOf(searchString, position);
		return lastIndex !== -1 && lastIndex === position;
	};
}
/* --- */

if (typeof refreshBtnText === 'undefined') {
	var refreshBtnText = 'Refresh';
}

preventPageLoadingInIframe();

$(document).ready(function () {
	/* Confirm Actions Links */
	$(document).on('click', '.confirm-simple-action', function(e) {
		e.preventDefault(); /* prevents a submitting or reloading */
		
		try {
			let showCancelInfo = false;
			if (isAdminPanel) {
				if (isDemoDomain()) {
					return false;
				}
				showCancelInfo = true;
			}
			
			confirmSimpleAction(this, showCancelInfo);
		} catch (e) {
			jsAlert(e, 'error', false);
		}
	});
});

/**
 * Prevent the page to load in IFRAME by redirecting it to the top-level window
 */
function preventPageLoadingInIframe() {
	try {
		if (window.top.location !== window.location) {
			window.top.location.replace(siteUrl);
		}
	} catch (e) {
		console.error(e);
	}
}

/**
 * Open Login Modal
 */
function openLoginModal() {
	let quickLoginEl = document.getElementById('quickLogin');
	
	if (typeof(quickLoginEl) !== 'undefined' && quickLoginEl !== null) {
		let loginModal = new bootstrap.Modal(quickLoginEl, {});
		loginModal.show();
	}
}

/**
 * Set|Create cookie
 * @param name
 * @param value
 * @param expires (in Minutes)
 */
function createCookie(name, value, expires = null) {
	/* Get app's cookie parameters */
	expires = (!isEmpty(expires)) ? expires : cookieParams.expires;
	let path = cookieParams.path;
	let domain = cookieParams.domain;
	let secure = cookieParams.secure;
	let sameSite = cookieParams.sameSite;
	
	/* Build JS cookie parts string */
	// let dataStr = name + '=' + value;
	let dataStr = encodeURIComponent(name) + "=" + encodeURIComponent(value);
	let expiresStr;
	if (expires) {
		let date = new Date();
		date.setTime(date.getTime() + (expires * 60 * 1000));
		expiresStr = '; expires=' + date.toUTCString();
	} else {
		expiresStr = '';
	}
	let pathStr = path ? '; path=' + path : '';
	let domainStr = domain ? '; domain=' + domain : '';
	let secureStr = secure ? '; secure' : '';
	let sameSiteStr = sameSite ? '; SameSite=' + sameSite : '';
	
	document.cookie = dataStr + expiresStr + pathStr + domainStr + secureStr + sameSiteStr;
}

/**
 * Get|Read cookie
 * @param name
 * @returns {string|null}
 */
function readCookie(name) {
	let encName = encodeURIComponent(name) + "=";
	let ca = document.cookie.split(';');
	
	for (let i = 0; i < ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) === ' ') {
			c = c.substring(1, c.length);
		}
		if (c.indexOf(encName) === 0) {
			return decodeURIComponent(c.substring(encName.length, c.length));
		}
	}
	
	return null;
}

/**
 * Check if cookie exists
 * @param name
 * @returns {boolean}
 */
function cookieExists(name) {
	return isFilled(readCookie(name));
}

/**
 * Delete cookie
 * @param name
 */
function eraseCookie(name) {
	createCookie(name, '', -1);
}

/**
 * Redirect URL
 * @param url
 */
function redirect(url) {
	window.location.replace(url);
	window.location.href = url;
}

/**
 * Raw URL encode
 * @param value
 * @returns {string}
 */
function rawurlencode(value) {
	value = (value + '').toString();
	
	return encodeURIComponent(value)
		.replace(/!/g, '%21')
		.replace(/'/g, '%27')
		.replace(/\(/g, '%28')
		.replace(/\)/g, '%29')
		.replace(/\*/g, '%2A');
}

/**
 * Check if a variable is defined
 *
 * @param value
 * @returns {boolean}
 */
function isDefined(value) {
	return (typeof value !== 'undefined');
}

/**
 * Check if a value is undefined, null, 0, or blank
 *
 * @param value
 * @returns {boolean}
 */
function isEmpty(value) {
	if (isBlank(value)) {
		return true;
	}
	
	if (isNumeric(value, true)) {
		return value === 0 || value === '0';
	}
	
	return false;
}

/**
 * Check if a variable is blank
 * Support: undefined, null, array, object, date, number and string
 *
 * @param value
 * @returns {boolean}
 */
function isBlank(value) {
	if (!isDefined(value) || value === null) {
		return true;
	}
	
	if (isArray(value)) {
		return value.length === 0;
	}
	
	if (value instanceof Date) {
		return false;
	}
	
	if (isObject(value)) {
		/* 'value' is a JS HTML element */
		if (isDefined(value.nodeName)) {
			return value.nodeName.length === 0;
		}
		
		/* 'value' is a jQuery HTML element */
		if (isDefined(value.get)) {
			return value.get(0).tagName.length === 0;
		}
		
		/* Classic JSON object */
		return Object.keys(value).length === 0;
	}
	
	return [''].includes(value);
}

/**
 * Check if a variable is filled
 *
 * @param value
 * @returns {boolean}
 */
function isFilled(value) {
	return !isBlank(value);
}

/**
 * Check if a string is blank or null
 *
 * @param value
 * @returns {boolean}
 */
function isBlankString(value) {
	return (isEmpty(value) || /^\s*$/.test(value));
}

/**
 * Check if 'value' is a string
 *
 * @param value
 * @returns {boolean}
 */
function isString(value) {
	if (isDefined(value)) {
		if (typeof value === 'string' || value instanceof String) {
			if (value !== '') {
				return true;
			}
		}
	}
	
	return false;
}

/**
 * Check if 'value' is an array
 *
 * @param value
 * @returns {arg is any[]}
 */
function isArray(value) {
	return Array.isArray(value);
}

/**
 * Check if 'value' is an object
 * Note: Since 'null' is an object in JS, exclude it
 *
 * @param value
 * @returns {boolean}
 */
function isObject(value) {
	return (typeof value === 'object' && value !== null);
}

/**
 * Check if 'value' is numeric (Integer or Float)
 * Note: Second argument to check if string containing an integer
 *
 * @param value
 * @param checkIfStringContainingAnInteger
 * @returns {boolean}
 */
function isNumeric(value, checkIfStringContainingAnInteger = false) {
	let isNumeric = (typeof value === 'number' && !Number.isNaN(value));
	
	if (checkIfStringContainingAnInteger) {
		let parsedValue;
		if (!isNumeric) {
			parsedValue = parseInt(value, 10);
			isNumeric = (value == parsedValue && !Number.isNaN(parsedValue));
		}
		if (!isNumeric) {
			parsedValue = parseFloat(value);
			isNumeric = (value == parsedValue && !Number.isNaN(parsedValue));
		}
	}
	
	return isNumeric;
}

/**
 * Check if 'value' is an integer (strictly)
 *
 * @param value
 * @returns {boolean}
 */
function isInt(value) {
	return isNumeric(value) && Number.isSafeInteger(value);
}

/**
 * Check if 'value' is a float number (strictly)
 *
 * @param value
 * @returns {boolean}
 */
function isFloat(value) {
	return isNumeric(value) && !Number.isInteger(value);
}

/**
 * Check if a string is JSON or not
 *
 * @param value
 * @returns {boolean}
 */
function isJson(value) {
	if (!isString(value)) {
		return false;
	}
	
	try {
		JSON.parse(value);
	} catch (e) {
		return false;
	}
	
	return true;
}

/**
 * Convert a string to lowercase
 *
 * @param value
 * @returns {string}
 */
function strToLower(value) {
	if (isString(value)) {
		value = value.toLowerCase();
	}
	
	return value;
}

/**
 * Convert a string to uppercase
 *
 * @param value
 * @returns {string}
 */
function strToUpper(value) {
	if (isString(value)) {
		value = value.toUpperCase();
	}
	
	return value;
}

/**
 * sleep() version in JS
 * https://stackoverflow.com/a/39914235
 *
 * Usage:
 * await sleep(2000);
 * or
 * sleep(2000).then(() => {
 *     // Do something after the sleep!
 * });
 *
 * @param ms
 * @returns {Promise<unknown>}
 */
function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Array each
 *
 * Usage:
 * forEach(array, function(item, i) {});
 *
 * @param array
 * @param fn
 */
function forEach(array, fn) {
	for (var i = 0; i < array.length; i++) {
		fn(array[i], i);
	}
}

/**
 * Array map
 *
 * Usage:
 * map(array, function(value, index) {});
 *
 * @param arr
 * @param fn
 * @returns {*[]}
 */
function map(arr, fn) {
	var results = [];
	for (var i = 0; i < arr.length; i++) {
		results.push(fn(arr[i], i));
	}
	return results;
}

/**
 * Confirm Simple Action (Links or forms without AJAX)
 * Usage: Add 'confirm-simple-action' in the element class attribute
 *
 * @param clickedEl
 * @param showCancelInfo
 * @param cancelInfoAutoDismiss
 * @returns {boolean}
 */
function confirmSimpleAction(clickedEl, showCancelInfo=true, cancelInfoAutoDismiss=true) {
	if (typeof Swal === 'undefined') {
		return false;
	}
	
	Swal.fire({
		text: langLayout.confirm.message.question,
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: langLayout.confirm.button.yes,
		cancelButtonText: langLayout.confirm.button.no
	}).then((result) => {
		if (result.isConfirmed) {
			
			try {
				if ($(clickedEl).is('a')) {
					let actionUrl = $(clickedEl).attr('href');
					if (actionUrl !== 'undefined') {
						console.log(actionUrl);
						redirect(actionUrl);
					}
				} else {
					let actionForm = $(clickedEl).parents('form:first');
					$(actionForm).submit();
				}
			} catch (e) {
				console.log(e);
			}
			
		} else if (result.dismiss === Swal.DismissReason.cancel) {
			if (showCancelInfo === true) {
				jsAlert(langLayout.confirm.message.cancel, 'info', cancelInfoAutoDismiss);
			}
		}
	});
	
	return false;
}

/**
 * Show JS Alert Messages (Swal)
 *
 * @param message
 * @param type
 * @param cancelAlertAutoDismiss
 * @param reloadPageIfConfirmed
 * @param blockUi
 * @returns {boolean}
 */
function jsAlert(message, type='info', cancelAlertAutoDismiss=true, reloadPageIfConfirmed=false, blockUi=false) {
	if (typeof Swal === 'undefined') {
		return false;
	}
	
	let alertParams = {
		html: message,
		icon: type,
		position: 'center'
	};
	
	if (cancelAlertAutoDismiss === true) {
		alertParams.showCancelButton = false;
		alertParams.showConfirmButton = false;
		alertParams.timer = 3000;
	} else {
		alertParams.showCancelButton = true;
		if (reloadPageIfConfirmed === true) {
			alertParams.confirmButtonText = refreshBtnText;
		} else {
			alertParams.confirmButtonText = langLayout.confirm.button.ok;
			alertParams.cancelButtonText = langLayout.confirm.button.cancel;
		}
	}
	if (blockUi) {
		alertParams.showCancelButton = false;
		alertParams.allowOutsideClick = false;
		alertParams.allowEscapeKey = false;
	}
	
	let alertObj = Swal.fire(alertParams);
	
	if (reloadPageIfConfirmed === true) {
		alertObj.then((result) => {
			if (result.isConfirmed) {
				/* Reload Page */
				/* JS 1.1 - Does not create a history entry */
				window.location.replace(window.location.pathname + window.location.search + window.location.hash);
				
				/* JS 1.0 - Creates a history entry */
				window.location.href = window.location.pathname + window.location.search + window.location.hash;
			}
		});
	}
}

/**
 * Show JS Alert Messages (PNotify)
 * PNotify: https://github.com/sciactive/pnotify
 *
 * @param message
 * @param type
 * @param icon
 * @returns {boolean}
 */
function pnAlert(message, type='notice', icon=null) {
	if (typeof PNotify === 'undefined') {
		return false;
	}
	
	if (type === 'warning') {
		type = 'notice';
	}
	
	if (typeof window.stackTopRight === 'undefined') {
		window.stackTopRight = new PNotify.Stack({
			dir1: 'down',
			dir2: 'left',
			firstpos1: 25,
			firstpos2: 25,
			spacing1: 10,
			spacing2: 25,
			modal: false,
			maxOpen: Infinity
		});
	}
	let alertParams = {
		text: message,
		type: type,
		stack: window.stackTopRight
	};
	if (icon !== null) {
		alertParams.icon = icon;
	}
	
	new PNotify.alert(alertParams);
}

/**
 * Get jQuery AJAX Error Message
 *
 * @param xhr
 * @returns {null|*}
 */
function getJqueryAjaxError(xhr) {
	/* Show an alert with the result */
	/* console.log(xhr); */
	
	let message;
	
	if (isDefined(xhr.responseText)) {
		message = xhr.responseText;
	}
	
	if (isDefined(xhr.responseJSON) && isDefined(xhr.responseJSON.message)) {
		message = xhr.responseJSON.message;
	}
	
	if (!isDefined(message)) {
		return null;
	}
	
	return message;
}

/**
 * Get Query String (as JSON object)
 *
 * Example:
 * getQueryParams('https://foo.tld/search?q=node&page=2')
 * => { q: 'node', page: '2' }
 *
 * @param url
 * @returns {{}}
 */
function getQueryParams(url = window.location.href) {
	const paramArr = url.slice(url.indexOf('?') + 1).split('&');
	const params = {};
	paramArr.map(param => {
		const [key, val] = param.split('=');
		params[key] = decodeURIComponent(val);
	})
	return params;
}

/**
 * Get URL query string by name
 *
 * Usage:
 * query string: ?foo=lorem&bar=&baz
 * var foo = getQueryParameter('foo'); // "lorem"
 * var bar = getQueryParameter('bar'); // "" (present with empty value)
 * var baz = getQueryParameter('baz'); // "" (present with no value)
 * var qux = getQueryParameter('qux'); // null (absent)
 *
 * @param name
 * @param url
 * @returns {string|null}
 */
function getQueryParameter(name, url = window.location.href) {
	name = name.replace(/[\[\]]/g, '\\$&');
	var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
		results = regex.exec(url);
	if (!results) return null;
	if (!results[2]) return '';
	return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

/**
 * Check if URL query string has a specific parameter
 *
 * @param name
 * @param url
 * @returns {boolean}
 */
function hasQueryParameter(name, url = window.location.href) {
	return getQueryParameter(name, url) !== null;
}

/**
 * Remove a parameter from a URL query string
 *
 * @param name
 * @param url
 * @returns {string}
 */
function removeURLParameter(name, url = window.location.href) {
	/* prefer to use l.search if you have a location/link object */
	var urlParts = url.split('?');
	if (urlParts.length >= 2) {
		var prefix = encodeURIComponent(name) + '=';
		var pars = urlParts[1].split(/[&;]/g);
		
		/* reverse iteration as may be destructive */
		for (var i = pars.length; i-- > 0;) {
			/* idiom for string.startsWith */
			if (pars[i].lastIndexOf(prefix, 0) !== -1) {
				pars.splice(i, 1);
			}
		}
		
		return urlParts[0] + (pars.length > 0 ? '?' + pars.join('&') : '');
	}
	return url;
}

/**
 * Check user is on demo domain
 * @returns {boolean}
 */
function isDemoDomain() {
	try {
		if (demoMode) {
			jsAlert(demoMessage, 'error');
			
			return true;
		}
	} catch (e) {
		jsAlert(e, 'error', false);
		
		return true;
	}
	
	return false;
}
