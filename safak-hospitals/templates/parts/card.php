<?php
/**
 * Hospital Card Component (Used in grids, archives & shortcodes).
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$hospital_id = isset( $hospital_id ) ? (int) $hospital_id : get_the_ID();
$lang        = isset( $lang ) ? $lang : shm_post_lang( $hospital_id );
$title       = get_the_title( $hospital_id );
$link        = get_permalink( $hospital_id );
$thumb_url   = get_the_post_thumbnail_url( $hospital_id, 'large' );
if ( ! $thumb_url ) {
	$main_img_id = absint( shm_meta( $hospital_id, '_hospital_main_photo' ) );
	if ( $main_img_id ) {
		$thumb_url = wp_get_attachment_image_url( $main_img_id, 'large' );
	}
}
if ( ! $thumb_url && function_exists( 'safak_get_hospital_image_url' ) ) {
	$thumb_url = safak_get_hospital_image_url( $hospital_id, 'large' );
}
$city_line   = shm_get_location_line( $hospital_id, $lang, false );
$badges      = shm_get_badges( $hospital_id, $lang );
$wa_url      = shm_whatsapp_url( $hospital_id, $lang );

// Category
$cats     = shm_terms( $hospital_id, SHM_TAX_CATEGORY );
$cat_name = ! empty( $cats ) ? $cats[0]->name : '';

// Quick stats snippet
$beds     = shm_int( $hospital_id, '_hospital_beds_count' );
$doctors  = shm_int( $hospital_id, '_hospital_doctors_count' );
$theatres = shm_int( $hospital_id, '_hospital_operating_theatres' );
?>

<article class="shm-hospital-card">
	<!-- Media Header -->
	<div class="shm-hcard-media">
		<a href="<?php echo esc_url( $link ); ?>" class="shm-hcard-img-link" aria-label="<?php echo esc_attr( $title ); ?>">
			<?php if ( $thumb_url ) : ?>
				<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" class="shm-hcard-img" />
			<?php else : ?>
				<div class="shm-hcard-placeholder">
					<?php echo shm_icon( 'hospital', 'shm-hcard-ph-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			<?php endif; ?>
		</a>

		<!-- Category Tag -->
		<?php if ( $cat_name ) : ?>
			<span class="shm-hcard-cat"><?php echo esc_html( $cat_name ); ?></span>
		<?php endif; ?>

		<!-- Badges Overlays -->
		<?php if ( ! empty( $badges ) ) : ?>
			<div class="shm-hcard-badges">
				<?php foreach ( array_slice( $badges, 0, 2 ) as $b_key => $b ) : ?>
					<span class="shm-badge-pill shm-badge-pill--<?php echo esc_attr( $b_key ); ?>" title="<?php echo esc_attr( $b['full'] ); ?>">
						<?php echo esc_html( $b['label'] ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div><!-- /.shm-hcard-media -->

	<!-- Card Body -->
	<div class="shm-hcard-body">
		<!-- Location -->
		<?php if ( $city_line ) : ?>
			<div class="shm-hcard-location">
				<?php echo shm_icon( 'map-pin', 'shm-hcard-loc-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span><?php echo esc_html( $city_line ); ?></span>
			</div>
		<?php endif; ?>

		<!-- Title -->
		<h3 class="shm-hcard-title">
			<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a>
		</h3>

		<!-- Excerpt / description -->
		<p class="shm-hcard-excerpt"><?php echo esc_html( shm_description( $hospital_id, 18 ) ); ?></p>

		<!-- Mini Stats Snippet -->
		<?php if ( $beds > 0 || $doctors > 0 || $theatres > 0 ) : ?>
			<div class="shm-hcard-stats">
				<?php if ( $beds > 0 ) : ?>
					<div class="shm-hcard-stat">
						<span class="shm-hcard-stat-val"><?php echo esc_html( $beds ); ?></span>
						<span class="shm-hcard-stat-lbl"><?php echo esc_html( shm_t( 'beds_short', $lang ) ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( $doctors > 0 ) : ?>
					<div class="shm-hcard-stat">
						<span class="shm-hcard-stat-val"><?php echo esc_html( $doctors ); ?>+</span>
						<span class="shm-hcard-stat-lbl"><?php echo esc_html( shm_t( 'doctors_short', $lang ) ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( $theatres > 0 ) : ?>
					<div class="shm-hcard-stat">
						<span class="shm-hcard-stat-val"><?php echo esc_html( $theatres ); ?></span>
						<span class="shm-hcard-stat-lbl"><?php echo esc_html( shm_t( 'theatres_short', $lang ) ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Actions -->
		<div class="shm-hcard-actions">
			<a href="<?php echo esc_url( $link ); ?>" class="shm-btn shm-btn--card-view">
				<span><?php echo esc_html( shm_t( 'view_hospital', $lang ) ); ?></span>
				<?php echo shm_icon( 'arrow', 'shm-btn-arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>

			<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="shm-btn shm-btn--card-wa" aria-label="<?php echo esc_attr( shm_t( 'book_whatsapp', $lang ) ); ?>">
				<?php echo shm_icon( 'whatsapp', 'shm-wa-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
		</div>
	</div><!-- /.shm-hcard-body -->
</article>

