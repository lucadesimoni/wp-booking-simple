/**
 * Admin JavaScript for WP Booking System
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize calendar if on calendar page
		if ($('#wpbs-calendar').length) {
			initCalendar();
		}

		// Handle booking deletion
		$('.wpbs-delete-booking').on('click', handleDeleteBooking);

		// Handle booking view
		$('.wpbs-view-booking').on('click', handleViewBooking);

		// Handle status changes (confirm / cancel)
		$('.wpbs-set-status').on('click', handleStatusChange);
	});

	/**
	 * Handle confirm/cancel status changes from the bookings list
	 */
	function handleStatusChange(e) {
		e.preventDefault();

		const link = $(this);
		const bookingId = link.data('id');
		const status = link.data('status');
		const row = link.closest('tr');

		if (status === 'cancelled' && !confirm(wpbslAdmin.i18n.confirmCancel)) {
			return;
		}

		$.ajax({
			url: wpbslAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wpbsl_update_status',
				nonce: wpbslAdmin.nonce,
				id: bookingId,
				status: status
			},
			success: function(response) {
				if (response.success) {
					const label = status.charAt(0).toUpperCase() + status.slice(1);
					row.find('.wpbs-status')
						.removeClass('wpbs-status-pending wpbs-status-confirmed wpbs-status-cancelled')
						.addClass('wpbs-status-' + status)
						.text(label);

					if (status === 'confirmed' || status === 'cancelled') {
						row.find('.wpbs-set-status[data-status="confirmed"]').remove();
					}
					if (status === 'cancelled') {
						row.find('.wpbs-set-status[data-status="cancelled"]').remove();
					}
				} else {
					alert((response.data && response.data.message) || wpbslAdmin.i18n.genericError);
				}
			},
			error: function() {
				alert(wpbslAdmin.i18n.genericError);
			}
		});
	}

	/**
	 * Initialize FullCalendar
	 */
	function initCalendar() {
		// Wait for FullCalendar to be available
		if (typeof FullCalendar === 'undefined') {
			setTimeout(initCalendar, 100);
			return;
		}

		const calendarEl = document.getElementById('wpbs-calendar');
		if (!calendarEl) {
			return;
		}

		const calendar = new FullCalendar.Calendar(calendarEl, {
			initialView: 'dayGridMonth',
			initialDate: calendarEl.dataset.initialDate || undefined,
			headerToolbar: {
				left: 'prev,next today',
				center: 'title',
				right: 'dayGridMonth,listMonth'
			},
			firstDay: 1,
			displayEventTime: false,
			eventDidMount: function(info) {
				const p = info.event.extendedProps || {};
				const parts = [];
				if (p.status) { parts.push(p.status); }
				if (typeof p.guests !== 'undefined') { parts.push(p.guests + ' guests'); }
				if (p.owner) { parts.push('Owner: ' + p.owner); }
				if (p.checkIn && p.checkOut) { parts.push(p.checkIn + ' → ' + p.checkOut); }
				info.el.setAttribute('title', info.event.title + (parts.length ? ' — ' + parts.join(', ') : ''));
			},
			events: function(fetchInfo, successCallback, failureCallback) {
				$.ajax({
					url: wpbslAdmin.ajaxUrl,
					type: 'GET',
					data: {
						action: 'wpbsl_get_bookings',
						nonce: wpbslAdmin.nonce,
						start: fetchInfo.startStr,
						end: fetchInfo.endStr
					},
					success: function(response) {
						if (response.success) {
							successCallback(response.data);
						} else {
							failureCallback();
						}
					},
					error: function() {
						failureCallback();
					}
				});
			},
			eventClick: function(info) {
				// Show booking details
				viewBookingDetails(info.event.id);
			},
			eventDisplay: 'block',
			height: 'auto'
		});

		calendar.render();
	}

	/**
	 * View booking details
	 */
	function viewBookingDetails(bookingId) {
		$.ajax({
			url: wpbslAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wpbsl_get_booking',
				nonce: wpbslAdmin.nonce,
				id: bookingId
			},
			success: function(response) {
				if (response.success) {
					const booking = response.data;
					const message = `
						<strong>Guest:</strong> ${booking.first_name} ${booking.last_name}<br>
						<strong>Email:</strong> ${booking.email}<br>
						<strong>Phone:</strong> ${booking.phone || 'N/A'}<br>
						<strong>Check-in:</strong> ${booking.check_in}<br>
						<strong>Check-out:</strong> ${booking.check_out}<br>
						<strong>Guests:</strong> ${booking.adults} adults, ${booking.kids} kids<br>
						${booking.owner ? '<strong>Owner:</strong> ' + booking.owner + '<br>' : ''}
						<strong>Visitors welcome:</strong> ${(booking.visitors_welcome == 1) ? 'Yes' : 'No'}<br>
						<strong>Price:</strong> ${booking.total_price} ${wpbslAdmin.currency || 'CHF'}<br>
						<strong>Status:</strong> ${booking.status}<br>
						${booking.notes ? '<strong>Notes:</strong> ' + booking.notes + '<br>' : ''}
					`;
					alert(message);
				}
			}
		});
	}

	/**
	 * Handle booking deletion
	 */
	function handleDeleteBooking(e) {
		e.preventDefault();

		if (!confirm(wpbslAdmin.i18n.confirmDelete)) {
			return;
		}

		const link = $(this);
		const bookingId = link.data('id');
		const row = link.closest('tr');

		$.ajax({
			url: wpbslAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wpbsl_delete_booking',
				nonce: wpbslAdmin.nonce,
				id: bookingId
			},
			success: function(response) {
				if (response.success) {
					row.fadeOut(300, function() {
						$(this).remove();
					});
				} else {
					alert(response.data.message || 'Failed to delete booking.');
				}
			},
			error: function() {
				alert('An error occurred. Please try again.');
			}
		});
	}

	/**
	 * Handle booking view
	 */
	function handleViewBooking(e) {
		e.preventDefault();
		const bookingId = $(this).data('id');
		viewBookingDetails(bookingId);
	}

})(jQuery);

