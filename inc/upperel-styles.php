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

	foreach ( array( 'buttons', 'forms', 'woocommerce', 'title', 'links', 'filters', 'tailwind' ) as $sheet ) {
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
