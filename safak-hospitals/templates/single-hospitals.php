<?php
/**
 * Single Hospital Template for Safak Medical.
 *
 * Automatically loaded if the active theme does not have single-hospitals.php.
 * Styled to match the Safak Medical / Dribbble medical hospital aesthetic with .sv-hero.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

get_header();

$post_id     = get_the_ID();
$lang        = shm_post_lang( $post_id );
$is_rtl      = shm_is_rtl( $lang );
$dir         = $is_rtl ? 'rtl' : 'ltr';
$title       = get_the_title( $post_id );
$tagline     = trim( (string) shm_meta( $post_id, '_hospital_tagline' ) );
$city_line   = shm_get_location_line( $post_id, $lang, true );
$badges      = shm_get_badges( $post_id, $lang );
$wa_url      = shm_whatsapp_url( $post_id, $lang );
$consult_url = shm_consultation_url( true );

// Category & Established Year
$cats     = shm_terms( $post_id, SHM_TAX_CATEGORY );
$cat_name = ! empty( $cats ) ? $cats[0]->name : '';
$year     = shm_int( $post_id, '_hospital_established_year' );
$beds     = shm_int( $post_id, '_hospital_beds_count' );
$doctors  = shm_int( $post_id, '_hospital_doctors_count' );

// Dedicated image for the Split Story / Overview card (_hospital_overview_photo -> _hospital_main_photo -> featured photo -> fallback)
$split_img_url   = '';
$overview_img_id = absint( shm_meta( $post_id, '_hospital_overview_photo' ) );
if ( $overview_img_id ) {
	$split_img_url = wp_get_attachment_image_url( $overview_img_id, 'large' );
}
if ( ! $split_img_url ) {
	$main_img_id = absint( shm_meta( $post_id, '_hospital_main_photo' ) );
	if ( $main_img_id ) {
		$split_img_url = wp_get_attachment_image_url( $main_img_id, 'large' );
	}
}
if ( ! $split_img_url ) {
	$split_img_url = get_the_post_thumbnail_url( $post_id, 'large' );
}
if ( ! $split_img_url ) {
	$split_img_url = 'https://safakmedical.com/wp-content/uploads/2024/12/Snapinsta.app_469875971_18364790326141214_8384261363775878112_n_1080-1.webp';
}

// Floating stat card values on the 36px rounded image
if ( $beds > 0 ) {
	$float_num   = '+' . number_format_i18n( $beds );
	$float_label = shm_t( 'stat_beds', $lang ) . ( isset( $badges['jci'] ) ? ' (' . $badges['jci']['label'] . ')' : '' );
} elseif ( $doctors > 0 ) {
	$float_num   = '+' . number_format_i18n( $doctors );
	$float_label = shm_t( 'stat_doctors', $lang );
} else {
	$float_num   = 'JCI';
	$float_label = shm_t( 'jci_full', $lang );
}

// Content & lead excerpt
$raw_content = get_the_content();
$processed   = shm_process_content_toc( apply_filters( 'the_content', $raw_content ) );
$html        = $processed['html'];
$lead_text   = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : shm_description( $post_id, 42 );
?>

<main id="primary" class="shm-site-main shm-single-hospital <?php echo $is_rtl ? 'shm-rtl' : 'shm-ltr'; ?>" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>" data-hospital-id="<?php echo esc_attr( $post_id ); ?>">

	<?php
	// 1. HERO SECTION (.sv-hero)
	shm_template_part( 'hero', array( 'post_id' => $post_id, 'lang' => $lang ) );
	?>

	<!-- 2. PRESENTATION & KEY HOSPITAL STATS (.safak-dept-section) -->
	<section class="safak-dept-section" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
		<div class="safak-dept-container">

			<div class="safak-dept-header">
				<div class="safak-dept-title-col">
					<h6 class="safak-dept-eyebrow"><?php echo esc_html( $cat_name ? $cat_name : shm_t( 'pres_eyebrow', $lang ) ); ?></h6>
					<h2 class="safak-dept-title">
						<?php echo esc_html( $title ); ?><br>
						<em><?php echo esc_html( $city_line ? $city_line : shm_t( 'pres_title_em', $lang ) ); ?></em>
					</h2>

					<?php if ( ! empty( $badges ) || $year > 1800 ) : ?>
						<div class="shm-pres-badges">
							<?php foreach ( $badges as $b_key => $b ) : ?>
								<span class="shm-pres-badge shm-pres-badge--<?php echo esc_attr( $b_key ); ?>" title="<?php echo esc_attr( $b['full'] ); ?>">
									<?php echo shm_icon( $b['icon'], 'shm-badge-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span><?php echo esc_html( $b['label'] ); ?></span>
								</span>
							<?php endforeach; ?>
							<?php if ( $year > 1800 ) : ?>
								<span class="shm-pres-badge shm-pres-badge--year">
									<?php echo shm_icon( 'calendar', 'shm-badge-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span><?php echo esc_html( shm_t( 'since', $lang ) . ' ' . $year ); ?></span>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="safak-dept-desc-col">
					<?php if ( $lead_text ) : ?>
						<p class="safak-dept-lead"><?php echo esc_html( $lead_text ); ?></p>
					<?php endif; ?>
					<p class="safak-dept-sub">
						<?php echo esc_html( $tagline ? $tagline : shm_t( 'pres_sub_default', $lang ) ); ?>
					</p>
				</div>
			</div>

			<?php
			// Top-bordered Key Statistics Grid
			$stats = shm_get_stats( $post_id, 4, array(), $lang );
			if ( ! empty( $stats ) ) {
				shm_template_part(
					'stats',
					array(
						'post_id'    => $post_id,
						'stats'      => $stats,
						'cols'       => count( $stats ),
						'lang'       => $lang,
						'standalone' => false,
					)
				);
			}
			?>

		</div>
	</section>

	<!-- 3. SPLIT STORY, OVERVIEW & 36px ROUNDED IMAGE CARD (.safak-mod-hero) -->
	<section id="shm-overview" class="safak-mod-hero" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
		<div class="safak-mod-hero__container">

			<div class="safak-mod-hero__content">
				<h6 class="safak-mod-hero__eyebrow"><?php echo esc_html( shm_t( 'story_eyebrow', $lang ) ); ?></h6>
				<h2 class="safak-mod-hero__title"><?php echo esc_html( shm_t( 'story_title', $lang ) ); ?></h2>

				<div class="safak-mod-hero__desc shm-entry-content">
					<?php
					if ( ! empty( trim( $html ) ) ) {
						echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
					} else {
						echo '<p>' . esc_html( shm_t( 'pres_sub_default', $lang ) ) . '</p>';
					}
					?>
				</div>

				<div class="safak-mod-hero__actions">
					<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="safak-mod-hero__btn safak-mod-hero__btn--primary">
						<?php echo shm_icon( 'whatsapp', 'shm-btn-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( shm_t( 'book_whatsapp', $lang ) ); ?></span>
					</a>

					<a href="<?php echo esc_url( $consult_url ); ?>" class="safak-mod-hero__btn safak-mod-hero__btn--secondary">
						<span><?php echo esc_html( shm_t( 'free_consultation', $lang ) ); ?></span>
					</a>
				</div>
			</div>

			<div class="safak-mod-hero__media-col">
				<div class="safak-mod-hero__card">
					<img decoding="async" src="<?php echo esc_url( $split_img_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="safak-mod-hero__img">
					<div class="safak-mod-hero__stat-card">
						<div class="safak-mod-hero__stat-icon" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
						</div>
						<div class="safak-mod-hero__stat-number"><?php echo esc_html( $float_num ); ?></div>
						<div class="safak-mod-hero__stat-label"><?php echo esc_html( $float_label ); ?></div>
					</div>
				</div>
			</div>

		</div>
	</section>

	<?php
	// 4. PHOTO GALLERY BENTO GRID (with Interactive Lightbox)
	shm_template_part( 'gallery', array( 'post_id' => $post_id, 'lang' => $lang ) );

	// 5. DEPARTMENTS & INFRASTRUCTURE PILLARS (.safak-dept-wrapper & .safak-why-grid)
	shm_template_part( 'departments', array( 'post_id' => $post_id, 'lang' => $lang ) );

	// 6. VIRTUAL VIDEO TOUR
	shm_template_part( 'video', array( 'post_id' => $post_id, 'lang' => $lang ) );

	// 7. ASSOCIATED DOCTORS GRID
	shm_template_part( 'doctors', array( 'post_id' => $post_id, 'lang' => $lang ) );

	// 8. INTERNATIONAL PATIENT SERVICES & AMENITIES (.safak-patient-services-wrapper)
	shm_template_part( 'amenities', array( 'post_id' => $post_id, 'lang' => $lang ) );

	// 9. LOCATION & INTERACTIVE MAP
	shm_template_part( 'map', array( 'post_id' => $post_id, 'lang' => $lang ) );

	// 10. OTHER HOSPITALS IN TURKEY
	shm_template_part( 'related', array( 'post_id' => $post_id, 'lang' => $lang ) );
	?>

	<!-- 11. BOTTOM CONSULTATION CTA BANNER (.safak-cta-wrapper) -->
	<div class="safak-cta-wrapper" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
		<div class="safak-cta-card">
			<div class="safak-cta-content">
				<h6 class="safak-cta-tag"><?php echo esc_html( shm_t( 'cta_tag', $lang ) ); ?></h6>
				<div class="safak-cta-heading"><?php echo esc_html( shm_t( 'cta_heading', $lang ) ); ?></div>
				<p class="safak-cta-desc">
					<?php echo esc_html( shm_t( 'cta_desc', $lang ) ); ?>
				</p>
			</div>

			<div class="safak-cta-action">
				<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="safak-cta-btn">
					<span><?php echo esc_html( shm_t( 'cta_btn', $lang ) ); ?></span>
					<svg viewBox="0 0 24 24" aria-hidden="true">
						<line x1="19" y1="12" x2="5" y2="12"></line>
						<polyline points="12 19 5 12 12 5"></polyline>
					</svg>
				</a>
			</div>
		</div>
	</div>

</main>

<?php
get_footer();
