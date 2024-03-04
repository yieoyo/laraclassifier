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
	
	/* Handle the locations modal */
	let locationsModal = null;
	let browseLocationsEl = document.getElementById('browseLocations');
	if (isDefined(browseLocationsEl) && browseLocationsEl != null) {
		locationsModal = new bootstrap.Modal(browseLocationsEl, {});
	}
	
	/* When trying to open the city select box, open the locations modal */
	$(document).on('select2:opening', '#cityId', function (e) {
		e.preventDefault();
		
		$('#modalTriggerName').val('select');
		if (locationsModal != null) {
			locationsModal.show(this); /* The browseLocations() function will be fired */
		}
		
		return false;
	});
	
	/* Retrieve selected city and its admin. division */
	selectCity();
	
	/* When clicking on a city link, add the city in the select box option and select it (without fire the HTML link) */
	$(document).on('click', '#browseLocations .is-city', function (e) {
		let modalTriggerName = $('#modalTriggerName').val();
		
		if (modalTriggerName === 'select') {
			e.preventDefault();
			
			selectCity(this);
			if (locationsModal != null) {
				locationsModal.hide();
			}
			
			return false;
		}
	});
	
});

/**
 * Click on a city link to select it (to add it in the city's select box)
 *
 * @param jsThis
 * @returns {boolean}
 */
function selectCity(jsThis = null) {
	/* Check required variables */
	if (typeof languageCode === 'undefined' || typeof countryCode === 'undefined') {
		return false;
	}
	
	/* Location's modal fields */
	let adminTypeEl = $('#modalAdminType');
	let adminCodeEl = $('#modalAdminCode');
	
	/* Main form fields */
	let selectedAdminTypeEl = $('#selectedAdminType');
	let selectedAdminCodeEl = $('#selectedAdminCode');
	let selectedCityIdEl = $('#selectedCityId');
	let selectedCityNameEl = $('#selectedCityName');
	let cityIdEl = $('#cityId');
	
	if (
		!isDefined(adminTypeEl)
		|| !isDefined(adminCodeEl)
		|| !isDefined(selectedAdminTypeEl)
		|| !isDefined(selectedAdminCodeEl)
		|| !isDefined(selectedCityIdEl)
		|| !isDefined(selectedCityNameEl)
		|| !isDefined(cityIdEl)
	) {
		return false;
	}
	
	let adminType, adminCode, cityId, cityName;
	
	if (isDefined(jsThis) && jsThis !== null) {
		let thisEl = $(jsThis);
		
		adminType = thisEl.data('adminType');
		adminCode = thisEl.data('adminCode');
		cityId = thisEl.data('id');
		cityName = thisEl.data('name');
		
		if (!isEmpty(cityId) && !isEmpty(cityName)) {
			cityIdEl.empty().append('<option value="' + cityId + '">' + cityName + '</option>').val(cityId).trigger('change');
			adminTypeEl.val(adminType);
			adminCodeEl.val(adminCode);
			
			selectedAdminTypeEl.val(adminType);
			selectedAdminCodeEl.val(adminCode)
			selectedCityIdEl.val(cityId);
			selectedCityNameEl.val(cityName);
			
			/* Update the modal form */
			if (!isEmpty(adminType) && !isEmpty(adminCode)) {
				let urlEl = $('#modalUrl');
				if (isDefined(urlEl)) {
					let url = siteUrl + '/ajax/locations/' + strToLower(countryCode) + '/admins/' + adminType + '/' + adminCode + '/cities';
					urlEl.val(url);
				}
			}
			let queryEl = $('#modalQuery');
			if (isDefined(queryEl)) {
				queryEl.val('');
			}
		} else {
			let error = 'Error: Impossible to select the city.';
			jsAlert(error, 'error', false, true);
		}
	} else {
		adminTypeEl.val(selectedAdminTypeEl.val());
		adminCodeEl.val(selectedAdminCodeEl.val());
		cityId = selectedCityIdEl.val();
		cityName = selectedCityNameEl.val();
		
		if (!isEmpty(cityId) && !isEmpty(cityName)) {
			cityIdEl.empty().append('<option value="' + cityId + '">' + cityName + '</option>').val(cityId).trigger('change');
		}
	}
}
