(function (blocks, element, components, blockEditor, i18n) {
	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = blockEditor.InspectorControls;
	var ColorPalette = blockEditor.ColorPalette || components.ColorPalette;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var BaseControl = components.BaseControl;
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
				className: 'wp-booking-simple-block-preview',
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
	function makeEdit(icon, panelTitle, previewText, colors) {
		return function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var panels = [
				el(
					PanelBody,
					{ title: panelTitle, initialOpen: true, key: 'content' },
					el(TextControl, {
						label: __('Title', 'wp-booking-simple'),
						value: attributes.title,
						onChange: function (value) {
							setAttributes({ title: value });
						}
					})
				)
			];

			if (ColorPalette && colors && colors.length) {
				var colorChildren = colors.map(function (c) {
					return el(
						BaseControl,
						{ label: c.label, key: c.attr, __nextHasNoMarginBottom: true },
						el(ColorPalette, {
							value: attributes[c.attr],
							onChange: function (value) {
								var update = {};
								update[c.attr] = value || '';
								setAttributes(update);
							}
						})
					);
				});
				panels.push(
					el(
						PanelBody,
						{ title: __('Colors', 'wp-booking-simple'), initialOpen: false, key: 'colors' },
						colorChildren
					)
				);
			}

			return el(
				'div',
				{},
				el(InspectorControls, {}, panels),
				preview(icon, attributes.title, previewText)
			);
		};
	}

	registerBlockType('wp-booking-simple/calendar', {
		title: __('Booking Calendar', 'wp-booking-simple'),
		description: __('Show a monthly availability calendar.', 'wp-booking-simple'),
		icon: 'calendar-alt',
		category: 'wp-booking-simple',
		keywords: [__('booking', 'wp-booking-simple'), __('calendar', 'wp-booking-simple'), __('availability', 'wp-booking-simple')],
		attributes: {
			title: { type: 'string', default: __('Booking Calendar', 'wp-booking-simple') },
			accentColor: { type: 'string', default: '' },
			bookedColor: { type: 'string', default: '' }
		},
		edit: makeEdit(
			'calendar-alt',
			__('Calendar Settings', 'wp-booking-simple'),
			__('The availability calendar will appear here on the frontend.', 'wp-booking-simple'),
			[
				{ attr: 'accentColor', label: __('Accent color', 'wp-booking-simple') },
				{ attr: 'bookedColor', label: __('Booked color', 'wp-booking-simple') }
			]
		),
		save: function () {
			return null;
		}
	});

	registerBlockType('wp-booking-simple/form', {
		title: __('Booking Form', 'wp-booking-simple'),
		description: __('Show the booking form with live price and availability.', 'wp-booking-simple'),
		icon: 'calendar',
		category: 'wp-booking-simple',
		keywords: [__('booking', 'wp-booking-simple'), __('reservation', 'wp-booking-simple'), __('form', 'wp-booking-simple')],
		attributes: {
			title: { type: 'string', default: __('Book Your Stay', 'wp-booking-simple') },
			accentColor: { type: 'string', default: '' },
			buttonBg: { type: 'string', default: '' },
			buttonColor: { type: 'string', default: '' },
			buttonHoverBg: { type: 'string', default: '' }
		},
		edit: makeEdit(
			'calendar',
			__('Form Settings', 'wp-booking-simple'),
			__('The booking form will appear here on the frontend.', 'wp-booking-simple'),
			[
				{ attr: 'accentColor', label: __('Accent color', 'wp-booking-simple') },
				{ attr: 'buttonBg', label: __('Button background', 'wp-booking-simple') },
				{ attr: 'buttonColor', label: __('Button text', 'wp-booking-simple') },
				{ attr: 'buttonHoverBg', label: __('Button hover background', 'wp-booking-simple') }
			]
		),
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
