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

	foreach ( array( 'buttons', 'forms', 'woocommerce', 'title', 'links', 'tailwind' ) as $sheet ) {
		$css = '/assets/css/base/upperel-' . $sheet . '.css';
		$ver = file_exists( $theme_dir . $css ) ? (string) filemtime( $theme_dir . $css ) : null;

		wp_enqueue_style( 'upperel-' . $sheet, $theme_uri . $css, $deps, $ver );
		wp_style_add_data( 'upperel-' . $sheet, 'rtl', 'replace' );
	}
}
add_action( 'wp_enqueue_scripts', 'upperel_styles', 25 );
