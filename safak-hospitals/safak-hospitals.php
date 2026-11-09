<?php
/**
 * Plugin Name:       Safak Hospitals Manager
 * Plugin URI:        https://safakmedical.com
 * Description:       Complete management system and frontend presentation for Safak Medical partner hospitals in Turkey: statistics, accreditations, associated doctors, galleries, automated Schema.org medical SEO, multilingual (Arabic / French / English) and Elementor-ready shortcodes.
 * Version:           1.2.6
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Safak Medical
 * Author URI:        https://safakmedical.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       safak-hospitals
 * Domain Path:       /languages
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

define( 'SHM_VERSION', '1.2.6' );
define( 'SHM_FILE', __FILE__ );
define( 'SHM_PATH', plugin_dir_path( __FILE__ ) );
define( 'SHM_URL', plugin_dir_url( __FILE__ ) );
define( 'SHM_POST_TYPE', 'hospitals' );
define( 'SHM_DOCTOR_POST_TYPE', 'doctors' );
define( 'SHM_TAX_CATEGORY', 'hospital_categories' );
define( 'SHM_TAX_CITY', 'hospital_cities' );
define( 'SHM_INQUIRY_POST_TYPE', 'shm_inquiry' );
define( 'SHM_DEFAULT_WHATSAPP', '+90 537 691 76 95' );

require_once SHM_PATH . 'includes/helpers.php';
require_once SHM_PATH . 'includes/class-shm-i18n.php';
require_once SHM_PATH . 'includes/class-shm-post-type.php';
require_once SHM_PATH . 'includes/class-shm-admin.php';
require_once SHM_PATH . 'includes/class-shm-settings.php';
require_once SHM_PATH . 'includes/class-shm-inquiries.php';
require_once SHM_PATH . 'includes/class-shm-frontend.php';
require_once SHM_PATH . 'includes/class-shm-seo.php';
require_once SHM_PATH . 'includes/class-shm-shortcodes.php';
require_once SHM_PATH . 'includes/class-shm-elementor.php';

/**
 * Boot every module.
 */
function shm_bootstrap() {
	load_plugin_textdomain( 'safak-hospitals', false, dirname( plugin_basename( SHM_FILE ) ) . '/languages' );

	SHM_I18n::init();
	SHM_Post_Type::init();
	SHM_Settings::init();
	SHM_Inquiries::init();
	SHM_Frontend::init();
	SHM_SEO::init();
	SHM_Shortcodes::init();
	SHM_Elementor::init();

	if ( is_admin() ) {
		SHM_Admin::init();
	}
}
add_action( 'plugins_loaded', 'shm_bootstrap' );

/**
 * Activation: register CPT/taxonomies, protect upload dir, flush permalinks.
 */
function shm_activate() {
	SHM_Post_Type::register_post_types();
	SHM_Post_Type::register_taxonomies();
	SHM_Inquiries::register_post_type();
	SHM_Inquiries::ensure_protected_dir();

	if ( false === get_option( SHM_Settings::OPTION ) ) {
		add_option( SHM_Settings::OPTION, SHM_Settings::defaults() );
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'shm_activate' );

/**
 * Deactivation: clean permalinks.
 */
function shm_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'shm_deactivate' );

/**
 * Automatically flush rewrite rules once on version change.
 */
function shm_maybe_flush_rewrites() {
	$flushed_version = get_option( 'shm_flushed_version' );
	if ( SHM_VERSION !== $flushed_version ) {
		flush_rewrite_rules( false );
		update_option( 'shm_flushed_version', SHM_VERSION );
	}
}
add_action( 'init', 'shm_maybe_flush_rewrites', 99 );





