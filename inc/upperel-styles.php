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
 * Google Fonts: Poppins, for headings, and for text until Soleil is set up
 * (font stacks in assets/css/sass/utils/_variables.scss), instead of
 * Storefront's Source Sans Pro. Storefront loads it on the front end and
 * in the block editor.
 *
 * Weights: Storefront's headings are light (300); text uses 400, 600 and
 * 700, with italics.
 *
 * @return array Google Fonts families.
 */
function upperel_google_fonts() {
	return array(
		'poppins' => 'Poppins:300,400,400italic,500,600,600italic,700,700italic',
	);
}
add_filter( 'storefront_google_font_families', 'upperel_google_fonts' );

/**
 * Adobe Fonts stylesheet URL for the project set in the Customizer, or ''.
 *
 * Soleil, the text font, is an Adobe Fonts typeface, not on Google Fonts.
 * Add it to a web project at fonts.adobe.com (with this site's domain)
 * and enter the project ID in Customize → Typography.
 *
 * @return string
 */
function upperel_adobe_fonts_url() {
	$id = upperel_sanitize_adobe_fonts_id( get_theme_mod( 'upperel_adobe_fonts_id', '' ) );
	return $id ? 'https://use.typekit.net/' . $id . '.css' : '';
}

/**
 * Keep only a valid Adobe Fonts project ID (letters and digits).
 *
 * @param string $value Entered value.
 * @return string
 */
function upperel_sanitize_adobe_fonts_id( $value ) {
	$value = strtolower( trim( (string) $value ) );
	return preg_match( '/^[a-z0-9]{5,12}$/', $value ) ? $value : '';
}

/**
 * Customize → Typography: "Adobe Fonts project ID" for Soleil.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function upperel_fonts_customize_register( $wp_customize ) {
	$wp_customize->add_setting(
		'upperel_adobe_fonts_id',
		array(
			'default'           => '',
			'sanitize_callback' => 'upperel_sanitize_adobe_fonts_id',
		)
	);

	$wp_customize->add_control(
		'upperel_adobe_fonts_id',
		array(
			'type'        => 'text',
			'label'       => __( 'Adobe Fonts project ID', 'storefront' ),
			'description' => __( 'Loads Soleil, the text font. Add Soleil to a web project at fonts.adobe.com and enter its ID (e.g. abc1def). Until then, text uses Poppins.', 'storefront' ),
			'section'     => 'storefront_typography',
			'priority'    => 1,
		)
	);
}
add_action( 'customize_register', 'upperel_fonts_customize_register', 20 );

/**
 * Load the Adobe Fonts project on the front end.
 */
function upperel_adobe_fonts() {
	$url = upperel_adobe_fonts_url();
	if ( $url ) {
		wp_enqueue_style( 'upperel-adobe-fonts', $url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Adobe versions the kit.
	}
}
add_action( 'wp_enqueue_scripts', 'upperel_adobe_fonts', 5 );

/**
 * Load the Adobe Fonts project in the block editor too.
 */
function upperel_adobe_fonts_editor() {
	$url = upperel_adobe_fonts_url();
	if ( $url ) {
		add_editor_style( $url );
	}
}
add_action( 'after_setup_theme', 'upperel_adobe_fonts_editor', 20 );

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
