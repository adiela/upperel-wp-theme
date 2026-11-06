/**
 * Upperel shop filters panel (inc/upperel-shop-filters.php).
 *
 * The "Filters" button opens the panel as a modal dialog, which keeps
 * focus inside it and closes on Escape. It also closes from its close
 * button or a click on the backdrop.
 *
 * On submit, builds a clean URL in WooCommerce's format, with the ticked
 * values of each group joined by commas (categories=bags,clothes) and
 * empty fields left out, instead of the browser's categories[]=… form.
 */
( function () {
	'use strict';

	const dialog = document.getElementById( 'upperel-filters' );
	if ( ! dialog || typeof dialog.showModal !== 'function' ) {
		return;
	}

	const form = dialog.querySelector( 'form' );
	let opener = null;

	document.addEventListener( 'click', function ( e ) {
		const toggle = e.target.closest( '.upperel-filters-toggle' );
		if ( toggle ) {
			opener = toggle;
			dialog.showModal();
			toggle.setAttribute( 'aria-expanded', 'true' );
		}
	} );

	dialog.addEventListener( 'close', function () {
		document
			.querySelectorAll( '.upperel-filters-toggle' )
			.forEach( function ( toggle ) {
				toggle.setAttribute( 'aria-expanded', 'false' );
			} );
		if ( opener ) {
			opener.focus();
		}
	} );

	dialog.addEventListener( 'click', function ( e ) {
		// A click on the dialog element itself is on the backdrop: the
		// form fills the whole panel.
		if (
			e.target === dialog ||
			e.target.closest( '[data-upperel-filters-close]' )
		) {
			dialog.close();
		}
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();

		const values = {};
		new FormData( form ).forEach( function ( value, name ) {
			value = String( value ).trim();
			if ( value === '' ) {
				return;
			}
			const key = name.replace( /\[\]$/, '' );
			( values[ key ] = values[ key ] || [] ).push( value );
		} );

		// Attribute groups send query_type_…=or; drop it when nothing in
		// that group is ticked.
		form.querySelectorAll( '[data-upperel-query-type]' ).forEach(
			function ( input ) {
				if ( ! values[ input.dataset.upperelQueryType ] ) {
					delete values[ input.name ];
				}
			}
		);

		const query = Object.keys( values )
			.map( function ( key ) {
				return (
					encodeURIComponent( key ) +
					'=' +
					values[ key ].map( encodeURIComponent ).join( ',' )
				);
			} )
			.join( '&' );

		window.location.href = form.action + ( query ? '?' + query : '' );
	} );
} )();
