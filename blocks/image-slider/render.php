<?php
/**
 * Front-end markup for the Image Slider block.
 *
 * @package storefront
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */

$upperel_slides = array();
foreach ( ( isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ? $attributes['slides'] : array() ) as $upperel_slide ) {
	if ( ! is_array( $upperel_slide ) ) {
		continue;
	}
	$upperel_is_first = empty( $upperel_slides );
	$upperel_alt      = isset( $upperel_slide['alt'] ) ? (string) $upperel_slide['alt'] : '';
	$upperel_img_args = array(
		'class'    => 'upperel-slider__img',
		'alt'      => $upperel_alt,
		'sizes'    => '100vw',
		'decoding' => $upperel_is_first ? 'auto' : 'async',
		'loading'  => $upperel_is_first ? false : 'lazy',
	);
	if ( $upperel_is_first ) {
		$upperel_img_args['fetchpriority'] = 'high';
	}

	$upperel_img = '';
	if ( ! empty( $upperel_slide['id'] ) ) {
		$upperel_img = wp_get_attachment_image( (int) $upperel_slide['id'], 'full', false, $upperel_img_args );
	}
	if ( ! $upperel_img && ! empty( $upperel_slide['url'] ) ) {
		$upperel_img = sprintf(
			'<img class="upperel-slider__img" src="%1$s" alt="%2$s"%3$s>',
			esc_url( $upperel_slide['url'] ),
			esc_attr( $upperel_alt ),
			$upperel_is_first ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"'
		);
	}
	if ( ! $upperel_img ) {
		continue; // Attachment deleted and no fallback URL.
	}

	$upperel_slides[] = array(
		'img'    => $upperel_img,
		'alt'    => $upperel_alt,
		'link'   => isset( $upperel_slide['link'] ) ? trim( (string) $upperel_slide['link'] ) : '',
		'newTab' => ! empty( $upperel_slide['newTab'] ),
	);
}

if ( empty( $upperel_slides ) ) {
	return;
}

$upperel_count       = count( $upperel_slides );
$upperel_height      = max( 10, min( 100, (int) ( $attributes['height'] ?? 70 ) ) );
$upperel_mob_height  = max( 10, min( 100, (int) ( $attributes['mobileHeight'] ?? 50 ) ) );
$upperel_transition  = ( $attributes['transition'] ?? 'slide' ) === 'fade' ? 'fade' : 'slide';
$upperel_autoplay    = ! empty( $attributes['autoplay'] ) && $upperel_count > 1;
$upperel_interval    = max( 2, min( 30, (int) ( $attributes['interval'] ?? 5 ) ) ) * 1000;
$upperel_show_arrows = ! empty( $attributes['showArrows'] ) && $upperel_count > 1;
$upperel_show_dots   = ! empty( $attributes['showDots'] ) && $upperel_count > 1;

$upperel_wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'upperel-slider is-transition-' . $upperel_transition,
		'style'                => sprintf( '--upperel-slider-height:%dvh;--upperel-slider-height-mobile:%dvh;', $upperel_height, $upperel_mob_height ),
		'role'                 => 'region',
		'aria-roledescription' => __( 'carousel', 'storefront' ),
		'aria-label'           => __( 'Featured images', 'storefront' ),
		'data-autoplay'        => $upperel_autoplay ? 'true' : 'false',
		'data-interval'        => (string) $upperel_interval,
	)
);

/*
 * Buffer the markup so whitespace can be stripped before it is returned.
 * WooCommerce passes the Shop page content through wpautop() *after* blocks
 * render, which turns line breaks into <br> and <p> tags that push the
 * images apart (the "black bars").
 */
ob_start();
?>
<div <?php echo $upperel_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="upperel-slider__viewport">
		<ul class="upperel-slider__track" aria-live="<?php echo $upperel_autoplay ? 'off' : 'polite'; ?>">
			<?php
			foreach ( $upperel_slides as $upperel_i => $upperel_slide ) :
				$upperel_is_first = 0 === $upperel_i;
				$upperel_alt      = $upperel_slide['alt'];
				$upperel_link     = $upperel_slide['link'];
				$upperel_img      = $upperel_slide['img'];
				?>
				<li
					class="upperel-slider__slide<?php echo $upperel_is_first ? ' is-active' : ''; ?>"
					role="group"
					aria-roledescription="<?php esc_attr_e( 'slide', 'storefront' ); ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: 1: slide number, 2: total slides */ __( '%1$d of %2$d', 'storefront' ), $upperel_i + 1, $upperel_count ) ); ?>"
					<?php echo $upperel_is_first ? '' : 'inert'; ?>
				>
					<?php if ( $upperel_link ) : ?>
						<a
							class="upperel-slider__link"
							href="<?php echo esc_url( $upperel_link ); ?>"
							<?php if ( $upperel_slide['newTab'] ) : ?>
								target="_blank" rel="noopener"
							<?php endif; ?>
							<?php if ( '' === $upperel_alt ) : ?>
								aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Slide %d', 'storefront' ), $upperel_i + 1 ) ); ?>"
							<?php endif; ?>
						>
							<?php echo $upperel_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php else : ?>
						<?php echo $upperel_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<?php if ( $upperel_show_arrows ) : ?>
		<div class="upperel-slider__nav">
		<button type="button" class="upperel-slider__arrow upperel-slider__arrow--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'storefront' ); ?>">
			<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
		<button type="button" class="upperel-slider__arrow upperel-slider__arrow--next" aria-label="<?php esc_attr_e( 'Next slide', 'storefront' ); ?>">
			<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
		</div>
	<?php endif; ?>

	<?php if ( $upperel_show_dots || $upperel_autoplay ) : ?>
		<div class="upperel-slider__controls">
			<?php if ( $upperel_autoplay ) : ?>
				<div class="upperel-slider__toggle-wrap">
				<button
					type="button"
					class="upperel-slider__toggle"
					aria-label="<?php esc_attr_e( 'Pause slideshow', 'storefront' ); ?>"
					data-label-pause="<?php esc_attr_e( 'Pause slideshow', 'storefront' ); ?>"
					data-label-play="<?php esc_attr_e( 'Play slideshow', 'storefront' ); ?>"
				>
					<svg class="upperel-slider__icon-pause" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M7 5h3v14H7zM14 5h3v14h-3z" fill="currentColor"/></svg>
					<svg class="upperel-slider__icon-play" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>
				</button>
				</div>
			<?php endif; ?>

			<?php if ( $upperel_show_dots ) : ?>
				<div class="upperel-slider__dots">
					<?php for ( $upperel_d = 0; $upperel_d < $upperel_count; $upperel_d++ ) : ?>
						<button
							type="button"
							class="upperel-slider__dot"
							data-index="<?php echo (int) $upperel_d; ?>"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'storefront' ), $upperel_d + 1 ) ); ?>"
							aria-current="<?php echo 0 === $upperel_d ? 'true' : 'false'; ?>"
						></button>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
<?php
// Collapse all line breaks (also inside attributes), then the gaps between tags.
echo preg_replace( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
	array( '/\s*\n\s*/', '/>\s+</' ),
	array( ' ', '><' ),
	trim( ob_get_clean() )
);
