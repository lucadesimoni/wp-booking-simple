(function (blocks, element, components, blockEditor, i18n) {
	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var ColorPalette = blockEditor.ColorPalette || components.ColorPalette;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var BaseControl = components.BaseControl;
	var Disabled = components.Disabled;
	var ServerSideRender = window.wp.serverSideRender
		? window.wp.serverSideRender.default || window.wp.serverSideRender
		: null;
	var __ = i18n.__;

	/*
	 * Alignment, spacing and anchor supports, the category and the API
	 * version come from the server registration (WP_Booking_Simple_Block),
	 * so they are not repeated here.
	 */

	/**
	 * Static preview, used for the calendar (it needs the front-end script to
	 * draw) and whenever server-side rendering is unavailable.
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
				style: { fontSize: '48px', width: '48px', height: '48px', color: 'var(--wpbs-primary, #8B0000)', marginBottom: '10px', display: 'block', marginLeft: 'auto', marginRight: 'auto' }
			}),
			el('h3', { style: { margin: '10px 0' } }, title),
			el('p', { style: { color: '#666', fontStyle: 'italic', margin: 0 } }, message)
		);
	}

	/**
	 * Build an edit() with a "title" text control, optional colour pickers
	 * (fed by the theme palette, e.g. Astra's global colours) and a preview.
	 *
	 * @param {string}  name        Block name, for the live preview.
	 * @param {string}  icon        Dashicon suffix.
	 * @param {string}  panelTitle  Inspector panel title.
	 * @param {string}  previewText Helper text in the static preview.
	 * @param {Array}   colors      Colour attributes and labels.
	 * @param {boolean} live        Render the real block server-side.
	 */
	function makeEdit(name, icon, panelTitle, previewText, colors, live) {
		return function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var panels = [
				el(
					PanelBody,
					{ title: panelTitle, initialOpen: true, key: 'content' },
					el(TextControl, {
						// WordPress 7.0 control styles — older versions ignore these props.
						__nextHasNoMarginBottom: true,
						__next40pxDefaultSize: true,
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

			var body = live && ServerSideRender
				? el(Disabled, null, el(ServerSideRender, {
					block: name,
					attributes: attributes,
					// Spacing and classes are applied by the editor's own
					// wrapper; sending them as well would apply them twice.
					skipBlockSupportAttributes: true
				}))
				: preview(icon, attributes.title, previewText);

			return el(
				'div',
				useBlockProps(),
				el(InspectorControls, {}, panels),
				body
			);
		};
	}

	var calendar = {
		apiVersion: 3,
		title: __('Booking Calendar', 'wp-booking-simple'),
		description: __('Show a monthly availability calendar.', 'wp-booking-simple'),
		icon: 'calendar-alt',
		keywords: [__('booking', 'wp-booking-simple'), __('calendar', 'wp-booking-simple'), __('availability', 'wp-booking-simple')],
		attributes: {
			title: { type: 'string', default: __('Booking Calendar', 'wp-booking-simple') },
			accentColor: { type: 'string', default: '' },
			bookedColor: { type: 'string', default: '' }
		},
		edit: makeEdit(
			'wp-booking-simple/calendar',
			'calendar-alt',
			__('Calendar Settings', 'wp-booking-simple'),
			__('The availability calendar will appear here on the frontend.', 'wp-booking-simple'),
			[
				{ attr: 'accentColor', label: __('Accent color', 'wp-booking-simple') },
				{ attr: 'bookedColor', label: __('Booked color', 'wp-booking-simple') }
			],
			false
		),
		save: function () {
			return null;
		}
	};

	var form = {
		apiVersion: 3,
		title: __('Booking Form', 'wp-booking-simple'),
		description: __('Show the booking form with live price and availability.', 'wp-booking-simple'),
		icon: 'calendar',
		keywords: [__('booking', 'wp-booking-simple'), __('reservation', 'wp-booking-simple'), __('form', 'wp-booking-simple')],
		attributes: {
			title: { type: 'string', default: __('Book Your Stay', 'wp-booking-simple') },
			accentColor: { type: 'string', default: '' },
			buttonBg: { type: 'string', default: '' },
			buttonColor: { type: 'string', default: '' },
			buttonHoverBg: { type: 'string', default: '' }
		},
		edit: makeEdit(
			'wp-booking-simple/form',
			'calendar',
			__('Form Settings', 'wp-booking-simple'),
			__('The booking form will appear here on the frontend.', 'wp-booking-simple'),
			[
				{ attr: 'accentColor', label: __('Accent color', 'wp-booking-simple') },
				{ attr: 'buttonBg', label: __('Button background', 'wp-booking-simple') },
				{ attr: 'buttonColor', label: __('Button text', 'wp-booking-simple') },
				{ attr: 'buttonHoverBg', label: __('Button hover background', 'wp-booking-simple') }
			],
			true
		),
		save: function () {
			return null;
		}
	};

	registerBlockType('wp-booking-simple/calendar', calendar);
	registerBlockType('wp-booking-simple/form', form);

	// Pre-rename names: registered in the editor too, so content saved with
	// them stays editable instead of showing as an unsupported block. The
	// server registration hides them from the inserter.
	registerBlockType('wp-booking-system/calendar', Object.assign({}, calendar, {
		edit: makeEdit('wp-booking-system/calendar', 'calendar-alt', __('Calendar Settings', 'wp-booking-simple'),
			__('The availability calendar will appear here on the frontend.', 'wp-booking-simple'),
			[
				{ attr: 'accentColor', label: __('Accent color', 'wp-booking-simple') },
				{ attr: 'bookedColor', label: __('Booked color', 'wp-booking-simple') }
			], false)
	}));
	registerBlockType('wp-booking-system/form', Object.assign({}, form, {
		edit: makeEdit('wp-booking-system/form', 'calendar', __('Form Settings', 'wp-booking-simple'),
			__('The booking form will appear here on the frontend.', 'wp-booking-simple'),
			[
				{ attr: 'accentColor', label: __('Accent color', 'wp-booking-simple') },
				{ attr: 'buttonBg', label: __('Button background', 'wp-booking-simple') },
				{ attr: 'buttonColor', label: __('Button text', 'wp-booking-simple') },
				{ attr: 'buttonHoverBg', label: __('Button hover background', 'wp-booking-simple') }
			], true)
	}));
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.components,
	window.wp.blockEditor,
	window.wp.i18n
);
