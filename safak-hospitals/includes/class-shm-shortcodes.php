<?php
/**
 * Shortcodes: [safak_hospitals] and [safak_hospital_stats].
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode Manager.
 */
class SHM_Shortcodes {

	/**
	 * Hooks.
	 */
	public static function init() {
		self::register_shortcodes();
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 999 );
	}

	/**
	 * Register all shortcodes.
	 */
	public static function register_shortcodes() {
		add_shortcode( 'safak_hospitals', array( __CLASS__, 'render_hospitals_grid' ) );
		add_shortcode( 'safak_hospitals_grid', array( __CLASS__, 'render_hospitals_grid' ) );
		add_shortcode( 'safak_hospital_stats', array( __CLASS__, 'render_hospital_stats' ) );
		add_shortcode( 'safak_hospital_gallery', array( __CLASS__, 'render_hospital_gallery' ) );
	}

	/**
	 * Render [safak_hospitals] grid.
	 *
	 * @param array $atts Attributes.
	 * @return string HTML output.
	 */
	public static function render_hospitals_grid( $atts ) {
		$atts = shortcode_atts(
			array(
				'hero'     => 'yes',
				'hero_bg'  => 'https://safakmedical.com/wp-content/uploads/2026/07/atakent-new-1-1.webp',
				'title'    => '',
				'eyebrow'  => '',
				'filters'  => 'no',
				'columns'  => '3',
				'limit'    => '-1',
				'city'     => '',
				'category' => '',
				'ids'      => '',
				'orderby'  => 'menu_order date',
				'order'    => 'ASC',
				'class'    => '',
			),
			$atts,
			'safak_hospitals'
		);

		$lang   = shm_lang();
		$is_rtl = shm_is_rtl( $lang );
		$dir    = $is_rtl ? 'rtl' : 'ltr';
		$cols   = max( 1, min( 4, (int) $atts['columns'] ) );

		// Ensure styles and scripts are enqueued even inside standalone pages/Elementor widgets.
		wp_enqueue_style( 'shm-frontend' );
		wp_enqueue_script( 'shm-frontend' );

		$args = array(
			'post_type'        => SHM_POST_TYPE,
			'post_status'      => 'publish',
			'posts_per_page'   => (int) $atts['limit'],
			'orderby'          => sanitize_text_field( $atts['orderby'] ),
			'order'            => 'DESC' === strtoupper( $atts['order'] ) ? 'DESC' : 'ASC',
			'suppress_filters' => false,
		);

		if ( function_exists( 'pll_current_language' ) ) {
			$args['lang'] = $lang;
		}

		if ( ! empty( $atts['ids'] ) ) {
			$ids = array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );
			if ( ! empty( $ids ) ) {
				$translated_ids = array();
				foreach ( $ids as $orig_id ) {
					$trans            = shm_translate_post_id( $orig_id, SHM_POST_TYPE, $lang );
					$translated_ids[] = $trans ? $trans : $orig_id;
				}
				$args['post__in'] = $translated_ids;
				$args['orderby']  = 'post__in';
			}
		}

		$tax_queries = array();

		if ( ! empty( $atts['city'] ) ) {
			$tax_queries[] = array(
				'taxonomy' => SHM_TAX_CITY,
				'field'    => 'slug',
				'terms'    => sanitize_title( $atts['city'] ),
			);
		}

		if ( ! empty( $atts['category'] ) ) {
			$tax_queries[] = array(
				'taxonomy' => SHM_TAX_CATEGORY,
				'field'    => 'slug',
				'terms'    => sanitize_title( $atts['category'] ),
			);
		}

		if ( ! empty( $tax_queries ) ) {
			if ( count( $tax_queries ) > 1 ) {
				$tax_queries['relation'] = 'AND';
			}
			$args['tax_query'] = $tax_queries;
		}

		$query = new WP_Query( $args );

		// Hero data
		$show_hero = ! in_array( strtolower( (string) $atts['hero'] ), array( 'no', 'false', '0', 'off' ), true );
		$hero_bg   = ! empty( $atts['hero_bg'] ) ? esc_url( $atts['hero_bg'] ) : 'https://safakmedical.com/wp-content/uploads/2026/07/atakent-new-1-1.webp';
		$hero_desc = ! empty( $atts['eyebrow'] ) ? $atts['eyebrow'] : shm_t( 'hero_eyebrow_default', $lang );
		$hero_ttl  = ! empty( $atts['title'] ) ? $atts['title'] : shm_t( 'hospitals_in_turkey', $lang );
		$features  = array(
			shm_t( 'hero_trust_1', $lang ),
			shm_t( 'hero_trust_2', $lang ),
			shm_t( 'hero_trust_3', $lang ),
		);

		ob_start();
		?>
		<div class="shm-shortcode-hospitals <?php echo $is_rtl ? 'shm-rtl' : 'shm-ltr'; ?> <?php echo esc_attr( $atts['class'] ); ?>" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
			<?php if ( $show_hero ) : ?>
				<!-- 1. HERO SECTION (.sv-hero) -->
				<section class="sv-hero" id="sv-hero" dir="<?php echo esc_attr( $dir ); ?>" lang="<?php echo esc_attr( $lang ); ?>"
				         style="--sv-bg: url('<?php echo esc_url( $hero_bg ); ?>');">

				  <div class="sv-hero__inner">

				    <!-- Eyebrow Subtitle -->
				    <h6 class="sv-hero__eyebrow"><?php echo esc_html( $hero_desc ); ?></h6>

				    <!-- Title -->
				    <h1 class="sv-hero__title"><?php echo esc_html( $hero_ttl ); ?></h1>

				    <div class="sv-hero__row">

				      <!-- 3 Knowledge & Trust Badges -->
				      <ul class="sv-hero__features">
				        <?php foreach ( $features as $feat ) : ?>
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
			<?php endif; ?>

			<div class="shm-container shm-archive-content">
				<?php if ( ! empty( $atts['filters'] ) && ! in_array( strtolower( (string) $atts['filters'] ), array( 'no', 'false', '0', 'off' ), true ) ) : ?>
					<?php
					$cities = get_terms( array( 'taxonomy' => SHM_TAX_CITY, 'hide_empty' => true ) );
					$cats   = get_terms( array( 'taxonomy' => SHM_TAX_CATEGORY, 'hide_empty' => true ) );
					if ( ( ! is_wp_error( $cities ) && ! empty( $cities ) ) || ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ) :
						?>
						<div class="shm-filter-bar">
							<a href="<?php echo esc_url( get_post_type_archive_link( SHM_POST_TYPE ) ); ?>" class="shm-filter-pill is-active">
								<?php echo esc_html( shm_t( 'hospitals', $lang ) ); ?>
							</a>
							<?php if ( ! is_wp_error( $cities ) ) : ?>
								<?php foreach ( $cities as $c ) : ?>
									<a href="<?php echo esc_url( get_term_link( $c ) ); ?>" class="shm-filter-pill">
										<?php echo shm_icon( 'map-pin', 'shm-pill-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span><?php echo esc_html( $c->name ); ?></span>
									</a>
								<?php endforeach; ?>
							<?php endif; ?>
							<?php if ( ! is_wp_error( $cats ) ) : ?>
								<?php foreach ( $cats as $cat ) : ?>
									<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="shm-filter-pill">
										<span><?php echo esc_html( $cat->name ); ?></span>
									</a>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( $query->have_posts() ) : ?>
					<div class="shm-cards-grid shm-cards-grid--cols-<?php echo esc_attr( $cols ); ?>" style="--shm-cols: <?php echo esc_attr( $cols ); ?>;">
						<?php
						while ( $query->have_posts() ) :
							$query->the_post();
							shm_template_part( 'card', array( 'hospital_id' => get_the_ID(), 'lang' => $lang ) );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				<?php else : ?>
					<div class="shm-no-results-card">
						<p class="shm-no-results"><?php echo esc_html( shm_t( 'no_hospitals', $lang ) ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render [safak_hospital_stats id="123"].
	 *
	 * @param array $atts Attributes.
	 * @return string HTML output.
	 */
	public static function render_hospital_stats( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'      => '0',
				'columns' => '4',
				'max'     => '6',
				'class'   => '',
			),
			$atts,
			'safak_hospital_stats'
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id || SHM_POST_TYPE !== get_post_type( $post_id ) ) {
			return '';
		}

		$lang = shm_post_lang( $post_id );
		$max  = absint( $atts['max'] );
		if ( ! $max ) {
			$max = 6;
		}

		$stats = shm_get_stats( $post_id, $max, array(), $lang );
		if ( empty( $stats ) ) {
			return '';
		}

		$cols = max( 2, min( 6, (int) $atts['columns'] ) );

		ob_start();
		?>
		<div class="shm-shortcode-stats <?php echo shm_is_rtl( $lang ) ? 'shm-rtl' : 'shm-ltr'; ?> <?php echo esc_attr( $atts['class'] ); ?>">
			<?php
			shm_template_part(
				'stats',
				array(
					'post_id'    => $post_id,
					'stats'      => $stats,
					'cols'       => $cols,
					'lang'       => $lang,
					'standalone' => true,
				)
			);
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render [safak_hospital_gallery id="123"].
	 *
	 * @param array $atts Attributes.
	 * @return string HTML output.
	 */
	public static function render_hospital_gallery( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => '0',
				'class' => '',
			),
			$atts,
			'safak_hospital_gallery'
		);

		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id ) {
			return '';
		}

		$lang = shm_post_lang( $post_id );

		ob_start();
		?>
		<div class="shm-shortcode-gallery <?php echo shm_is_rtl( $lang ) ? 'shm-rtl' : 'shm-ltr'; ?> <?php echo esc_attr( $atts['class'] ); ?>">
			<?php
			shm_template_part(
				'gallery',
				array(
					'post_id' => $post_id,
					'lang'    => $lang,
				)
			);
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

