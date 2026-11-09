<?php
/**
 * Single Hospital: International Patient Services & Amenities (.safak-patient-services-wrapper).
 *
 * Exact structure and styling from Safak Medical /departments/ & about-us pages.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id      = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang         = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl       = shm_is_rtl( $lang );
$dir          = $is_rtl ? 'rtl' : 'ltr';
$all_defs     = shm_amenity_definitions();
$amenity_keys = shm_meta( $post_id, '_hospital_amenities', array() );

if ( empty( $amenity_keys ) || ! is_array( $amenity_keys ) ) {
	$amenity_keys = array_keys( $all_defs );
}
?>

<div id="shm-amenities" class="safak-patient-services-wrapper" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-patient-services-card">

		<div class="safak-services-content">
			<span class="safak-services-tag"><?php echo esc_html( shm_t( 'services_tag', $lang ) ); ?></span>
			<h2 class="safak-services-title">
				<?php echo esc_html( shm_t( 'services_title_1', $lang ) ); ?><br>
				<?php echo esc_html( shm_t( 'services_title_2', $lang ) ); ?>
			</h2>
			<p class="safak-services-desc">
				<?php echo esc_html( shm_t( 'services_desc', $lang ) ); ?>
			</p>
		</div>

		<div class="safak-services-list">
			<?php foreach ( $amenity_keys as $key ) : ?>
				<?php
				if ( ! isset( $all_defs[ $key ] ) ) {
					continue;
				}
				$name = shm_t( 'am_' . $key, $lang );
				?>
				<div class="safak-service-item">
					<span class="safak-service-check">✓</span>
					<span class="safak-service-text"><?php echo esc_html( $name ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

	</div>
</div>
