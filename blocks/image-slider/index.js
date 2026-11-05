/**
 * Image Slider – editor UI.
 *
 * Written against the global `wp.*` packages so it runs without a build step.
 * Front-end markup is rendered server-side by render.php.
 */
( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const useState = wp.element.useState;
	const __ = wp.i18n.__;
	const sprintf = wp.i18n.sprintf;

	const be = wp.blockEditor;
	const c = wp.components;

	const ALLOWED_TYPES = [ 'image' ];

	// Inline SVG icons: dashicons are not loaded inside the editor iframe.
	function svgIcon( d, size ) {
		return el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				viewBox: '0 0 24 24',
				width: size || 24,
				height: size || 24,
				'aria-hidden': true,
				focusable: false,
			},
			el( 'path', { d, fill: 'currentColor' } )
		);
	}
	const ICON_PREV = svgIcon( 'M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z' );
	const ICON_NEXT = svgIcon( 'M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z' );
	const ICON_PLUS = svgIcon(
		'M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z'
	);
	const ICON_LINK = svgIcon(
		'M10 17.4l-1.5 1.5a2.5 2.5 0 01-3.5-3.5l3-3a2.5 2.5 0 013.5 0l1.1-1.1a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1.5-1.5zM14 6.6l1.5-1.5a2.5 2.5 0 013.5 3.5l-3 3a2.5 2.5 0 01-3.5 0l-1.1 1.1a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7L12.9 5.5z',
		12
	);

	function slideFromMedia( media ) {
		return {
			id: media.id,
			url: media.url,
			alt: media.alt || '',
			link: '',
			newTab: false,
		};
	}

	function Edit( props ) {
		const attributes = props.attributes;
		const setAttributes = props.setAttributes;
		const slides = attributes.slides || [];

		const activeState = useState( 0 );
		const active = Math.min(
			activeState[ 0 ],
			Math.max( 0, slides.length - 1 )
		);
		const setActive = activeState[ 1 ];
		const current = slides[ active ];

		const blockProps = be.useBlockProps( {
			className: 'upperel-slider-editor',
			style: {
				'--upperel-slider-height': attributes.height + 'vh',
			},
		} );

		function setSlides( next ) {
			setAttributes( { slides: next } );
		}

		function updateCurrent( changes ) {
			setSlides(
				slides.map( function ( s, i ) {
					return i === active ? Object.assign( {}, s, changes ) : s;
				} )
			);
		}

		function addMedia( media ) {
			const list = Array.isArray( media ) ? media : [ media ];
			const next = slides.concat( list.map( slideFromMedia ) );
			setSlides( next );
			setActive( slides.length ); // Jump to the first new slide.
		}

		function replaceCurrent( media ) {
			updateCurrent( {
				id: media.id,
				url: media.url,
				alt: media.alt || current.alt || '',
			} );
		}

		function move( dir ) {
			const target = active + dir;
			if ( target < 0 || target >= slides.length ) {
				return;
			}
			const next = slides.slice();
			const tmp = next[ target ];
			next[ target ] = next[ active ];
			next[ active ] = tmp;
			setSlides( next );
			setActive( target );
		}

		function removeCurrent() {
			setSlides(
				slides.filter( function ( s, i ) {
					return i !== active;
				} )
			);
			setActive( Math.max( 0, active - 1 ) );
		}

		// ---- Empty state ----------------------------------------------------
		if ( ! slides.length ) {
			return el(
				'div',
				blockProps,
				el( be.MediaPlaceholder, {
					icon: 'slides',
					labels: {
						title: __( 'Image Slider', 'woocommerce' ),
						instructions: __(
							'Upload or pick images for the slider. You can set a link for each slide afterwards in the block settings.',
							'woocommerce'
						),
					},
					accept: 'image/*',
					allowedTypes: ALLOWED_TYPES,
					multiple: true,
					onSelect: addMedia,
				} )
			);
		}

		// ---- Toolbar --------------------------------------------------------
		const toolbar = el(
			be.BlockControls,
			{ group: 'other' },
			el(
				c.ToolbarGroup,
				null,
				el(
					be.MediaUploadCheck,
					null,
					el( be.MediaUpload, {
						allowedTypes: ALLOWED_TYPES,
						value: current.id,
						onSelect: replaceCurrent,
						render( o ) {
							return el( c.ToolbarButton, {
								icon: 'format-image',
								label: __( 'Replace image', 'woocommerce' ),
								onClick: o.open,
							} );
						},
					} )
				),
				el( c.ToolbarButton, {
					icon: 'arrow-left-alt2',
					label: __( 'Move slide earlier', 'woocommerce' ),
					disabled: active === 0,
					onClick() {
						move( -1 );
					},
				} ),
				el( c.ToolbarButton, {
					icon: 'arrow-right-alt2',
					label: __( 'Move slide later', 'woocommerce' ),
					disabled: active === slides.length - 1,
					onClick() {
						move( 1 );
					},
				} ),
				el( c.ToolbarButton, {
					icon: 'trash',
					label: __( 'Remove slide', 'woocommerce' ),
					onClick: removeCurrent,
				} )
			)
		);

		// ---- Sidebar --------------------------------------------------------
		const inspector = el(
			be.InspectorControls,
			null,
			el(
				c.PanelBody,
				{
					title: sprintf(
						/* translators: 1: slide number, 2: total */
						__( 'Slide %1$d of %2$d', 'woocommerce' ),
						active + 1,
						slides.length
					),
					initialOpen: true,
				},
				el(
					'div',
					{ className: 'upperel-slider-editor__field' },
					el(
						c.BaseControl,
						{
							id: 'upperel-slider-link-' + props.clientId,
							label: __( 'Link to', 'woocommerce' ),
							help: __(
								'Search for a page or paste any URL. Leave empty for no link.',
								'woocommerce'
							),
							__nextHasNoMarginBottom: true,
						},
						el( be.URLInput, {
							id: 'upperel-slider-link-' + props.clientId,
							value: current.link || '',
							onChange( url ) {
								updateCurrent( { link: url } );
							},
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el( c.ToggleControl, {
					label: __( 'Open in new tab', 'woocommerce' ),
					checked: !! current.newTab,
					disabled: ! current.link,
					onChange( v ) {
						updateCurrent( { newTab: v } );
					},
					__nextHasNoMarginBottom: true,
				} ),
				el( c.TextareaControl, {
					label: __( 'Alternative text', 'woocommerce' ),
					help: __(
						'Describe the image (and where the link goes, if it has one). Read by screen readers.',
						'woocommerce'
					),
					value: current.alt || '',
					onChange( v ) {
						updateCurrent( { alt: v } );
					},
					__nextHasNoMarginBottom: true,
				} )
			),
			el(
				c.PanelBody,
				{ title: __( 'Slider settings', 'woocommerce' ) },
				el( c.RangeControl, {
					label: __(
						'Height (desktop, % of screen height)',
						'woocommerce'
					),
					value: attributes.height,
					min: 20,
					max: 100,
					onChange( v ) {
						setAttributes( { height: v || 70 } );
					},
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ),
				el( c.RangeControl, {
					label: __(
						'Height (mobile, % of screen height)',
						'woocommerce'
					),
					value: attributes.mobileHeight,
					min: 20,
					max: 100,
					onChange( v ) {
						setAttributes( { mobileHeight: v || 50 } );
					},
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ),
				el( c.SelectControl, {
					label: __( 'Transition', 'woocommerce' ),
					value: attributes.transition,
					options: [
						{ label: __( 'Slide', 'woocommerce' ), value: 'slide' },
						{ label: __( 'Fade', 'woocommerce' ), value: 'fade' },
					],
					onChange( v ) {
						setAttributes( { transition: v } );
					},
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
				} ),
				el( c.ToggleControl, {
					label: __( 'Autoplay', 'woocommerce' ),
					checked: attributes.autoplay,
					onChange( v ) {
						setAttributes( { autoplay: v } );
					},
					__nextHasNoMarginBottom: true,
				} ),
				attributes.autoplay &&
					el( c.RangeControl, {
						label: __( 'Seconds per slide', 'woocommerce' ),
						value: attributes.interval,
						min: 2,
						max: 30,
						onChange( v ) {
							setAttributes( { interval: v || 5 } );
						},
						__nextHasNoMarginBottom: true,
						__next40pxDefaultSize: true,
					} ),
				el( c.ToggleControl, {
					label: __( 'Show arrows', 'woocommerce' ),
					checked: attributes.showArrows,
					onChange( v ) {
						setAttributes( { showArrows: v } );
					},
					__nextHasNoMarginBottom: true,
				} ),
				el( c.ToggleControl, {
					label: __( 'Show dots', 'woocommerce' ),
					checked: attributes.showDots,
					onChange( v ) {
						setAttributes( { showDots: v } );
					},
					__nextHasNoMarginBottom: true,
				} )
			)
		);

		// ---- Canvas preview -------------------------------------------------
		const stage = el(
			'div',
			{ className: 'upperel-slider-editor__stage' },
			el( 'img', {
				className: 'upperel-slider-editor__img',
				src: current.url,
				alt: current.alt || '',
			} ),
			slides.length > 1 &&
				el( c.Button, {
					className:
						'upperel-slider-editor__nav upperel-slider-editor__nav--prev',
					icon: ICON_PREV,
					label: __( 'Previous slide', 'woocommerce' ),
					onClick() {
						setActive(
							( active - 1 + slides.length ) % slides.length
						);
					},
				} ),
			slides.length > 1 &&
				el( c.Button, {
					className:
						'upperel-slider-editor__nav upperel-slider-editor__nav--next',
					icon: ICON_NEXT,
					label: __( 'Next slide', 'woocommerce' ),
					onClick() {
						setActive( ( active + 1 ) % slides.length );
					},
				} ),
			el(
				'div',
				{ className: 'upperel-slider-editor__badge' },
				el(
					'span',
					null,
					sprintf(
						/* translators: 1: slide number, 2: total */
						__( 'Slide %1$d / %2$d', 'woocommerce' ),
						active + 1,
						slides.length
					)
				),
				el(
					'span',
					{
						className:
							'upperel-slider-editor__link' +
							( current.link ? '' : ' is-empty' ),
					},
					current.link
						? '→ ' + current.link
						: __(
								'No link – set one in the sidebar',
								'woocommerce'
						  )
				)
			)
		);

		const thumbs = el(
			'div',
			{ className: 'upperel-slider-editor__thumbs' },
			slides.map( function ( s, i ) {
				return el(
					'button',
					{
						key: ( s.id || s.url ) + '-' + i,
						type: 'button',
						className:
							'upperel-slider-editor__thumb' +
							( i === active ? ' is-active' : '' ),
						onClick() {
							setActive( i );
						},
						'aria-label': sprintf(
							/* translators: %d: slide number */
							__( 'Edit slide %d', 'woocommerce' ),
							i + 1
						),
						'aria-pressed': i === active,
					},
					el( 'img', { src: s.url, alt: '' } ),
					s.link &&
						el(
							'span',
							{ className: 'upperel-slider-editor__thumb-link' },
							ICON_LINK
						)
				);
			} ),
			el(
				be.MediaUploadCheck,
				null,
				el( be.MediaUpload, {
					allowedTypes: ALLOWED_TYPES,
					multiple: true,
					onSelect: addMedia,
					render( o ) {
						return el( c.Button, {
							className: 'upperel-slider-editor__add',
							icon: ICON_PLUS,
							label: __( 'Add images', 'woocommerce' ),
							onClick: o.open,
						} );
					},
				} )
			)
		);

		return el(
			Fragment,
			null,
			toolbar,
			inspector,
			el( 'div', blockProps, stage, thumbs )
		);
	}

	wp.blocks.registerBlockType( 'upperel/image-slider', {
		edit: Edit,
		save() {
			return null; // Rendered by render.php.
		},
	} );
} )( window.wp );
