<?php
/**
 * Single Hospital: Departments & Infrastructure Highlights Part (.safak-dept-wrapper & .safak-why-grid).
 *
 * Uses the numbered pillars layout (01, 02, 03, 04...) + clean medical department tags.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id     = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang        = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl      = shm_is_rtl( $lang );
$dir         = $is_rtl ? 'rtl' : 'ltr';
$departments = shm_lines( $post_id, '_hospital_departments' );
$infra       = shm_lines( $post_id, '_hospital_infrastructure' );

if ( empty( $departments ) && empty( $infra ) ) {
	return;
}

// Combine top highlights for the 4 numbered pillars (01 - 04)
$pillars = array();
foreach ( array_slice( $departments, 0, 4 ) as $idx => $dept_line ) {
	$sub_desc = isset( $infra[ $idx ] ) ? $infra[ $idx ] : shm_t( 'dept_header_desc', $lang );
	$pillars[] = array(
		'num'   => sprintf( '%02d', $idx + 1 ),
		'title' => $dept_line,
		'desc'  => $sub_desc,
	);
}
if ( empty( $pillars ) && ! empty( $infra ) ) {
	foreach ( array_slice( $infra, 0, 4 ) as $idx => $inf_line ) {
		$pillars[] = array(
			'num'   => sprintf( '%02d', $idx + 1 ),
			'title' => $inf_line,
			'desc'  => shm_t( 'dept_header_desc', $lang ),
		);
	}
}
?>

<section id="shm-departments" class="safak-dept-wrapper" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
	<div class="safak-dept-header-row">
		<div class="safak-header-start">
			<span class="safak-kicker"><?php echo esc_html( shm_t( 'dept_kicker', $lang ) ); ?></span>
			<h2 class="safak-headline"><?php echo esc_html( shm_t( 'dept_headline', $lang ) ); ?></h2>
		</div>
		<div class="safak-header-end">
			<p class="safak-header-desc-top">
				<?php echo esc_html( shm_t( 'dept_header_desc', $lang ) ); ?>
			</p>
		</div>
	</div>

	<?php if ( ! empty( $pillars ) ) : ?>
		<div class="safak-why-grid">
			<?php foreach ( $pillars as $p ) : ?>
				<div class="safak-why-box">
					<div class="safak-why-line"></div>
					<div class="safak-why-num"><?php echo esc_html( $p['num'] ); ?></div>
					<h4 class="safak-why-title"><?php echo esc_html( $p['title'] ); ?></h4>
					<p class="safak-why-desc"><?php echo esc_html( $p['desc'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( count( $departments ) > 4 || ! empty( $infra ) ) : ?>
		<div class="shm-dept-pills-grid">
			<?php if ( ! empty( $departments ) ) : ?>
				<div class="shm-dept-pill-box">
					<h3 class="shm-dept-pill-heading">
						<?php echo shm_icon( 'stethoscope', 'shm-sub-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( shm_t( 'departments_list', $lang ) ); ?></span>
					</h3>
					<div class="shm-dept-tags">
						<?php foreach ( $departments as $dept ) : ?>
							<span class="shm-dept-tag">
								<span class="shm-dept-tag-dot"></span>
								<?php echo esc_html( $dept ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $infra ) ) : ?>
				<div class="shm-dept-pill-box">
					<h3 class="shm-dept-pill-heading">
						<?php echo shm_icon( 'microscope', 'shm-sub-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( shm_t( 'infrastructure_list', $lang ) ); ?></span>
					</h3>
					<div class="shm-dept-tags">
						<?php foreach ( $infra as $item ) : ?>
							<span class="shm-dept-tag shm-dept-tag--infra">
								<span class="shm-dept-tag-check">✓</span>
								<?php echo esc_html( $item ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
