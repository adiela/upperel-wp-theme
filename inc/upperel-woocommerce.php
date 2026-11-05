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

/**
 * Size the single product image for the equal-width columns.
 *
 * Storefront uses 416px, sized for its narrower 5/12 image column. With
 * the 1320px container and equal 6/12 columns (upperel-woocommerce.scss)
 * the image column is up to ~631px, so a 416px image would leave it part
 * empty. The image's `sizes` attribute follows this value too, so larger
 * versions are still picked for high-density screens.
 *
 * @param array $args WooCommerce theme support arguments.
 * @return array
 */
function upperel_woocommerce_image_sizes( $args ) {
	$args['single_image_width'] = 640;
	return $args;
}
add_filter( 'storefront_woocommerce_args', 'upperel_woocommerce_image_sizes' );

/**
 * Single product page script: fits the main image and gallery thumbnails
 * in the first screen (see assets/js/single-product.js).
 */
function upperel_single_product_assets() {
	if ( ! is_product() ) {
		return;
	}

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$js     = '/assets/js/single-product' . $suffix . '.js';
	$path   = get_template_directory() . $js;

	wp_enqueue_script(
		'upperel-single-product',
		get_template_directory_uri() . $js,
		array(),
		file_exists( $path ) ? (string) filemtime( $path ) : null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'upperel_single_product_assets', 20 );

/**
 * Single product gallery: dots and arrows instead of thumbnails.
 *
 * WooCommerce's gallery slider (FlexSlider) shows a thumbnail strip below
 * the main image by default. Swap it for dots and prev/next arrows, styled
 * in upperel-woocommerce.scss. Autoplay stays off. animationLoop stays
 * off too: WooCommerce notes looping breaks the lightbox paging, so the
 * arrow at either end is disabled.
 *
 * @param array $options FlexSlider options.
 * @return array
 */
function upperel_product_gallery_slider_options( $options ) {
	$options['controlNav']   = true;
	$options['directionNav'] = true;
	$options['slideshow']    = false;
	$options['prevText']     = __( 'Previous image', 'storefront' );
	$options['nextText']     = __( 'Next image', 'storefront' );
	return $options;
}
add_filter( 'woocommerce_single_product_carousel_options', 'upperel_product_gallery_slider_options' );

/**
 * Single product: show the description in the right-hand column, under the
 * add-to-cart form, instead of in the "Description" tab below.
 *
 * Priority 35 places it between the add-to-cart form (30) and the product
 * meta, e.g. the category line (40).
 */
function upperel_product_summary_description() {
	if ( '' === trim( get_the_content() ) ) {
		return;
	}
	?>
	<div class="woocommerce-product-details__description">
		<h3 class="woocommerce-product-details__description-title"><?php esc_html_e( 'Product Description', 'storefront' ); ?></h3>
		<?php the_content(); ?>
	</div>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'upperel_product_summary_description', 35 );

/**
 * Remove the "Description" tab now the description is in the summary.
 *
 * @param array $tabs Product tabs.
 * @return array
 */
function upperel_remove_description_tab( $tabs ) {
	unset( $tabs['description'] );
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'upperel_remove_description_tab', 98 );

/**
 * Single product: show the product tabs (Reviews, Additional information)
 * as collapsible sections that start closed, instead of tabs where the
 * first one is always open.
 *
 * Uses native <details> elements, so they open and close without
 * JavaScript and work with the keyboard. The wrapper avoids WooCommerce's
 * tab classes (.woocommerce-tabs, .wc-tabs-wrapper) so its tab script
 * leaves the sections alone. Links to #reviews or a review (#comment-…)
 * open the right section first (assets/js/single-product.js).
 */
function upperel_replace_product_tabs() {
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	add_action( 'woocommerce_after_single_product_summary', 'upperel_product_sections', 10 );
}
add_action( 'after_setup_theme', 'upperel_replace_product_tabs', 20 );

/**
 * Output the product tabs as collapsed <details> sections.
 */
function upperel_product_sections() {
	$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

	if ( empty( $product_tabs ) ) {
		return;
	}
	?>
	<div class="upperel-product-sections">
		<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
			<details class="upperel-product-section upperel-product-section--<?php echo esc_attr( $key ); ?>" id="tab-<?php echo esc_attr( $key ); ?>">
				<summary class="upperel-product-section__title">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key ) ); ?>
				</summary>
				<div class="upperel-product-section__panel woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr( $key ); ?> entry-content">
					<?php
					if ( isset( $product_tab['callback'] ) ) {
						call_user_func( $product_tab['callback'], $key, $product_tab );
					}
					?>
				</div>
			</details>
		<?php endforeach; ?>

		<?php do_action( 'woocommerce_product_after_tabs' ); ?>
	</div>
	<?php
}

/**
 * Single product: − and + buttons around the quantity field, styled like
 * the cart page's quantity selector (upperel-woocommerce.scss). The
 * script in assets/js/single-product.js makes them work, and removes them
 * where the field is hidden or read-only (a fixed quantity).
 */
function upperel_quantity_minus_button() {
	if ( is_product() ) {
		echo '<button type="button" class="upperel-qty__button upperel-qty__button--minus" aria-label="' . esc_attr__( 'Reduce quantity', 'storefront' ) . '">&minus;</button>';
	}
}
add_action( 'woocommerce_before_quantity_input_field', 'upperel_quantity_minus_button' );

/**
 * See upperel_quantity_minus_button().
 */
function upperel_quantity_plus_button() {
	if ( is_product() ) {
		echo '<button type="button" class="upperel-qty__button upperel-qty__button--plus" aria-label="' . esc_attr__( 'Increase quantity', 'storefront' ) . '">+</button>';
	}
}
add_action( 'woocommerce_after_quantity_input_field', 'upperel_quantity_plus_button' );
