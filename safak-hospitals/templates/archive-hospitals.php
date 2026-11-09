<?php
/**
 * Archive Hospital Template for Safak Medical.
 *
 * Automatically loaded if the active theme does not have archive-hospitals.php.
 * Uses the .sv-hero banner, filter pills, and modern Dribbble-inspired hospital cards.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lang   = shm_lang();
$is_rtl = shm_is_rtl( $lang );
$dir    = $is_rtl ? 'rtl' : 'ltr';

$term_title = '';
$term_desc  = '';
if ( is_tax() ) {
	$current_term = get_queried_object();
	if ( $current_term && ! is_wp_error( $current_term ) ) {
		$term_title = $current_term->name;
		$term_desc  = $current_term->description;
	}
}

$page_title = $term_title ? $term_title : shm_t( 'hospitals_in_turkey', $lang );
$page_desc  = $term_desc ? $term_desc : shm_t( 'hero_eyebrow_default', $lang );

$archive_features = array(
	shm_t( 'hero_trust_1', $lang ),
	shm_t( 'hero_trust_2', $lang ),
	shm_t( 'hero_trust_3', $lang ),
);
?>

<main id="primary" class="shm-site-main shm-archive-hospitals <?php echo $is_rtl ? 'shm-rtl' : 'shm-ltr'; ?>" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">

	<!-- 1. HERO SECTION (.sv-hero) -->
	<section class="sv-hero" id="sv-hero" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>"
	         style="--sv-bg: url('https://safakmedical.com/wp-content/uploads/2026/07/atakent-new-1-1.webp');">

	  <div class="sv-hero__inner">

	    <!-- Eyebrow Subtitle -->
	    <h6 class="sv-hero__eyebrow"><?php echo esc_html( $page_desc ); ?></h6>

	    <!-- Title -->
	    <h1 class="sv-hero__title"><?php echo esc_html( $page_title ); ?></h1>

	    <div class="sv-hero__row">

	      <!-- 3 Knowledge & Trust Badges -->
	      <ul class="sv-hero__features">
	        <?php foreach ( $archive_features as $feat ) : ?>
	          <li>
	            <span class="sv-hero__check" aria-hidden="true">
	              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
	            </span>
	            <?php echo esc_html( $feat ); ?>
	          </li>
	        <?php endforeach; ?>
	      </ul>

	    </div>
	  </div>
	</section>

	<div class="shm-container shm-archive-content">
		<!-- City & Category Filter Pills -->
		<?php
		$cities = get_terms( array( 'taxonomy' => SHM_TAX_CITY, 'hide_empty' => true ) );
		$cats   = get_terms( array( 'taxonomy' => SHM_TAX_CATEGORY, 'hide_empty' => true ) );
		if ( ( ! is_wp_error( $cities ) && ! empty( $cities ) ) || ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ) :
			?>
			<div class="shm-filter-bar">
				<a href="<?php echo esc_url( get_post_type_archive_link( SHM_POST_TYPE ) ); ?>" class="shm-filter-pill <?php echo ( ! is_tax() ) ? 'is-active' : ''; ?>">
					<?php echo esc_html( shm_t( 'hospitals', $lang ) ); ?>
				</a>
				<?php if ( ! is_wp_error( $cities ) ) : ?>
					<?php foreach ( $cities as $c ) : ?>
						<a href="<?php echo esc_url( get_term_link( $c ) ); ?>" class="shm-filter-pill <?php echo ( is_tax( SHM_TAX_CITY, $c->term_id ) ) ? 'is-active' : ''; ?>">
							<?php echo shm_icon( 'map-pin', 'shm-pill-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( $c->name ); ?></span>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php if ( ! is_wp_error( $cats ) ) : ?>
					<?php foreach ( $cats as $cat ) : ?>
						<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="shm-filter-pill <?php echo ( is_tax( SHM_TAX_CATEGORY, $cat->term_id ) ) ? 'is-active' : ''; ?>">
							<span><?php echo esc_html( $cat->name ); ?></span>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="shm-cards-grid shm-cards-grid--cols-3" style="--shm-cols: 3;">
				<?php
				while ( have_posts() ) :
					the_post();
					shm_template_part( 'card', array( 'hospital_id' => get_the_ID(), 'lang' => $lang ) );
				endwhile;
				?>
			</div>

			<div class="shm-pagination">
				<?php
				the_posts_pagination(
					array(
						'prev_text' => shm_is_rtl( $lang ) ? '→' : '←',
						'next_text' => shm_is_rtl( $lang ) ? '←' : '→',
					)
				);
				?>
			</div>
		<?php else : ?>
			<div class="shm-no-results-card">
				<p class="shm-no-results"><?php echo esc_html( shm_t( 'no_hospitals', $lang ) ); ?></p>
			</div>
		<?php endif; ?>
	</div>

</main>

<?php
get_footer();
