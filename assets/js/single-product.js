/**
 * Single product page.
 *
 * 1. Gallery: fit the main image in the first screen, and label the slider's
 *    dots. Measures the space above the main image (admin bar, header and
 *    the gap below it) plus the space the gallery takes below it (the
 *    slider's row of arrows and dots), and stores the total in
 *    --upperel-gallery-offset on the gallery. CSS
 *    (assets/css/base/upperel-woocommerce.scss) then sets
 *      max-height: calc(100svh - var(--upperel-gallery-offset))
 *    on the main image(s).
 *
 * 2. Quantity − / + buttons (added in inc/upperel-woocommerce.php): step
 *    the field within its min/max/step and disable at the limits.
 *
 * 3. Collapsed sections (Reviews etc., inc/upperel-woocommerce.php): open
 *    the right section when the page is opened at, or a link points to,
 *    #reviews or a review (#comment-…), e.g. the "customer reviews" link by
 *    the price, or the redirect after posting a review.
 */
( function () {
	'use strict';

	function initGallery( gallery ) {
		let frame = null;

		// The slider's dots are links whose text is just "1", "2"…; give them
		// clearer names for screen readers.
		function labelDots() {
			const dots = gallery.querySelectorAll( '.flex-control-paging a' );
			dots.forEach( function ( dot, i ) {
				if ( ! dot.hasAttribute( 'aria-label' ) ) {
					dot.setAttribute(
						'aria-label',
						'Show image ' + ( i + 1 ) + ' of ' + dots.length
					);
				}
			} );
		}

		function update() {
			frame = null;

			// The slide currently shown (the slider marks it), else the first.
			const slide =
				gallery.querySelector(
					'.woocommerce-product-gallery__image.flex-active-slide'
				) ||
				gallery.querySelector( '.woocommerce-product-gallery__image' );
			if ( ! slide ) {
				return;
			}

			// Distance from the top of the page to the top of the main image.
			const above = slide.getBoundingClientRect().top + window.scrollY;

			// Space the gallery takes below the image: the slider's control row
			// (arrows and dots), or a thumbnail strip if one is shown. Measured
			// from the bottom of the image area, which the controls follow, so
			// it stays constant while the slider animates its height between
			// differently shaped images.
			const imageArea =
				gallery.querySelector( '.flex-viewport' ) ||
				gallery.querySelector(
					'.woocommerce-product-gallery__wrapper'
				);
			const controls = gallery.querySelectorAll(
				'.flex-control-nav, .flex-direction-nav li'
			);
			let below = 0;
			controls.forEach( function ( el ) {
				const style = window.getComputedStyle( el );
				if (
					style.position === 'absolute' ||
					style.display === 'none'
				) {
					return;
				}
				below = Math.max(
					below,
					el.getBoundingClientRect().bottom +
						parseFloat( style.marginBottom ) -
						imageArea.getBoundingClientRect().bottom
				);
			} );

			gallery.style.setProperty(
				'--upperel-gallery-offset',
				Math.ceil( above + below ) + 'px'
			);

			labelDots();
		}

		function schedule() {
			if ( ! frame ) {
				frame = window.requestAnimationFrame( update );
			}
		}

		update();
		window.addEventListener( 'load', schedule );
		window.addEventListener( 'resize', schedule );

		// WooCommerce's slider script builds the dots and arrows after load,
		// and images arriving can shift things; re-measure when sizes change.
		if ( 'ResizeObserver' in window ) {
			const observer = new window.ResizeObserver( schedule );
			observer.observe( gallery );
			const header = document.getElementById( 'masthead' );
			if ( header ) {
				observer.observe( header );
			}
		}
	}

	function initSections( container ) {
		// Open the section containing the element a #hash points to.
		function openFor( hash, scroll ) {
			if ( ! hash || hash.length < 2 ) {
				return;
			}
			let target = null;
			try {
				target = document.querySelector( hash );
			} catch ( e ) {
				return; // Not a valid selector.
			}
			if ( ! target || ! container.contains( target ) ) {
				return;
			}
			const section = target.closest( 'details' );
			if ( section && ! section.open ) {
				section.open = true;
				if ( scroll ) {
					target.scrollIntoView();
				}
			}
		}

		// Page opened at #reviews, #comment-123, etc.
		openFor( window.location.hash, true );
		window.addEventListener( 'hashchange', function () {
			openFor( window.location.hash, true );
		} );

		// In-page links to a section (e.g. "(2 customer reviews)" by the
		// price): open it before the browser scrolls to the link target.
		document.addEventListener( 'click', function ( e ) {
			const link = e.target.closest( 'a[href*="#"]' );
			if ( ! link || link.pathname !== window.location.pathname ) {
				return;
			}
			openFor( link.hash, false );
		} );
	}

	function initQuantity( wrapper ) {
		const minus = wrapper.querySelector( '.upperel-qty__button--minus' );
		const plus = wrapper.querySelector( '.upperel-qty__button--plus' );
		if ( ! minus || ! plus ) {
			return;
		}
		const input = wrapper.querySelector( 'input.qty' );

		// Hidden or read-only field: the quantity is fixed, no buttons.
		if ( ! input || input.type === 'hidden' || input.readOnly ) {
			minus.remove();
			plus.remove();
			return;
		}
		wrapper.classList.add( 'upperel-qty' );

		function limits() {
			const min = parseFloat( input.min );
			const max = parseFloat( input.max );
			return {
				min: isNaN( min ) ? 0 : min,
				max: isNaN( max ) ? Infinity : max,
			};
		}

		function refresh() {
			const value = parseFloat( input.value ) || 0;
			const { min, max } = limits();
			minus.disabled = value <= min;
			plus.disabled = value >= max;
		}

		function step( direction ) {
			const { min, max } = limits();
			const stepSize = parseFloat( input.step ) || 1;
			const value = parseFloat( input.value ) || 0;
			const next = Math.min(
				max,
				Math.max( min, value + direction * stepSize )
			);
			if ( next === value ) {
				return;
			}
			// Avoid floating-point noise for decimal steps (e.g. 0.1).
			input.value = parseFloat( next.toFixed( 6 ) );
			// Let WooCommerce and other scripts react as if typed.
			input.dispatchEvent(
				new window.Event( 'input', { bubbles: true } )
			);
			input.dispatchEvent(
				new window.Event( 'change', { bubbles: true } )
			);
			refresh();
		}

		minus.addEventListener( 'click', function () {
			step( -1 );
		} );
		plus.addEventListener( 'click', function () {
			step( 1 );
		} );
		input.addEventListener( 'input', refresh );
		input.addEventListener( 'change', refresh );

		// Variable products: WooCommerce updates the field's min/max with
		// jQuery when a variation is chosen, without a native event.
		const form = wrapper.closest( 'form' );
		if ( form && window.jQuery ) {
			window
				.jQuery( form )
				.on( 'found_variation reset_data', function () {
					window.setTimeout( refresh, 0 );
				} );
		}

		refresh();
	}

	document
		.querySelectorAll( '.single-product .quantity' )
		.forEach( initQuantity );

	const gallery = document.querySelector(
		'.single-product div.product .woocommerce-product-gallery'
	);
	if ( gallery ) {
		initGallery( gallery );
	}

	const sections = document.querySelector( '.upperel-product-sections' );
	if ( sections ) {
		initSections( sections );
	}
} )();
