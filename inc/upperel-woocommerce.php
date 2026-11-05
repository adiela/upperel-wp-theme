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
