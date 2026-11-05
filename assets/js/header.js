/**
 * Upperel header: search toggle.
 *
 * The magnifying glass opens a search bar that slides out to the left
 * (animation in assets/css/base/upperel-header.scss). Closes on a second
 * click, Escape, a click outside, or tabbing away.
 */
( function () {
	'use strict';

	const wrap = document.querySelector( '.upperel-header-search' );
	if ( ! wrap ) {
		return;
	}

	const toggle = wrap.querySelector( '.upperel-header-search__toggle' );
	const field = wrap.querySelector( '.upperel-header-search__field' );

	function isOpen() {
		return wrap.classList.contains( 'is-open' );
	}

	function setState( expanded ) {
		wrap.classList.toggle( 'is-open', expanded );
		toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
		toggle.setAttribute(
			'aria-label',
			expanded ? toggle.dataset.labelClose : toggle.dataset.labelOpen
		);
	}

	function open() {
		setState( true );
		// Wait a frame so the panel is visible (focusable) before focusing.
		window.requestAnimationFrame( function () {
			field.focus( { preventScroll: true } );
		} );
	}

	function close( returnFocus ) {
		if ( ! isOpen() ) {
			return;
		}
		setState( false );
		if ( returnFocus ) {
			toggle.focus();
		}
	}

	toggle.addEventListener( 'click', function () {
		if ( isOpen() ) {
			close( true );
		} else {
			open();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && isOpen() ) {
			close( wrap.contains( wrap.ownerDocument.activeElement ) );
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		if ( isOpen() && ! wrap.contains( e.target ) ) {
			close( false );
		}
	} );

	wrap.addEventListener( 'focusout', function ( e ) {
		if ( e.relatedTarget && ! wrap.contains( e.relatedTarget ) ) {
			close( false );
		}
	} );
} )();
