<?php
/**
 * Upperel header.
 *
 * One row: site title/logo and primary menu on the left; search toggle,
 * account and cart icons on the right. Built by re-wiring Storefront's
 * `storefront_header` hooks rather than editing header.php.
 *
 * Styles: assets/css/base/upperel-header.scss
 * Script: assets/js/header.js
 *
 * @package storefront
 */

/**
 * Swap Storefront's header pieces for the Upperel layout.
 *
 * Runs on after_setup_theme so that Storefront's (and its WooCommerce)
 * hook files have already added the actions being removed.
 */
function upperel_header_setup() {
	// Storefront's two-row layout.
	remove_action( 'storefront_header', 'storefront_header_container', 0 );
	remove_action( 'storefront_header', 'storefront_secondary_navigation', 30 );
	remove_action( 'storefront_header', 'storefront_header_container_close', 41 );
	remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper', 42 );
	remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper_close', 68 );

	// WooCommerce search box and mini-cart; replaced by the icons.
	remove_action( 'storefront_header', 'storefront_product_search', 40 );
	remove_action( 'storefront_header', 'storefront_header_cart', 60 );

	// Menu: shown only when switched on in the Customizer and a menu is
	// set up (see upperel_header_navigation()).
	remove_action( 'storefront_header', 'storefront_primary_navigation', 50 );
	add_action( 'storefront_header', 'upperel_header_navigation', 50 );

	// Kept: storefront_skip_links (5), storefront_site_branding (20).
	add_action( 'storefront_header', 'upperel_header_container', 0 );
	add_action( 'storefront_header', 'upperel_header_actions', 60 );
	add_action( 'storefront_header', 'upperel_header_container_close', 70 );
}
add_action( 'after_setup_theme', 'upperel_header_setup', 20 );

/**
 * The header menu, if it's switched on (Customize → Header → "Show the
 * menu") and a menu is assigned to the Primary or Handheld location.
 *
 * Its links are edited in Appearance → Menus or Customize → Menus.
 * Without an assigned menu nothing is shown, rather than WordPress's
 * fallback list of every page, which can't be edited.
 *
 * Checked when the header is printed, not on after_setup_theme, so the
 * Customizer preview reflects the checkbox.
 */
function upperel_header_navigation() {
	if ( ! get_theme_mod( 'upperel_header_menu', true ) ) {
		return;
	}
	if ( ! has_nav_menu( 'primary' ) && ! has_nav_menu( 'handheld' ) ) {
		return;
	}
	storefront_primary_navigation();
}

/**
 * Header menu locations: no fallback list of pages, and the Handheld
 * (mobile) location shows the Primary menu when it has none of its own,
 * so one menu covers both.
 *
 * @param array $args wp_nav_menu() arguments.
 * @return array
 */
function upperel_header_menu_args( $args ) {
	if ( ! in_array( $args['theme_location'], array( 'primary', 'handheld' ), true ) ) {
		return $args;
	}

	$args['fallback_cb'] = false;

	if ( 'handheld' === $args['theme_location'] && ! has_nav_menu( 'handheld' ) ) {
		$locations = get_nav_menu_locations();
		if ( ! empty( $locations['primary'] ) ) {
			$args['menu'] = $locations['primary'];
		}
	}
	if ( 'primary' === $args['theme_location'] && ! has_nav_menu( 'primary' ) ) {
		$locations = get_nav_menu_locations();
		if ( ! empty( $locations['handheld'] ) ) {
			$args['menu'] = $locations['handheld'];
		}
	}

	return $args;
}
add_filter( 'wp_nav_menu_args', 'upperel_header_menu_args' );

/**
 * Customize → Header: "Show the menu" checkbox.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function upperel_header_customize_register( $wp_customize ) {
	$wp_customize->add_setting(
		'upperel_header_menu',
		array(
			'default'           => true,
			'sanitize_callback' => 'wp_validate_boolean',
		)
	);

	$wp_customize->add_control(
		'upperel_header_menu',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Show the menu', 'storefront' ),
			'description' => __( 'Edit its links in Menus (the Primary Menu location; the Handheld Menu location for mobile, if set, or the same menu).', 'storefront' ),
			'section'     => 'header_image',
			'priority'    => 5,
		)
	);

	$wp_customize->add_setting(
		'upperel_header_layout',
		array(
			'default'           => 'logo-left',
			'sanitize_callback' => 'upperel_sanitize_header_layout',
		)
	);

	$wp_customize->add_control(
		'upperel_header_layout',
		array(
			'type'        => 'radio',
			'label'       => __( 'Desktop layout', 'storefront' ),
			'description' => __( 'On phones the logo stays on the left, with the Menu button on the right.', 'storefront' ),
			'section'     => 'header_image',
			'priority'    => 4,
			'choices'     => upperel_header_layouts(),
		)
	);
}
add_action( 'customize_register', 'upperel_header_customize_register', 20 );

/**
 * Open the header row, with a class for the desktop layout chosen in the
 * Customizer (see upperel_header_layout()).
 */
function upperel_header_container() {
	$class = 'col-full upperel-header';
	if ( 'logo-center' === upperel_header_layout() ) {
		$class .= ' upperel-header--logo-center';
	}
	echo '<div class="' . esc_attr( $class ) . '">';
}

/**
 * Desktop header layouts (Customize → Header → "Desktop layout").
 *
 * @return array Layout => label.
 */
function upperel_header_layouts() {
	return array(
		'logo-left'   => __( 'Logo on the left, menu beside it', 'storefront' ),
		'logo-center' => __( 'Logo in the centre, menu on the left', 'storefront' ),
	);
}

/**
 * The chosen desktop header layout.
 *
 * @return string A key of upperel_header_layouts().
 */
function upperel_header_layout() {
	return upperel_sanitize_header_layout( get_theme_mod( 'upperel_header_layout', 'logo-left' ) );
}

/**
 * Keep only a known header layout.
 *
 * @param string $value Chosen layout.
 * @return string
 */
function upperel_sanitize_header_layout( $value ) {
	return array_key_exists( $value, upperel_header_layouts() ) ? $value : 'logo-left';
}

/**
 * Close the header row.
 */
function upperel_header_container_close() {
	echo '</div>';
}

/**
 * Inline SVG icons used in the header.
 *
 * @param string $name Icon name: search, close, account, bag.
 * @return string SVG markup.
 */
function upperel_header_icon( $name ) {
	$paths = array(
		'search'  => '<circle cx="11" cy="11" r="7"/><path d="M20.5 20.5 16 16"/>',
		'close'   => '<path d="M6 6l12 12M18 6 6 18"/>',
		'account' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'bag'     => '<path d="M5.5 7.5h13l-1 13h-11z"/><path d="M9 10V6.5a3 3 0 0 1 6 0V10"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg class="upperel-icon upperel-icon--' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Cart icon link with item-count badge. Also returned as a WooCommerce cart
 * fragment so it updates after AJAX add-to-cart.
 *
 * @return string Link markup.
 */
function upperel_header_cart_link() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

	return sprintf(
		'<a class="upperel-header-icon upperel-header-cart" href="%1$s" aria-label="%2$s">%3$s<span class="upperel-header-cart__count"%4$s aria-hidden="true">%5$s</span></a>',
		esc_url( wc_get_cart_url() ),
		esc_attr( sprintf( /* translators: %d: number of items in the cart */ _n( 'Cart, %d item', 'Cart, %d items', $count, 'storefront' ), $count ) ),
		upperel_header_icon( 'bag' ),
		$count ? '' : ' hidden',
		esc_html( $count > 99 ? '99+' : (string) $count )
	);
}

/**
 * Right-hand header icons: search toggle + panel, account, cart.
 */
function upperel_header_actions() {
	$wc          = function_exists( 'wc_get_page_permalink' );
	$account_url = $wc ? wc_get_page_permalink( 'myaccount' ) : ( is_user_logged_in() ? get_edit_profile_url() : wp_login_url() );
	?>
	<div class="upperel-header-actions">
		<div class="upperel-header-search">
			<div class="upperel-header-search__panel" id="upperel-header-search-panel">
				<form role="search" method="get" class="upperel-header-search__form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="upperel-header-search-field">
						<?php echo esc_html( $wc ? __( 'Search for products', 'storefront' ) : __( 'Search for', 'storefront' ) ); ?>
					</label>
					<input
						type="search"
						id="upperel-header-search-field"
						class="upperel-header-search__field"
						name="s"
						value="<?php echo esc_attr( get_search_query() ); ?>"
						placeholder="<?php echo esc_attr( $wc ? __( 'Search products…', 'storefront' ) : __( 'Search…', 'storefront' ) ); ?>"
						autocomplete="off"
					/>
					<?php if ( $wc ) : ?>
						<input type="hidden" name="post_type" value="product" />
					<?php endif; ?>
					<button type="submit" class="upperel-header-search__submit">
						<?php echo upperel_header_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Search', 'storefront' ); ?></span>
					</button>
				</form>
			</div>

			<button
				type="button"
				class="upperel-header-icon upperel-header-search__toggle"
				aria-controls="upperel-header-search-panel"
				aria-expanded="false"
				aria-label="<?php esc_attr_e( 'Open search', 'storefront' ); ?>"
				data-label-open="<?php esc_attr_e( 'Open search', 'storefront' ); ?>"
				data-label-close="<?php esc_attr_e( 'Close search', 'storefront' ); ?>"
			>
				<?php echo upperel_header_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo upperel_header_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<a class="upperel-header-icon upperel-header-account" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'My account', 'storefront' ); ?>">
			<?php echo upperel_header_icon( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>

		<?php
		if ( $wc ) {
			echo upperel_header_cart_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
	<?php
}

/**
 * Keep the cart link's badge and label current after AJAX add-to-cart.
 *
 * @param array $fragments Fragments keyed by CSS selector.
 * @return array
 */
function upperel_header_cart_fragments( $fragments ) {
	$fragments['a.upperel-header-cart'] = upperel_header_cart_link();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'upperel_header_cart_fragments' );

/**
 * Enqueue header styles and script, plus the Customizer header colours as
 * CSS variables so the search panel and mobile menu match the header.
 */
function upperel_header_assets() {
	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$suffix    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	$css     = '/assets/css/base/upperel-header.css';
	$js      = '/assets/js/header' . $suffix . '.js';
	$deps    = wp_style_is( 'storefront-woocommerce-style', 'registered' ) ? array( 'storefront-woocommerce-style' ) : array( 'storefront-style' );
	$css_ver = file_exists( $theme_dir . $css ) ? (string) filemtime( $theme_dir . $css ) : null;
	$js_ver  = file_exists( $theme_dir . $js ) ? (string) filemtime( $theme_dir . $js ) : null;

	wp_enqueue_style( 'upperel-header', $theme_uri . $css, $deps, $css_ver );
	wp_style_add_data( 'upperel-header', 'rtl', 'replace' );

	wp_add_inline_style(
		'upperel-header',
		sprintf(
			'#masthead{--upperel-header-bg:%1$s;--upperel-header-text:%2$s;--upperel-header-link:%3$s;}',
			sanitize_hex_color( get_theme_mod( 'storefront_header_background_color', '#ffffff' ) ) ?: '#ffffff',
			sanitize_hex_color( get_theme_mod( 'storefront_header_text_color', '#404040' ) ) ?: '#404040',
			sanitize_hex_color( get_theme_mod( 'storefront_header_link_color', '#333333' ) ) ?: '#333333'
		)
	);

	wp_enqueue_script( 'upperel-header', $theme_uri . $js, array(), $js_ver, true );
}
add_action( 'wp_enqueue_scripts', 'upperel_header_assets', 20 );
