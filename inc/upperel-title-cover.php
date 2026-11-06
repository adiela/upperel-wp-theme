<?php
/**
 * Upperel title cover images.
 *
 * Pages, and product/blog categories and tags, can have a "title cover
 * image". When set, the title is shown in a full-width banner directly
 * under the site header (over the image, with a dark overlay) instead of
 * at the top of the content. Titles are uppercase either way.
 *
 * - Pages: "Title cover image" box in the page editor sidebar.
 * - Terms: "Title cover image" field on the add/edit category or tag
 *   screens.
 * - Admin picker: assets/js/admin/title-cover.js (WordPress media library).
 * - Styles: assets/css/base/upperel-title.scss.
 *
 * @package storefront
 */

/**
 * Taxonomies whose terms can have a cover image.
 *
 * @return string[]
 */
function upperel_title_cover_taxonomies() {
	return array( 'product_cat', 'product_tag', 'category', 'post_tag' );
}

const UPPEREL_TITLE_COVER_META = '_upperel_title_cover';

/**
 * The cover image (attachment ID) for the current page or term archive.
 *
 * @return int Attachment ID, or 0 if none.
 */
function upperel_get_title_cover_id() {
	$id = 0;

	if ( is_page() && ! ( function_exists( 'is_shop' ) && is_shop() ) ) {
		$id = (int) get_post_meta( get_queried_object_id(), UPPEREL_TITLE_COVER_META, true );
	} elseif ( is_category() || is_tag() || is_tax( upperel_title_cover_taxonomies() ) ) {
		$id = (int) get_term_meta( get_queried_object_id(), UPPEREL_TITLE_COVER_META, true );
	}

	return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
}

/**
 * The title shown in the cover banner.
 *
 * @return string
 */
function upperel_get_title_cover_title() {
	if ( is_page() ) {
		return single_post_title( '', false );
	}
	return single_term_title( '', false );
}

/*
 * -------------------------------------------------------------------------
 * Front end
 * -------------------------------------------------------------------------
 */

/**
 * Print the cover banner under the site header (before #content), so it
 * spans the full width whatever the content layout.
 */
function upperel_title_cover_banner() {
	$cover = upperel_get_title_cover_id();
	if ( ! $cover ) {
		return;
	}
	?>
	<div class="upperel-title-cover">
		<?php
		// Decorative: the title is the banner's text.
		echo wp_get_attachment_image(
			$cover,
			'full',
			false,
			array(
				'class'         => 'upperel-title-cover__image',
				'alt'           => '',
				'sizes'         => '100vw',
				'loading'       => false,
				'fetchpriority' => 'high',
			)
		);
		?>
		<div class="col-full upperel-title-cover__inner">
			<h1 class="upperel-title-cover__title"><?php echo esc_html( upperel_get_title_cover_title() ); ?></h1>
		</div>
	</div>
	<?php
}
add_action( 'storefront_before_content', 'upperel_title_cover_banner', 5 );

/**
 * Body class, so the space below the site header can be dropped when the
 * banner sits there.
 *
 * @param array $classes Body classes.
 * @return array
 */
function upperel_title_cover_body_class( $classes ) {
	if ( upperel_get_title_cover_id() ) {
		$classes[] = 'has-title-cover';
	}
	return $classes;
}
add_filter( 'body_class', 'upperel_title_cover_body_class' );

/**
 * Pages: Storefront's page header, minus the title when the banner shows it.
 */
function upperel_page_header() {
	if ( ! upperel_get_title_cover_id() ) {
		storefront_page_header();
		return;
	}

	// Keep the featured image, if any; the title is in the banner.
	if ( has_post_thumbnail() ) {
		?>
		<header class="entry-header">
			<?php storefront_post_thumbnail( 'full' ); ?>
		</header>
		<?php
	}
}

/**
 * Swap Storefront's page header for upperel_page_header().
 */
function upperel_title_cover_setup() {
	remove_action( 'storefront_page', 'storefront_page_header', 10 );
	add_action( 'storefront_page', 'upperel_page_header', 10 );
}
add_action( 'after_setup_theme', 'upperel_title_cover_setup', 20 );

/**
 * Product categories/tags: no title in the content when the banner shows
 * it. (The term description still shows.)
 *
 * @param bool $show Whether WooCommerce prints the archive title.
 * @return bool
 */
function upperel_title_cover_hide_product_title( $show ) {
	return ( is_product_taxonomy() && upperel_get_title_cover_id() ) ? false : $show;
}
add_filter( 'woocommerce_show_page_title', 'upperel_title_cover_hide_product_title' );

/*
 * -------------------------------------------------------------------------
 * Admin: pages
 * -------------------------------------------------------------------------
 */

/**
 * "Title cover image" box in the page editor sidebar (not on the Shop page,
 * whose title is hidden and which uses the slider).
 *
 * @param WP_Post $post The page being edited.
 */
function upperel_title_cover_add_meta_box( $post ) {
	if ( function_exists( 'wc_get_page_id' ) && (int) wc_get_page_id( 'shop' ) === (int) $post->ID ) {
		return;
	}
	add_meta_box( 'upperel-title-cover', __( 'Title cover image', 'storefront' ), 'upperel_title_cover_meta_box', 'page', 'side', 'low' );
}
add_action( 'add_meta_boxes_page', 'upperel_title_cover_add_meta_box' );

/**
 * Meta box contents.
 *
 * @param WP_Post $post The page being edited.
 */
function upperel_title_cover_meta_box( $post ) {
	wp_nonce_field( 'upperel_title_cover', 'upperel_title_cover_nonce' );
	upperel_title_cover_field( (int) get_post_meta( $post->ID, UPPEREL_TITLE_COVER_META, true ) );
	echo '<p class="description">' . esc_html__( 'Shown behind the page title as a full-width banner.', 'storefront' ) . '</p>';
}

/**
 * Save the page's cover image.
 *
 * @param int $post_id Page ID.
 */
function upperel_title_cover_save_page( $post_id ) {
	if (
		! isset( $_POST['upperel_title_cover_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['upperel_title_cover_nonce'] ), 'upperel_title_cover' ) ||
		( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
		! current_user_can( 'edit_page', $post_id )
	) {
		return;
	}
	upperel_title_cover_save( 'post', $post_id );
}
add_action( 'save_post_page', 'upperel_title_cover_save_page' );

/*
 * -------------------------------------------------------------------------
 * Admin: categories and tags
 * -------------------------------------------------------------------------
 */

/**
 * Field on the "Add new category/tag" form.
 */
function upperel_title_cover_add_term_field() {
	wp_nonce_field( 'upperel_title_cover', 'upperel_title_cover_nonce' );
	?>
	<div class="form-field upperel-title-cover-term">
		<label><?php esc_html_e( 'Title cover image', 'storefront' ); ?></label>
		<?php upperel_title_cover_field( 0 ); ?>
		<p class="description"><?php esc_html_e( 'Shown behind the title on this archive page as a full-width banner.', 'storefront' ); ?></p>
	</div>
	<?php
}

/**
 * Field on the "Edit category/tag" form.
 *
 * @param WP_Term $term The term being edited.
 */
function upperel_title_cover_edit_term_field( $term ) {
	?>
	<tr class="form-field upperel-title-cover-term">
		<th scope="row"><label><?php esc_html_e( 'Title cover image', 'storefront' ); ?></label></th>
		<td>
			<?php
			wp_nonce_field( 'upperel_title_cover', 'upperel_title_cover_nonce' );
			upperel_title_cover_field( (int) get_term_meta( $term->term_id, UPPEREL_TITLE_COVER_META, true ) );
			?>
			<p class="description"><?php esc_html_e( 'Shown behind the title on this archive page as a full-width banner.', 'storefront' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Save a term's cover image.
 *
 * @param int $term_id Term ID.
 */
function upperel_title_cover_save_term( $term_id ) {
	if (
		! isset( $_POST['upperel_title_cover_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['upperel_title_cover_nonce'] ), 'upperel_title_cover' ) ||
		! current_user_can( 'edit_term', $term_id )
	) {
		return;
	}
	upperel_title_cover_save( 'term', $term_id );
}

foreach ( upperel_title_cover_taxonomies() as $upperel_taxonomy ) {
	add_action( "{$upperel_taxonomy}_add_form_fields", 'upperel_title_cover_add_term_field' );
	add_action( "{$upperel_taxonomy}_edit_form_fields", 'upperel_title_cover_edit_term_field' );
	add_action( "created_{$upperel_taxonomy}", 'upperel_title_cover_save_term' );
	add_action( "edited_{$upperel_taxonomy}", 'upperel_title_cover_save_term' );
}
unset( $upperel_taxonomy );

/*
 * -------------------------------------------------------------------------
 * Admin: shared
 * -------------------------------------------------------------------------
 */

/**
 * Image picker: preview, hidden attachment ID, Select/Remove buttons.
 * Wired up by assets/js/admin/title-cover.js.
 *
 * @param int $attachment_id Current image, or 0.
 */
function upperel_title_cover_field( $attachment_id ) {
	$has = $attachment_id && wp_attachment_is_image( $attachment_id );
	?>
	<div class="upperel-title-cover-field">
		<div class="upperel-title-cover-field__preview"<?php echo $has ? '' : ' hidden'; ?>>
			<?php echo $has ? wp_get_attachment_image( $attachment_id, 'medium' ) : ''; ?>
		</div>
		<input type="hidden" name="upperel_title_cover" class="upperel-title-cover-field__id" value="<?php echo esc_attr( $has ? $attachment_id : '' ); ?>" />
		<p class="upperel-title-cover-field__actions">
			<button type="button" class="button upperel-title-cover-field__select" data-label-set="<?php esc_attr_e( 'Select image', 'storefront' ); ?>" data-label-replace="<?php esc_attr_e( 'Replace image', 'storefront' ); ?>">
				<?php echo esc_html( $has ? __( 'Replace image', 'storefront' ) : __( 'Select image', 'storefront' ) ); ?>
			</button>
			<button type="button" class="button-link button-link-delete upperel-title-cover-field__remove"<?php echo $has ? '' : ' hidden'; ?>>
				<?php esc_html_e( 'Remove', 'storefront' ); ?>
			</button>
		</p>
	</div>
	<?php
}

/**
 * Store (or clear) the submitted cover image. Nonce and capability are
 * checked by the callers.
 *
 * @param string $type 'post' or 'term'.
 * @param int    $id   Post or term ID.
 */
function upperel_title_cover_save( $type, $id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by the caller.
	$value = isset( $_POST['upperel_title_cover'] ) ? absint( $_POST['upperel_title_cover'] ) : 0;

	if ( $value && wp_attachment_is_image( $value ) ) {
		'post' === $type ? update_post_meta( $id, UPPEREL_TITLE_COVER_META, $value ) : update_term_meta( $id, UPPEREL_TITLE_COVER_META, $value );
	} else {
		'post' === $type ? delete_post_meta( $id, UPPEREL_TITLE_COVER_META ) : delete_term_meta( $id, UPPEREL_TITLE_COVER_META );
	}
}

/**
 * Load the media library and the picker script on the page editor and the
 * category/tag screens.
 *
 * @param string $hook_suffix Admin page.
 */
function upperel_title_cover_admin_assets( $hook_suffix ) {
	$screen = get_current_screen();
	$is_page_editor = in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) && $screen && 'page' === $screen->post_type;
	$is_term_screen = in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) && $screen && in_array( $screen->taxonomy, upperel_title_cover_taxonomies(), true );

	if ( ! $is_page_editor && ! $is_term_screen ) {
		return;
	}

	wp_enqueue_media();

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$js     = '/assets/js/admin/title-cover' . $suffix . '.js';
	$path   = get_template_directory() . $js;

	wp_enqueue_script( 'upperel-title-cover-admin', get_template_directory_uri() . $js, array( 'jquery' ), file_exists( $path ) ? (string) filemtime( $path ) : null, true );
	wp_localize_script(
		'upperel-title-cover-admin',
		'upperelTitleCover',
		array(
			'frameTitle'  => __( 'Title cover image', 'storefront' ),
			'frameButton' => __( 'Use this image', 'storefront' ),
		)
	);
	wp_add_inline_style(
		'common',
		'.upperel-title-cover-field__preview img{display:block;max-width:100%;height:auto;margin-bottom:8px;border-radius:2px}' .
		'.upperel-title-cover-field__actions{display:flex;align-items:center;gap:12px;margin:0}' .
		'.upperel-title-cover-field [hidden]{display:none!important}'
	);
}
add_action( 'admin_enqueue_scripts', 'upperel_title_cover_admin_assets' );
