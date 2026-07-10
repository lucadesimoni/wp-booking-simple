/**
 * Frontend JavaScript for WP Booking System
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize date picker
		initDatePicker();

		// Initialize the interactive availability calendar (if present)
		initAvailabilityCalendar();

		// Render the Swiss QR payment code (manage page, if present)
		initQrPayment();

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

		// Weeks start on Monday.
		flatpickr.l10ns.default.firstDayOfWeek = 1;

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

	/** Base64 → UTF-8 string (with a Latin-1 fallback). */
	function decodeQrPayload(b64) {
		try {
			return decodeURIComponent(escape(window.atob(b64)));
		} catch (e) {
			try { return window.atob(b64); } catch (e2) { return ''; }
		}
	}

	/**
	 * Draw a Swiss QR-bill payment code (with the Swiss cross in the centre)
	 * into the given element. The guest scans it with TWINT or any Swiss
	 * banking app to pay.
	 */
	function renderSwissQr(box, payload) {
		if (!box || typeof WPBSLQRCode === 'undefined' || !payload) {
			return;
		}

		// errorCorrectLevel 0 = M, required by the Swiss QR-bill; typeNumber 0 = auto.
		const qr = new WPBSLQRCode(0, 0);
		qr.addData(payload);
		qr.make();

		const count = qr.getModuleCount();
		const cell = 6;
		const margin = cell * 4;
		const size = count * cell + margin * 2;

		const canvas = document.createElement('canvas');
		canvas.width = size;
		canvas.height = size;
		canvas.setAttribute('role', 'img');
		const ctx = canvas.getContext('2d');
		ctx.fillStyle = '#ffffff';
		ctx.fillRect(0, 0, size, size);
		ctx.fillStyle = '#000000';
		for (let r = 0; r < count; r++) {
			for (let c = 0; c < count; c++) {
				if (qr.isDark(r, c)) {
					ctx.fillRect(margin + c * cell, margin + r * cell, cell, cell);
				}
			}
		}

		// Swiss cross in the centre (white cross on a black square with a white border).
		const center = size / 2;
		const sq = Math.round(size * 0.14);
		const bx = Math.round(center - sq / 2);
		ctx.fillStyle = '#ffffff';
		ctx.fillRect(bx - 3, bx - 3, sq + 6, sq + 6);
		ctx.fillStyle = '#000000';
		ctx.fillRect(bx, bx, sq, sq);
		ctx.fillStyle = '#ffffff';
		const arm = Math.round(sq * 0.16);
		const armLen = Math.round(sq * 0.56);
		ctx.fillRect(Math.round(center - arm / 2), Math.round(center - armLen / 2), arm, armLen);
		ctx.fillRect(Math.round(center - armLen / 2), Math.round(center - arm / 2), armLen, arm);

		box.innerHTML = '';
		box.appendChild(canvas);
	}

	/** Manage-page QR (rendered from the #wpbs-qr data-payload). */
	function initQrPayment() {
		const box = document.getElementById('wpbs-qr');
		if (box && box.getAttribute('data-payload')) {
			renderSwissQr(box, decodeQrPayload(box.getAttribute('data-payload')));
		}
	}

	/**
	 * Build the checkout payment panel (QR + bank details + optional TWINT
	 * pay link) from the payment context returned by the booking submission.
	 */
	function buildPaymentPanel(p) {
		const i18n = (wpbslFrontend && wpbslFrontend.i18n) || {};
		const infoLi = function (label, value) {
			const li = $('<li></li>');
			if (label) { li.append($('<strong></strong>').text(label)); li.append(document.createTextNode(' ')); }
			li.append(document.createTextNode(value));
			return li;
		};

		const wrap = $('<div class="wpbs-qr-pay"></div>');
		wrap.append($('<h3></h3>').text(i18n.payTitle || 'Pay now'));
		wrap.append($('<p></p>').text(i18n.payIntro || ''));
		wrap.append($('<div class="wpbs-qr"></div>'));

		const ul = $('<ul class="wpbs-qr-info"></ul>');
		ul.append(infoLi(i18n.labelAmount || 'Amount:', p.amount));
		if (p.name) { ul.append(infoLi('', p.name)); }
		if (p.bank) { ul.append(infoLi('', p.bank)); }
		ul.append(infoLi(i18n.labelIban || 'IBAN:', p.iban));
		ul.append(infoLi(i18n.labelReference || 'Reference:', p.reference));
		wrap.append(ul);

		if (p.paylink) {
			wrap.append(
				$('<a class="wpbs-twint-btn" target="_blank" rel="noopener noreferrer"></a>')
					.attr('href', p.paylink)
					.text(i18n.payWithTwint || 'Pay with TWINT')
			);
		}
		return wrap;
	}

	/**
	 * Booked dates (Y-m-d) provided by the server, disabled in the picker.
	 */
	function getUnavailableDates() {
		return (wpbslFrontend && wpbslFrontend.unavailableDates) || [];
	}

	/**
	 * Turn the availability calendar into an interactive date selector:
	 * click a check-in day, then a check-out day, and the stay is highlighted
	 * and pushed into the booking form (booked/past days can't be selected).
	 */
	function initAvailabilityCalendar() {
		if (typeof FullCalendar === 'undefined') {
			return;
		}
		// Support any number of calendars on a page (shortcode, block, widget).
		document.querySelectorAll('.wpbs-calendar-shortcode').forEach(initOneCalendar);
	}

	/**
	 * Build one interactive availability calendar in the given element.
	 */
	function initOneCalendar(calendarEl) {
		if (!calendarEl) {
			return;
		}

		const unavailableDates = getUnavailableDates();
		const cfg = (wpbslFrontend && wpbslFrontend.config) || {};
		const minNights = parseInt(cfg.minNights, 10) || 1;
		const todayStr = new Date().toISOString().split('T')[0];

		const ymd = function(d) {
			return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
		};
		const addDays = function(str, n) {
			const d = new Date(str + 'T00:00:00');
			d.setDate(d.getDate() + n);
			return ymd(d);
		};
		// True when a booked night lies in [from, to) — blocks selecting across a booking.
		const rangeHasBooked = function(from, to) {
			for (let d = from; d < to; d = addDays(d, 1)) {
				if (unavailableDates.indexOf(d) !== -1) { return true; }
			}
			return false;
		};
		// Push a date into the form's flatpickr-backed field (triggers price/availability).
		const setFormDate = function(id, dateStr) {
			const el = document.getElementById(id);
			if (!el) { return; }
			if (el._flatpickr) { el._flatpickr.setDate(dateStr || '', true); }
			else { el.value = dateStr || ''; $(el).trigger('change'); }
		};
		// Highlight the chosen stay (check-in … check-out) on the calendar.
		const paintRange = function() {
			const ci = (document.getElementById('wpbs-check-in') || {}).value || '';
			const co = (document.getElementById('wpbs-check-out') || {}).value || '';
			calendarEl.querySelectorAll('.fc-daygrid-day').forEach(function(cell) {
				const d = cell.getAttribute('data-date');
				cell.classList.toggle('wpbs-selected-range', !!(ci && co && d >= ci && d <= co));
				cell.classList.toggle('wpbs-selected-edge', !!(d === ci || (co && d === co)));
			});
		};

		const calendar = new FullCalendar.Calendar(calendarEl, {
			initialView: 'dayGridMonth',
			firstDay: 1,
			headerToolbar: { left: 'prev,next', center: 'title', right: '' },
			height: 'auto',
			events: function(fetchInfo, successCallback, failureCallback) {
				$.ajax({
					url: wpbslFrontend.ajaxUrl,
					type: 'GET',
					data: {
						action: 'wpbsl_get_calendar_availability',
						nonce: wpbslFrontend.nonce,
						start: fetchInfo.startStr,
						end: fetchInfo.endStr
					},
					success: function(response) {
						if (response.success) { successCallback(response.data); }
						else { failureCallback(); }
					},
					error: function() { failureCallback(); }
				});
			},
			dayCellClassNames: function(arg) {
				const dateStr = ymd(arg.date);
				const classes = [];
				if (unavailableDates.indexOf(dateStr) !== -1) { classes.push('wpbs-unavailable-date'); }
				if (dateStr < todayStr) { classes.push('wpbs-past-date'); }
				return classes;
			},
			dayCellDidMount: function(arg) {
				if (!arg.el) { return; }
				const dateStr = ymd(arg.date);
				const i18n = (wpbslFrontend && wpbslFrontend.i18n) || {};
				let tip;
				if (dateStr < todayStr) {
					tip = i18n.tipPast || 'Not available';
				} else if (unavailableDates.indexOf(dateStr) !== -1) {
					tip = i18n.tipBooked || 'Booked';
				} else {
					tip = i18n.tipAvailable || 'Available';
				}
				arg.el.setAttribute('title', tip);
			},
			dateClick: function(info) {
				const dateStr = info.dateStr;
				if (dateStr < todayStr || unavailableDates.indexOf(dateStr) !== -1) { return; }

				const ci = (document.getElementById('wpbs-check-in') || {}).value || '';
				const co = (document.getElementById('wpbs-check-out') || {}).value || '';

				if (!ci || (ci && co) || dateStr <= ci) {
					// Begin a new selection.
					setFormDate('wpbs-check-in', dateStr);
					setFormDate('wpbs-check-out', '');
				} else if (rangeHasBooked(ci, dateStr) || dateStr < addDays(ci, minNights)) {
					// Can't complete here (booking in the way, or too short) — restart.
					setFormDate('wpbs-check-in', dateStr);
					setFormDate('wpbs-check-out', '');
				} else {
					setFormDate('wpbs-check-out', dateStr);
				}
				paintRange();
			},
			datesSet: function() { paintRange(); },
			eventDisplay: 'background',
			eventBackgroundColor: '#8B0000',
			eventBorderColor: '#8B0000'
		});
		calendar.render();
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
					showBookingSuccess(response.data.message, response.data.payment);
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
	function showBookingSuccess(message, payment) {
		const form = $('#wpbs-booking-form');
		const note = wpbslFrontend.i18n.submittedNote || '';

		const panel = $('<div class="wpbs-booking-confirmation wpbs-success" role="status" tabindex="-1"></div>');
		panel.append($('<div class="wpbs-confirmation-icon" aria-hidden="true">✓</div>'));
		panel.append($('<p class="wpbs-confirmation-title"></p>').text(message));
		if (note) {
			panel.append($('<p class="wpbs-confirmation-note"></p>').text(note));
		}

		// Offer payment right at checkout when configured (QR + bank details + TWINT).
		let payPanel = null;
		if (payment && payment.qr_payload) {
			payPanel = buildPaymentPanel(payment);
			panel.append(payPanel);
		}

		const again = $('<button type="button" class="wpbs-book-another"></button>')
			.text(wpbslFrontend.i18n.bookAnother || 'Book another stay')
			.on('click', function() { window.location.reload(); });
		panel.append(again);

		form.hide().after(panel);

		// Render the QR after the panel is in the DOM.
		if (payPanel) {
			renderSwissQr(payPanel.find('.wpbs-qr')[0], decodeQrPayload(payment.qr_payload));
		}

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

