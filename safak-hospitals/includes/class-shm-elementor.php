<?php
/**
 * Elementor compatibility: category and custom widgets.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration manager.
 */
class SHM_Elementor {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_categories' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
	}

	/**
	 * Register "Safak Medical" widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elements Manager.
	 */
	public static function register_categories( $elements_manager ) {
		$elements_manager->add_category(
			'safak-medical',
			array(
				'title' => __( 'Safak Medical', 'safak-hospitals' ),
				'icon'  => 'fa fa-hospital-o',
			)
		);
	}

	/**
	 * Register widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets Manager.
	 */
	public static function register_widgets( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once SHM_PATH . 'includes/elementor/class-widget-hospitals-grid.php';
		require_once SHM_PATH . 'includes/elementor/class-widget-hospital-stats.php';

		$widgets_manager->register( new SHM_Widget_Hospitals_Grid() );
		$widgets_manager->register( new SHM_Widget_Hospital_Stats() );
	}
}

