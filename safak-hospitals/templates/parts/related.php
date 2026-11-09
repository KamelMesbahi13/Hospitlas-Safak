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

// Guaranteed language-appropriate kicker and headline for related hospitals
$rel_kicker = shm_t( 'partner_badge', $lang );
if ( 'fr' === $lang && ( empty( $rel_kicker ) || preg_match( '/[\x{0600}-\x{06FF}]/u', $rel_kicker ) ) ) {
	$rel_kicker = 'Hôpital partenaire accrédité';
} elseif ( 'en' === $lang && ( empty( $rel_kicker ) || preg_match( '/[\x{0600}-\x{06FF}]/u', $rel_kicker ) ) ) {
	$rel_kicker = 'Accredited Partner Hospital';
}

$rel_title = shm_t( 'related', $lang );
if ( 'fr' === $lang && ( empty( $rel_title ) || preg_match( '/[\x{0600}-\x{06FF}]/u', $rel_title ) ) ) {
	$rel_title = 'Autres hôpitaux en Turquie';
} elseif ( 'en' === $lang && ( empty( $rel_title ) || preg_match( '/[\x{0600}-\x{06FF}]/u', $rel_title ) ) ) {
	$rel_title = 'Other Hospitals in Turkey';
}
?>

<section id="shm-related" class="safak-dept-wrapper shm-related-section" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-dept-header-row">
		<div class="safak-header-start">
			<span class="safak-kicker"><?php echo esc_html( $rel_kicker ); ?></span>
			<h2 class="safak-headline"><?php echo esc_html( $rel_title ); ?></h2>
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
