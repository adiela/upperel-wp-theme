<?php
/**
 * Upperel WooCommerce tweaks.
 *
 * @package storefront
 */

/**
 * Don't show the visible "Shop" title on the main shop page.
 *
 * Category and tag archives use the same template but keep their titles,
 * because is_shop() is only true on the main shop page.
 *
 * @param bool $show Whether WooCommerce should print the page title.
 * @return bool
 */
function upperel_hide_shop_page_title( $show ) {
	return is_shop() ? false : $show;
}
add_filter( 'woocommerce_show_page_title', 'upperel_hide_shop_page_title' );

/**
 * Keep the shop page's <h1> for screen readers.
 *
 * Printed at priority 0 on woocommerce_archive_description, which runs
 * inside the same <header class="woocommerce-products-header"> right after
 * where the visible title would have been, so heading order is unchanged.
 */
function upperel_shop_page_title_for_screen_readers() {
	if ( ! is_shop() || apply_filters( 'woocommerce_show_page_title', true ) ) {
		return;
	}
	?>
	<h1 class="woocommerce-products-header__title page-title screen-reader-text"><?php woocommerce_page_title(); ?></h1>
	<?php
}
add_action( 'woocommerce_archive_description', 'upperel_shop_page_title_for_screen_readers', 0 );

/**
 * Remove the breadcrumb from all pages.
 *
 * Storefront prints WooCommerce's breadcrumb above the content on every
 * page via storefront_before_content (and already removes WooCommerce's
 * own copy). Runs on after_setup_theme because Storefront's WooCommerce
 * hook file is loaded after this one.
 */
function upperel_remove_breadcrumb() {
	remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
}
add_action( 'after_setup_theme', 'upperel_remove_breadcrumb', 20 );

/**
 * Restore the `no-wc-breadcrumb` body class.
 *
 * Storefront removes it when WooCommerce is active, but its CSS uses it to
 * add space below the header on pages without a breadcrumb. Without it,
 * content would sit right against the header now the breadcrumb is gone.
 * (The main shop page opts out of that space in upperel-header.scss.)
 *
 * @param array $classes Body classes.
 * @return array
 */
function upperel_no_breadcrumb_body_class( $classes ) {
	if ( ! in_array( 'no-wc-breadcrumb', $classes, true ) ) {
		$classes[] = 'no-wc-breadcrumb';
	}
	return $classes;
}
add_filter( 'body_class', 'upperel_no_breadcrumb_body_class', 20 );
