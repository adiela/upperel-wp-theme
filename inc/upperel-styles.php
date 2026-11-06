<?php
/**
 * Upperel site-wide style overrides.
 *
 * Stylesheets that adjust Storefront/WooCommerce defaults. They load after
 * Storefront's and WooCommerce's CSS so equal-specificity rules win.
 *
 * @package storefront
 */

/**
 * Google Fonts: Montserrat for headings and Poppins for everything else
 * (font stacks in assets/css/sass/utils/_variables.scss), instead of
 * Storefront's Source Sans Pro. Storefront loads these on the front end
 * and in the block editor.
 *
 * Weights: Storefront's headings are light (300), the footer's headings
 * semi-bold (600); text uses 400, 600 and 700, with italics.
 *
 * @return array Google Fonts families.
 */
function upperel_google_fonts() {
	return array(
		'montserrat' => 'Montserrat:300,400,500,600,700',
		'poppins'    => 'Poppins:300,400,400italic,500,600,600italic,700,700italic',
	);
}
add_filter( 'storefront_google_font_families', 'upperel_google_fonts' );

/**
 * Show text in a fallback font while the Google Fonts load, rather than
 * hiding it.
 *
 * @param string $src    Stylesheet URL.
 * @param string $handle Stylesheet handle.
 * @return string
 */
function upperel_google_fonts_display_swap( $src, $handle ) {
	if ( 'storefront-fonts' === $handle ) {
		$src = add_query_arg( 'display', 'swap', $src );
	}
	return $src;
}
add_filter( 'style_loader_src', 'upperel_google_fonts_display_swap', 10, 2 );

/**
 * Enqueue Upperel override stylesheets.
 */
function upperel_styles() {
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$deps      = array( 'storefront-style' );

	foreach ( array( 'storefront-woocommerce-style', 'wc-blocks-style' ) as $handle ) {
		if ( wp_style_is( $handle, 'registered' ) ) {
			$deps[] = $handle;
		}
	}

	foreach ( array( 'buttons', 'forms', 'woocommerce', 'title', 'links', 'filters', 'footer', 'notices', 'tailwind' ) as $sheet ) {
		$css = '/assets/css/base/upperel-' . $sheet . '.css';
		$ver = file_exists( $theme_dir . $css ) ? (string) filemtime( $theme_dir . $css ) : null;

		wp_enqueue_style( 'upperel-' . $sheet, $theme_uri . $css, $deps, $ver );
		wp_style_add_data( 'upperel-' . $sheet, 'rtl', 'replace' );
	}

	// The Customizer's button background colour (Customize → Buttons), for
	// outlined buttons' text and border in upperel-buttons.scss.
	$button_bg = sanitize_hex_color( get_theme_mod( 'storefront_button_background_color' ) );
	wp_add_inline_style(
		'upperel-buttons',
		sprintf( ':root{--upperel-button-bg:%s;}', $button_bg ? $button_bg : '#96588a' )
	);
}
add_action( 'wp_enqueue_scripts', 'upperel_styles', 25 );

/**
 * Floating WooCommerce notices (assets/js/notices.js, upperel-notices.scss).
 */
function upperel_notices_assets() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$js     = '/assets/js/notices' . $suffix . '.js';
	$path   = get_template_directory() . $js;

	wp_enqueue_script(
		'upperel-notices',
		get_template_directory_uri() . $js,
		array(),
		file_exists( $path ) ? (string) filemtime( $path ) : null,
		true
	);
	wp_localize_script( 'upperel-notices', 'upperelNotices', array( 'close' => __( 'Close notice', 'storefront' ) ) );
}
add_action( 'wp_enqueue_scripts', 'upperel_notices_assets', 20 );
