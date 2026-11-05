/**
 * Image Slider – front-end behaviour.
 *
 * Vanilla JS, no dependencies. Handles: full-bleed sizing, prev/next,
 * dots, autoplay (pause on hover/focus/hidden tab + explicit pause button),
 * keyboard arrows, touch/mouse swipe and lazy-image preloading.
 */
( function () {
	'use strict';

	const SWIPE_THRESHOLD = 50;
	const reducedMotion =
		window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/**
	 * Stretch the slider to the full viewport width, wherever it sits in the
	 * layout (centred content, sidebar layouts, RTL). CSS provides a
	 * calc(50% - 50vw) fallback for before this runs.
	 *
	 * @param {HTMLElement} root Slider element.
	 */
	function fitToViewport( root ) {
		const parent = root.parentElement;
		if ( ! parent ) {
			return;
		}
		const cs = window.getComputedStyle( parent );
		const rect = parent.getBoundingClientRect();
		const vw = document.documentElement.clientWidth;
		const rtl = window.getComputedStyle( root ).direction === 'rtl';
		const offset = rtl
			? vw -
			  ( rect.right -
					parseFloat( cs.paddingRight ) -
					parseFloat( cs.borderRightWidth ) )
			: rect.left +
			  parseFloat( cs.paddingLeft ) +
			  parseFloat( cs.borderLeftWidth );

		root.style.setProperty( '--upperel-slider-width', vw + 'px' );
		root.style.setProperty( '--upperel-slider-offset', -offset + 'px' );
	}

	function init( root ) {
		if ( root.dataset.sliderReady ) {
			return;
		}
		root.dataset.sliderReady = 'true';

		// Ensure fitting runs now and on resize.
		let resizeFrame = null;
		fitToViewport( root );
		window.addEventListener( 'resize', function () {
			if ( resizeFrame ) {
				return;
			}
			resizeFrame = window.requestAnimationFrame( function () {
				resizeFrame = null;
				fitToViewport( root );
			} );
		} );

		const slides = Array.prototype.slice.call(
			root.querySelectorAll( '.upperel-slider__slide' )
		);
		const count = slides.length;
		if ( count < 2 ) {
			root.classList.add( 'is-ready' );
			return;
		}

		const track = root.querySelector( '.upperel-slider__track' );
		const dots = Array.prototype.slice.call(
			root.querySelectorAll( '.upperel-slider__dot' )
		);
		const prevBtn = root.querySelector( '.upperel-slider__arrow--prev' );
		const nextBtn = root.querySelector( '.upperel-slider__arrow--next' );
		const toggleBtn = root.querySelector( '.upperel-slider__toggle' );
		const isFade = root.classList.contains( 'is-transition-fade' );
		const rtl = window.getComputedStyle( root ).direction === 'rtl';

		let index = 0;
		let timer = null;
		const interval = parseInt( root.dataset.interval, 10 ) || 5000;
		const autoplay =
			root.dataset.autoplay === 'true' && count > 1 && ! reducedMotion;
		let userPaused = false;
		let hoverPaused = false;
		let focusPaused = false;

		function preload( i ) {
			const img = slides[ ( i + count ) % count ].querySelector( 'img' );
			if ( img && img.loading === 'lazy' ) {
				img.loading = 'eager';
			}
		}

		function goTo( i ) {
			index = ( i + count ) % count;

			if ( ! isFade ) {
				track.style.transform =
					'translateX(' + ( rtl ? 1 : -1 ) * index * 100 + '%)';
			}

			slides.forEach( function ( slide, n ) {
				const active = n === index;
				slide.classList.toggle( 'is-active', active );
				if ( active ) {
					slide.removeAttribute( 'inert' );
				} else {
					slide.setAttribute( 'inert', '' );
				}
			} );

			dots.forEach( function ( dot, n ) {
				dot.setAttribute(
					'aria-current',
					n === index ? 'true' : 'false'
				);
			} );

			preload( index );
			preload( index + 1 );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function start() {
			stop();
			if (
				! autoplay ||
				userPaused ||
				hoverPaused ||
				focusPaused ||
				document.hidden
			) {
				return;
			}
			timer = window.setInterval( function () {
				goTo( index + 1 );
			}, interval );
		}

		// User-driven navigation also restarts the autoplay countdown.
		function navigate( i ) {
			goTo( i );
			start();
		}

		function updateToggle() {
			// Announce slide changes only while not auto-rotating.
			track.setAttribute(
				'aria-live',
				autoplay && ! userPaused ? 'off' : 'polite'
			);
			if ( ! toggleBtn ) {
				return;
			}
			root.classList.toggle( 'is-paused', userPaused );
			toggleBtn.setAttribute(
				'aria-label',
				userPaused
					? toggleBtn.dataset.labelPlay
					: toggleBtn.dataset.labelPause
			);
		}

		// Controls.
		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				navigate( index - 1 );
			} );
		}
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				navigate( index + 1 );
			} );
		}
		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				navigate( parseInt( dot.dataset.index, 10 ) );
			} );
		} );
		if ( toggleBtn ) {
			if ( ! autoplay ) {
				// Reduced motion: autoplay is off, so the toggle is pointless.
				toggleBtn.hidden = true;
			}
			toggleBtn.addEventListener( 'click', function () {
				userPaused = ! userPaused;
				updateToggle();
				start();
			} );
		}

		// Keyboard: arrow keys while focus is inside the slider.
		root.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' ) {
				return;
			}
			const forward = e.key === 'ArrowRight' ? ! rtl : rtl;
			e.preventDefault();
			navigate( index + ( forward ? 1 : -1 ) );
		} );

		// Pause while hovered or focused (WCAG 2.2.2).
		root.addEventListener( 'mouseenter', function () {
			hoverPaused = true;
			stop();
		} );
		root.addEventListener( 'mouseleave', function () {
			hoverPaused = false;
			start();
		} );
		root.addEventListener( 'focusin', function () {
			focusPaused = true;
			stop();
		} );
		root.addEventListener( 'focusout', function ( e ) {
			if ( ! root.contains( e.relatedTarget ) ) {
				focusPaused = false;
				start();
			}
		} );
		document.addEventListener( 'visibilitychange', start );

		// Swipe (touch + mouse drag). A swipe must not trigger the slide link.
		const viewport = root.querySelector( '.upperel-slider__viewport' );
		let startX = null;
		let startY = null;
		let swiped = false;

		viewport.addEventListener( 'pointerdown', function ( e ) {
			if ( e.pointerType === 'mouse' && e.button !== 0 ) {
				return;
			}
			startX = e.clientX;
			startY = e.clientY;
			swiped = false;
		} );
		viewport.addEventListener( 'pointerup', function ( e ) {
			if ( startX === null ) {
				return;
			}
			const dx = e.clientX - startX;
			const dy = e.clientY - startY;
			startX = null;
			if (
				Math.abs( dx ) > SWIPE_THRESHOLD &&
				Math.abs( dx ) > Math.abs( dy )
			) {
				swiped = true;
				const forward = dx < 0 ? ! rtl : rtl;
				navigate( index + ( forward ? 1 : -1 ) );
			}
		} );
		viewport.addEventListener( 'pointercancel', function () {
			startX = null;
		} );
		viewport.addEventListener(
			'click',
			function ( e ) {
				if ( swiped ) {
					e.preventDefault();
					e.stopPropagation();
					swiped = false;
				}
			},
			true
		);
		viewport.addEventListener( 'dragstart', function ( e ) {
			e.preventDefault();
		} );

		updateToggle();
		goTo( 0 );
		start();
		root.classList.add( 'is-ready' );
	}

	function initAll() {
		Array.prototype.forEach.call(
			document.querySelectorAll( '.upperel-slider' ),
			init
		);
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
