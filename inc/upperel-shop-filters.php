<?php
/**
 * Upperel shop filters.
 *
 * A "Filters" button at the start of the shop's sorting row opens a panel
 * that slides in from the right (assets/js/shop-filters.js,
 * upperel-filters.scss) with the product filters: categories, tags,
 * brands, attributes, price and availability. The active filters
 * are listed under the sorting row, each with a link to remove it.
 *
 * Shown on the shop, product category/tag/brand/attribute archives and
 * product search results.
 *
 * Uses WooCommerce's own filter URL parameters (the ones its Product
 * Filters blocks use), so WooCommerce does the filtering in the main
 * product query:
 *   categories=a,b  tags=a,b  brands=a,b   (taxonomy terms, any of)
 *   filter_{attribute}=a,b + query_type_{attribute}=or
 *   min_price=10  max_price=50
 *   filter_stock_status=instock,onbackorder
 *
 * @package storefront
 */

/**
 * Whether the current page is a product archive the filters apply to.
 *
 * @return bool
 */
function upperel_filters_enabled() {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}
	return is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' );
}

/**
 * Tidy the filter parameters for WooCommerce when the form is sent
 * without JavaScript (the script builds a clean URL itself):
 * - The checkboxes are named like `categories[]`, so the browser sends
 *   arrays; WooCommerce expects comma-separated strings.
 * - Empty price fields arrive as `min_price=&max_price=`, and WooCommerce
 *   reads an empty max_price as 0, which would hide every product.
 * Runs on init, before WordPress parses the query from $_GET.
 */
function upperel_filters_normalize_request() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only shop filters.
	foreach ( $_GET as $key => $value ) {
		if ( ! upperel_filters_is_filter_key( $key ) ) {
			continue;
		}
		if ( is_array( $value ) ) {
			$value = implode( ',', array_filter( array_map( 'sanitize_title', wp_unslash( $value ) ), 'strlen' ) );
		}
		if ( '' === $value ) {
			unset( $_GET[ $key ], $_REQUEST[ $key ] );
		} else {
			$_GET[ $key ]     = $value;
			$_REQUEST[ $key ] = $value;
		}
	}
	// phpcs:enable
}
add_action( 'init', 'upperel_filters_normalize_request', 0 );

/**
 * Whether a URL parameter is one of the filter parameters.
 *
 * @param string $key Parameter name.
 * @return bool
 */
function upperel_filters_is_filter_key( $key ) {
	return in_array( $key, array( 'categories', 'tags', 'brands', 'min_price', 'max_price', 'filter_stock_status' ), true )
		|| 0 === strpos( $key, 'filter_' )
		|| 0 === strpos( $key, 'query_type_' );
}

/**
 * The checkbox filter groups for the current archive.
 *
 * Each group: label, URL parameter, and options (value => label, plus
 * depth for nested categories). Groups with no options are left out.
 * The price group (type "price") has min and max fields instead.
 *
 * @return array[]
 */
function upperel_filters_groups() {
	static $groups = null;
	if ( null !== $groups ) {
		return $groups;
	}

	$groups  = array();
	$queried = get_queried_object();
	$on_term = $queried instanceof WP_Term;

	// Categories: all of them on the shop, the subcategories on a category
	// archive.
	$cat_parent = ( $on_term && 'product_cat' === $queried->taxonomy ) ? $queried->term_id : 0;
	$groups[]   = array(
		'key'     => 'categories',
		'label'   => __( 'Category', 'storefront' ),
		'options' => upperel_filters_term_options( 'product_cat', $cat_parent ),
	);

	// Tags and brands, except on that tag's or brand's own archive.
	foreach ( array(
		'product_tag'   => array( 'tags', __( 'Tag', 'storefront' ) ),
		'product_brand' => array( 'brands', __( 'Brand', 'storefront' ) ),
	) as $taxonomy => $group ) {
		if ( ! taxonomy_exists( $taxonomy ) || ( $on_term && $taxonomy === $queried->taxonomy ) ) {
			continue;
		}
		$groups[] = array(
			'key'     => $group[0],
			'label'   => $group[1],
			'options' => upperel_filters_term_options( $taxonomy, is_taxonomy_hierarchical( $taxonomy ) ? 0 : null ),
		);
	}

	// Global attributes (Products → Attributes), e.g. Color, Size.
	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
		if ( ! taxonomy_exists( $taxonomy ) || ( $on_term && $taxonomy === $queried->taxonomy ) ) {
			continue;
		}
		$groups[] = array(
			'key'        => 'filter_' . $attribute->attribute_name,
			'label'      => $attribute->attribute_label,
			'options'    => upperel_filters_term_options( $taxonomy, null ),
			'query_type' => 'query_type_' . $attribute->attribute_name,
		);
	}

	// Price (min and max fields rather than checkboxes).
	$groups[] = array(
		'key'     => 'price',
		'type'    => 'price',
		'label'   => __( 'Price', 'storefront' ),
		'options' => array( true ),
	);

	// Availability.
	$statuses = wc_get_product_stock_status_options();
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		unset( $statuses['outofstock'] );
	}
	$groups[] = array(
		'key'     => 'filter_stock_status',
		'label'   => __( 'Availability', 'storefront' ),
		'options' => array_map(
			function ( $label ) {
				return array( 'label' => $label );
			},
			$statuses
		),
	);

	$groups = array_values(
		array_filter(
			$groups,
			function ( $group ) {
				return ! empty( $group['options'] );
			}
		)
	);

	return $groups;
}

/**
 * The filter groups except price.
 *
 * @return array[]
 */
function upperel_filters_checkbox_groups() {
	return array_filter(
		upperel_filters_groups(),
		function ( $group ) {
			return empty( $group['type'] );
		}
	);
}

/**
 * Non-empty terms of a taxonomy as filter options, keyed by slug.
 *
 * @param string   $taxonomy Taxonomy name.
 * @param int|null $parent   For hierarchical lists, the term whose
 *                           descendants to list (0 for all), nested by
 *                           depth. Null for a flat list.
 * @return array
 */
function upperel_filters_term_options( $taxonomy, $parent ) {
	$args = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => true,
		'orderby'    => 'name',
	);
	if ( 'product_cat' === $taxonomy ) {
		$args['exclude'] = array( (int) get_option( 'default_product_cat' ) ); // "Uncategorized".
	}
	if ( null !== $parent && 0 !== $parent ) {
		$args['child_of'] = $parent;
	}

	$terms = get_terms( $args );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	if ( null === $parent ) {
		$options = array();
		foreach ( $terms as $term ) {
			$options[ $term->slug ] = array( 'label' => $term->name );
		}
		return $options;
	}

	// Nest children under their parents.
	$children = array();
	foreach ( $terms as $term ) {
		$children[ $term->parent ][] = $term;
	}
	$options = array();
	$walk    = function ( $parent_id, $depth ) use ( &$walk, &$options, $children ) {
		foreach ( $children[ $parent_id ] ?? array() as $term ) {
			$options[ $term->slug ] = array(
				'label' => $term->name,
				'depth' => $depth,
			);
			$walk( $term->term_id, $depth + 1 );
		}
	};
	$walk( $parent, 0 );

	// A child whose parent is empty (so not listed) would be skipped above.
	foreach ( $terms as $term ) {
		if ( ! isset( $options[ $term->slug ] ) ) {
			$options[ $term->slug ] = array( 'label' => $term->name );
		}
	}

	return $options;
}

/**
 * The selected values of a filter parameter.
 *
 * @param string $key URL parameter.
 * @return string[]
 */
function upperel_filters_selected( $key ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$value = isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? wp_unslash( $_GET[ $key ] ) : '';
	return array_values( array_filter( array_map( 'sanitize_title', explode( ',', $value ) ), 'strlen' ) );
}

/**
 * The selected price range.
 *
 * @return array Keys min_price and max_price; '' when not set.
 */
function upperel_filters_selected_price() {
	$price = array();
	foreach ( array( 'min_price', 'max_price' ) as $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value         = isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? wc_format_decimal( wp_unslash( $_GET[ $key ] ) ) : '';
		$price[ $key ] = ( '' !== $value && is_numeric( $value ) && $value >= 0 ) ? $value : '';
	}
	return $price;
}

/**
 * Lowest and highest product price in the store, for the price fields'
 * placeholders.
 *
 * @return array Keys min and max (whole numbers), or empty if no prices.
 */
function upperel_filters_price_range() {
	global $wpdb;

	$range = wp_cache_get( 'upperel_filters_price_range' );
	if ( false === $range ) {
		$range = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT MIN( lookup.min_price ) AS min, MAX( lookup.max_price ) AS max
			FROM {$wpdb->wc_product_meta_lookup} lookup
			INNER JOIN {$wpdb->posts} posts ON posts.ID = lookup.product_id
			WHERE posts.post_type = 'product' AND posts.post_status = 'publish'",
			ARRAY_A
		);
		wp_cache_set( 'upperel_filters_price_range', $range );
	}

	if ( empty( $range ) || null === $range['max'] ) {
		return array();
	}
	return array(
		'min' => (int) floor( $range['min'] ),
		'max' => (int) ceil( $range['max'] ),
	);
}

/**
 * The archive URL without filters or pagination, and the other
 * parameters to keep (sorting, search, and plain-permalink parameters
 * such as post_type or product_cat).
 *
 * @return array Keys url (no query string) and params.
 */
function upperel_filters_base() {
	$queried = get_queried_object();
	$link    = $queried instanceof WP_Term ? get_term_link( $queried ) : get_post_type_archive_link( 'product' );
	if ( is_wp_error( $link ) || ! $link ) {
		$link = home_url( '/' );
	}

	$parts  = wp_parse_url( $link );
	$params = array();
	if ( ! empty( $parts['query'] ) ) {
		wp_parse_str( $parts['query'], $params );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	foreach ( wp_unslash( $_GET ) as $key => $value ) {
		if ( is_string( $value ) && ! upperel_filters_is_filter_key( $key ) && ! in_array( $key, array( 'paged', 'product-page' ), true ) && ! isset( $params[ $key ] ) ) {
			$params[ $key ] = $value;
		}
	}

	return array(
		'url'    => strtok( $link, '?' ),
		'params' => $params,
	);
}

/**
 * URL of the archive with the given filters.
 *
 * @param array $filters Parameter => value; empty values are dropped.
 *                       None gives the archive without filters.
 * @return string
 */
function upperel_filters_url( $filters = array() ) {
	$base = upperel_filters_base();
	$args = array_merge( $base['params'], array_filter( $filters, 'strlen' ) );
	$args = array_map(
		function ( $value ) {
			return str_replace( '%2C', ',', rawurlencode( $value ) ); // Keep commas readable.
		},
		$args
	);
	return add_query_arg( $args, $base['url'] );
}

/**
 * The active filters, each with its label and the URL without it.
 *
 * @return array[] Each: label, remove_url.
 */
function upperel_filters_active() {
	$groups = upperel_filters_checkbox_groups();

	$current = array();
	foreach ( $groups as $group ) {
		$values = upperel_filters_selected( $group['key'] );
		if ( $values ) {
			$current[ $group['key'] ] = implode( ',', $values );
			if ( ! empty( $group['query_type'] ) ) {
				$current[ $group['query_type'] ] = 'or';
			}
		}
	}
	$price   = upperel_filters_selected_price();
	$current = array_merge( $current, $price );

	$active = array();

	foreach ( $groups as $group ) {
		$values = upperel_filters_selected( $group['key'] );
		foreach ( $values as $value ) {
			if ( ! isset( $group['options'][ $value ] ) ) {
				continue;
			}
			$without                  = $current;
			$rest                     = array_diff( $values, array( $value ) );
			$without[ $group['key'] ] = implode( ',', $rest );
			if ( ! $rest && ! empty( $group['query_type'] ) ) {
				unset( $without[ $group['query_type'] ] );
			}
			$active[] = array(
				'label'      => $group['label'] . ': ' . $group['options'][ $value ]['label'],
				'remove_url' => upperel_filters_url( $without ),
			);
		}
	}

	if ( '' !== $price['min_price'] || '' !== $price['max_price'] ) {
		$min = '' !== $price['min_price'] ? wp_strip_all_tags( wc_price( $price['min_price'] ) ) : '';
		$max = '' !== $price['max_price'] ? wp_strip_all_tags( wc_price( $price['max_price'] ) ) : '';
		if ( $min && $max ) {
			/* translators: 1: minimum price, 2: maximum price. */
			$label = sprintf( __( 'Price: %1$s – %2$s', 'storefront' ), $min, $max );
		} elseif ( $min ) {
			/* translators: %s: minimum price. */
			$label = sprintf( __( 'Price: from %s', 'storefront' ), $min );
		} else {
			/* translators: %s: maximum price. */
			$label = sprintf( __( 'Price: up to %s', 'storefront' ), $max );
		}
		$without = $current;
		unset( $without['min_price'], $without['max_price'] );
		$active[] = array(
			'label'      => html_entity_decode( $label, ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'remove_url' => upperel_filters_url( $without ),
		);
	}

	return $active;
}

/**
 * The "Filters" button, at the start of the sorting row above the grid.
 *
 * Priority 10 on woocommerce_before_shop_loop, inside Storefront's
 * sorting wrapper (9 to 31). This file loads before Storefront's
 * WooCommerce hooks, so the button comes before the sorting dropdown,
 * which is also at 10.
 */
function upperel_filters_toggle() {
	static $done = false;
	if ( $done || ! upperel_filters_enabled() || ! upperel_filters_groups() ) {
		return;
	}
	$done  = true; // Only in the top sorting row, not the one below the grid.
	$count = count( upperel_filters_active() );
	?>
	<button type="button" class="upperel-filters-toggle" aria-haspopup="dialog" aria-controls="upperel-filters">
		<svg class="upperel-filters-toggle__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
		<span class="upperel-filters-toggle__label"><?php esc_html_e( 'Filters', 'storefront' ); ?></span>
		<?php if ( $count ) : ?>
			<span class="upperel-filters-toggle__count">
				<span aria-hidden="true"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
				<span class="screen-reader-text">
					<?php
					/* translators: %s: number of active filters. */
					echo esc_html( sprintf( _n( '(%s active)', '(%s active)', $count, 'storefront' ), number_format_i18n( $count ) ) );
					?>
				</span>
			</span>
		<?php endif; ?>
	</button>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'upperel_filters_toggle', 10 );

/**
 * The active filters, under the sorting row.
 */
function upperel_filters_active_list() {
	if ( ! upperel_filters_enabled() ) {
		return;
	}
	$active = upperel_filters_active();
	if ( ! $active ) {
		return;
	}
	?>
	<div class="upperel-filters-active">
		<h2 class="screen-reader-text"><?php esc_html_e( 'Active filters', 'storefront' ); ?></h2>
		<ul class="upperel-filters-active__list">
			<?php foreach ( $active as $filter ) : ?>
				<li>
					<a class="upperel-filters-active__chip" href="<?php echo esc_url( $filter['remove_url'] ); ?>">
						<span><?php echo esc_html( $filter['label'] ); ?></span>
						<span class="upperel-filters-active__remove" aria-hidden="true">&times;</span>
						<span class="screen-reader-text"><?php esc_html_e( '(remove filter)', 'storefront' ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
			<li>
				<a class="upperel-filters-active__clear" href="<?php echo esc_url( upperel_filters_url() ); ?>"><?php esc_html_e( 'Clear all', 'storefront' ); ?></a>
			</li>
		</ul>
	</div>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'upperel_filters_active_list', 32 ); // After the sorting row (closes at 31).

/**
 * When the filters match no products WooCommerce skips the sorting row,
 * so show the button and active filters above "No products were found".
 */
function upperel_filters_no_results() {
	if ( ! upperel_filters_enabled() || ! upperel_filters_active() ) {
		return;
	}
	echo '<div class="storefront-sorting upperel-filters-empty-row">';
	upperel_filters_toggle();
	echo '</div>';
	upperel_filters_active_list();
}
add_action( 'woocommerce_no_products_found', 'upperel_filters_no_results', 5 );

/**
 * The filter panel: a modal <dialog> printed in the footer.
 */
function upperel_filters_panel() {
	if ( ! upperel_filters_enabled() ) {
		return;
	}
	$groups = upperel_filters_groups();
	if ( ! $groups ) {
		return;
	}

	$base  = upperel_filters_base();
	$price = upperel_filters_selected_price();
	$range = upperel_filters_price_range();
	$first = true;
	?>
	<dialog id="upperel-filters" class="upperel-filters" aria-labelledby="upperel-filters-title">
		<form class="upperel-filters__form" method="get" action="<?php echo esc_url( $base['url'] ); ?>">
			<div class="upperel-filters__header">
				<h2 id="upperel-filters-title" class="upperel-filters__title"><?php esc_html_e( 'Filters', 'storefront' ); ?></h2>
				<button type="button" class="upperel-filters__close" data-upperel-filters-close aria-label="<?php esc_attr_e( 'Close filters', 'storefront' ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
				</button>
			</div>

			<div class="upperel-filters__body">
				<?php foreach ( $base['params'] as $key => $value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<?php endforeach; ?>

				<?php foreach ( $groups as $group ) : ?>
					<?php
					if ( ! empty( $group['type'] ) ) {
						$selected = array_filter( $price, 'strlen' );
					} else {
						$selected = upperel_filters_selected( $group['key'] );
					}
					$open  = $first || $selected;
					$first = false;
					?>
					<details class="upperel-filters__group"<?php echo $open ? ' open' : ''; ?>>
						<?php if ( ! empty( $group['type'] ) ) : ?>
						<summary class="upperel-filters__group-title"><?php esc_html_e( 'Price', 'storefront' ); ?></summary>
						<fieldset class="upperel-filters__options upperel-filters__price">
							<legend class="screen-reader-text">
								<?php
								/* translators: %s: currency symbol. */
								echo esc_html( sprintf( __( 'Price (%s)', 'storefront' ), html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
								?>
							</legend>
							<label class="upperel-filters__price-field">
								<span><?php esc_html_e( 'Min', 'storefront' ); ?></span>
								<input type="number" name="min_price" min="0" step="any" inputmode="decimal" value="<?php echo esc_attr( $price['min_price'] ); ?>" placeholder="<?php echo esc_attr( $range ? $range['min'] : '' ); ?>" />
							</label>
							<span class="upperel-filters__price-sep" aria-hidden="true">–</span>
							<label class="upperel-filters__price-field">
								<span><?php esc_html_e( 'Max', 'storefront' ); ?></span>
								<input type="number" name="max_price" min="0" step="any" inputmode="decimal" value="<?php echo esc_attr( $price['max_price'] ); ?>" placeholder="<?php echo esc_attr( $range ? $range['max'] : '' ); ?>" />
							</label>
						</fieldset>
						<?php else : ?>
						<summary class="upperel-filters__group-title">
							<?php echo esc_html( $group['label'] ); ?>
							<?php if ( $selected ) : ?>
								<span class="upperel-filters__group-count">(<?php echo esc_html( number_format_i18n( count( $selected ) ) ); ?>)</span>
							<?php endif; ?>
						</summary>
						<fieldset class="upperel-filters__options">
							<legend class="screen-reader-text"><?php echo esc_html( $group['label'] ); ?></legend>
							<?php if ( ! empty( $group['query_type'] ) ) : ?>
								<input type="hidden" name="<?php echo esc_attr( $group['query_type'] ); ?>" value="or" data-upperel-query-type="<?php echo esc_attr( $group['key'] ); ?>" />
							<?php endif; ?>
							<?php foreach ( $group['options'] as $value => $option ) : ?>
								<label class="upperel-filters__option" style="--upperel-filter-depth: <?php echo (int) ( $option['depth'] ?? 0 ); ?>">
									<input type="checkbox" name="<?php echo esc_attr( $group['key'] ); ?>[]" value="<?php echo esc_attr( $value ); ?>"<?php checked( in_array( (string) $value, $selected, true ) ); ?> />
									<span><?php echo esc_html( $option['label'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<?php endif; ?>
					</details>
				<?php endforeach; ?>
			</div>

			<div class="upperel-filters__footer">
				<a class="upperel-filters__clear" href="<?php echo esc_url( upperel_filters_url() ); ?>"><?php esc_html_e( 'Clear all', 'storefront' ); ?></a>
				<button type="submit" class="button upperel-filters__apply"><?php esc_html_e( 'Show results', 'storefront' ); ?></button>
			</div>
		</form>
	</dialog>
	<?php
}
add_action( 'wp_footer', 'upperel_filters_panel' );

/**
 * Filter panel script.
 */
function upperel_filters_assets() {
	if ( ! upperel_filters_enabled() ) {
		return;
	}

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$js     = '/assets/js/shop-filters' . $suffix . '.js';
	$path   = get_template_directory() . $js;

	wp_enqueue_script(
		'upperel-shop-filters',
		get_template_directory_uri() . $js,
		array(),
		file_exists( $path ) ? (string) filemtime( $path ) : null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'upperel_filters_assets', 20 );
