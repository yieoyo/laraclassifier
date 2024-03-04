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

if (typeof defaultAdminType === 'undefined') {
	var defaultAdminType = 0;
}
if (typeof defaultAdminCode === 'undefined') {
	var defaultAdminCode = 0;
}
if (typeof loadingWd === 'undefined') {
	var loadingWd = 'Loading...';
}

$(document).ready(function () {
	
	$(document).on('change', '#countryCode', function (e) {
		countryCode = changeCountry(countryCode, this);
	});
	
	$('#browseLocations').on('show.bs.modal', function (e) {
		let triggerEl = $(e.relatedTarget); /* Button that triggered the modal */
		let params = getLocationsBrowsingParameters(countryCode, triggerEl, defaultAdminType, defaultAdminCode);
		browseLocations(params);
	});
	$(document).on('click submit', '#modalQuerySearchBtn', function (e) {
		e.preventDefault(); /* prevents submit or reload */
		
		let params = getLocationsBrowsingParameters(countryCode, this);
		browseLocations(params);
		
		return false;
	});
	$(document).on('click', '#browseLocations .is-admin, #browseLocations .page-link', function (e) {
		e.preventDefault();
		
		let params = getLocationsBrowsingParameters(countryCode, this);
		browseLocations(params);
		
		return false;
	});
	$(document).on('click', '#modalQueryClearBtn', function (e) {
		e.preventDefault();
		
		let queryEl = $('#modalQuery');
		let query = queryEl.val();
		if (!isEmpty(query)) {
			queryEl.val('');
			let params = getLocationsBrowsingParameters(countryCode, this);
			browseLocations(params);
		}
		queryEl.focus();
		
		return false;
	});
	
});

/**
 * Get & update the locations browsing parameters
 *
 * @param countryCode
 * @param jsThis
 * @param adminType
 * @param adminCode
 * @returns {{}|{countryChanged: (*|number), currSearch: (*|jQuery), adminType: *, query: string, adminCode: *, _token: (*|jQuery), cityId: number, languageCode: string, url: string}}
 */
function getLocationsBrowsingParameters(countryCode, jsThis = null, adminType = null, adminCode = null) {
	let params = {};
	
	/* Check required variables */
	if (typeof languageCode === 'undefined' || typeof countryCode === 'undefined') {
		return params;
	}
	
	let thisEl = null;
	let thisElTagName = '';
	let thisElClass = '';
	if (isDefined(jsThis) && jsThis !== null) {
		thisEl = $(jsThis);
		thisElTagName = thisEl.get(0).tagName.toLowerCase();
		thisElClass = thisEl.attr('class');
		thisElClass = (isDefined(thisElClass) && thisElClass != null) ? thisElClass : '';
	}
	
	/* Location's modal fields */
	let countryChangedEl = $('#modalCountryChanged');
	let urlEl = $('#modalUrl');
	let adminTypeEl = $('#modalAdminType');
	let adminCodeEl = $('#modalAdminCode');
	let queryEl = $('#modalQuery');
	let query = '';
	
	/* Main form fields */
	let cityIdEl = $('#cityId');
	let cityId = 0;
	
	let isGoingToBaseUrlTrigger = (
		isDefined(thisElTagName)
		&& isDefined(thisElClass)
		&& thisElTagName != null
		&& thisElClass != null
		&& thisElTagName === 'a'
		&& thisElClass.indexOf('is-admin') !== -1
		&& thisElClass.indexOf('go-base-url') !== -1
	);
	
	/* Retrieve the adminType & the adminCode */
	adminType = !isEmpty(adminType) ? adminType : adminTypeEl.val();
	adminType = !isEmpty(adminType) ? adminType : 0;
	adminCode = !isEmpty(adminCode) ? adminCode : 0;
	
	/* Base URL */
	let baseUrl = '';
	let url = '';
	
	if (!isEmpty(adminType)) {
		baseUrl = siteUrl + '/ajax/locations/' + strToLower(countryCode) + '/admins/' + adminType;
	} else {
		baseUrl = siteUrl + '/ajax/locations/' + strToLower(countryCode) + '/cities';
	}
	
	if (isGoingToBaseUrlTrigger) {
		let goBaseUrl = null;
		if (thisEl !== null) {
			goBaseUrl = thisEl.data('url');
		}
		url = !isEmpty(goBaseUrl) ? goBaseUrl : baseUrl;
		urlEl.val(url);
		queryEl.val('');
	} else {
		if (thisElTagName === 'select') {
			adminCode = !isEmpty(adminCode) ? adminCode : adminCodeEl.val();
			adminCode = !isEmpty(adminCode) ? adminCode : 0;
		}
		
		if (thisEl !== null) {
			let dataAdminCode = thisEl.data('adminCode');
			if (isDefined(dataAdminCode)) {
				adminCode = dataAdminCode;
			}
			
			if (thisElTagName === 'select') {
				$('#modalTriggerName').val(thisElTagName);
			}
			
			if (thisElTagName === 'a') {
				$('.tooltip.show.modal-tooltip').hide();
				if (thisElClass.indexOf('is-admin') !== -1) {
					queryEl.val('');
				}
			}
			
			if (!isDefined(adminCode) || isEmpty(adminCode)) {
				if (thisElTagName === 'a') {
					if ((thisElClass.indexOf('is-admin') !== -1) || (thisElClass.indexOf('page-link') !== -1)) {
						url = thisEl.data('url');
						if (!isEmpty(url)) {
							let urlToSave = removeURLParameter('page', url);
							urlEl.val(urlToSave);
							
							let urlArr = url.split('/');
							if (url.indexOf(baseUrl) !== -1) {
								let tmpPart = isDefined(urlArr[7]) ? urlArr[7] : '';
								let tmpArr = tmpPart.split('?');
								adminType = (isDefined(tmpArr[0]) && !isEmpty(tmpArr[0])) ? tmpArr[0] : 0;
								adminCode = 0;
							} else {
								adminType = isDefined(urlArr[7]) ? urlArr[7] : 0;
								adminCode = isDefined(urlArr[8]) ? urlArr[8] : 0;
							}
						}
					}
				} else {
					url = urlEl.val();
					adminCode = adminCodeEl.val();
				}
			}
			if (thisElTagName === 'button' || thisElTagName === 'a') {
				query = queryEl.val();
				if (thisElTagName === 'button') {
					url = removeURLParameter('page', url);
				}
			}
			if (thisElTagName === 'input' && thisEl.attr('type') === 'text') {
				query = thisEl.val();
			}
			
			/* Get the (eventual) current city's ID from bootstrap modal link */
			if (thisElTagName === 'a' && thisEl.attr('href') === '#browseLocations') {
				cityId = thisEl.data('cityId');
			}
		}
		
		/* Get the (eventual) current city's ID from a main form's city's field */
		if (!isDefined(cityId) || isEmpty(cityId)) {
			cityId = (isDefined(cityIdEl) && cityIdEl != null) ? cityIdEl.val() : 0;
			cityId = isDefined(cityId) ? cityId : 0;
		}
		
		if (isEmpty(url)) {
			url = baseUrl;
			if (!isEmpty(adminType) && !isEmpty(adminCode)) {
				url = siteUrl + '/ajax/locations/' + strToLower(countryCode) + '/admins/' + adminType + '/' + adminCode + '/cities';
			}
		}
		
		/* Save the adminType & the adminCode */
		adminTypeEl.val(adminType);
		adminCodeEl.val(adminCode);
	}
	
	let countryChanged = (isDefined(countryChangedEl) && countryChangedEl != null) ? countryChangedEl.val() : 0;
	
	params = {
		languageCode: languageCode,
		countryChanged: countryChanged,
		url: url,
		adminType: adminType,
		adminCode: adminCode,
		query: query,
		cityId: cityId,
		currSearch: $('#currSearch').val(),
		_token: $('input[name=_token]').val(),
	};
	
	return params;
}

/**
 * Get & append cities of a given administrative division's code
 *
 * @param params
 */
function browseLocations(params) {
	/* console.log(params); */
	/* Check required variables */
	if (
		typeof params.languageCode === 'undefined'
		|| typeof params.countryChanged === 'undefined'
		|| typeof params.url === 'undefined'
		|| typeof params.adminType === 'undefined'
		|| typeof params.adminCode === 'undefined'
		|| typeof params.query === 'undefined'
		|| typeof params.cityId === 'undefined'
		|| typeof params.currSearch === 'undefined'
		|| typeof params._token === 'undefined'
	) {
		return false;
	}
	
	/* Elements to update */
	let locationsListEl = $('#locationsList');
	let locationsTitleEl = $('#locationsTitle');
	
	let ajax = $.ajax({
		method: 'POST',
		url: params.url,
		data: {
			'languageCode': params.languageCode,
			'countryChanged': params.countryChanged,
			'adminType': params.adminType,
			'adminCode': params.adminCode,
			'query': params.query,
			'cityId': params.cityId,
			'currSearch': params.currSearch,
			'_token': params._token
		},
		beforeSend: function () {
			/*
			let spinner = '<div class="d-flex align-items-center">\n' +
				'  <strong>' + loadingWd + '</strong>\n' +
				'  <div class="spinner-border ms-auto" role="status" aria-hidden="true"></div>\n' +
				'</div>';
			locationsListEl.html(spinner);
			*/
			
			locationsListEl.empty().addClass('py-4').busyLoad('hide');
			locationsListEl.busyLoad('show', {
				text: loadingWd,
				custom: createCustomSpinnerEl(),
				background: '#fff',
				containerItemClass: 'm-5',
			});
		}
	});
	ajax.done(function (xhr) {
		locationsListEl.removeClass('py-4').busyLoad('hide');
		
		try {
			if (typeof xhr.locationsTitle == 'undefined' || typeof xhr.locationsContent == 'undefined') {
				locationsListEl.empty();
				
				return false;
			}
			
			/* Update modal content */
			locationsTitleEl.html(xhr.locationsTitle);
			locationsListEl.html(xhr.locationsContent);
			
			/* Enable the tooltip */
			/* To prevent the tooltip in bootstrap doesn't work after ajax, use selector on exist element like body */
			let bodyEl = $('body');
			bodyEl.tooltip({selector: '[data-bs-toggle="tooltip"]'});
		} catch (e) {
			jsAlert(e, 'error', false, true);
		}
	});
	ajax.fail(function (xhr) {
		locationsListEl.empty();
		
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error', false, true);
			
			/* Close the Modal */
			let modalEl = document.querySelector('#browseLocations');
			if (typeof modalEl !== 'undefined' && modalEl !== null) {
				let modalObj = bootstrap.Modal.getInstance(modalEl);
				if (modalObj !== null) {
					modalObj.hide();
				}
			}
		}
	});
}

/**
 * Change Country
 *
 * @param countryCode
 * @param jsThis
 * @returns {*}
 */
function changeCountry(countryCode, jsThis) {
	let thisEl = $(jsThis);
	
	let newCountryCode = thisEl.val();
	let newAdminType = thisEl.find(':selected').data('adminType');
	
	if (isEmpty(newCountryCode)) {
		return countryCode;
	}
	
	/* Location's modal fields */
	let countryChangedEl = $('#modalCountryChanged');
	let urlEl = $('#modalUrl');
	let adminTypeEl = $('#modalAdminType');
	let adminCodeEl = $('#modalAdminCode');
	let queryEl = $('#modalQuery');
	
	/* Main form fields */
	let cityIdEl = $('#cityId');
	
	/* Reset all fields and match them with the new country */
	countryChangedEl.val(1);
	let baseUrl = '';
	if (!isEmpty(newAdminType)) {
		baseUrl = siteUrl + '/ajax/locations/' + strToLower(newCountryCode) + '/admins/' + newAdminType;
	} else {
		baseUrl = siteUrl + '/ajax/locations/' + strToLower(newCountryCode) + '/cities';
	}
	urlEl.val(baseUrl);
	adminTypeEl.val(!isEmpty(newAdminType) ? newAdminType : 0);
	adminCodeEl.val(0);
	queryEl.val('');
	cityIdEl.empty().append('<option value="0">' + lang.select.city + '</option>').val('0').trigger('change');
	
	return newCountryCode;
}
