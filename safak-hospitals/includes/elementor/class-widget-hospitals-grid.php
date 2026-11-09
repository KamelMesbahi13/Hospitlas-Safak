<?php
/**
 * Elementor Widget: Hospitals Grid.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Hospitals Grid Elementor Widget.
 */
class SHM_Widget_Hospitals_Grid extends Widget_Base {

	public function get_name() {
		return 'shm_hospitals_grid';
	}

	public function get_title() {
		return __( 'Safak Hospitals Grid', 'safak-hospitals' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'safak-medical', 'general' );
	}

	public function get_keywords() {
		return array( 'safak', 'hospital', 'hospitals', 'medical', 'turkey', 'grid' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Hospital Grid Settings', 'safak-hospitals' ),
			)
		);

		$this->add_control(
			'show_hero',
			array(
				'label'        => __( 'Show Hero Section', 'safak-hospitals' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'safak-hospitals' ),
				'label_off'    => __( 'No', 'safak-hospitals' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'hero_bg',
			array(
				'label'       => __( 'Hero Background Image URL', 'safak-hospitals' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'https://safakmedical.com/wp-content/uploads/2026/07/atakent-new-1-1.webp',
				'condition'   => array( 'show_hero' => 'yes' ),
			)
		);

		$this->add_control(
			'hero_title',
			array(
				'label'       => __( 'Hero Title (optional)', 'safak-hospitals' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'condition'   => array( 'show_hero' => 'yes' ),
			)
		);

		$this->add_control(
			'hero_eyebrow',
			array(
				'label'       => __( 'Hero Eyebrow (optional)', 'safak-hospitals' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'condition'   => array( 'show_hero' => 'yes' ),
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => __( 'Columns', 'safak-hospitals' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '3',
				'options' => array(
					'1' => '1 Column',
					'2' => '2 Columns',
					'3' => '3 Columns',
					'4' => '4 Columns',
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'       => __( 'Number of Hospitals', 'safak-hospitals' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 6,
				'min'         => -1,
				'max'         => 100,
				'description' => __( 'Use -1 to display all hospitals.', 'safak-hospitals' ),
			)
		);

		// City options
		$city_options = array( '' => __( 'All Cities', 'safak-hospitals' ) );
		$cities       = get_terms( array( 'taxonomy' => SHM_TAX_CITY, 'hide_empty' => false ) );
		if ( ! is_wp_error( $cities ) && ! empty( $cities ) ) {
			foreach ( $cities as $c ) {
				$city_options[ $c->slug ] = $c->name;
			}
		}

		$this->add_control(
			'city',
			array(
				'label'   => __( 'Filter by City', 'safak-hospitals' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $city_options,
			)
		);

		// Category options
		$cat_options = array( '' => __( 'All Categories', 'safak-hospitals' ) );
		$categories  = get_terms( array( 'taxonomy' => SHM_TAX_CATEGORY, 'hide_empty' => false ) );
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			foreach ( $categories as $cat ) {
				$cat_options[ $cat->slug ] = $cat->name;
			}
		}

		$this->add_control(
			'category',
			array(
				'label'   => __( 'Filter by Category', 'safak-hospitals' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $cat_options,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'safak-hospitals' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'menu_order date',
				'options' => array(
					'menu_order date' => __( 'Menu Order & Date', 'safak-hospitals' ),
					'date'            => __( 'Publication Date', 'safak-hospitals' ),
					'title'           => __( 'Title / Name', 'safak-hospitals' ),
					'rand'            => __( 'Random', 'safak-hospitals' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'safak-hospitals' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'ASC',
				'options' => array(
					'ASC'  => __( 'Ascending (ASC)', 'safak-hospitals' ),
					'DESC' => __( 'Descending (DESC)', 'safak-hospitals' ),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo SHM_Shortcodes::render_hospitals_grid(
			array(
				'hero'     => ! empty( $settings['show_hero'] ) ? $settings['show_hero'] : 'no',
				'hero_bg'  => ! empty( $settings['hero_bg'] ) ? $settings['hero_bg'] : '',
				'title'    => ! empty( $settings['hero_title'] ) ? $settings['hero_title'] : '',
				'eyebrow'  => ! empty( $settings['hero_eyebrow'] ) ? $settings['hero_eyebrow'] : '',
				'columns'  => $settings['columns'],
				'limit'    => $settings['limit'],
				'city'     => $settings['city'],
				'category' => $settings['category'],
				'orderby'  => $settings['orderby'],
				'order'    => $settings['order'],
			)
		);
	}
}

