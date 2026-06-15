/**
 * Frontend JavaScript for WP Booking System
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize date picker
		initDatePicker();
		
		// Handle form submission
		$('#wpbs-booking-form').on('submit', handleFormSubmit);
		
		// Handle date changes
		$('#wpbs-check-in, #wpbs-check-out').on('change', handleDateChange);
		
		// Handle guest count changes
		$('#wpbs-adults, #wpbs-kids').on('change', calculatePrice);
		
		// Handle booking cancellation
		$('.wpbs-cancel-booking').on('click', handleCancelBooking);
	});

	/**
	 * Initialize Flatpickr date picker
	 */
	function initDatePicker() {
		if (typeof flatpickr === 'undefined') {
			return;
		}

		const checkInInput = document.getElementById('wpbs-check-in');
		const checkOutInput = document.getElementById('wpbs-check-out');

		if (!checkInInput || !checkOutInput) {
			return;
		}

		// Get unavailable dates from server
		const unavailableDates = getUnavailableDates();

		// Booking rules from the server (with safe fallbacks).
		const cfg = (wpbslFrontend && wpbslFrontend.config) || {};
		const minNights = parseInt(cfg.minNights, 10) || 1;
		const maxNights = parseInt(cfg.maxNights, 10) || 0;
		const minAdvanceDays = parseInt(cfg.minAdvanceDays, 10) || 0;
		const maxAdvanceDays = parseInt(cfg.maxAdvanceDays, 10) || 0;

		const addDays = (date, days) => {
			const d = new Date(date);
			d.setDate(d.getDate() + days);
			return d;
		};

		const earliestCheckIn = addDays(new Date(), minAdvanceDays);
		const latestCheckIn = maxAdvanceDays > 0 ? addDays(new Date(), maxAdvanceDays) : undefined;

		const checkInPicker = flatpickr(checkInInput, {
			minDate: earliestCheckIn,
			maxDate: latestCheckIn,
			dateFormat: 'Y-m-d',
			disable: unavailableDates,
			onChange: function(selectedDates) {
				if (selectedDates.length) {
					const checkIn = selectedDates[0];
					// Check-out must be at least the minimum number of nights later.
					checkOutPicker.set('minDate', addDays(checkIn, minNights));
					if (maxNights > 0) {
						checkOutPicker.set('maxDate', addDays(checkIn, maxNights));
					} else if (latestCheckIn) {
						checkOutPicker.set('maxDate', addDays(latestCheckIn, 1));
					}
					handleDateChange();
				}
			}
		});

		const checkOutPicker = flatpickr(checkOutInput, {
			minDate: addDays(earliestCheckIn, minNights),
			maxDate: latestCheckIn ? addDays(latestCheckIn, 1) : undefined,
			dateFormat: 'Y-m-d',
			disable: unavailableDates,
			onChange: function() {
				handleDateChange();
			}
		});
	}

	/**
	 * Get unavailable dates (simplified - in production, fetch from server)
	 */
	function getUnavailableDates() {
		// This would be populated from server-side data
		return [];
	}

	/**
	 * Handle date change
	 */
	function handleDateChange() {
		const checkIn = $('#wpbs-check-in').val();
		const checkOut = $('#wpbs-check-out').val();

		if (checkIn && checkOut) {
			// Validate dates
			if (new Date(checkOut) <= new Date(checkIn)) {
				showMessage('error', wpbslFrontend.i18n.invalidDates);
				$('#wpbs-price-summary').hide();
				return;
			}

			// Check availability
			checkAvailability(checkIn, checkOut);
			
			// Calculate price
			calculatePrice();
		} else {
			$('#wpbs-price-summary').hide();
		}
	}

	/**
	 * Check availability
	 */
	function checkAvailability(checkIn, checkOut) {
		$.ajax({
			url: wpbslFrontend.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wpbsl_check_availability',
				nonce: wpbslFrontend.nonce,
				check_in: checkIn,
				check_out: checkOut
			},
			success: function(response) {
				if (response.success) {
					if (!response.data.available) {
						showMessage('error', wpbslFrontend.i18n.unavailable);
					}
				}
			}
		});
	}

	/**
	 * Calculate price
	 */
	function calculatePrice() {
		const checkIn = $('#wpbs-check-in').val();
		const checkOut = $('#wpbs-check-out').val();
		const adults = parseInt($('#wpbs-adults').val()) || 1;
		const kids = parseInt($('#wpbs-kids').val()) || 0;

		if (!checkIn || !checkOut) {
			$('#wpbs-price-summary').hide();
			return;
		}

		if (new Date(checkOut) <= new Date(checkIn)) {
			$('#wpbs-price-summary').hide();
			return;
		}

		$.ajax({
			url: wpbslFrontend.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wpbsl_calculate_price',
				nonce: wpbslFrontend.nonce,
				check_in: checkIn,
				check_out: checkOut,
				adults: adults,
				kids: kids
			},
			beforeSend: function() {
				$('#wpbs-total-price').text(wpbslFrontend.i18n.calculating);
				$('#wpbs-price-summary').show();
			},
			success: function(response) {
				if (response.success) {
					$('#wpbs-total-price').text(response.data.formatted);
					$('#wpbs-price-summary').show();
				} else {
					showMessage('error', response.data.message || 'Unable to calculate price.');
					$('#wpbs-price-summary').hide();
				}
			},
			error: function() {
				showMessage('error', 'An error occurred while calculating the price.');
				$('#wpbs-price-summary').hide();
			}
		});
	}

	/**
	 * Handle form submission
	 */
	function handleFormSubmit(e) {
		e.preventDefault();

		const form = $(this);
		const submitButton = form.find('.wpbs-submit-button');
		const formData = form.serialize();

		// Store original text if not already stored
		if (!submitButton.data('original-text')) {
			submitButton.data('original-text', submitButton.text());
		}

		submitButton.prop('disabled', true).text(wpbslFrontend.i18n.submitting || 'Submitting...');

		$.ajax({
			url: wpbslFrontend.ajaxUrl,
			type: 'POST',
			data: formData + '&action=wpbsl_submit_booking&nonce=' + wpbslFrontend.nonce,
			success: function(response) {
				if (response.success) {
					showBookingSuccess(response.data.message);
				} else {
					showMessage('error', response.data.message || 'An error occurred. Please try again.');
				}
			},
			error: function() {
				showMessage('error', 'An error occurred. Please try again.');
			},
			complete: function() {
				submitButton.prop('disabled', false);
				const originalText = submitButton.data('original-text') || 'Book Now';
				submitButton.text(originalText);
			}
		});
	}

	/**
	 * Replace the booking form with a clear confirmation panel so the guest
	 * knows the request went through and isn't tempted to submit again.
	 */
	function showBookingSuccess(message) {
		const form = $('#wpbs-booking-form');
		const note = wpbslFrontend.i18n.submittedNote || '';

		const panel = $('<div class="wpbs-booking-confirmation wpbs-success" role="status" tabindex="-1"></div>');
		panel.append($('<div class="wpbs-confirmation-icon" aria-hidden="true">✓</div>'));
		panel.append($('<p class="wpbs-confirmation-title"></p>').text(message));
		if (note) {
			panel.append($('<p class="wpbs-confirmation-note"></p>').text(note));
		}
		const again = $('<button type="button" class="wpbs-book-another"></button>')
			.text(wpbslFrontend.i18n.bookAnother || 'Book another stay')
			.on('click', function() { window.location.reload(); });
		panel.append(again);

		form.hide().after(panel);
		$('html, body').animate({ scrollTop: panel.offset().top - 100 }, 300);
	}

	/**
	 * Handle booking cancellation
	 */
	function handleCancelBooking(e) {
		e.preventDefault();

		if (!confirm(wpbslFrontend.i18n.confirmCancel || 'Are you sure you want to cancel this booking?')) {
			return;
		}

		const button = $(this);
		const token = button.data('token');

		button.prop('disabled', true);

		$.ajax({
			url: wpbslFrontend.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wpbsl_cancel_booking',
				nonce: wpbslFrontend.nonce,
				token: token
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message, '#wpbs-manage-messages');
					button.closest('.wpbs-booking-actions').fadeOut();
					$('.wpbs-status').removeClass('wpbs-status-confirmed wpbs-status-pending').addClass('wpbs-status-cancelled').text(wpbslFrontend.i18n.cancelled || 'Cancelled');
				} else {
					showMessage('error', response.data.message || 'Failed to cancel booking.', '#wpbs-manage-messages');
				}
			},
			error: function() {
				showMessage('error', 'An error occurred. Please try again.', '#wpbs-manage-messages');
			},
			complete: function() {
				button.prop('disabled', false);
			}
		});
	}

	/**
	 * Show message
	 */
	function showMessage(type, message, selector) {
		selector = selector || '#wpbs-form-messages';
		const messageEl = $(selector);
		messageEl.removeClass('wpbs-success wpbs-error').addClass('wpbs-' + type).text(message).show();
		
		// Scroll to message
		$('html, body').animate({
			scrollTop: messageEl.offset().top - 100
		}, 300);
	}

})(jQuery);

