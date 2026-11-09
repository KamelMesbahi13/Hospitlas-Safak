<?php
/**
 * Single Hospital: Related Hospitals Section.
 *
 * Displays up to 3 partner hospitals in a clean modern grid.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang    = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl  = shm_is_rtl( $lang );
$dir     = $is_rtl ? 'rtl' : 'ltr';
$related = shm_get_related_hospitals( $post_id, 3, $lang );

if ( empty( $related ) ) {
	return;
}
?>

<section id="shm-related" class="safak-dept-wrapper shm-related-section" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-dept-header-row">
		<div class="safak-header-start">
			<span class="safak-kicker"><?php echo esc_html( shm_t( 'partner_badge', $lang ) ); ?></span>
			<h2 class="safak-headline"><?php echo esc_html( shm_t( 'related', $lang ) ); ?></h2>
		</div>
		<div class="safak-header-end">
			<a href="<?php echo esc_url( get_post_type_archive_link( SHM_POST_TYPE ) ); ?>" class="safak-mod-hero__btn safak-mod-hero__btn--secondary">
				<span><?php echo esc_html( shm_t( 'hospitals_in_turkey', $lang ) ); ?></span>
			</a>
		</div>
	</div>

	<div class="shm-cards-grid shm-cards-grid--cols-3" style="--shm-cols: 3;">
		<?php foreach ( $related as $rel_post ) : ?>
			<?php shm_template_part( 'card', array( 'hospital_id' => $rel_post->ID, 'lang' => $lang ) ); ?>
		<?php endforeach; ?>
	</div>
</section>
