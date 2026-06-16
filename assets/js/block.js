(function (blocks, element, components, blockEditor, i18n) {
	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var __ = i18n.__;

	/**
	 * Shared editor preview used by both blocks.
	 *
	 * @param {string} icon    Dashicon suffix.
	 * @param {string} title   Heading text.
	 * @param {string} message Helper text.
	 */
	function preview(icon, title, message) {
		return el(
			'div',
			{
				className: 'wp-booking-system-block-preview',
				style: { padding: '24px', border: '1px dashed #ccc', borderRadius: '6px', textAlign: 'center' }
			},
			el('span', {
				className: 'dashicons dashicons-' + icon,
				style: { fontSize: '48px', width: '48px', height: '48px', color: '#8B0000', marginBottom: '10px', display: 'block', marginLeft: 'auto', marginRight: 'auto' }
			}),
			el('h3', { style: { margin: '10px 0' } }, title),
			el('p', { style: { color: '#666', fontStyle: 'italic', margin: 0 } }, message)
		);
	}

	/**
	 * Build a standard edit() with a single "title" text control.
	 *
	 * @param {string} icon         Dashicon suffix.
	 * @param {string} panelTitle   Inspector panel title.
	 * @param {string} previewText  Helper text in the preview.
	 */
	function makeEdit(icon, panelTitle, previewText) {
		return function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: panelTitle, initialOpen: true },
						el(TextControl, {
							label: __('Title', 'wp-booking-system-luca'),
							value: attributes.title,
							onChange: function (value) {
								setAttributes({ title: value });
							}
						})
					)
				),
				preview(icon, attributes.title, previewText)
			);
		};
	}

	registerBlockType('wp-booking-system/calendar', {
		title: __('Booking Calendar', 'wp-booking-system-luca'),
		description: __('Show a monthly availability calendar.', 'wp-booking-system-luca'),
		icon: 'calendar-alt',
		category: 'wp-booking-luca',
		keywords: [__('booking', 'wp-booking-system-luca'), __('calendar', 'wp-booking-system-luca'), __('availability', 'wp-booking-system-luca')],
		attributes: {
			title: { type: 'string', default: __('Booking Calendar', 'wp-booking-system-luca') }
		},
		edit: makeEdit('calendar-alt', __('Calendar Settings', 'wp-booking-system-luca'), __('The availability calendar will appear here on the frontend.', 'wp-booking-system-luca')),
		save: function () {
			return null;
		}
	});

	registerBlockType('wp-booking-system/form', {
		title: __('Booking Form', 'wp-booking-system-luca'),
		description: __('Show the booking form with live price and availability.', 'wp-booking-system-luca'),
		icon: 'calendar',
		category: 'wp-booking-luca',
		keywords: [__('booking', 'wp-booking-system-luca'), __('reservation', 'wp-booking-system-luca'), __('form', 'wp-booking-system-luca')],
		attributes: {
			title: { type: 'string', default: __('Book Your Stay', 'wp-booking-system-luca') }
		},
		edit: makeEdit('calendar', __('Form Settings', 'wp-booking-system-luca'), __('The booking form will appear here on the frontend.', 'wp-booking-system-luca')),
		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.components,
	window.wp.blockEditor,
	window.wp.i18n
);
