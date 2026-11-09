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
		$cols   = max( 1, min( 4, (int) $atts['columns'] ) );

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
					$trans = shm_translate_post_id( $orig_id, SHM_POST_TYPE, $lang );
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

		ob_start();
		?>
		<div class="shm-shortcode-hospitals <?php echo $is_rtl ? 'shm-rtl' : 'shm-ltr'; ?> <?php echo esc_attr( $atts['class'] ); ?>">
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
				<p class="shm-no-results"><?php echo esc_html( shm_t( 'no_hospitals', $lang ) ); ?></p>
			<?php endif; ?>
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

