/**
 * Single product page.
 *
 * 1. Gallery: make the thumbnail column usable from the keyboard. (The
 *    main image's height cap is plain CSS, in upperel-woocommerce.scss.)
 *
 * 2. Quantity − / + buttons (added in inc/upperel-woocommerce.php): step
 *    the field within its min/max/step and disable at the limits.
 *
 * 2b. Variation buttons in place of the variation dropdowns.
 *
 * 3. Description / Attributes tabs in the summary
 *    (inc/upperel-woocommerce.php): switch panels on click and arrow keys.
 *
 * 4. Collapsed sections (Reviews etc., inc/upperel-woocommerce.php): open
 *    the right section when the page is opened at, or a link points to,
 *    #reviews or a review (#comment-…), e.g. the "customer reviews" link by
 *    the price, or the redirect after posting a review.
 */
( function () {
	'use strict';

	function initGallery( gallery ) {
		let frame = null;

		// The slider's thumbnails are bare images with click handlers. Make
		// them buttons: focusable, named "Show image N of M", marked when
		// showing (aria-current), and pressed with Enter or Space.
		function setUpThumbs() {
			const thumbs = gallery.querySelectorAll( '.flex-control-thumbs img' );
			thumbs.forEach( function ( thumb, i ) {
				if ( thumb.dataset.upperelThumb ) {
					return;
				}
				thumb.dataset.upperelThumb = '1';
				thumb.setAttribute( 'role', 'button' );
				thumb.setAttribute( 'tabindex', '0' );
				thumb.setAttribute( 'alt', '' );
				thumb.setAttribute(
					'aria-label',
					'Show image ' + ( i + 1 ) + ' of ' + thumbs.length
				);
				thumb.addEventListener( 'keydown', function ( e ) {
					if ( e.key === 'Enter' || e.key === ' ' ) {
						e.preventDefault();
						thumb.click();
					}
				} );
			} );
			thumbs.forEach( function ( thumb ) {
				if ( thumb.classList.contains( 'flex-active' ) ) {
					thumb.setAttribute( 'aria-current', 'true' );
				} else {
					thumb.removeAttribute( 'aria-current' );
				}
			} );
		}

		function update() {
			frame = null;
			setUpThumbs();
		}

		function schedule() {
			if ( ! frame ) {
				frame = window.requestAnimationFrame( update );
			}
		}

		update();

		// The slider adds the thumbnails after load, and moves .flex-active
		// between them as the image changes.
		new MutationObserver( schedule ).observe( gallery, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: [ 'class' ],
		} );
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

	// Tabs (WAI-ARIA tabs pattern): click, or arrow keys / Home / End when a
	// tab has focus. Selecting a tab shows its panel and hides the others.
	function initInfoTabs( container ) {
		const tabs = Array.prototype.slice.call(
			container.querySelectorAll( '[role="tab"]' )
		);

		function select( tab, focus ) {
			tabs.forEach( function ( t ) {
				const selected = t === tab;
				t.setAttribute( 'aria-selected', selected ? 'true' : 'false' );
				t.tabIndex = selected ? 0 : -1;
				const panel = document.getElementById(
					t.getAttribute( 'aria-controls' )
				);
				if ( panel ) {
					panel.hidden = ! selected;
				}
			} );
			if ( focus ) {
				tab.focus();
			}
		}

		tabs.forEach( function ( tab, i ) {
			tab.addEventListener( 'click', function () {
				select( tab, false );
			} );
			tab.addEventListener( 'keydown', function ( e ) {
				let next = null;
				if ( e.key === 'ArrowRight' ) {
					next = tabs[ ( i + 1 ) % tabs.length ];
				} else if ( e.key === 'ArrowLeft' ) {
					next = tabs[ ( i - 1 + tabs.length ) % tabs.length ];
				} else if ( e.key === 'Home' ) {
					next = tabs[ 0 ];
				} else if ( e.key === 'End' ) {
					next = tabs[ tabs.length - 1 ];
				}
				if ( next ) {
					e.preventDefault();
					select( next, true );
				}
			} );
		} );
	}

	// Variation buttons: one outlined button per option, the selected one
	// filled, in place of each variation dropdown. The (hidden) dropdowns
	// stay the source of truth, so WooCommerce's variation script, the form
	// submission and the "Clear" link work unchanged. Without JavaScript the
	// dropdowns simply stay visible.
	function initSwatches( form ) {
		const $ = window.jQuery;
		if ( ! $ ) {
			return;
		}

		form.querySelectorAll( 'table.variations select' ).forEach( function (
			select
		) {
			const options = Array.prototype.slice
				.call( select.options )
				.filter( function ( o ) {
					return o.value !== '';
				} )
				.map( function ( o ) {
					return { value: o.value, label: o.textContent.trim() };
				} );
			if ( ! options.length ) {
				return;
			}

			const group = document.createElement( 'div' );
			group.className = 'upperel-swatches';
			group.setAttribute( 'role', 'group' );
			const label = select.id
				? form.querySelector(
						'label[for="' + window.CSS.escape( select.id ) + '"]'
				  )
				: null;
			if ( label ) {
				label.id = label.id || select.id + '-label';
				group.setAttribute( 'aria-labelledby', label.id );
			}

			// Mark which option is chosen and which are still available
			// (WooCommerce removes or disables the ones that don't match
			// the other choices).
			function sync() {
				group
					.querySelectorAll( '.upperel-swatch' )
					.forEach( function ( button ) {
						const option = Array.prototype.find.call(
							select.options,
							function ( o ) {
								return o.value === button.dataset.value;
							}
						);
						button.disabled = ! option || option.disabled;
						button.setAttribute(
							'aria-pressed',
							select.value === button.dataset.value
								? 'true'
								: 'false'
						);
					} );
			}

			options.forEach( function ( option ) {
				const button = document.createElement( 'button' );
				button.type = 'button';
				button.className = 'upperel-swatch';
				button.textContent = option.label;
				button.dataset.value = option.value;
				button.addEventListener( 'click', function () {
					// Clicking the selected option again deselects it.
					const next =
						select.value === option.value ? '' : option.value;
					$( select ).val( next ).trigger( 'change' );
					sync();
				} );
				group.appendChild( button );
			} );

			select.insertAdjacentElement( 'afterend', group );
			select.hidden = true;
			select.classList.add( 'upperel-swatches__select' );

			$( select ).on( 'change', sync );
			$( form ).on(
				'woocommerce_update_variation_values reset_data found_variation check_variations',
				function () {
					window.setTimeout( sync, 0 );
				}
			);
			sync();
		} );
	}

	document
		.querySelectorAll( '.single-product .quantity' )
		.forEach( initQuantity );

	document
		.querySelectorAll( '.single-product form.variations_form' )
		.forEach( initSwatches );

	document
		.querySelectorAll( '.upperel-product-info--tabs' )
		.forEach( initInfoTabs );

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
