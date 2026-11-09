<?php
/**
 * Single Hospital / Shortcode: Key Statistics Strip Part (.safak-dept-stats).
 *
 * Matches the Safak Medical / Dribbble top-bordered stat counter strip.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id    = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang       = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl     = shm_is_rtl( $lang );
$dir        = $is_rtl ? 'rtl' : 'ltr';
$stats      = isset( $stats ) ? $stats : shm_get_stats( $post_id, 4, array(), $lang );
$cols       = isset( $cols ) ? (int) $cols : count( $stats );
$cols       = max( 2, min( 6, $cols ) );
$standalone = ! empty( $standalone );

if ( empty( $stats ) ) {
	return;
}
?>

<?php if ( $standalone ) : ?>
<section class="safak-dept-section shm-stats-standalone" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-dept-container">
<?php endif; ?>

		<div class="safak-dept-stats shm-stats-grid--cols-<?php echo esc_attr( $cols ); ?>" style="--shm-stats-cols: <?php echo esc_attr( $cols ); ?>;">
			<?php foreach ( $stats as $key => $s ) : ?>
				<div class="safak-stat-item shm-stat-card--<?php echo esc_attr( $key ); ?>">
					<div class="safak-stat-num">
						+<span class="shm-stat-number" data-target="<?php echo esc_attr( $s['value'] ); ?>"><?php echo esc_html( number_format_i18n( $s['value'] ) ); ?></span>
					</div>
					<div class="safak-stat-text"><?php echo esc_html( $s['label'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>

<?php if ( $standalone ) : ?>
	</div>
</section>
<?php endif; ?>
