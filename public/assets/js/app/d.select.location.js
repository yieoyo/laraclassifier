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

/* Prevent errors, If these variables are missing. */
if (typeof countryCode === 'undefined') {
	var countryCode = '--';
}
if (typeof adminType === 'undefined') {
	var adminType = 0;
}
if (typeof selectedAdminCode === 'undefined') {
	var selectedAdminCode = 0;
}
if (typeof cityId === 'undefined') {
	var cityId = 0;
}
if (typeof languageCode === 'undefined') {
	var languageCode = 'en';
}

var select2Language = languageCode;
if (typeof langLayout !== 'undefined' && typeof langLayout.select2 !== 'undefined') {
	select2Language = langLayout.select2;
}

$(document).ready(function () {
	
	/* The adminType possible values are: 0, 1 or 2. Check the 'admin_type' enum column in 'countries' table in DB */
	if ([0, 1, 2].includes(adminType) !== true && ['0', '1', '2'].includes(adminType) !== true) {
		adminType = 0;
	}
	
	/* Get and Bind administrative divisions */
	getAdminDivisions(countryCode, adminType, selectedAdminCode);
	$(document).on('change', '#countryCode', function (e) {
		let thisEl = $(this);
		countryCode = thisEl.val();
		adminType = thisEl.find(':selected').data('adminType');
		getAdminDivisions(countryCode, adminType, 0, true);
	});
	
	/* Get and Bind the selected city */
	if (adminType == 0) {
		getSelectedCity(countryCode, cityId);
	}
	
	/* Get AJAX's URL */
	let url = function () {
		/* Get the current country code */
		var selectedCountryCode = $('#countryCode').val();
		if (typeof selectedCountryCode !== "undefined") {
			countryCode = selectedCountryCode;
		}
		
		/* Get the current admin code */
		var selectedAdminCode = $('#adminCode').val();
		if (typeof selectedAdminCode === "undefined") {
			selectedAdminCode = 0;
		}
		
		return siteUrl + '/ajax/countries/' + strToLower(countryCode) + '/admins/' + adminType + '/' + strToLower(selectedAdminCode) + '/cities';
	};
	
	/* Get and Bind cities */
	$('#cityId').select2({
		language: select2Language,
		ajax: {
			url: url,
			dataType: 'json',
			delay: 50,
			data: function (params) {
				return {
					languageCode: languageCode,
					q: params.term, /* search term */
					page: params.page
				};
			},
			processResults: function (data, params) {
				/*
				// parse the results into the format expected by Select2
				// since we are using custom formatting functions we do not need to
				// alter the remote JSON data, except to indicate that infinite
				// scrolling can be used
				*/
				params.page = params.page || 1;
				
				return {
					results: data.items,
					pagination: {
						more: (params.page * 10) < data.totalEntries
					}
				};
			},
			error: function (jqXHR, status, error) {
				showErrorModal(jqXHR, error);
				
				return { results: [] }; /* Return dataset to load after error */
			},
			cache: true
		},
		escapeMarkup: function (markup) {
			return markup;
		}, /* let our custom formatter work */
		minimumInputLength: 2,
		templateResult: function (data) {
			return data.text;
		},
		templateSelection: function (data, container) {
			return data.text;
		}
	});
	
});

/**
 * Get and Bind Administrative Divisions
 *
 * @param countryCode
 * @param adminType
 * @param selectedAdminCode
 * @param countryChanged
 * @returns {boolean|*}
 */
function getAdminDivisions(countryCode, adminType, selectedAdminCode, countryChanged = false) {
	if (countryCode === 0 || countryCode === '') return false;
	
	let locationBoxEl = $('#locationBox');
	if (isDefined(locationBoxEl)  && locationBoxEl != null) {
		if ([1, 2].includes(adminType) !== true && ['1', '2'].includes(adminType) !== true) {
			$('#adminCode').empty().append('<option value="0">' + lang.select.admin + '</option>').val('0').trigger('change');
			locationBoxEl.hide();
			
			return 0;
		} else {
			locationBoxEl.show();
		}
	}
	
	let url = siteUrl + '/ajax/countries/' + strToLower(countryCode) + '/admins/' + adminType + '?languageCode=' + languageCode;
	
	let ajax = $.ajax({
		method: 'GET',
		url: url
	});
	ajax.done(function (xhr) {
		/* Init. */
		let adminCodeEl = $('#adminCode');
		adminCodeEl.empty().append('<option value="0">' + lang.select.admin + '</option>').val('0').trigger('change');
		$('#cityId').empty().append('<option value="0">' + lang.select.city + '</option>').val('0').trigger('change');
		
		/* Bind data into Select list */
		if (typeof xhr.error !== 'undefined') {
			adminCodeEl.find('option').remove().end().append('<option value="0"> ' + xhr.error.message + ' </option>');
			adminCodeEl.addClass('is-invalid');
			return false;
		} else {
			adminCodeEl.removeClass('is-invalid');
		}
		
		if (typeof xhr.data === 'undefined') {
			return false;
		}
		$.each(xhr.data, function (key, item) {
			if (selectedAdminCode == item.code) {
				adminCodeEl.append('<option value="' + item.code + '" selected="selected">' + item.name + '</option>');
			} else {
				adminCodeEl.append('<option value="' + item.code + '">' + item.name + '</option>');
			}
		});
		
		/* Get and Bind the selected city */
		getSelectedCity(countryCode, cityId, countryChanged);
	});
	ajax.fail(function(xhr) {
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error');
		}
	});
	
	return selectedAdminCode;
}

/**
 * Get and Bind (Selected) City by ID
 *
 * @param countryCode
 * @param cityId
 * @param countryChanged
 * @returns {number}
 */
function getSelectedCity(countryCode, cityId, countryChanged = false) {
	/* Clear by administrative divisions selection */
	$('#adminCode').on('click, change', function () {
		$('#cityId').empty().append('<option value="0">' + lang.select.city + '</option>').val('0').trigger('change');
	});
	
	if (isEmpty(cityId) || countryChanged) {
		return 0;
	}
	
	let url = siteUrl + '/ajax/countries/' + strToLower(countryCode) + '/cities/' + cityId + '?languageCode=' + languageCode;
	
	let ajax = $.ajax({
		method: 'GET',
		url: url
	});
	ajax.done(function (xhr) {
		$('#cityId').empty().append('<option value="' + xhr.id + '">' + xhr.text + '</option>').val(xhr.id).trigger('change');
		return xhr.id;
	});
	ajax.fail(function (xhr) {
		$('#cityId').empty().append('<option value="0">' + lang.select.city + '</option>').val('0').trigger('change');
		
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error');
		}
		
		return 0;
	});
	
	return 0;
}
