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

if (typeof siteUrl === 'undefined') {
	var siteUrl = '';
}
if (typeof isLogged === 'undefined') {
	var isLogged = false;
}
if (typeof phoneCountries === 'undefined') {
	var phoneCountries = [];
}
if (typeof phoneCountry === 'undefined') {
	var phoneCountry = 'us';
}
if (typeof defaultAuthField === 'undefined') {
	var defaultAuthField = 'email';
}

$(document).ready(function () {
	
	/* Apply the 'intl-tel-input' plugin to the modal phone field */
	let mPhoneInput = document.querySelector('#mPhone');
	let mIti = applyIntlTelInput(mPhoneInput, phoneCountries, phoneCountry);
	
	/* Apply the 'intl-tel-input' plugin to the phone field */
	let phoneInput = document.querySelector("input[name='phone']:not([type=hidden]):not(.m-phone)");
	let iti = applyIntlTelInput(phoneInput, phoneCountries, phoneCountry);
	
	/* Apply the 'intl-tel-input' plugin to the 2nd modal phone field */
	let fromPhoneInput = document.querySelector('#fromPhone');
	let fIti = applyIntlTelInput(fromPhoneInput, phoneCountries, phoneCountry);
	
	/* Select an auth field */
	selectAuthField(null, defaultAuthField);
	$(document).on('click', 'a.auth-field', function(e) {
		e.preventDefault();
		selectAuthField(this);
		
		return false;
	});
	$(document).on('change', 'input.auth-field-input', function(e) {
		selectAuthField(this);
	});
	
});

/**
 * Apply the 'intl-tel-input' plugin to the phone field
 *
 * @param inputEl
 * @param countries
 * @param phoneCountry
 * @returns {null|*}
 */
function applyIntlTelInput(inputEl, countries, phoneCountry = null)
{
	if (isEmpty(inputEl)) {
		return null;
	}
	
	let params = {
		/* hiddenInput: 'phone_intl', */
		initialCountry: '',
		separateDialCode: true,
		preferredCountries: [],
	};
	
	if (!isEmpty(siteUrl)) {
		params.utilsScript = siteUrl + '/assets/plugins/intl-tel-input/17.0.18/js/utils.js';
	}
	
	if (!isEmpty(phoneCountry)) {
		/* Is the current country's item/object? */
		let isCurrPhoneCountryItem = function(e) {
			return (!isEmpty(e.iso2) && e.iso2.toLowerCase() === phoneCountry.toLowerCase());
		};
		/*
		 * Check the (eventual) initial country exists in the countries list,
		 * If so, set it as initial country.
		 */
		if (countries.filter(e => isCurrPhoneCountryItem(e)).length > 0) {
			params.initialCountry = phoneCountry.toLowerCase();
		}
	}
	
	/* Replace dynamically the countries list */
	if (!isEmpty(countries)) {
		/* Get all the countries data */
		var allCountries = window.intlTelInputGlobals.getCountryData();
		allCountries.length = 0;
		
		$.each(countries, function (i, country) {
			allCountries.push(country);
		});
		
		if (allCountries.length > 1 && !isEmpty(phoneCountry)) {
			params.preferredCountries = [phoneCountry.toLowerCase()];
		}
	}
	
	/*
	 * Store the instance variable in 'window.iti',
	 * so we can access it in the console e.g. window.iti.getNumber()
	 */
	let iti = window.intlTelInput(inputEl, params);
	
	/* Populate phone hidden inputs */
	const populatePhoneHiddenInputs = function() {
		/* phone_intl */
		let phoneIntlEls = document.querySelectorAll("input[name='phone_intl']");
		if (phoneIntlEls.length) {
			let phoneIntl = iti.getNumber();
			phoneIntlEls.forEach(function(phoneIntlEl) {
				if (!isEmpty(phoneIntlEl)) {
					phoneIntlEl.value = phoneIntl;
				}
			});
		}
		
		/* phone_country */
		let phoneCountryEls = document.querySelectorAll("input[name='phone_country']");
		if (phoneCountryEls.length) {
			let countryData = iti.getSelectedCountryData();
			phoneCountryEls.forEach(function(phoneCountryEl) {
				if (!isEmpty(phoneCountryEl)) {
					if (!isEmpty(countryData.iso2)) {
						phoneCountryEl.value = countryData.iso2;
					}
				}
			});
		}
	};
	
	inputEl.addEventListener('focus', populatePhoneHiddenInputs);
	inputEl.addEventListener('blur', populatePhoneHiddenInputs);
	inputEl.addEventListener('change', populatePhoneHiddenInputs);
	inputEl.addEventListener('keyup', populatePhoneHiddenInputs);
	
	return iti;
}

/**
 * Select an auth field (email or phone)
 *
 * @param thisEl
 * @param defaultAuthField
 * @returns {boolean}
 */
function selectAuthField(thisEl = null, defaultAuthField = null) {
	defaultAuthField = defaultAuthField ? defaultAuthField : 'email';
	
	/* Select default auth field */
	let authFieldTagName;
	let authField;
	if (thisEl) {
		authFieldTagName = $(thisEl).get(0).tagName.toLowerCase();
		authField = (authFieldTagName == 'input') ? $(thisEl).val() : $(thisEl).data('auth-field');
	} else {
		authField = defaultAuthField;
	}
	
	if (!authField || authField.length == 0) {
		jsAlert('Impossible to get the auth field!', 'error', false);
		return false;
	}
	
	/* Update the 'auth_field' filed value */
	if (!isEmpty(authFieldTagName) && authFieldTagName == 'a') {
		$("input[name='auth_field']:not([type=radio], [type=checkbox])").val(authField);
	}
	
	/* Get the auth field items (email|phone) & the selected item elements */
	let itemsEl = $('.auth-field-item');
	let canBeHiddenItemsEl = $('.auth-field-item:not(.force-to-display)');
	let selectedItemEl = $("input[name='" + authField + "']").closest('.auth-field-item');
	
	/* Manage required '<sup>' tag in the auth field items' label */
	itemsEl.removeClass('required');
	itemsEl.find('label sup').remove();
	selectedItemEl.addClass('required');
	selectedItemEl.find('label').append(' <sup>*</sup>');
	
	/* Manage auth field items display */
	if (isLogged !== true) {
		/*
		itemsEl.addClass('d-none');
		selectedItemEl.removeClass('d-none');
		*/
		canBeHiddenItemsEl.addClass('d-none');
		selectedItemEl.removeClass('d-none');
	}
}
