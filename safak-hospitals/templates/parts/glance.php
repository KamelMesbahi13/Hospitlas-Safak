<?php
/**
 * Single Hospital: Hospital At a Glance Part.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id   = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang      = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$city      = shm_get_city( $post_id );
$district  = trim( (string) shm_meta( $post_id, '_hospital_district' ) );
$address   = trim( (string) shm_meta( $post_id, '_hospital_address' ) );
$cats      = shm_terms( $post_id, SHM_TAX_CATEGORY );
$cat_name  = ! empty( $cats ) ? $cats[0]->name : '';
$year      = shm_int( $post_id, '_hospital_established_year' );
$beds      = shm_int( $post_id, '_hospital_beds_count' );
$badges    = shm_get_badges( $post_id, $lang );
$phone     = shm_phone_display( $post_id );
$wa_url    = shm_whatsapp_url( $post_id, $lang );
?>

<div class="shm-sidebar-widget shm-glance-widget">
	<h3 class="shm-widget-title">
		<?php echo shm_icon( 'hospital', 'shm-widget-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span><?php echo esc_html( shm_t( 'glance', $lang ) ); ?></span>
	</h3>

	<dl class="shm-glance-list">
		<?php if ( $city ) : ?>
			<div class="shm-glance-item">
				<dt class="shm-glance-dt"><?php echo esc_html( shm_t( 'city', $lang ) ); ?></dt>
				<dd class="shm-glance-dd"><strong><?php echo esc_html( $city ); ?></strong></dd>
			</div>
		<?php endif; ?>

		<?php if ( $district ) : ?>
			<div class="shm-glance-item">
				<dt class="shm-glance-dt"><?php echo esc_html( shm_t( 'district', $lang ) ); ?></dt>
				<dd class="shm-glance-dd"><?php echo esc_html( $district ); ?></dd>
			</div>
		<?php endif; ?>

		<?php if ( $cat_name ) : ?>
			<div class="shm-glance-item">
				<dt class="shm-glance-dt"><?php echo esc_html( shm_t( 'category', $lang ) ); ?></dt>
				<dd class="shm-glance-dd"><?php echo esc_html( $cat_name ); ?></dd>
			</div>
		<?php endif; ?>

		<?php if ( $year > 1800 ) : ?>
			<div class="shm-glance-item">
				<dt class="shm-glance-dt"><?php echo esc_html( shm_t( 'established', $lang ) ); ?></dt>
				<dd class="shm-glance-dd"><?php echo esc_html( $year ); ?></dd>
			</div>
		<?php endif; ?>

		<?php if ( $beds > 0 ) : ?>
			<div class="shm-glance-item">
				<dt class="shm-glance-dt"><?php echo esc_html( shm_t( 'beds', $lang ) ); ?></dt>
				<dd class="shm-glance-dd"><strong><?php echo esc_html( number_format_i18n( $beds ) ); ?></strong></dd>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $badges ) ) : ?>
			<div class="shm-glance-item">
				<dt class="shm-glance-dt"><?php echo esc_html( shm_t( 'accreditations', $lang ) ); ?></dt>
				<dd class="shm-glance-dd">
					<div class="shm-glance-badges">
						<?php foreach ( $badges as $b ) : ?>
							<span class="shm-badge-mini" title="<?php echo esc_attr( $b['full'] ); ?>"><?php echo esc_html( $b['label'] ); ?></span>
						<?php endforeach; ?>
					</div>
				</dd>
			</div>
		<?php endif; ?>

		<div class="shm-glance-item">
			<dt class="shm-glance-dt"><?php echo esc_html( shm_t( 'languages', $lang ) ); ?></dt>
			<dd class="shm-glance-dd"><?php echo esc_html( shm_t( 'languages_value', $lang ) ); ?></dd>
		</div>

		<div class="shm-glance-item">
			<dt class="shm-glance-dt"><?php echo esc_html( shm_t( 'whatsapp', $lang ) ); ?></dt>
			<dd class="shm-glance-dd">
				<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="shm-glance-wa-link" dir="ltr">
					<?php echo shm_icon( 'whatsapp', 'shm-wa-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( $phone ); ?></span>
				</a>
			</dd>
		</div>
	</dl>
</div>

