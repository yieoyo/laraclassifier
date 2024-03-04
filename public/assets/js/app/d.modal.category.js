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
if (typeof packageIsEnabled === 'undefined') {
	var packageIsEnabled = false;
}
var select2Language = languageCode;
if (typeof langLayout !== 'undefined' && typeof langLayout.select2 !== 'undefined') {
	select2Language = langLayout.select2;
}
if (typeof permanentPostsEnabled === 'undefined') {
	var permanentPostsEnabled = 0;
}
if (typeof postTypeId === 'undefined') {
	var postTypeId = 0;
}
if (typeof editLabel === 'undefined') {
	var editLabel = 'Edit';
}

$(document).ready(function () {
	
	/* Select a category */
	getCategories(siteUrl, languageCode);
	$(document).on('click', '.cat-link, #selectCats .page-link', function (e) {
		e.preventDefault(); /* prevents submit or reload */
		
		getCategories(siteUrl, languageCode, this);
	});
	
	/* Show the permanent listings option field */
	showPermanentPostsOption(permanentPostsEnabled, postTypeId);
	$('input[name="post_type_id"]').on('click', function () {
		postTypeId = $(this).val();
		showPermanentPostsOption(permanentPostsEnabled, postTypeId);
	});
	
});

/**
 * Get subcategories buffer and/or Append selected category
 *
 * @param siteUrl
 * @param languageCode
 * @param jsThis
 * @returns {boolean}
 */
function getCategories(siteUrl, languageCode, jsThis = null) {
	let csrfToken = $('input[name=_token]').val();
	
	/* Get Request URL */
	let url = siteUrl + '/ajax/categories/select';
	
	let selectedCatId = $('#categoryId').val();
	let catId;
	
	if (!isDefined(jsThis) || jsThis === null) {
		catId = !isEmpty(selectedCatId) ? selectedCatId : 0;
	} else {
		let thisEl = $(jsThis);
		
		let thisElClass = thisEl.attr('class');
		if (thisElClass == 'page-link') {
			
			url = thisEl.attr('href');
			
			/* Extract the category ID */
			catId = 0;
			if (!isEmpty(url)) {
				let queryString = getQueryParams(url);
				catId = isDefined(queryString.catId) ? queryString.catId : 0;
			}
			
		} else {
			
			/* Get the category ID */
			catId = thisEl.data('id');
			catId = !isEmpty(catId) ? catId : 0;
			
		}
		
		/*
		 * Optimize the category selection
		 * by preventing AJAX request to append the selection
		 */
		let hasChildren = thisEl.data('has-children');
		if (isDefined(hasChildren) && hasChildren == 0) {
			let catName = thisEl.text();
			let catType = thisEl.data('type');
			let parentId = thisEl.data('parent-id');
			
			let linkText = '<i class="far fa-edit"></i> ' + editLabel;
			let outputHtml = catName + '[ <a href="#browseCategories" data-bs-toggle="modal" class="cat-link" data-id="' + parentId + '" >' + linkText + '</a> ]';
			
			return appendSelectedCategory(siteUrl, languageCode, catId, catType, outputHtml);
		}
	}
	
	/* AJAX Call */
	let ajax = $.ajax({
		method: 'POST',
		url: url,
		data: {
			'_token': csrfToken,
			'selectedCatId': selectedCatId,
			'catId': catId
		},
		beforeSend: function() {
			/*
			let spinner = '<i class="spinner-border"></i>';
			$('#selectCats').addClass('text-center').html(spinner);
			*/
			
			let selectCatsEl = $('#selectCats');
			selectCatsEl.empty().addClass('py-4').busyLoad('hide');
			selectCatsEl.busyLoad('show', {
				text: loadingWd,
				custom: createCustomSpinnerEl(),
				background: '#fff',
				containerItemClass: 'm-5',
			});
		}
	});
	ajax.done(function (xhr) {
		let selectCatsEl = $('#selectCats');
		selectCatsEl.removeClass('py-4').busyLoad('hide');
		
		if (!isDefined(xhr.html) || !isDefined(xhr.hasChildren)) {
			return false;
		}
		
		/* Get & append the category's children */
		if (xhr.hasChildren) {
			selectCatsEl.removeClass('text-center');
			selectCatsEl.html(xhr.html);
		} else {
			/*
			 * Section to append default category field info
			 * or to append selected category during form loading.
			 * Not intervene when the onclick event is fired.
			 */
			if (!isDefined(xhr.category) || !isDefined(xhr.category.id) || !isDefined(xhr.category.type) || !isDefined(xhr.html)) {
				return false;
			}
			
			return appendSelectedCategory(siteUrl, languageCode, xhr.category.id, xhr.category.type, xhr.html);
		}
	});
	ajax.fail(function(xhr) {
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error', false, true);
			
			/* Close the Modal */
			let modalEl = document.querySelector('#browseCategories');
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
 * Append the selected category to its field in the form
 *
 * @param siteUrl
 * @param languageCode
 * @param catId
 * @param catType
 * @param outputHtml
 * @returns {boolean}
 */
function appendSelectedCategory(siteUrl, languageCode, catId, catType, outputHtml) {
	if (!isDefined(catId) || !isDefined(catType) || !isDefined(outputHtml)) {
		return false;
	}
	
	try {
		/* Select the category & append it */
		$('#catsContainer').html(outputHtml);
		
		/* Save data in hidden field */
		$('#categoryId').val(catId);
		$('#categoryType').val(catType);
		
		/* Close the Modal */
		let modalEl = document.querySelector('#browseCategories');
		if (isDefined(modalEl) && modalEl !== null) {
			let modalObj = bootstrap.Modal.getInstance(modalEl);
			if (modalObj !== null) {
				modalObj.hide();
			}
		}
		
		/* Apply category's type actions & Get category's custom-fields */
		applyCategoryTypeActions('categoryType', catType, packageIsEnabled);
		getCustomFieldsByCategory(siteUrl, languageCode, catId);
	} catch (e) {
		console.log(e);
	}
	
	return false;
}

/**
 * Get the Custom Fields by Category
 *
 * @param siteUrl
 * @param languageCode
 * @param catId
 * @returns {*}
 */
function getCustomFieldsByCategory(siteUrl, languageCode, catId) {
	/* Check undefined variables */
	if (!isDefined(languageCode) || !isDefined(catId)) {
		return false;
	}
	
	/* Don't make ajax request if any category has selected. */
	if (isEmpty(catId) || catId === 0) {
		return false;
	}
	
	let csrfToken = $('input[name=_token]').val();
	
	let url = siteUrl + '/ajax/categories/' + catId + '/fields';
	
	let dataObj = {
		'_token': csrfToken,
		'languageCode': languageCode,
		'postId': (isDefined(postId)) ? postId : ''
	};
	if (isDefined(errors)) {
		/* console.log(errors); */
		dataObj.errors = errors;
	}
	if (isDefined(oldInput)) {
		/* console.log(oldInput); */
		dataObj.oldInput = oldInput;
	}
	
	let ajax = $.ajax({
		method: 'POST',
		url: url,
		data: dataObj,
		beforeSend: function() {
			let cfEl = $('#cfContainer');
			
			let spinner = '<i class="spinner-border"></i>';
			cfEl.addClass('text-center mb-3').html(spinner);
		}
	});
	ajax.done(function (xhr) {
		let cfEl = $('#cfContainer');
		
		/* Load Custom Fields */
		cfEl.removeClass('text-center mb-3');
		cfEl.html(xhr.customFields);
		
		/* Apply Fields Components */
		initSelect2(cfEl, languageCode);
		cfEl.find('.selecter, .large-data-selecter').select2({
			width: '100%'
		});
	});
	ajax.fail(function(xhr) {
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
	});
	
	return catId;
}

/**
 * Apply Category Type actions (for Job offer/search & Services for example)
 *
 * @param categoryTypeFieldId
 * @param categoryTypeValue
 * @param packageIsEnabled
 */
function applyCategoryTypeActions(categoryTypeFieldId, categoryTypeValue, packageIsEnabled) {
	$('#' + categoryTypeFieldId).val(categoryTypeValue);
	
	/* Debug */
	/* console.log(categoryTypeFieldId + ': ' + categoryTypeValue); */
	
	if (categoryTypeValue === 'job-offer') {
		$('#postTypeBloc label[for="post_type_id-1"]').show();
		$('#priceBloc label[for="price"]').html(lang.salary);
		$('#priceBloc').show();
	} else if (categoryTypeValue === 'job-search') {
		$('#postTypeBloc label[for="post_type_id-2"]').hide();
		
		$('#postTypeBloc input[value="1"]').attr('checked', 'checked');
		$('#priceBloc label[for="price"]').html(lang.salary);
		$('#priceBloc').show();
	} else if (categoryTypeValue === 'not-salable') {
		$('#priceBloc').hide();
		
		$('#postTypeBloc label[for="post_type_id-2"]').show();
	} else {
		$('#postTypeBloc label[for="post_type_id-2"]').show();
		$('#priceBloc label[for="price"]').html(lang.price);
		$('#priceBloc').show();
	}
	
	$('#nextStepBtn').html(lang.nextStepBtnLabel.next);
}

function initSelect2(selectElementObj, languageCode) {
	selectElementObj.find('.selecter').select2({
		language: select2Language,
		dropdownAutoWidth: 'true',
		minimumResultsForSearch: Infinity /* Hiding the search box */
	});
	
	selectElementObj.find('.large-data-selecter').select2({
		language: select2Language,
		dropdownAutoWidth: 'true'
	});
}

/**
 * Show the permanent listings option field
 *
 * @param permanentPostsEnabled
 * @param postTypeId
 * @returns {boolean}
 */
function showPermanentPostsOption(permanentPostsEnabled, postTypeId)
{
	if (permanentPostsEnabled == '0') {
		$('#isPermanentBox').empty();
		return false;
	}
	if (permanentPostsEnabled == '1') {
		if (postTypeId == '1') {
			$('#isPermanentBox').removeClass('hide');
		} else {
			$('#isPermanentBox').addClass('hide');
			$('#isPermanent').prop('checked', false);
		}
	}
	if (permanentPostsEnabled == '2') {
		if (postTypeId == '2') {
			$('#isPermanentBox').removeClass('hide');
		} else {
			$('#isPermanentBox').addClass('hide');
			$('#isPermanent').prop('checked', false);
		}
	}
	if (permanentPostsEnabled == '3') {
		var isPermanentField = $('#isPermanent');
		if (isPermanentField.length) {
			if (postTypeId == '2') {
				isPermanentField.val('1');
			} else {
				isPermanentField.val('0');
			}
		}
	}
	if (permanentPostsEnabled == '4') {
		$('#isPermanentBox').removeClass('hide');
	}
}
