<?php
/**
 * Single Hospital: Associated Doctors Part.
 *
 * Uses the .safak-dept-wrapper header and modern rounded doctor cards.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang    = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl  = shm_is_rtl( $lang );
$dir     = $is_rtl ? 'rtl' : 'ltr';
$doctors = shm_get_doctors( $post_id, $lang );

if ( empty( $doctors ) ) {
	return;
}
?>

<section id="shm-doctors" class="safak-dept-wrapper shm-doctors-section" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-dept-header-row">
		<div class="safak-header-start">
			<span class="safak-kicker"><?php echo esc_html( shm_t( 'doctors_kicker', $lang ) ); ?></span>
			<h2 class="safak-headline"><?php echo esc_html( shm_t( 'doctors_headline', $lang ) ); ?></h2>
		</div>
		<div class="safak-header-end">
			<p class="safak-header-desc-top">
				<?php echo esc_html( shm_t( 'doctors_header_desc', $lang ) ); ?>
			</p>
		</div>
	</div>

	<div class="shm-doctors-grid">
		<?php foreach ( $doctors as $doctor ) : ?>
			<?php
			$doc_id    = $doctor->ID;
			$doc_title = get_the_title( $doc_id );
			$doc_link  = get_permalink( $doc_id );
			$doc_thumb = get_the_post_thumbnail_url( $doc_id, shm_doctor_image_size() );
			$doc_spec  = shm_get_doctor_specialty( $doc_id );
			?>
			<article class="shm-doctor-card">
				<a href="<?php echo esc_url( $doc_link ); ?>" class="shm-doctor-card-media" aria-label="<?php echo esc_attr( $doc_title ); ?>">
					<?php if ( $doc_thumb ) : ?>
						<img src="<?php echo esc_url( $doc_thumb ); ?>" alt="<?php echo esc_attr( $doc_title ); ?>" loading="lazy" class="shm-doctor-img" />
					<?php else : ?>
						<div class="shm-doctor-ph">
							<?php echo shm_icon( 'stethoscope', 'shm-doctor-ph-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php endif; ?>
				</a>
				<div class="shm-doctor-card-body">
					<?php if ( $doc_spec ) : ?>
						<span class="shm-doctor-specialty"><?php echo esc_html( $doc_spec ); ?></span>
					<?php endif; ?>
					<h3 class="shm-doctor-name">
						<a href="<?php echo esc_url( $doc_link ); ?>"><?php echo esc_html( $doc_title ); ?></a>
					</h3>
					<a href="<?php echo esc_url( $doc_link ); ?>" class="shm-doctor-link">
						<span><?php echo esc_html( shm_t( 'view_profile', $lang ) ); ?></span>
						<?php echo shm_icon( 'arrow', 'shm-doctor-arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
