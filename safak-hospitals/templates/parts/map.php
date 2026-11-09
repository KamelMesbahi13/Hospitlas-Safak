<?php
/**
 * Single Hospital: Location Map with Directions Part.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id    = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang       = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl     = shm_is_rtl( $lang );
$dir        = $is_rtl ? 'rtl' : 'ltr';
$map_src    = shm_map_embed_src( $post_id );
$directions = shm_directions_url( $post_id );
$address    = trim( (string) shm_meta( $post_id, '_hospital_address' ) );
$city_line  = shm_get_location_line( $post_id, $lang, true );

if ( empty( $map_src ) && empty( $address ) && empty( $city_line ) ) {
	return;
}
?>

<section id="shm-location" class="safak-dept-wrapper shm-location-section" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-dept-header-row">
		<div class="safak-header-start">
			<span class="safak-kicker"><?php echo esc_html( shm_t( 'location', $lang ) ); ?></span>
			<h2 class="safak-headline"><?php echo esc_html( $city_line ? $city_line : shm_t( 'location', $lang ) ); ?></h2>
			<?php if ( $address ) : ?>
				<p class="shm-loc-address-line"><?php echo esc_html( $address ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $directions ) : ?>
			<div class="safak-header-end">
				<a href="<?php echo esc_url( $directions ); ?>" target="_blank" rel="noopener noreferrer" class="safak-mod-hero__btn safak-mod-hero__btn--secondary">
					<?php echo shm_icon( 'navigation', 'shm-btn-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( shm_t( 'get_directions', $lang ) ); ?></span>
				</a>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $map_src ) : ?>
		<div class="shm-map-container">
			<iframe src="<?php echo esc_url( $map_src ); ?>"
				width="100%"
				height="420"
				style="border:0;"
				allowfullscreen=""
				loading="lazy"
				referrerpolicy="no-referrer-when-downgrade"
				title="<?php echo esc_attr( sprintf( '%s - %s', get_the_title( $post_id ), shm_t( 'map_title', $lang ) ) ); ?>">
			</iframe>
		</div>
	<?php endif; ?>
</section>
