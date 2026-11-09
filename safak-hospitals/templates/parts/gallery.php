<?php
/**
 * Single Hospital: Photo Gallery Part (Image-4 Showcase Style + Blurred Lightbox).
 *
 * Renders a compact rounded showcase gallery card:
 * - Main rounded preview image on top (clicking opens the blurred lightbox).
 * - Horizontal strip of rounded thumbnails directly underneath (clicking switches main photo).
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id       = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang          = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl        = shm_is_rtl( $lang );
$dir           = $is_rtl ? 'rtl' : 'ltr';
$gallery_items = shm_get_gallery_items( $post_id, $lang );

if ( empty( $gallery_items ) ) {
	return;
}

$total_images = count( $gallery_items );
$first_item   = $gallery_items[0];
$first_full   = $first_item['full'];
$first_large  = ! empty( $first_item['large'] ) ? $first_item['large'] : $first_full;
$first_cap    = ! empty( $first_item['caption'] ) ? $first_item['caption'] : get_the_title( $post_id );
?>

<section id="shm-gallery" class="safak-dept-wrapper shm-gallery-section" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-dept-header-row">
		<div class="safak-header-start">
			<span class="safak-kicker"><?php echo esc_html( shm_t( 'gallery_kicker', $lang ) ); ?></span>
			<h2 class="safak-headline"><?php echo esc_html( shm_t( 'gallery', $lang ) ); ?></h2>
		</div>
		<div class="safak-header-end shm-gallery-header-end">
			<p class="safak-header-desc-top">
				<?php echo esc_html( shm_t( 'gallery_sub_desc', $lang ) ); ?>
			</p>
		</div>
	</div>

	<div class="shm-showcase-gallery" data-shm-gallery-lightbox>
		<!-- Main Top Image (Click to open Blurred Lightbox) -->
		<a href="<?php echo esc_url( $first_full ); ?>"
			class="shm-showcase-stage"
			data-shm-showcase-main
			data-current-index="0">
			<img src="<?php echo esc_url( $first_large ); ?>"
				alt="<?php echo esc_attr( $first_cap ); ?>"
				decoding="async"
				class="shm-showcase-main-img"
				data-shm-showcase-img />
			<span class="shm-showcase-zoom-pill" aria-hidden="true">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="15 3 21 3 21 9"></polyline>
					<polyline points="9 21 3 21 3 15"></polyline>
					<line x1="21" y1="3" x2="14" y2="10"></line>
					<line x1="3" y1="21" x2="10" y2="14"></line>
				</svg>
			</span>
		</a>

		<!-- Bottom Row of Rounded Thumbnails (Style of Attached Image 4) -->
		<div class="shm-showcase-strip-wrap">
			<div class="shm-showcase-strip" role="list">
				<?php
				foreach ( $gallery_items as $index => $item ) :
					$full_url  = $item['full'];
					$large_url = ! empty( $item['large'] ) ? $item['large'] : $full_url;
					$caption   = ! empty( $item['caption'] ) ? $item['caption'] : get_the_title( $post_id );
					?>
					<a href="<?php echo esc_url( $full_url ); ?>"
						role="listitem"
						class="shm-showcase-thumb <?php echo 0 === $index ? 'is-active' : ''; ?>"
						data-shm-gallery-item
						data-index="<?php echo esc_attr( $index ); ?>"
						data-full="<?php echo esc_url( $full_url ); ?>"
						data-large="<?php echo esc_url( $large_url ); ?>"
						data-thumb="<?php echo esc_url( $large_url ); ?>"
						data-caption="<?php echo esc_attr( $caption ); ?>">
						<img src="<?php echo esc_url( $large_url ); ?>"
							alt="<?php echo esc_attr( $caption ); ?>"
							loading="lazy"
							decoding="async"
							class="shm-showcase-thumb-img" />
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
