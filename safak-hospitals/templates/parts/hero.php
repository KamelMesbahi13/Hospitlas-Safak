<?php
/**
 * Single Hospital: Hero Banner Part (.sv-hero).
 *
 * Uses the exact Safak Medical .sv-hero markup and styling.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

$post_id   = isset( $post_id ) ? (int) $post_id : get_the_ID();
$lang      = isset( $lang ) ? $lang : shm_post_lang( $post_id );
$is_rtl    = shm_is_rtl( $lang );
$dir       = $is_rtl ? 'rtl' : 'ltr';
$title     = get_the_title( $post_id );
$tagline   = trim( (string) shm_meta( $post_id, '_hospital_tagline' ) );
$city_line = shm_get_location_line( $post_id, $lang, true );

// Background image (Featured Image -> _hospital_main_photo -> fallback)
$thumb_url = get_the_post_thumbnail_url( $post_id, 'full' );
if ( ! $thumb_url ) {
	$main_img_id = absint( shm_meta( $post_id, '_hospital_main_photo' ) );
	if ( $main_img_id ) {
		$thumb_url = wp_get_attachment_image_url( $main_img_id, 'full' );
	}
}
if ( ! $thumb_url && function_exists( 'safak_get_hospital_image_url' ) ) {
	$thumb_url = safak_get_hospital_image_url( $post_id, 'full' );
}
if ( ! $thumb_url ) {
	$thumb_url = 'https://safakmedical.com/wp-content/uploads/2025/10/1-scaled.webp';
}

// Eyebrow subtitle
if ( '' !== $tagline ) {
	$eyebrow = $tagline;
} elseif ( '' !== $city_line ) {
	$eyebrow = shm_t( 'partner_badge', $lang ) . ' — ' . $city_line;
} else {
	$eyebrow = shm_t( 'hero_eyebrow_default', $lang );
}

// 3 Knowledge & Trust Badges
$badges   = shm_get_badges( $post_id, $lang );
$beds     = shm_int( $post_id, '_hospital_beds_count' );
$theatres = shm_int( $post_id, '_hospital_operating_theatres' );
$doctors  = shm_int( $post_id, '_hospital_doctors_count' );

// Feature 1: Accreditation
if ( isset( $badges['jci'] ) ) {
	$feat_1 = $badges['jci']['full'];
} elseif ( ! empty( $badges ) ) {
	$first_b = reset( $badges );
	$feat_1  = $first_b['full'];
} else {
	$feat_1 = shm_t( 'hero_trust_1', $lang );
}

// Feature 2: Capacity / Medical Staff
if ( $beds > 0 && $theatres > 0 ) {
	$feat_2 = '+' . number_format_i18n( $beds ) . ' ' . shm_t( 'stat_beds', $lang ) . ' • ' . number_format_i18n( $theatres ) . ' ' . shm_t( 'stat_theatres', $lang );
} elseif ( $beds > 0 ) {
	$feat_2 = '+' . number_format_i18n( $beds ) . ' ' . shm_t( 'stat_beds', $lang );
} elseif ( $doctors > 0 ) {
	$feat_2 = '+' . number_format_i18n( $doctors ) . ' ' . shm_t( 'stat_doctors', $lang );
} else {
	$feat_2 = shm_t( 'hero_trust_2', $lang );
}

// Feature 3: International Patient Support
$feat_3 = shm_t( 'hero_trust_3', $lang );

$features = array( $feat_1, $feat_2, $feat_3 );
?>

<section class="sv-hero" id="sv-hero" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>"
         style="--sv-bg: url('<?php echo esc_url( $thumb_url ); ?>');">

  <div class="sv-hero__inner">

    <!-- Eyebrow Subtitle -->
    <h6 class="sv-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></h6>

    <!-- Title -->
    <h1 class="sv-hero__title"><?php echo esc_html( $title ); ?></h1>

    <div class="sv-hero__row">

      <!-- 3 Knowledge & Trust Badges -->
      <ul class="sv-hero__features">
        <?php foreach ( $features as $feature_text ) : ?>
          <li>
            <span class="sv-hero__check" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            </span>
            <?php echo esc_html( $feature_text ); ?>
          </li>
        <?php endforeach; ?>
      </ul>

    </div>
  </div>
</section>
