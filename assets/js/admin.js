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

		// Booking edit modal
		$(document).on('click', '.wpbs-modal-close, .wpbs-modal-backdrop', closeModal);
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape') { closeModal(); }
		});
		$('#wpbs-recalc-price').on('click', recalcPrice);
		$('#wpbs-edit-form').on('submit', saveBooking);
		$('#wpbs-send-reminder').on('click', sendReminder);

		// Settings page tabs
		if ($('.wpbs-settings-tabs').length) {
			initSettingsTabs();
		}

		// Insert German starter email templates
		$('#wpbsl-insert-de').on('click', insertGermanTemplates);
	});

	/**
	 * Fill the guest email templates (confirmation / cancellation / reminder)
	 * with ready-made German text from the button's data-templates payload.
	 */
	function insertGermanTemplates() {
		var data;
		try {
			data = JSON.parse(decodeURIComponent(escape(window.atob(this.getAttribute('data-templates')))));
		} catch (e) {
			try { data = JSON.parse(window.atob(this.getAttribute('data-templates'))); } catch (e2) { return; }
		}
		var i18n = (window.wpbslAdmin && wpbslAdmin.i18n) || {};
		if (!window.confirm(i18n.confirmInsertDe || 'Replace the confirmation, cancellation and reminder email text with the German starter?')) {
			return;
		}
		Object.keys(data).forEach(function (slug) {
			var subj = document.getElementById('wpbsl_email_' + slug + '_subject');
			var body = document.getElementById('wpbsl_email_' + slug + '_body');
			if (subj) { subj.value = data[slug].subject; }
			if (body) { body.value = data[slug].body; }
		});
		var msg = document.getElementById('wpbsl-insert-de-msg');
		if (msg) {
			msg.style.color = '#0a7d28';
			msg.textContent = i18n.insertedDe || 'Inserted — remember to click Save Settings.';
		}
	}

	/**
	 * Tabbed navigation on the settings screen. Keeps everything in one form
	 * (so a single Save persists all tabs) while showing one section at a time.
	 */
	function initSettingsTabs() {
		const tabs = $('.wpbs-settings-tabs .nav-tab');

		function activate(slug) {
			if (!slug || !$('.wpbs-tab-panel[data-tab="' + slug + '"]').length) {
				slug = tabs.first().data('tab');
			}
			tabs.removeClass('nav-tab-active')
				.filter('[data-tab="' + slug + '"]').addClass('nav-tab-active');
			$('.wpbs-tab-panel').removeClass('is-active')
				.filter('[data-tab="' + slug + '"]').addClass('is-active');
			$('#wpbsl_active_tab').val(slug);
		}

		tabs.on('click', function(e) {
			e.preventDefault();
			const slug = $(this).data('tab');
			if (history.replaceState) {
				history.replaceState(null, '', '#' + slug);
			} else {
				window.location.hash = slug;
			}
			activate(slug);
		});

		// Restore from the URL hash (e.g. after following an anchored link),
		// otherwise keep whatever the server marked active (survives Save).
		const fromHash = window.location.hash.replace('#', '');
		if (fromHash) {
			activate(fromHash);
		}
	}

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
	 * Open the booking view/edit modal and populate it.
	 */
	function viewBookingDetails(bookingId) {
		const $modal = $('#wpbs-modal');
		if (!$modal.length) {
			return;
		}

		$.ajax({
			url: wpbslAdmin.ajaxUrl,
			type: 'POST',
			data: { action: 'wpbsl_get_booking', nonce: wpbslAdmin.nonce, id: bookingId },
			success: function(response) {
				if (!response.success) {
					alert((response.data && response.data.message) || wpbslAdmin.i18n.genericError);
					return;
				}
				populateModal(response.data.booking);
				renderHistory(response.data.history);
				$('#wpbs-edit-msg').text('');
				openModal();
			},
			error: function() { alert(wpbslAdmin.i18n.genericError); }
		});
	}

	function setVal(field, value) {
		$('#wpbs-f-' + field).val(typeof value === 'undefined' || value === null ? '' : value);
	}

	function populateModal(b) {
		$('#wpbs-modal-id').text('#' + b.id);
		['id','first_name','last_name','email','phone','check_in','check_out','adults','kids',
		 'owner','total_price','status','payment_status','payment_method','amount_paid','notes'].forEach(function(f) {
			setVal(f, b[f]);
		});
		setVal('visitors_welcome', (b.visitors_welcome == 1) ? '1' : '0');
	}

	function renderHistory(history) {
		const $list = $('#wpbs-history-list');
		$list.empty();
		if (!history || !history.length) {
			$list.append($('<p class="description"></p>').text(wpbslAdmin.i18n.noHistory));
			return;
		}
		history.forEach(function(rev) {
			const $rev = $('<div class="wpbs-history-rev"></div>');
			$rev.append($('<div class="wpbs-history-meta"></div>')
				.text(rev.changed_at + ' · ' + rev.changed_by));
			const $ul = $('<ul></ul>');
			rev.items.forEach(function(it) {
				const $li = $('<li></li>');
				$li.append($('<strong></strong>').text(it.label + ': '));
				$li.append($('<span class="wpbs-h-from"></span>').text(it.from));
				$li.append(document.createTextNode(' → '));
				$li.append($('<span class="wpbs-h-to"></span>').text(it.to));
				$ul.append($li);
			});
			$rev.append($ul);
			$list.append($rev);
		});
	}

	function openModal() {
		$('#wpbs-modal').css('display', 'block').attr('aria-hidden', 'false');
	}

	function closeModal() {
		$('#wpbs-modal').css('display', 'none').attr('aria-hidden', 'true');
	}

	function recalcPrice() {
		const adults = parseInt($('#wpbs-f-adults').val(), 10) || 0;
		const kids = parseInt($('#wpbs-f-kids').val(), 10) || 0;
		const ci = $('#wpbs-f-check_in').val();
		const co = $('#wpbs-f-check_out').val();
		let nights = 1;
		if (ci && co) {
			const diff = (new Date(co) - new Date(ci)) / 86400000;
			nights = Math.max(1, Math.round(diff));
		}
		const price = (adults * (wpbslAdmin.priceAdult || 0) + kids * (wpbslAdmin.priceKid || 0)) * nights;
		$('#wpbs-f-total_price').val(price.toFixed(2));
	}

	function sendReminder() {
		const id = $('#wpbs-f-id').val();
		if (!id) { return; }
		const $msg = $('#wpbs-edit-msg');
		const $btn = $('#wpbs-send-reminder');
		$btn.prop('disabled', true);
		$msg.css('color', '#666').text(wpbslAdmin.i18n.sending);
		$.ajax({
			url: wpbslAdmin.ajaxUrl,
			type: 'POST',
			data: { action: 'wpbsl_send_payment_reminder', nonce: wpbslAdmin.nonce, id: id },
			success: function(response) {
				const ok = response.success;
				$msg.css('color', ok ? '#00a32a' : '#d63638')
					.text((response.data && response.data.message) || wpbslAdmin.i18n.genericError);
			},
			error: function() { $msg.css('color', '#d63638').text(wpbslAdmin.i18n.genericError); },
			complete: function() { $btn.prop('disabled', false); }
		});
	}

	function saveBooking(e) {
		e.preventDefault();
		const $msg = $('#wpbs-edit-msg');
		$msg.css('color', '#666').text(wpbslAdmin.i18n.saving);
		$.ajax({
			url: wpbslAdmin.ajaxUrl,
			type: 'POST',
			data: $('#wpbs-edit-form').serialize() + '&action=wpbsl_update_booking&nonce=' + encodeURIComponent(wpbslAdmin.nonce),
			success: function(response) {
				if (response.success) {
					$msg.css('color', '#00a32a').text(response.data.message);
					renderHistory(response.data.history);
					setTimeout(function() { window.location.reload(); }, 700);
				} else {
					$msg.css('color', '#d63638').text((response.data && response.data.message) || wpbslAdmin.i18n.genericError);
				}
			},
			error: function() { $msg.css('color', '#d63638').text(wpbslAdmin.i18n.genericError); }
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

