<?php
/**
 * Upperel footer.
 *
 * Columns (one on phones, two on tablets, four on desktop):
 * - Brand: logo (or site title), a short "about" text and social links.
 * - Column 1: the menu in the "Footer: Column 1" location, headed by the
 *   menu's name; without one, "Shop" and the top-level product categories.
 * - Column 2: the menu in the "Footer: Column 2" location, likewise;
 *   without one, "Help" with My account, Cart and Checkout.
 * - Contact: the store address (WooCommerce → Settings → General), and a
 *   phone number and email address.
 * Then a bottom bar with the copyright line and the privacy policy and
 * terms links. Columns with nothing to show are left out.
 *
 * Edited in Customize → Footer (texts, contact details, social links)
 * and Appearance → Menus (the two link columns).
 *
 * Replaces Storefront's credit line; its footer widget areas still show
 * above the columns if widgets are added to them.
 *
 * Styles: assets/css/base/upperel-footer.scss
 *
 * @package storefront
 */

/**
 * Swap Storefront's credit line for the Upperel footer, and register the
 * two footer menu locations.
 */
function upperel_footer_setup() {
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
	add_action( 'storefront_footer', 'upperel_footer_columns', 15 );
	add_action( 'storefront_footer', 'upperel_footer_bottom', 20 );

	register_nav_menus(
		array(
			'footer-1' => __( 'Footer: Column 1', 'storefront' ),
			'footer-2' => __( 'Footer: Column 2', 'storefront' ),
		)
	);
}
add_action( 'after_setup_theme', 'upperel_footer_setup', 20 );

/**
 * Social networks offered in the Customizer.
 *
 * @return array Key => label.
 */
function upperel_footer_social_networks() {
	return array(
		'instagram' => __( 'Instagram', 'storefront' ),
		'facebook'  => __( 'Facebook', 'storefront' ),
		'tiktok'    => __( 'TikTok', 'storefront' ),
		'x'         => __( 'X (Twitter)', 'storefront' ),
		'youtube'   => __( 'YouTube', 'storefront' ),
		'whatsapp'  => __( 'WhatsApp', 'storefront' ),
	);
}

/**
 * Default "about" text under the logo.
 *
 * @return string
 */
function upperel_footer_default_about() {
	return __( 'Clothing, bags and accessories.', 'storefront' );
}

/**
 * Default copyright line. {year} and {site} are replaced.
 *
 * @return string
 */
function upperel_footer_default_copyright() {
	return __( '© {year} {site}. All rights reserved.', 'storefront' );
}

/**
 * Customize → Footer: texts, contact details and social links.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function upperel_footer_customize_register( $wp_customize ) {
	$priority = 100; // After Storefront's footer colours.

	$fields = array(
		'upperel_footer_about'     => array(
			'type'        => 'textarea',
			'label'       => __( 'About text', 'storefront' ),
			'description' => __( 'Shown under the logo. Leave empty to hide it.', 'storefront' ),
			'default'     => upperel_footer_default_about(),
			'sanitize'    => 'sanitize_textarea_field',
		),
		'upperel_footer_phone'     => array(
			'type'        => 'tel',
			'label'       => __( 'Phone number', 'storefront' ),
			'description' => __( 'The address comes from WooCommerce → Settings → General.', 'storefront' ),
			'sanitize'    => 'sanitize_text_field',
		),
		'upperel_footer_email'     => array(
			'type'     => 'email',
			'label'    => __( 'Email address', 'storefront' ),
			'sanitize' => 'sanitize_email',
		),
		'upperel_footer_copyright' => array(
			'type'        => 'text',
			'label'       => __( 'Copyright line', 'storefront' ),
			'description' => __( '{year} is replaced by the current year and {site} by the site title.', 'storefront' ),
			'default'     => upperel_footer_default_copyright(),
			'sanitize'    => 'sanitize_text_field',
		),
	);

	foreach ( upperel_footer_social_networks() as $network => $label ) {
		$fields[ 'upperel_social_' . $network ] = array(
			'type'        => 'url',
			/* translators: %s: social network name. */
			'label'       => sprintf( __( '%s link', 'storefront' ), $label ),
			'description' => 'whatsapp' === $network ? __( 'e.g. https://wa.me/254700000000', 'storefront' ) : '',
			'sanitize'    => 'esc_url_raw',
		);
	}

	foreach ( $fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $field['default'] ?? '',
				'sanitize_callback' => $field['sanitize'],
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'type'        => $field['type'],
				'label'       => $field['label'],
				'description' => $field['description'] ?? '',
				'section'     => 'storefront_footer',
				'priority'    => $priority++,
			)
		);
	}
}
add_action( 'customize_register', 'upperel_footer_customize_register', 20 );

/**
 * Inline SVG line icons used in the footer (same style as the header's).
 *
 * @param string $name Icon name.
 * @return string SVG markup.
 */
function upperel_footer_icon( $name ) {
	$paths = array(
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.6" fill="currentColor"/>',
		'facebook'  => '<path d="M15.5 3.5H13A3.5 3.5 0 0 0 9.5 7v2.5H7v3.5h2.5v7.5H13V13h2.5l.5-3.5h-3V7.5a1 1 0 0 1 1-1h2z"/>',
		'tiktok'    => '<path d="M13.5 3.5v11a3.5 3.5 0 1 1-3.5-3.5"/><path d="M13.5 3.5c.4 2.6 2.4 4.6 5 5"/>',
		'x'         => '<path d="M4 4h4.5L20 20h-4.5z"/><path d="M19.5 4l-6.4 7.1M4.5 20l6.4-7.1"/>',
		'youtube'   => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9.25v5.5L14.75 12z"/>',
		'whatsapp'  => '<path d="M4 20l1.2-3.8A8.5 8.5 0 1 1 8 18.9z"/><path d="M9.2 8.6c-.3 2.9 3.2 6.4 6.2 6.2l.9-1.4-1.9-1-.9.8a4.6 4.6 0 0 1-2.1-2.1l.8-.9-1-1.9z"/>',
		'email'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 7 8.5-7"/>',
		'phone'     => '<path d="M5.5 3.5h3.5l1.75 4.5-2.25 1.4a10.5 10.5 0 0 0 6.1 6.1L16 13.25l4.5 1.75v3.5a2 2 0 0 1-2 2A15 15 0 0 1 3.5 5.5a2 2 0 0 1 2-2z"/>',
		'pin'       => '<path d="M12 21s-6.5-5.7-6.5-11a6.5 6.5 0 0 1 13 0c0 5.3-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.5"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg class="upperel-icon upperel-icon--' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * A published page's link as [ url, title ], or null.
 *
 * @param int    $page_id Page ID.
 * @param string $title   Link text; the page title if empty.
 * @return array|null
 */
function upperel_footer_page_link( $page_id, $title = '' ) {
	$page_id = (int) $page_id;
	if ( $page_id <= 0 || 'publish' !== get_post_status( $page_id ) ) {
		return null;
	}
	return array( get_permalink( $page_id ), $title ? $title : get_the_title( $page_id ) );
}

/**
 * Default links for a footer menu column when no menu is assigned.
 *
 * @param string $column shop (column 1) or help (column 2).
 * @return array[] Each [ url, title ].
 */
function upperel_footer_default_links( $column ) {
	$links = array();

	if ( 'shop' === $column && function_exists( 'wc_get_page_id' ) ) {
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
				'number'     => 6,
				'orderby'    => 'name',
			)
		);
		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				$links[] = array( get_term_link( $category ), $category->name );
			}
		}
		$links[] = array( get_post_type_archive_link( 'product' ), __( 'All products', 'storefront' ) );
	}

	if ( 'help' === $column && function_exists( 'wc_get_page_id' ) ) {
		$links[] = upperel_footer_page_link( wc_get_page_id( 'myaccount' ), __( 'My account', 'storefront' ) );
		$links[] = upperel_footer_page_link( wc_get_page_id( 'cart' ), __( 'Cart', 'storefront' ) );
		$links[] = upperel_footer_page_link( wc_get_page_id( 'checkout' ), __( 'Checkout', 'storefront' ) );
	}

	return array_values( array_filter( $links ) );
}

/**
 * A menu column: the menu in the "Footer: Column N" location, headed by
 * the menu's name (so renaming the menu renames the column), or the
 * default links under the default heading.
 *
 * @param int    $number   Column number: 1 or 2.
 * @param string $heading  Default column heading.
 * @param string $defaults Default links: shop or help.
 * @return string Column markup, or '' if there are no links.
 */
function upperel_footer_link_column( $number, $heading, $defaults ) {
	$location = 'footer-' . $number;

	if ( has_nav_menu( $location ) ) {
		$locations = get_nav_menu_locations();
		$menu_obj  = wp_get_nav_menu_object( $locations[ $location ] );
		if ( $menu_obj && '' !== trim( $menu_obj->name ) ) {
			$heading = $menu_obj->name;
		}

		$menu = wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'upperel-footer__links',
				'depth'          => 1,
				'fallback_cb'    => false,
				'echo'           => false,
			)
		);
	} else {
		$links = upperel_footer_default_links( $defaults );
		if ( ! $links ) {
			return '';
		}
		$menu = '<ul class="upperel-footer__links">';
		foreach ( $links as $link ) {
			$menu .= '<li><a href="' . esc_url( $link[0] ) . '">' . esc_html( $link[1] ) . '</a></li>';
		}
		$menu .= '</ul>';
	}

	if ( ! $menu ) {
		return '';
	}

	return '<nav class="upperel-footer__column upperel-footer__column--menu-' . (int) $number . '" aria-label="' . esc_attr( $heading ) . '">'
		. '<h2 class="upperel-footer__heading">' . esc_html( $heading ) . '</h2>'
		. $menu
		. '</nav>';
}

/**
 * The store address from WooCommerce's settings, one line per part.
 *
 * @return string[]
 */
function upperel_footer_address_lines() {
	if ( ! function_exists( 'WC' ) || ! WC()->countries ) {
		return array();
	}
	$countries = WC()->countries;
	if ( '' === trim( $countries->get_base_address() . $countries->get_base_city() ) ) {
		return array(); // Only a country set: not an address.
	}
	$address = $countries->get_formatted_address(
		array(
			'address_1' => $countries->get_base_address(),
			'address_2' => $countries->get_base_address_2(),
			'city'      => $countries->get_base_city(),
			'state'     => $countries->get_base_state(),
			'postcode'  => $countries->get_base_postcode(),
			'country'   => $countries->get_base_country(),
		),
		"\n"
	);
	return array_filter( array_map( 'trim', explode( "\n", wp_strip_all_tags( $address ) ) ) );
}

/**
 * The footer columns.
 */
function upperel_footer_columns() {
	$about  = get_theme_mod( 'upperel_footer_about', upperel_footer_default_about() );
	$phone  = trim( (string) get_theme_mod( 'upperel_footer_phone', '' ) );
	$email  = sanitize_email( get_theme_mod( 'upperel_footer_email', '' ) );
	$lines  = upperel_footer_address_lines();
	$social = array();
	foreach ( upperel_footer_social_networks() as $network => $label ) {
		$url = get_theme_mod( 'upperel_social_' . $network, '' );
		if ( $url ) {
			$social[ $network ] = array( $url, $label );
		}
	}

	$menu_1 = upperel_footer_link_column( 1, __( 'Shop', 'storefront' ), 'shop' );
	$menu_2 = upperel_footer_link_column( 2, __( 'Help', 'storefront' ), 'help' );
	?>
	<div class="upperel-footer">
		<div class="upperel-footer__column upperel-footer__column--brand">
			<div class="upperel-footer__brand">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<a class="upperel-footer__site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( $about ) : ?>
				<p class="upperel-footer__about"><?php echo nl2br( esc_html( $about ) ); ?></p>
			<?php endif; ?>

			<?php if ( $social ) : ?>
				<ul class="upperel-footer__social">
					<?php foreach ( $social as $network => $item ) : ?>
						<li>
							<a href="<?php echo esc_url( $item[0] ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo upperel_footer_icon( $network ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span class="screen-reader-text">
									<?php
									/* translators: %s: social network name. */
									echo esc_html( sprintf( __( '%s (opens in a new tab)', 'storefront' ), $item[1] ) );
									?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php
		echo $menu_1; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in upperel_footer_link_column().
		echo $menu_2; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in upperel_footer_link_column().
		?>

		<?php if ( $lines || $phone || $email ) : ?>
			<div class="upperel-footer__column upperel-footer__column--contact">
				<h2 class="upperel-footer__heading"><?php esc_html_e( 'Contact', 'storefront' ); ?></h2>
				<ul class="upperel-footer__contact">
					<?php if ( $lines ) : ?>
						<li>
							<?php echo upperel_footer_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<address><?php echo implode( '<br>', array_map( 'esc_html', $lines ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></address>
						</li>
					<?php endif; ?>
					<?php if ( $phone ) : ?>
						<li>
							<?php echo upperel_footer_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
						</li>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<li>
							<?php echo upperel_footer_icon( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<a href="<?php echo esc_url( 'mailto:' . antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The bottom bar: copyright line, and the privacy policy and terms links
 * (when those pages are set and published).
 */
function upperel_footer_bottom() {
	$copyright = get_theme_mod( 'upperel_footer_copyright', upperel_footer_default_copyright() );
	$copyright = str_replace( array( '{year}', '{site}' ), array( gmdate( 'Y' ), get_bloginfo( 'name' ) ), $copyright );

	$legal = array_filter(
		array(
			upperel_footer_page_link( (int) get_option( 'wp_page_for_privacy_policy' ) ),
			function_exists( 'wc_terms_and_conditions_page_id' ) ? upperel_footer_page_link( wc_terms_and_conditions_page_id() ) : null,
		)
	);
	?>
	<div class="upperel-footer-bottom site-info">
		<?php if ( $copyright ) : ?>
			<p class="upperel-footer-bottom__copyright"><?php echo esc_html( $copyright ); ?></p>
		<?php endif; ?>
		<?php if ( $legal ) : ?>
			<ul class="upperel-footer-bottom__links">
				<?php foreach ( $legal as $link ) : ?>
					<li><a href="<?php echo esc_url( $link[0] ); ?>"><?php echo esc_html( $link[1] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}
