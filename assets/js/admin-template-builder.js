/**
 * Email template block builder.
 *
 * Turns each `.wpbsl-builder` container into a drag-and-drop list of content
 * blocks, serialising to a sibling hidden input as JSON on every change.
 */
( function () {
	'use strict';

	var cfg = window.wpbslBuilder || { mergeTags: [], i18n: {} };
	var t = cfg.i18n || {};

	var BLOCK_TYPES = [
		{ type: 'text', label: t.text || 'Text' },
		{ type: 'heading', label: t.heading || 'Heading' },
		{ type: 'details', label: t.details || 'Booking details' },
		{ type: 'button', label: t.button || 'Button' },
		{ type: 'image', label: t.image || 'Image' },
		{ type: 'divider', label: t.divider || 'Divider' }
	];

	function el( tag, attrs, children ) {
		var node = document.createElement( tag );
		attrs = attrs || {};
		Object.keys( attrs ).forEach( function ( k ) {
			if ( k === 'class' ) { node.className = attrs[ k ]; }
			else if ( k === 'text' ) { node.textContent = attrs[ k ]; }
			else if ( k === 'html' ) { node.innerHTML = attrs[ k ]; }
			else { node.setAttribute( k, attrs[ k ] ); }
		} );
		( children || [] ).forEach( function ( c ) { if ( c ) { node.appendChild( c ); } } );
		return node;
	}

	function defaultBlock( type ) {
		switch ( type ) {
			case 'heading': return { type: 'heading', text: '' };
			case 'text': return { type: 'text', text: '' };
			case 'details': return { type: 'details' };
			case 'button': return { type: 'button', label: 'Manage Booking', url: '{manage_url}' };
			case 'image': return { type: 'image', src: '', alt: '', width: 0 };
			case 'divider': return { type: 'divider' };
		}
		return { type: 'text', text: '' };
	}

	function mergeTagSelect( onInsert ) {
		var sel = el( 'select', { class: 'wpbsl-tag-select' } );
		sel.appendChild( el( 'option', { value: '', text: t.insertTag || 'Insert tag' } ) );
		( cfg.mergeTags || [] ).forEach( function ( tag ) {
			sel.appendChild( el( 'option', { value: tag, text: tag } ) );
		} );
		sel.addEventListener( 'change', function () {
			if ( sel.value ) { onInsert( sel.value ); sel.value = ''; }
		} );
		return sel;
	}

	function insertAtCursor( field, value ) {
		var start = field.selectionStart || 0;
		var end = field.selectionEnd || 0;
		field.value = field.value.slice( 0, start ) + value + field.value.slice( end );
		field.selectionStart = field.selectionEnd = start + value.length;
		field.focus();
	}

	function Builder( container ) {
		var hidden = container.parentElement.querySelector( '.wpbsl-builder-data' );
		var blocks;
		try { blocks = JSON.parse( hidden.value || '[]' ); } catch ( e ) { blocks = []; }
		if ( ! Array.isArray( blocks ) ) { blocks = []; }

		var list = el( 'div', { class: 'wpbsl-builder-list' } );
		var palette = el( 'div', { class: 'wpbsl-builder-palette' } );

		BLOCK_TYPES.forEach( function ( bt ) {
			var btn = el( 'button', { type: 'button', class: 'button', text: '+ ' + bt.label } );
			btn.addEventListener( 'click', function () {
				blocks.push( defaultBlock( bt.type ) );
				render();
			} );
			palette.appendChild( btn );
		} );

		container.appendChild( palette );
		container.appendChild( list );

		function serialize() {
			hidden.value = JSON.stringify( blocks );
		}

		function bindText( field, block, key ) {
			field.addEventListener( 'input', function () {
				block[ key ] = field.value;
				serialize();
			} );
		}

		function blockEditor( block ) {
			var body = el( 'div', { class: 'wpbsl-block-body' } );

			if ( block.type === 'heading' || block.type === 'text' ) {
				var field = block.type === 'heading'
					? el( 'input', { type: 'text', class: 'large-text', value: block.text || '' } )
					: el( 'textarea', { rows: '4', class: 'large-text code' } );
				if ( block.type === 'text' ) { field.value = block.text || ''; }
				bindText( field, block, 'text' );
				body.appendChild( field );
				body.appendChild( mergeTagSelect( function ( tag ) {
					insertAtCursor( field, tag );
					block.text = field.value;
					serialize();
				} ) );
			} else if ( block.type === 'button' ) {
				var lab = el( 'input', { type: 'text', class: 'regular-text', placeholder: t.label || 'Button label', value: block.label || '' } );
				var url = el( 'input', { type: 'text', class: 'regular-text', placeholder: t.url || 'Button URL', value: block.url || '' } );
				bindText( lab, block, 'label' );
				bindText( url, block, 'url' );
				body.appendChild( lab );
				body.appendChild( url );
			} else if ( block.type === 'image' ) {
				var src = el( 'input', { type: 'text', class: 'large-text', placeholder: t.imageUrl || 'Image URL', value: block.src || '' } );
				var alt = el( 'input', { type: 'text', class: 'regular-text', placeholder: t.altText || 'Alt text', value: block.alt || '' } );
				var w = el( 'input', { type: 'number', min: '0', class: 'small-text', placeholder: t.widthPx || 'Width', value: block.width || '' } );
				bindText( src, block, 'src' );
				bindText( alt, block, 'alt' );
				w.addEventListener( 'input', function () { block.width = parseInt( w.value, 10 ) || 0; serialize(); } );
				body.appendChild( src );
				body.appendChild( alt );
				body.appendChild( w );
			} else if ( block.type === 'details' ) {
				body.appendChild( el( 'p', { class: 'description', text: t.detailsNote || 'Booking details box.' } ) );
			} else if ( block.type === 'divider' ) {
				body.appendChild( el( 'p', { class: 'description', text: t.dividerNote || 'Divider.' } ) );
			}

			return body;
		}

		function typeLabel( type ) {
			var found = BLOCK_TYPES.filter( function ( b ) { return b.type === type; } )[ 0 ];
			return found ? found.label : type;
		}

		function render() {
			serialize();
			list.innerHTML = '';

			if ( ! blocks.length ) {
				list.appendChild( el( 'p', { class: 'description wpbsl-builder-empty', text: t.empty || 'No blocks yet.' } ) );
				return;
			}

			blocks.forEach( function ( block, index ) {
				var row = el( 'div', { class: 'wpbsl-block', draggable: 'true' } );
				row.dataset.index = index;

				var handle = el( 'span', { class: 'wpbsl-block-handle', title: t.drag || 'Drag', text: '⠿' } );
				var title = el( 'span', { class: 'wpbsl-block-title', text: typeLabel( block.type ) } );
				var remove = el( 'button', { type: 'button', class: 'button-link wpbsl-block-remove', text: t.remove || 'Remove' } );
				remove.addEventListener( 'click', function () {
					blocks.splice( index, 1 );
					render();
				} );

				var head = el( 'div', { class: 'wpbsl-block-head' }, [ handle, title, remove ] );
				row.appendChild( head );
				row.appendChild( blockEditor( block ) );

				row.addEventListener( 'dragstart', function ( e ) {
					row.classList.add( 'is-dragging' );
					e.dataTransfer.effectAllowed = 'move';
					e.dataTransfer.setData( 'text/plain', String( index ) );
				} );
				row.addEventListener( 'dragend', function () { row.classList.remove( 'is-dragging' ); } );
				row.addEventListener( 'dragover', function ( e ) { e.preventDefault(); row.classList.add( 'is-over' ); } );
				row.addEventListener( 'dragleave', function () { row.classList.remove( 'is-over' ); } );
				row.addEventListener( 'drop', function ( e ) {
					e.preventDefault();
					row.classList.remove( 'is-over' );
					var from = parseInt( e.dataTransfer.getData( 'text/plain' ), 10 );
					var to = index;
					if ( isNaN( from ) || from === to ) { return; }
					var moved = blocks.splice( from, 1 )[ 0 ];
					blocks.splice( to, 0, moved );
					render();
				} );

				list.appendChild( row );
			} );
		}

		render();
	}

	function init() {
		var builders = document.querySelectorAll( '.wpbsl-builder' );
		Array.prototype.forEach.call( builders, function ( c ) { new Builder( c ); } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
