<?php
/**
 * Frontend loader, template switcher, scripts & styles.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * Frontend controller.
 */
class SHM_Frontend {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'unhook_legacy_hospital_loader' ), 1 );
		add_filter( 'template_include', array( __CLASS__, 'template_loader' ), 5 );
		add_filter( 'template_include', array( __CLASS__, 'template_loader' ), 999 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_classes' ) );
	}

	/**
	 * Ensure legacy unified snippet does not intercept single/archive hospital pages.
	 */
	public static function unhook_legacy_hospital_loader() {
		if ( is_singular( SHM_POST_TYPE ) || is_post_type_archive( SHM_POST_TYPE ) || is_tax( array( SHM_TAX_CATEGORY, SHM_TAX_CITY ) ) ) {
			remove_filter( 'template_include', 'safak_unified_template_loader', 20 );
		}
	}

	/**
	 * Template loader: use plugin templates for single hospital and archives
	 * if the active theme does not have single-hospitals.php / archive-hospitals.php.
	 *
	 * @param string $template Path to template.
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_singular( SHM_POST_TYPE ) ) {
			$theme_file = locate_template( array( 'single-hospitals.php', 'safak-hospitals/single-hospitals.php' ) );
			if ( $theme_file ) {
				return $theme_file;
			}
			return SHM_PATH . 'templates/single-hospitals.php';
		}

		if ( is_post_type_archive( SHM_POST_TYPE ) || is_tax( array( SHM_TAX_CATEGORY, SHM_TAX_CITY ) ) ) {
			$theme_file = locate_template( array( 'archive-hospitals.php', 'safak-hospitals/archive-hospitals.php' ) );
			if ( $theme_file ) {
				return $theme_file;
			}
			return SHM_PATH . 'templates/archive-hospitals.php';
		}

		return $template;
	}

	/**
	 * Enqueue frontend CSS and JavaScript.
	 */
	public static function enqueue_assets() {
		global $post;

		$is_hospital_single  = is_singular( SHM_POST_TYPE );
		$is_hospital_archive = is_post_type_archive( SHM_POST_TYPE ) || is_tax( array( SHM_TAX_CATEGORY, SHM_TAX_CITY ) );
		$has_shortcode       = is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'safak_hospitals' ) || has_shortcode( $post->post_content, 'safak_hospitals_grid' ) || has_shortcode( $post->post_content, 'safak_hospital_stats' ) || has_shortcode( $post->post_content, 'safak_hospital_gallery' ) );

		if ( ! $is_hospital_single && ! $is_hospital_archive && ! $has_shortcode ) {
			return;
		}

		$lang   = shm_lang();
		$is_rtl = shm_is_rtl( $lang );

		wp_enqueue_style(
			'shm-frontend',
			SHM_URL . 'assets/css/frontend.css',
			array(),
			SHM_VERSION
		);

		wp_enqueue_script(
			'shm-frontend',
			SHM_URL . 'assets/js/frontend.js',
			array(),
			SHM_VERSION,
			true
		);

		wp_localize_script(
			'shm-frontend',
			'shmFrontend',
			array(
				'lang'  => $lang,
				'isRtl' => $is_rtl,
				'i18n'  => array(
					'close' => shm_t( 'close', $lang ),
					'prev'  => shm_t( 'prev', $lang ),
					'next'  => shm_t( 'next', $lang ),
				),
			)
		);
	}

	/**
	 * Append custom body classes.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public static function body_classes( $classes ) {
		$lang = shm_lang();
		if ( is_singular( SHM_POST_TYPE ) || is_post_type_archive( SHM_POST_TYPE ) || is_tax( array( SHM_TAX_CATEGORY, SHM_TAX_CITY ) ) ) {
			$classes[] = 'shm-page';
			$classes[] = 'shm-lang-' . sanitize_html_class( $lang );
			$classes[] = shm_is_rtl( $lang ) ? 'shm-rtl' : 'shm-ltr';
		}
		return $classes;
	}
}

