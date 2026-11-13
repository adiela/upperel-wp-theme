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
 * because is_shop() is only true on the main shop page. Product search
 * results count as the shop page too, but keep their "Search results"
 * title.
 *
 * @param bool $show Whether WooCommerce should print the page title.
 * @return bool
 */
function upperel_hide_shop_page_title( $show ) {
	return ( is_shop() && ! is_search() ) ? false : $show;
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
 * Single product: "Shipping calculated at checkout." under the price.
 *
 * Only for products that are shipped (not virtual or downloadable-only
 * ones), and only while shipping is enabled in WooCommerce. Priority 11:
 * right after the price (10), before the short description (20).
 */
function upperel_product_shipping_note() {
	global $product;

	if ( ! $product || ! wc_shipping_enabled() || ! $product->needs_shipping() ) {
		return;
	}
	echo '<p class="upperel-shipping-note">' . esc_html__( 'Shipping calculated at checkout.', 'storefront' ) . '</p>';
}
add_action( 'woocommerce_single_product_summary', 'upperel_product_shipping_note', 11 );

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

/**
 * Single product: the size chart rows. Each measurement is
 * array( centimetres, inches ); the units are in the column headings.
 *
 * @return array
 */
function upperel_size_chart_rows() {
	return array(
		array( 'XS', array( '74–78', '29.1–30.7' ), array( '56–60', '22.0–23.6' ), array( '81–84.6', '31.9–33.3' ), '2' ),
		array( 'S', array( '78–82', '30.7–32.3' ), array( '60–64', '23.6–25.2' ), array( '84.6–88.2', '33.3–34.7' ), '4–6' ),
		array( 'M', array( '82–86', '32.3–33.9' ), array( '64–68', '25.2–26.8' ), array( '88.2–91.8', '34.7–36.1' ), '8' ),
		array( 'L', array( '86–90', '33.9–35.4' ), array( '68–72', '26.8–28.3' ), array( '91.8–95.4', '36.1–37.6' ), '10' ),
		array( 'XL', array( '90–94', '35.4–37.0' ), array( '72–76', '28.3–29.9' ), array( '95.4–99', '37.6–39.0' ), '12–14' ),
		array( '2XL', array( '94–98', '37.0–38.6' ), array( '76–80', '29.9–31.5' ), array( '99–102.6', '39.0–40.4' ), '14–16' ),
		array( '3XL', array( '98–102', '38.6–40.2' ), array( '80–84', '31.5–33.1' ), array( '102.6–106.2', '40.4–41.8' ), '16–18' ),
	);
}

/**
 * The product's size attribute, if it has one: any attribute whose name
 * contains "size" (e.g. the global "Size" attribute, pa_size, or a custom
 * "Sizes"). Products without one (bags, accessories) get no size chart.
 *
 * The upperel_show_size_chart filter can override that per product.
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function upperel_show_size_chart( $product ) {
	return (bool) apply_filters( 'upperel_show_size_chart', upperel_product_has_size_attribute( $product ), $product );
}

/**
 * See upperel_show_size_chart().
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function upperel_product_has_size_attribute( $product ) {
	if ( ! $product ) {
		return false;
	}
	foreach ( $product->get_attributes() as $key => $attribute ) {
		$name = is_a( $attribute, 'WC_Product_Attribute' ) ? $attribute->get_name() : $key;
		if ( false !== stripos( $name, 'size' ) || false !== stripos( wc_attribute_label( $name, $product ), 'size' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Single product: a "Size chart" link, just above the add-to-cart button
 * (after the quantity field), that opens the size chart in a modal
 * <dialog> (assets/js/single-product.js, styled in
 * upperel-woocommerce.scss).
 *
 * Shown only on products with a size attribute
 * (upperel_show_size_chart()).
 */
function upperel_product_size_chart_link() {
	global $product;

	if ( ! is_product() || ! upperel_show_size_chart( $product ) ) {
		return;
	}
	?>
	<p class="upperel-size-chart-link-wrap">
		<button type="button" class="upperel-size-chart-link" aria-haspopup="dialog" aria-controls="upperel-size-chart">
			<?php esc_html_e( 'Size chart', 'storefront' ); ?>
		</button>
	</p>
	<?php
}
add_action( 'woocommerce_after_add_to_cart_quantity', 'upperel_product_size_chart_link' );

/**
 * The size chart dialog, printed after the add-to-cart form (outside it).
 * See upperel_product_size_chart_link().
 */
function upperel_product_size_chart() {
	global $product;

	if ( ! is_product() || ! upperel_show_size_chart( $product ) ) {
		return;
	}
	?>
	<dialog id="upperel-size-chart" class="upperel-size-chart" aria-labelledby="upperel-size-chart-title">
		<div class="upperel-size-chart__header">
			<h2 id="upperel-size-chart-title" class="upperel-size-chart__title"><?php esc_html_e( 'Size chart', 'storefront' ); ?></h2>
			<button type="button" class="upperel-size-chart__close" data-upperel-size-chart-close aria-label="<?php esc_attr_e( 'Close size chart', 'storefront' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
			</button>
		</div>
		<div class="upperel-size-chart__body">
			<?php // has-background: opts out of Storefront's striped table rows. ?>
			<table class="upperel-size-chart__table has-background">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Size', 'storefront' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Bust', 'storefront' ); ?> <span class="upperel-size-chart__units"><?php esc_html_e( 'cm / in', 'storefront' ); ?></span></th>
						<th scope="col"><?php esc_html_e( 'Waist', 'storefront' ); ?> <span class="upperel-size-chart__units"><?php esc_html_e( 'cm / in', 'storefront' ); ?></span></th>
						<th scope="col"><?php esc_html_e( 'Hips', 'storefront' ); ?> <span class="upperel-size-chart__units"><?php esc_html_e( 'cm / in', 'storefront' ); ?></span></th>
						<th scope="col"><?php esc_html_e( '≈ UK size', 'storefront' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( upperel_size_chart_rows() as $row ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
							<?php foreach ( array( $row[1], $row[2], $row[3] ) as $measurement ) : ?>
								<td>
									<span class="upperel-size-chart__cm"><?php echo esc_html( $measurement[0] ); ?></span>
									<span class="upperel-size-chart__in"><?php echo esc_html( $measurement[1] ); ?></span>
								</td>
							<?php endforeach; ?>
							<td><?php echo esc_html( $row[4] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</dialog>
	<?php
}
add_action( 'woocommerce_after_add_to_cart_form', 'upperel_product_size_chart' );
