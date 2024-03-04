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

if (typeof showSecurityTips === 'undefined') {
	var showSecurityTips = '0';
}

$(document).ready(function () {
	
	$('.phoneBlock').click(function (e) {
		e.preventDefault(); /* prevents submit or reload */
		
		showPhone(showSecurityTips);
		
		return false;
	});
	
});

/**
 * Show the Seller's Phone
 * @returns {boolean}
 */
function showPhone(showSecurityTips)
{
	let postId = $('#postId').val();
	
	if (postId === 0 || postId === '0' || postId === '') {
		if (showSecurityTips === 1 || showSecurityTips === '1') {
			let securityTipsEl = $('#securityTips');
			
			securityTipsEl.modal({show: false})
			securityTipsEl.modal('show');
		}
		
		return false;
	}
	
	let phoneBlockEl = $('.phoneBlock');
	let iconEl = phoneBlockEl.find('i');
	
	let resultCanBeCached = true;
	let url = siteUrl + '/ajax/post/phone';
	
	let ajax = $.ajax({
		method: 'POST',
		url: url,
		data: {
			'post_id': postId,
			'_token': $('input[name=_token]').val()
		},
		cache: resultCanBeCached,
		beforeSend: function() {
			if (showSecurityTips === 1 || showSecurityTips === '1') {
				let spinner = '<i class="spinner-border text-primary"></i>';
				$('#phoneModal').html(spinner);
			} else {
				/* Change the button indicator */
				if (iconEl) {
					iconEl.removeClass('fas fa-mobile-alt');
					iconEl.addClass('spinner-border spinner-border-sm').css({'vertical-align': 'middle'});
					iconEl.attr({'role': 'status', 'aria-hidden': 'true'});
				}
			}
		}
	});
	ajax.done(function (xhr) {
		if (typeof xhr.phoneModal === 'undefined' || typeof xhr.phone === 'undefined') {
			return false;
		}
		
		if (showSecurityTips === 1 || showSecurityTips === '1') {
			$('#phoneModal').html(xhr.phoneModal);
			$('#phoneModalLink').attr('href', xhr.link);
			$('#securityTips').modal('show');
		} else {
			phoneBlockEl.html('<i class="fas fa-mobile-alt"></i> ' + xhr.phone);
			phoneBlockEl.attr('href', xhr.link);
			phoneBlockEl.tooltip('dispose'); /* Disable Tooltip */
		}
		
		if (resultCanBeCached) {
			$('#postId').val(0);
		}
	});
	ajax.fail(function(xhr) {
		let message = getJqueryAjaxError(xhr);
		if (message !== null) {
			jsAlert(message, 'error');
		}
		
		if (showSecurityTips !== 1 && showSecurityTips !== '1') {
			/* Reset the button indicator */
			if (iconEl) {
				iconEl.removeClass('spinner-border spinner-border-sm').css({'vertical-align': ''});
				iconEl.addClass('fas fa-mobile-alt').removeAttr('role aria-hidden');
			}
		}
	});
}
