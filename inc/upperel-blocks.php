<?php
/**
 * Upperel custom blocks.
 *
 * Blocks live in /blocks/<name>/ (block.json + plain JS, no build step).
 * Their stylesheets live in /assets/css/blocks/ so the theme's existing
 * `npm run build:css` pipeline compiles, prefixes and RTL-flips them.
 *
 * @package storefront
 */

/**
 * Register block stylesheets and blocks.
 */
function upperel_register_blocks() {
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$css       = '/assets/css/blocks/';

	foreach ( array( 'image-slider', 'image-slider-editor' ) as $sheet ) {
		$path = $theme_dir . $css . $sheet . '.css';
		$ver  = file_exists( $path ) ? (string) filemtime( $path ) : null;

		wp_register_style( 'upperel-' . $sheet, $theme_uri . $css . $sheet . '.css', array(), $ver );
		wp_style_add_data( 'upperel-' . $sheet, 'rtl', 'replace' );
	}

	register_block_type( $theme_dir . '/blocks/image-slider' );
}
add_action( 'init', 'upperel_register_blocks' );
