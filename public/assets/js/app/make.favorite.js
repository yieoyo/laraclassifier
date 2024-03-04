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

if (typeof isLogged === 'undefined') {
	var isLogged = false;
}

$(document).ready(function () {
	
	/* Save the Post */
	$("a[id].make-favorite").click(function () {
		if (isLogged !== true) {
			openLoginModal();
			
			return false;
		}
		
		savePost(this);
	});
	
	/* Save the Search */
	$('#saveSearch').click(function () {
		if (isLogged !== true) {
			openLoginModal();
			
			return false;
		}
		
		saveSearch(this);
	});
	
});

/**
 * Save Ad
 * @param el
 * @returns {boolean}
 */
function savePost(el) {
	/* Get element's icon */
	var iconEl = null;
	if ($(el).is('a')) {
		iconEl = $(el).find('i');
	}
	
	let postId = $(el).attr('id');
	if (!isDefined(postId)) {
		return false;
	}
	
	let url = siteUrl + '/ajax/save/post';
	
	let ajax = $.ajax({
		method: 'POST',
		url: url,
		data: {
			'post_id': postId,
			'_token': $('input[name=_token]').val()
		},
		beforeSend: function() {
			/* Change the button indicator */
			if (iconEl) {
				iconEl.removeClass('fas fa-bookmark');
				iconEl.addClass('spinner-border spinner-border-sm').css({'vertical-align': 'middle'});
				iconEl.attr({'role': 'status', 'aria-hidden': 'true'});
			}
		}
	});
	ajax.done(function (xhr) {
		/* console.log(xhr); */
		if (typeof xhr.isLogged === 'undefined') {
			/* Reset the button indicator */
			if (iconEl) {
				iconEl.removeClass('spinner-border spinner-border-sm').css({'vertical-align': ''});
				iconEl.addClass('fas fa-bookmark').removeAttr('role aria-hidden');
			}
			
			return false;
		}
		
		if (xhr.isLogged !== true) {
			openLoginModal();
			
			/* Reset the button indicator */
			if (iconEl) {
				iconEl.removeClass('spinner-border spinner-border-sm').css({'vertical-align': ''});
				iconEl.addClass('fas fa-bookmark').removeAttr('role aria-hidden');
			}
			
			return false;
		}
		
		/* Logged Users - Notification */
		if (xhr.isSaved === true) {
			if ($(el).hasClass('btn')) {
				$('#' + xhr.postId).removeClass('btn-default').addClass('btn-success');
			} else {
				$(el).html('<i class="fas fa-bookmark" data-bs-toggle="tooltip" title="' + lang.labelSavePostRemove + '"></i>');
			}
			
			jsAlert(xhr.message, 'success');
		} else {
			if ($(el).hasClass('btn')) {
				$('#' + xhr.postId).removeClass('btn-success').addClass('btn-default');
			} else {
				$(el).html('<i class="far fa-bookmark" data-bs-toggle="tooltip" title="' + lang.labelSavePostSave + '"></i>');
			}
			
			jsAlert(xhr.message, 'success');
		}
		
		/* Reset the button indicator */
		if (iconEl) {
			iconEl.removeClass('spinner-border spinner-border-sm').css({'vertical-align': ''});
			iconEl.addClass('fas fa-bookmark').removeAttr('role aria-hidden');
		}
		
		return false;
	});
	ajax.fail(function (xhr, textStatus, errorThrown) {
		/* Reset the button indicator */
		if (iconEl) {
			iconEl.removeClass('spinner-border spinner-border-sm').css({'vertical-align': ''});
			iconEl.addClass('fas fa-bookmark').removeAttr('role aria-hidden');
		}
		
		if (typeof xhr.status !== 'undefined') {
			if (xhr.status === 401) {
				openLoginModal();
				
				if (isLogged !== true) {
					return false;
				}
			}
		}
		
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
	});
	
	return false;
}

/**
 * Save Search
 * @param el
 * @returns {boolean}
 */
function saveSearch(el) {
	let searchUrl = $(el).data('name');
	let countPosts = $(el).data('count');
	
	let url = siteUrl + '/ajax/save/search';
	
	let ajax = $.ajax({
		method: 'POST',
		url: url,
		data: {
			'url': searchUrl,
			'count_posts': countPosts,
			'_token': $('input[name=_token]').val()
		}
	});
	ajax.done(function (xhr) {
		/* console.log(xhr); */
		if (typeof xhr.isLogged === 'undefined') {
			return false;
		}
		
		if (xhr.isLogged !== true) {
			openLoginModal();
			
			return false;
		}
		
		/* Logged Users - Notification */
		jsAlert(xhr.message, 'success');
		
		return false;
	});
	ajax.fail(function (xhr, textStatus, errorThrown) {
		if (typeof xhr.status !== 'undefined') {
			if (xhr.status === 401) {
				openLoginModal();
				
				return false;
			}
		}
		
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
	});
	
	return false;
}
