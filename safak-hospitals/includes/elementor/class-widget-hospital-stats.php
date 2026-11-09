<?php
/**
 * Elementor Widget: Hospital Stats Banner.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Hospital Stats Elementor Widget.
 */
class SHM_Widget_Hospital_Stats extends Widget_Base {

	public function get_name() {
		return 'shm_hospital_stats';
	}

	public function get_title() {
		return __( 'Safak Hospital Stats Strip', 'safak-hospitals' );
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	public function get_categories() {
		return array( 'safak-medical', 'general' );
	}

	public function get_keywords() {
		return array( 'safak', 'hospital', 'statistics', 'stats', 'counter', 'medical' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Hospital Statistics', 'safak-hospitals' ),
			)
		);

		// Hospital options
		$hosp_options = array( '0' => __( '— Current Hospital (Single Page) —', 'safak-hospitals' ) );
		$hospitals    = get_posts(
			array(
				'post_type'      => SHM_POST_TYPE,
				'posts_per_page' => 50,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		foreach ( $hospitals as $h ) {
			$hosp_options[ $h->ID ] = $h->post_title;
		}

		$this->add_control(
			'hospital_id',
			array(
				'label'   => __( 'Select Hospital', 'safak-hospitals' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '0',
				'options' => $hosp_options,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => __( 'Columns', 'safak-hospitals' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => array(
					'2' => '2 Columns',
					'3' => '3 Columns',
					'4' => '4 Columns',
					'6' => '6 Columns',
				),
			)
		);

		$this->add_control(
			'max',
			array(
				'label'   => __( 'Max Stats to Show', 'safak-hospitals' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 7,
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo SHM_Shortcodes::render_hospital_stats(
			array(
				'id'      => $settings['hospital_id'],
				'columns' => $settings['columns'],
				'max'     => $settings['max'],
			)
		);
	}
}

