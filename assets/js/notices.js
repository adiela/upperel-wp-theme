/**
 * Upperel notices: WooCommerce's notices (added to cart, errors, info) as
 * floating messages at the top of the screen, so they don't push the page
 * down when they appear (styles: upperel-notices.scss).
 *
 * Notices in WooCommerce's notice wrappers, including ones it adds later
 * (e.g. after updating the cart), are moved into one fixed stack at the
 * end of <body>. The wrappers: .woocommerce-notices-wrapper, and the
 * <div class="woocommerce"> Storefront prints its notices in, at the top
 * of the page content (storefront_shop_messages). Other notices, such as
 * "No products were found", stay where they are.
 *
 * Each gets a close button. Success and info messages close themselves
 * after a few seconds (paused while hovered or focused); errors stay until
 * closed, as they're often needed while fixing a form.
 *
 * Without JavaScript the notices stay where WooCommerce prints them.
 */
( function () {
	'use strict';

	const AUTO_CLOSE_MS = 6000;
	const NOTICE =
		'.woocommerce-message, .woocommerce-info, .woocommerce-error';
	const WRAPPER =
		'.woocommerce-notices-wrapper, .site-content > .col-full > .woocommerce';
	const label = ( window.upperelNotices && window.upperelNotices.close ) || 'Close';

	const stack = document.createElement( 'div' );
	stack.className = 'upperel-notices';
	document.body.appendChild( stack );

	function close( item ) {
		if ( item.classList.contains( 'is-closing' ) ) {
			return;
		}
		item.classList.add( 'is-closing' );
		item.classList.remove( 'is-visible' );
		window.setTimeout( function () {
			item.remove();
		}, 250 );
	}

	function autoClose( item ) {
		let timer = null;
		let paused = false;

		function start() {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				if ( ! paused ) {
					close( item );
				}
			}, AUTO_CLOSE_MS );
		}

		function pause() {
			paused = true;
			window.clearTimeout( timer );
		}

		function resume() {
			if ( item.matches( ':hover' ) || item.contains( document.activeElement ) ) {
				return;
			}
			paused = false;
			start();
		}

		item.addEventListener( 'mouseenter', pause );
		item.addEventListener( 'focusin', pause );
		item.addEventListener( 'mouseleave', resume );
		item.addEventListener( 'focusout', function () {
			window.setTimeout( resume, 0 );
		} );
		start();
	}

	function float( notice ) {
		if ( notice.closest( '.upperel-notices' ) ) {
			return;
		}

		const item = document.createElement( 'div' );
		item.className = 'upperel-notice';

		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'upperel-notice__close';
		button.setAttribute( 'aria-label', label );
		button.innerHTML =
			'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg>';
		button.addEventListener( 'click', function () {
			close( item );
		} );

		item.appendChild( notice );
		item.appendChild( button );
		stack.appendChild( item );

		// Close button in the notice's text colour (green, red, blue...).
		// Read once it's in the page: detached elements have no styles.
		item.style.setProperty(
			'--upperel-notice-color',
			window.getComputedStyle( notice ).color
		);

		// Next frame, so the entrance transition runs.
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				item.classList.add( 'is-visible' );
			} );
		} );

		if ( ! notice.classList.contains( 'woocommerce-error' ) ) {
			autoClose( item );
		}
	}

	function collect( root ) {
		if ( root.nodeType !== 1 ) {
			return;
		}
		const found = [];
		if ( root.matches( NOTICE ) && root.parentElement && root.parentElement.matches( WRAPPER ) ) {
			found.push( root );
		}
		root.querySelectorAll( WRAPPER ).forEach( function ( wrapper ) {
			Array.prototype.forEach.call( wrapper.children, function ( child ) {
				if ( child.matches( NOTICE ) ) {
					found.push( child );
				}
			} );
		} );
		if ( root.matches( WRAPPER ) ) {
			Array.prototype.forEach.call( root.children, function ( child ) {
				if ( child.matches( NOTICE ) && found.indexOf( child ) === -1 ) {
					found.push( child );
				}
			} );
		}
		found.forEach( float );
	}

	collect( document.body );

	// Notices WooCommerce adds after the page has loaded.
	new MutationObserver( function ( mutations ) {
		mutations.forEach( function ( mutation ) {
			mutation.addedNodes.forEach( collect );
		} );
	} ).observe( document.body, { childList: true, subtree: true } );
} )();
