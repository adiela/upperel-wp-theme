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
		array( 'jquery' ), // Variation buttons drive WooCommerce's jQuery variation form.
		file_exists( $path ) ? (string) filemtime( $path ) : null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'upperel_single_product_assets', 20 );

/**
 * Single product gallery: a column of thumbnails to the left of the main
 * image (styled in upperel-woocommerce.scss; made keyboard-usable in
 * assets/js/single-product.js), no arrows. Autoplay stays off.
 * animationLoop stays off too: WooCommerce notes looping breaks the
 * lightbox paging.
 *
 * @param array $options FlexSlider options.
 * @return array
 */
function upperel_product_gallery_slider_options( $options ) {
	$options['controlNav']   = 'thumbnails';
	$options['directionNav'] = false;
	$options['slideshow']    = false;
	return $options;
}
add_filter( 'woocommerce_single_product_carousel_options', 'upperel_product_gallery_slider_options' );

/**
 * Single product: product information in the right-hand column, under the
 * add-to-cart form, as tabs: "Description" and "Attributes"
 * (WooCommerce's "Additional information": attributes, weight, dimensions).
 * Replaces those two tabs in the sections below the product.
 *
 * With only one of the two available, it's shown under a plain heading
 * instead of a single tab. The tabs follow the WAI-ARIA tabs pattern and
 * are switched by assets/js/single-product.js.
 *
 * Priority 35 places it between the add-to-cart form (30) and the product
 * meta, e.g. the category line (40).
 */
function upperel_product_summary_info() {
	global $product;

	$panels = array();

	if ( '' !== trim( get_the_content() ) ) {
		$panels['description'] = array(
			'title'    => __( 'Description', 'storefront' ),
			'callback' => 'the_content',
		);
	}

	// Same condition WooCommerce uses for its "Additional information" tab.
	if ( $product && ( $product->has_attributes() || apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() ) ) ) {
		$panels['attributes'] = array(
			'title'    => __( 'Attributes', 'storefront' ),
			'callback' => function () use ( $product ) {
				wc_display_product_attributes( $product );
			},
		);
	}

	if ( empty( $panels ) ) {
		return;
	}

	// One panel: plain heading, no tabs.
	if ( 1 === count( $panels ) ) {
		$key   = key( $panels );
		$panel = current( $panels );
		?>
		<div class="upperel-product-info upperel-product-info--<?php echo esc_attr( $key ); ?>">
			<h3 class="upperel-product-info__heading"><?php echo esc_html( $panel['title'] ); ?></h3>
			<div class="upperel-product-info__panel"><?php call_user_func( $panel['callback'] ); ?></div>
		</div>
		<?php
		return;
	}

	$first = true;
	?>
	<div class="upperel-product-info upperel-product-info--tabs">
		<div class="upperel-product-info__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Product information', 'storefront' ); ?>">
			<?php foreach ( $panels as $key => $panel ) : ?>
				<button
					type="button"
					role="tab"
					class="upperel-product-info__tab"
					id="upperel-product-info-tab-<?php echo esc_attr( $key ); ?>"
					aria-controls="upperel-product-info-panel-<?php echo esc_attr( $key ); ?>"
					aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
					tabindex="<?php echo $first ? '0' : '-1'; ?>"
				><?php echo esc_html( $panel['title'] ); ?></button>
				<?php $first = false; ?>
			<?php endforeach; ?>
		</div>
		<?php $first = true; ?>
		<?php foreach ( $panels as $key => $panel ) : ?>
			<div
				class="upperel-product-info__panel"
				role="tabpanel"
				id="upperel-product-info-panel-<?php echo esc_attr( $key ); ?>"
				aria-labelledby="upperel-product-info-tab-<?php echo esc_attr( $key ); ?>"
				tabindex="0"
				<?php echo $first ? '' : 'hidden'; ?>
			><?php call_user_func( $panel['callback'] ); ?></div>
			<?php $first = false; ?>
		<?php endforeach; ?>
	</div>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'upperel_product_summary_info', 35 );

/**
 * Remove the "Description" and "Additional information" tabs from the
 * sections below the product; both are in the summary now.
 *
 * @param array $tabs Product tabs.
 * @return array
 */
function upperel_remove_summary_tabs( $tabs ) {
	unset( $tabs['description'], $tabs['additional_information'] );
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'upperel_remove_summary_tabs', 98 );

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

/**
 * Single product: remove Storefront's previous/next product links (the
 * product thumbnails pinned to the left and right screen edges).
 *
 * Unhooked rather than relying on the Customizer's "Product pagination"
 * switch, so it stays off.
 */
function upperel_remove_product_pagination() {
	remove_action( 'woocommerce_after_single_product_summary', 'storefront_single_product_pagination', 30 );
}
add_action( 'after_setup_theme', 'upperel_remove_product_pagination', 20 );

/**
 * Product grids: the same number of products per row everywhere, taken
 * from the shop setting (Customize → WooCommerce → Product Catalog →
 * Products per row), instead of Storefront's fixed counts (3 for Related
 * products, upsells and categories; 4 for the homepage sections).
 *
 * Grids with their own explicit setting keep it: [products columns="…"]
 * shortcodes and WooCommerce block grids.
 *
 * @return int Products per row.
 */
function upperel_products_per_row() {
	return max( 1, (int) wc_get_default_products_per_row() );
}

/**
 * Related products: one full row.
 *
 * @param array $args Related products args.
 * @return array
 */
function upperel_related_products_args( $args ) {
	$args['columns']        = upperel_products_per_row();
	$args['posts_per_page'] = upperel_products_per_row();
	return $args;
}
add_filter( 'storefront_related_products_args', 'upperel_related_products_args' );

add_filter( 'storefront_upsells_columns', 'upperel_products_per_row' );
add_filter( 'woocommerce_cross_sells_columns', 'upperel_products_per_row' );

/**
 * Storefront homepage sections (Shop by category, Recent, Featured,
 * Popular, On sale, Best sellers): one full row each.
 *
 * @param array $args Section args.
 * @return array
 */
function upperel_homepage_section_args( $args ) {
	$args['columns'] = upperel_products_per_row();
	$args['limit']   = upperel_products_per_row();
	return $args;
}
foreach ( array( 'product_categories', 'recent_products', 'featured_products', 'popular_products', 'on_sale_products', 'best_selling_products' ) as $upperel_section ) {
	add_filter( "storefront_{$upperel_section}_args", 'upperel_homepage_section_args' );
}
unset( $upperel_section );
