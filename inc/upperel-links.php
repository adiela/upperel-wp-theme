<?php
/**
 * Upperel link colour.
 *
 * Adds a "Link color" setting to Customize → Typography. It's printed as
 * the --upperel-link-color CSS variable, which upperel-links.scss uses for
 * links. Storefront's own "Link / accent color" setting is relabelled
 * "Accent color": it still colours buttons' focus outlines, the WooCommerce
 * accents and so on, but no longer links.
 *
 * @package storefront
 */

/**
 * Default link colour: Storefront's default accent colour, which links
 * used before.
 */
const UPPEREL_DEFAULT_LINK_COLOR = '#7f54b3';

/**
 * Register the "Link color" setting and control.
 *
 * Priority 20 runs after Storefront's customize_register (priority 10),
 * so its accent colour control exists to relabel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function upperel_link_color_customize_register( $wp_customize ) {
	$wp_customize->add_setting(
		'upperel_link_color',
		array(
			'default'           => UPPEREL_DEFAULT_LINK_COLOR,
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'upperel_link_color',
			array(
				'label'    => __( 'Link color', 'storefront' ),
				'section'  => 'storefront_typography',
				'settings' => 'upperel_link_color',
				'priority' => 41, // Right after the accent colour (40).
			)
		)
	);

	$accent = $wp_customize->get_control( 'storefront_accent_color' );
	if ( $accent ) {
		$accent->label = __( 'Accent color', 'storefront' );
	}
}
add_action( 'customize_register', 'upperel_link_color_customize_register', 20 );

/**
 * Print the link colour as a CSS variable.
 *
 * Attached to the upperel-links stylesheet (enqueued by upperel_styles()
 * at priority 25).
 */
function upperel_link_color_css() {
	$color = sanitize_hex_color( get_theme_mod( 'upperel_link_color', UPPEREL_DEFAULT_LINK_COLOR ) );

	wp_add_inline_style(
		'upperel-links',
		sprintf( ':root{--upperel-link-color:%s;}', $color ? $color : UPPEREL_DEFAULT_LINK_COLOR )
	);
}
add_action( 'wp_enqueue_scripts', 'upperel_link_color_css', 26 );
