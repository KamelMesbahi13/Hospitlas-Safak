<?php
/**
 * Shared helpers: language, settings, field definitions, icons, data getters.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/* ═══════════════════════════════════════════════════════════════
 * 1. LANGUAGE
 * ═══════════════════════════════════════════════════════════════ */

/**
 * Supported languages.
 *
 * @return string[]
 */
function shm_languages() {
	return array( 'ar', 'fr', 'en' );
}

/**
 * Normalise any language code/locale to ar|fr|en.
 *
 * @param mixed $lang Raw value.
 * @return string
 */
function shm_normalize_lang( $lang ) {
	$lang = strtolower( substr( (string) $lang, 0, 2 ) );
	return in_array( $lang, shm_languages(), true ) ? $lang : 'ar';
}

/**
 * Raw language detection (Polylang → WPML → URL prefix → Arabic).
 *
 * Mirrors the site-wide safak_unified_get_language() logic so both stay in sync.
 *
 * @return string
 */
function shm_detect_language() {
	$lang = '';

	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language();

		// Admin / AJAX / Elementor editor: fall back to the edited post language.
		if ( ! $lang && function_exists( 'pll_get_post_language' ) ) {
			$post_id = get_the_ID();
			if ( ! $post_id && isset( $_REQUEST['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$post_id = absint( $_REQUEST['post'] ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			if ( $post_id ) {
				$lang = pll_get_post_language( $post_id );
			}
		}
	} elseif ( defined( 'ICL_LANGUAGE_CODE' ) ) {
		$lang = ICL_LANGUAGE_CODE;
	}

	if ( ! $lang && isset( $_SERVER['REQUEST_URI'] ) ) {
		$path     = trim( (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' );
		$segments = explode( '/', $path );
		if ( isset( $segments[0] ) && in_array( strtolower( $segments[0] ), array( 'fr', 'en' ), true ) ) {
			$lang = strtolower( $segments[0] );
		}
	}

	return shm_normalize_lang( $lang ? $lang : 'ar' );
}

/**
 * Current language used by the plugin (ar|fr|en).
 *
 * Uses the site-wide safak_unified_get_language() helper when it exists so the
 * whole website shares one source of truth.
 *
 * @return string
 */
function shm_lang() {
	if ( function_exists( 'safak_unified_get_language' ) ) {
		$lang = shm_normalize_lang( safak_unified_get_language() );
	} else {
		$lang = shm_detect_language();
	}
	return shm_normalize_lang( apply_filters( 'shm_language', $lang ) );
}

/**
 * Language of a given post (falls back to current language).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function shm_post_lang( $post_id ) {
	$lang = '';
	if ( function_exists( 'pll_get_post_language' ) ) {
		$lang = pll_get_post_language( $post_id );
	} elseif ( has_filter( 'wpml_post_language_details' ) ) {
		$details = apply_filters( 'wpml_post_language_details', null, $post_id );
		if ( is_array( $details ) && ! empty( $details['language_code'] ) ) {
			$lang = $details['language_code'];
		}
	}
	return $lang ? shm_normalize_lang( $lang ) : shm_lang();
}

/**
 * Is the language right-to-left?
 *
 * @param string $lang Language.
 * @return bool
 */
function shm_is_rtl( $lang ) {
	return 'ar' === $lang;
}

/**
 * Translate an object ID to the current (or given) language with Polylang/WPML.
 *
 * @param int    $id        Post ID.
 * @param string $post_type Post type.
 * @param string $lang      Target language.
 * @return int 0 when no translation exists and a multilingual plugin is active.
 */
function shm_translate_post_id( $id, $post_type, $lang = '' ) {
	$id   = absint( $id );
	$lang = $lang ? $lang : shm_lang();

	if ( ! $id ) {
		return 0;
	}

	if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_is_translated_post_type' ) && pll_is_translated_post_type( $post_type ) ) {
		$translated = pll_get_post( $id, $lang );
		return $translated ? (int) $translated : 0;
	}

	if ( has_filter( 'wpml_object_id' ) ) {
		$translated = apply_filters( 'wpml_object_id', $id, $post_type, false, $lang );
		return $translated ? (int) $translated : 0;
	}

	return $id;
}

/**
 * Late fallback: expose safak_unified_get_language() for other site code when no
 * snippet/theme defines it. Declared on after_setup_theme (after Code Snippets
 * and functions.php ran) so it can never cause a "cannot redeclare" fatal error.
 */
add_action(
	'after_setup_theme',
	function () {
		if ( ! function_exists( 'safak_unified_get_language' ) ) {
			/**
			 * Site-wide language helper (fallback provided by Safak Hospitals Manager).
			 *
			 * @return string ar|fr|en
			 */
			function safak_unified_get_language() {
				return shm_detect_language();
			}
		}
	},
	999
);

/* ═══════════════════════════════════════════════════════════════
 * 2. TRANSLATED STRINGS & SETTINGS
 * ═══════════════════════════════════════════════════════════════ */

/**
 * Get a translated front-end string.
 *
 * @param string $key  String key.
 * @param string $lang Optional language.
 * @return string Raw (unescaped) string.
 */
function shm_t( $key, $lang = '' ) {
	return SHM_I18n::get( $key, $lang );
}

/**
 * Get one plugin setting.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function shm_setting( $key ) {
	return SHM_Settings::get( $key );
}

/* ═══════════════════════════════════════════════════════════════
 * 3. FIELD DEFINITIONS (single source of truth for admin, front & schema)
 * ═══════════════════════════════════════════════════════════════ */

/**
 * Statistic definitions, in display priority order.
 *
 * @return array
 */
function shm_stat_definitions() {
	return apply_filters(
		'shm_stat_definitions',
		array(
			'beds'       => array( 'meta' => '_hospital_beds_count', 'icon' => 'bed', 'label' => 'stat_beds', 'suffix' => '' ),
			'rooms'      => array( 'meta' => '_hospital_rooms_count', 'icon' => 'door', 'label' => 'stat_rooms', 'suffix' => '' ),
			'icu'        => array( 'meta' => '_hospital_icu_beds', 'icon' => 'heart-pulse', 'label' => 'stat_icu', 'suffix' => '' ),
			'theatres'   => array( 'meta' => '_hospital_operating_theatres', 'icon' => 'surgery-light', 'label' => 'stat_theatres', 'suffix' => '' ),
			'doctors'    => array( 'meta' => '_hospital_doctors_count', 'icon' => 'stethoscope', 'label' => 'stat_doctors', 'suffix' => '+' ),
			'experience' => array( 'meta' => '_hospital_established_year', 'icon' => 'award', 'label' => 'stat_experience', 'suffix' => '+' ),
			'surgeries'  => array( 'meta' => '_hospital_annual_surgeries', 'icon' => 'activity', 'label' => 'stat_surgeries', 'suffix' => '+' ),
		)
	);
}

/**
 * Amenity definitions.
 *
 * @return array key => icon
 */
function shm_amenity_definitions() {
	return apply_filters(
		'shm_amenity_definitions',
		array(
			'airport_transfer' => array( 'icon' => 'plane', 'admin' => __( 'Free Airport VIP Transfer', 'safak-hospitals' ) ),
			'interpreters'     => array( 'icon' => 'languages', 'admin' => __( 'Multilingual Medical Interpreters (Arabic, French, English)', 'safak-hospitals' ) ),
			'private_suites'   => array( 'icon' => 'bed-double', 'admin' => __( 'Private Patient Suites with Companion Beds', 'safak-hospitals' ) ),
			'halal_food'       => array( 'icon' => 'utensils', 'admin' => __( 'Halal Food & Room Service Options', 'safak-hospitals' ) ),
			'intl_desk'        => array( 'icon' => 'clock', 'admin' => __( '24/7 International Patient Desk', 'safak-hospitals' ) ),
			'wifi_sim'         => array( 'icon' => 'wifi', 'admin' => __( 'Free Wi-Fi & SIM Card Assistance', 'safak-hospitals' ) ),
		)
	);
}

/**
 * Admin meta-box field groups (tabs).
 *
 * @return array
 */
function shm_field_groups() {
	$groups = array(
		'general'       => array(
			'label'  => __( 'General & Photo Gallery', 'safak-hospitals' ),
			'icon'   => 'dashicons-format-gallery',
			'fields' => array(
				'_hospital_main_photo'     => array(
					'type'  => 'image',
					'label' => __( 'Hospital Main / Hero Photo (صورة الهيرو والبطاقة الرئيسية)', 'safak-hospitals' ),
					'wide'  => true,
					'desc'  => __( '★ Ideal Dimensions: 1920 × 900 px (or 1920 × 1080 px — 16:9 or 21:9 ratio). Recommended format: WebP or JPG, under 400 KB. Used as the main hero banner background, archive cards, and social share previews.', 'safak-hospitals' ),
				),
				'_hospital_overview_photo' => array(
					'type'  => 'image',
					'label' => __( 'Overview Section Photo (صورة قسم نبذة عن المستشفى)', 'safak-hospitals' ),
					'wide'  => true,
					'desc'  => __( '★ Ideal Dimensions: 1200 × 1000 px (approx 6:5 or 4:3 ratio). Recommended format: WebP or JPG, under 350 KB. Displays in the 36px rounded overview card alongside the hospital overview text and floating stats badge.', 'safak-hospitals' ),
				),
				'_hospital_gallery'        => array(
					'type'  => 'gallery',
					'label' => __( 'Hospital Facilities Gallery (معرض مرافق وأقسام المستشفى)', 'safak-hospitals' ),
					'wide'  => true,
					'desc'  => __( '★ Ideal Dimensions: 1400 × 900 px (or 1200 × 800 px — 3:2 ratio). Recommended format: WebP or JPG, under 300 KB each. Used in the showcase gallery and zoomed full-screen lightbox.', 'safak-hospitals' ),
				),
				'_hospital_gallery_urls'   => array(
					'type'        => 'lines',
					'label'       => __( 'Additional Gallery Image URLs (Optional — one image URL per line)', 'safak-hospitals' ),
					'placeholder' => "https://safakmedical.com/wp-content/uploads/2024/11/acibadem.webp\nhttps://safakmedical.com/wp-content/uploads/2024/11/memorial.webp",
					'wide'        => true,
					'desc'        => __( 'Paste direct image URLs here (one per line). Ideal Dimensions: 1400 × 900 px (or 1200 × 800 px). Supports optional "URL | Caption" format.', 'safak-hospitals' ),
				),
				'_hospital_tagline'        => array(
					'type'        => 'text',
					'label'       => __( 'Short Tagline / Subtitle', 'safak-hospitals' ),
					'placeholder' => __( 'e.g. Premier JCI-Accredited Medical Center in Istanbul', 'safak-hospitals' ),
					'wide'        => true,
				),
				'_hospital_featured'       => array(
					'type'  => 'checkbox',
					'label' => __( 'Mark as Featured Partner Hospital', 'safak-hospitals' ),
				),
			),
		),
		'stats'         => array(
			'label'  => __( 'Statistics & Capacity', 'safak-hospitals' ),
			'icon'   => 'dashicons-chart-bar',
			'fields' => array(
				'_hospital_beds_count'         => array( 'type' => 'number', 'label' => __( 'Patient beds', 'safak-hospitals' ), 'placeholder' => '250' ),
				'_hospital_rooms_count'        => array( 'type' => 'number', 'label' => __( 'Private rooms / suites', 'safak-hospitals' ), 'placeholder' => '180' ),
				'_hospital_operating_theatres' => array( 'type' => 'number', 'label' => __( 'Operating theatres', 'safak-hospitals' ), 'placeholder' => '14' ),
				'_hospital_icu_beds'           => array( 'type' => 'number', 'label' => __( 'ICU beds', 'safak-hospitals' ), 'placeholder' => '45' ),
				'_hospital_doctors_count'      => array( 'type' => 'number', 'label' => __( 'Doctors / medical staff', 'safak-hospitals' ), 'placeholder' => '300' ),
				'_hospital_annual_surgeries'   => array( 'type' => 'number', 'label' => __( 'Annual surgical procedures', 'safak-hospitals' ), 'placeholder' => '12000' ),
				'_hospital_established_year'   => array( 'type' => 'year', 'label' => __( 'Year established', 'safak-hospitals' ), 'placeholder' => '2004' ),
			),
		),
		'accreditation' => array(
			'label'  => __( 'Accreditations', 'safak-hospitals' ),
			'icon'   => 'dashicons-awards',
			'fields' => array(
				'_hospital_is_jci_accredited'   => array( 'type' => 'checkbox', 'label' => __( 'JCI Accredited (Joint Commission International)', 'safak-hospitals' ) ),
				'_hospital_iso_cert'            => array( 'type' => 'text', 'label' => __( 'ISO certification', 'safak-hospitals' ), 'placeholder' => 'ISO 9001:2015', 'desc' => __( 'Leave empty if the hospital has no ISO certificate.', 'safak-hospitals' ) ),
				'_hospital_moh_excellence'      => array( 'type' => 'checkbox', 'label' => __( 'Ministry of Health Excellence badge', 'safak-hospitals' ) ),
				'_hospital_intl_patient_center' => array( 'type' => 'checkbox', 'label' => __( 'International Patient Center badge', 'safak-hospitals' ) ),
			),
		),
		'location'      => array(
			'label'  => __( 'Location & Contact', 'safak-hospitals' ),
			'icon'   => 'dashicons-location',
			'fields' => array(
				'_hospital_address'          => array( 'type' => 'text', 'label' => __( 'Full street address', 'safak-hospitals' ), 'placeholder' => 'Merkez Mah. Hastane Sok. No:1', 'wide' => true ),
				'_hospital_district'         => array( 'type' => 'text', 'label' => __( 'District', 'safak-hospitals' ), 'placeholder' => 'Bahçelievler' ),
				'_hospital_city'             => array( 'type' => 'text', 'label' => __( 'City (display label)', 'safak-hospitals' ), 'placeholder' => 'Istanbul', 'desc' => __( 'Falls back to the "Hospital City" taxonomy term when empty.', 'safak-hospitals' ) ),
				'_hospital_lat'              => array( 'type' => 'coordinate', 'label' => __( 'Latitude', 'safak-hospitals' ), 'placeholder' => '41.0035' ),
				'_hospital_lng'              => array( 'type' => 'coordinate', 'label' => __( 'Longitude', 'safak-hospitals' ), 'placeholder' => '28.8616' ),
				'_hospital_map_embed'        => array( 'type' => 'map', 'label' => __( 'Google Maps embed URL', 'safak-hospitals' ), 'placeholder' => 'https://www.google.com/maps/embed?pb=…', 'wide' => true, 'desc' => __( 'Paste the embed URL or the full <iframe> code from Google Maps → Share → Embed a map. Optional when coordinates or an address are filled.', 'safak-hospitals' ) ),
				'_hospital_whatsapp'         => array( 'type' => 'phone', 'label' => __( 'WhatsApp booking number', 'safak-hospitals' ), 'placeholder' => SHM_DEFAULT_WHATSAPP, 'desc' => __( 'Leave empty to use the global number from Settings.', 'safak-hospitals' ) ),
				'_hospital_whatsapp_message' => array( 'type' => 'textarea', 'label' => __( 'Pre-filled WhatsApp message', 'safak-hospitals' ), 'placeholder' => '', 'wide' => true, 'desc' => __( 'Written in this translation\'s language. Use {hospital} for the hospital name. Leave empty for the automatic localized message.', 'safak-hospitals' ) ),
			),
		),
		'media'         => array(
			'label'  => __( 'Video Tour', 'safak-hospitals' ),
			'icon'   => 'dashicons-video-alt3',
			'fields' => array(
				'_hospital_video_url' => array( 'type' => 'url', 'label' => __( 'Video tour URL (YouTube, Vimeo or MP4)', 'safak-hospitals' ), 'placeholder' => 'https://www.youtube.com/watch?v=…', 'wide' => true ),
			),
		),
		'doctors'       => array(
			'label'  => __( 'Associated Doctors', 'safak-hospitals' ),
			'icon'   => 'dashicons-groups',
			'fields' => array(
				'_hospital_doctors' => array( 'type' => 'doctors', 'label' => __( 'Doctors practicing at this hospital', 'safak-hospitals' ), 'wide' => true ),
			),
		),
		'amenities'     => array(
			'label'  => __( 'Services & Amenities', 'safak-hospitals' ),
			'icon'   => 'dashicons-yes-alt',
			'fields' => array(
				'_hospital_amenities' => array( 'type' => 'amenities', 'label' => __( 'Available patient services', 'safak-hospitals' ), 'wide' => true ),
			),
		),
		'departments'   => array(
			'label'  => __( 'Departments & Infrastructure', 'safak-hospitals' ),
			'icon'   => 'dashicons-building',
			'fields' => array(
				'_hospital_departments'    => array( 'type' => 'lines', 'label' => __( 'Medical departments (one per line)', 'safak-hospitals' ), 'placeholder' => "Cardiology\nOncology\nOrthopedics", 'wide' => true ),
				'_hospital_infrastructure' => array( 'type' => 'lines', 'label' => __( 'Equipment & infrastructure highlights (one per line)', 'safak-hospitals' ), 'placeholder' => "Da Vinci robotic surgery system\n3 Tesla MRI\nPET-CT", 'wide' => true ),
			),
		),
	);

	return apply_filters( 'shm_field_groups', $groups );
}

/**
 * Flat list of all meta fields.
 *
 * @return array meta_key => definition
 */
function shm_all_fields() {
	$fields = array();
	foreach ( shm_field_groups() as $group ) {
		foreach ( $group['fields'] as $key => $def ) {
			$fields[ $key ] = $def;
		}
	}
	return $fields;
}

/* ═══════════════════════════════════════════════════════════════
 * 4. DATA GETTERS
 * ═══════════════════════════════════════════════════════════════ */

/**
 * Get a meta value.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param mixed  $default Default.
 * @return mixed
 */
function shm_meta( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, $key, true );
	return ( '' === $value || null === $value || false === $value || array() === $value ) ? $default : $value;
}

/**
 * Get an integer meta value (0 when empty).
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return int
 */
function shm_int( $post_id, $key ) {
	return absint( shm_meta( $post_id, $key, 0 ) );
}

/**
 * Get list meta stored as array of IDs.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return int[]
 */
function shm_id_list( $post_id, $key ) {
	$value = shm_meta( $post_id, $key, array() );
	if ( is_string( $value ) ) {
		$value = explode( ',', $value );
	}
	return array_values( array_filter( array_map( 'absint', (array) $value ) ) );
}

/**
 * Get textarea-lines meta as an array.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return string[]
 */
function shm_lines( $post_id, $key ) {
	$raw = (string) shm_meta( $post_id, $key, '' );
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) ) );
}

/**
 * Collect the statistics to show, honoring a max count.
 *
 * @param int   $post_id Hospital ID.
 * @param int   $max     Maximum number of stats.
 * @param array $only    Restrict to keys.
 * @param string $lang   Language.
 * @return array
 */
function shm_get_stats( $post_id, $max = 6, $only = array(), $lang = '' ) {
	$stats = array();
	$lang  = $lang ? $lang : shm_post_lang( $post_id );

	foreach ( shm_stat_definitions() as $key => $def ) {
		if ( $only && ! in_array( $key, $only, true ) ) {
			continue;
		}
		$value = shm_int( $post_id, $def['meta'] );
		if ( 'experience' === $key ) {
			$year  = $value;
			$value = ( $year > 1800 ) ? max( 1, (int) wp_date( 'Y' ) - $year ) : 0;
		}
		if ( $value <= 0 ) {
			continue;
		}
		$stats[ $key ] = array(
			'value'  => $value,
			'label'  => shm_t( $def['label'], $lang ),
			'icon'   => $def['icon'],
			'suffix' => $def['suffix'],
		);
	}

	if ( $only ) {
		$ordered = array();
		foreach ( $only as $key ) {
			if ( isset( $stats[ $key ] ) ) {
				$ordered[ $key ] = $stats[ $key ];
			}
		}
		$stats = $ordered;
	}

	return array_slice( $stats, 0, max( 1, (int) $max ), true );
}

/**
 * Accreditation badges for a hospital.
 *
 * @param int    $post_id Hospital ID.
 * @param string $lang    Language.
 * @return array[] key => [label, full, icon]
 */
function shm_get_badges( $post_id, $lang = '' ) {
	$lang   = $lang ? $lang : shm_post_lang( $post_id );
	$badges = array();

	if ( shm_meta( $post_id, '_hospital_is_jci_accredited' ) ) {
		$badges['jci'] = array( 'label' => shm_t( 'jci', $lang ), 'full' => shm_t( 'jci_full', $lang ), 'icon' => 'shield-check' );
	}
	$iso = trim( (string) shm_meta( $post_id, '_hospital_iso_cert' ) );
	if ( '' !== $iso ) {
		$badges['iso'] = array( 'label' => $iso, 'full' => $iso, 'icon' => 'badge-check' );
	}
	if ( shm_meta( $post_id, '_hospital_moh_excellence' ) ) {
		$badges['moh'] = array( 'label' => shm_t( 'moh', $lang ), 'full' => shm_t( 'moh_full', $lang ), 'icon' => 'star' );
	}
	if ( shm_meta( $post_id, '_hospital_intl_patient_center' ) ) {
		$badges['intl'] = array( 'label' => shm_t( 'intl_center', $lang ), 'full' => shm_t( 'intl_center', $lang ), 'icon' => 'globe' );
	}

	return apply_filters( 'shm_badges', $badges, $post_id, $lang );
}

/**
 * Terms of a taxonomy for a post (empty array on failure).
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy.
 * @return WP_Term[]
 */
function shm_terms( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms : array();
}

/**
 * City label (meta → taxonomy term).
 *
 * @param int $post_id Hospital ID.
 * @return string
 */
function shm_get_city( $post_id ) {
	$city = trim( (string) shm_meta( $post_id, '_hospital_city' ) );
	if ( '' === $city ) {
		$terms = shm_terms( $post_id, SHM_TAX_CITY );
		$city  = $terms ? $terms[0]->name : '';
	}
	return $city;
}

/**
 * Human location line: "District, City, Turkey".
 *
 * @param int    $post_id      Hospital ID.
 * @param string $lang         Language.
 * @param bool   $with_country Append country.
 * @return string
 */
function shm_get_location_line( $post_id, $lang = '', $with_country = true ) {
	$lang  = $lang ? $lang : shm_post_lang( $post_id );
	$parts = array_filter( array( trim( (string) shm_meta( $post_id, '_hospital_district' ) ), shm_get_city( $post_id ) ) );
	if ( $with_country ) {
		$parts[] = shm_t( 'turkey', $lang );
	}
	$sep = ( 'ar' === $lang ) ? '، ' : ', ';
	return implode( $sep, $parts );
}

/**
 * WhatsApp number digits only (international format without "+").
 *
 * @param int $post_id Hospital ID (0 = global).
 * @return string
 */
function shm_whatsapp_digits( $post_id = 0 ) {
	$number = $post_id ? (string) shm_meta( $post_id, '_hospital_whatsapp' ) : '';
	if ( '' === trim( $number ) ) {
		$number = (string) shm_setting( 'whatsapp_number' );
	}
	if ( '' === trim( $number ) ) {
		$number = SHM_DEFAULT_WHATSAPP;
	}
	$digits = preg_replace( '/\D+/', '', $number );
	if ( 0 === strpos( $digits, '00' ) ) {
		$digits = substr( $digits, 2 );
	}
	return $digits;
}

/**
 * E.164 phone (+905376917695) for schema/tel links.
 *
 * @param int $post_id Hospital ID.
 * @return string
 */
function shm_phone_e164( $post_id = 0 ) {
	return '+' . shm_whatsapp_digits( $post_id );
}

/**
 * Pretty phone for display.
 *
 * @param int $post_id Hospital ID.
 * @return string
 */
function shm_phone_display( $post_id = 0 ) {
	$number = $post_id ? trim( (string) shm_meta( $post_id, '_hospital_whatsapp' ) ) : '';
	if ( '' === $number ) {
		$number = trim( (string) shm_setting( 'whatsapp_number' ) );
	}
	return '' !== $number ? $number : SHM_DEFAULT_WHATSAPP;
}

/**
 * WhatsApp deep link with localized pre-filled message.
 *
 * @param int    $post_id Hospital ID.
 * @param string $lang    Language.
 * @return string
 */
function shm_whatsapp_url( $post_id = 0, $lang = '' ) {
	$lang    = $lang ? $lang : ( $post_id ? shm_post_lang( $post_id ) : shm_lang() );
	$message = $post_id ? trim( (string) shm_meta( $post_id, '_hospital_whatsapp_message' ) ) : '';
	if ( '' === $message ) {
		$message = shm_t( 'whatsapp_message', $lang );
	}
	$name    = $post_id ? wp_strip_all_tags( get_the_title( $post_id ) ) : '';
	$message = str_replace( '{hospital}', $name, $message );
	$message = apply_filters( 'shm_whatsapp_message', $message, $post_id, $lang );

	return 'https://wa.me/' . shm_whatsapp_digits( $post_id ) . '?text=' . rawurlencode( $message );
}

/**
 * Consultation CTA URL (settings page URL or in-page inquiry form).
 *
 * @param bool $on_single Whether we are on a single hospital page with the form.
 * @return string
 */
function shm_consultation_url( $on_single = true ) {
	$url = trim( (string) shm_setting( 'consultation_url' ) );
	if ( '' === $url ) {
		$lang = shm_lang();
		if ( 'en' === $lang ) {
			$url = home_url( '/en/contact/' );
		} elseif ( 'fr' === $lang ) {
			$url = home_url( '/fr/contact/' );
		} else {
			$url = home_url( '/contact/' );
		}
	}
	return apply_filters( 'shm_consultation_url', $url, $on_single );
}

/**
 * Associated doctors (published, translated to the hospital language).
 *
 * @param int    $post_id Hospital ID.
 * @param string $lang    Language.
 * @return WP_Post[]
 */
function shm_get_doctors( $post_id, $lang = '' ) {
	$lang = $lang ? $lang : shm_post_lang( $post_id );
	$ids  = array();

	foreach ( shm_id_list( $post_id, '_hospital_doctors' ) as $doctor_id ) {
		$translated = shm_translate_post_id( $doctor_id, SHM_DOCTOR_POST_TYPE, $lang );
		$ids[]      = $translated ? $translated : $doctor_id;
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );

	if ( ! $ids ) {
		return array();
	}

	$doctors = get_posts(
		array(
			'post_type'        => SHM_DOCTOR_POST_TYPE,
			'post__in'         => $ids,
			'orderby'          => 'post__in',
			'posts_per_page'   => -1,
			'post_status'      => array( 'publish', 'private' ),
			'lang'             => '', // Polylang: do not filter, IDs are already translated.
			'suppress_filters' => true,
			'no_found_rows'    => true,
		)
	);

	return apply_filters( 'shm_hospital_doctors', $doctors, $post_id, $lang );
}

/**
 * Doctor specialty label (same taxonomies as the doctor profile template).
 *
 * @param int $doctor_id Doctor ID.
 * @return string
 */
function shm_get_doctor_specialty( $doctor_id ) {
	$taxonomies = apply_filters( 'shm_doctor_specialty_taxonomies', array( 'doctors-categories', 'doctors_category', 'doctor-specialties', 'doctor_specialties' ) );
	foreach ( $taxonomies as $tax ) {
		if ( taxonomy_exists( $tax ) ) {
			$terms = shm_terms( $doctor_id, $tax );
			if ( $terms ) {
				return $terms[0]->name;
			}
		}
	}
	foreach ( array( 'specialty', 'speciality', 'doctor_specialty', '_specialty' ) as $key ) {
		$value = get_post_meta( $doctor_id, $key, true );
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return trim( $value );
		}
	}
	return '';
}

/**
 * Get the associated hospital post for a doctor.
 *
 * @param int    $doctor_id Doctor ID.
 * @param string $lang      Target language (optional).
 * @return WP_Post|null
 */
function shm_get_doctor_hospital( $doctor_id, $lang = '' ) {
	$doctor_id = absint( $doctor_id );
	if ( ! $doctor_id ) {
		return null;
	}

	// 1. Direct saved meta
	$hospital_id = absint( get_post_meta( $doctor_id, '_hospital_id', true ) );

	// 2. Fallback query: search hospital having this doctor in _hospital_doctors
	if ( ! $hospital_id ) {
		$hospitals = get_posts(
			array(
				'post_type'      => SHM_POST_TYPE,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'     => '_hospital_doctors',
						'value'   => '"' . $doctor_id . '"',
						'compare' => 'LIKE',
					),
				),
				'suppress_filters' => false,
			)
		);
		if ( ! empty( $hospitals ) ) {
			$hospital_id = $hospitals[0]->ID;
		}
	}

	if ( ! $hospital_id ) {
		return null;
	}

	$lang = $lang ? $lang : shm_lang();
	$translated_id = shm_translate_post_id( $hospital_id, SHM_POST_TYPE, $lang );
	$final_id      = $translated_id ? $translated_id : $hospital_id;

	return get_post( $final_id );
}

/**
 * Related hospitals: same city → same category → any.
 *
 * @param int    $post_id Hospital ID.
 * @param int    $limit   Number of hospitals.
 * @param string $lang    Language.
 * @return WP_Post[]
 */
function shm_get_related_hospitals( $post_id, $limit = 3, $lang = '' ) {
	$lang    = $lang ? $lang : shm_post_lang( $post_id );
	$exclude = array( $post_id );
	$found   = array();

	$base = array(
		'post_type'        => SHM_POST_TYPE,
		'post_status'      => 'publish',
		'orderby'          => 'menu_order date',
		'order'            => 'ASC',
		'no_found_rows'    => true,
		'suppress_filters' => false,
	);
	if ( function_exists( 'pll_current_language' ) ) {
		$base['lang'] = $lang;
	}

	foreach ( array( SHM_TAX_CITY, SHM_TAX_CATEGORY, '' ) as $tax ) {
		$need = $limit - count( $found );
		if ( $need <= 0 ) {
			break;
		}
		$args                   = $base;
		$args['posts_per_page'] = $need;
		$args['post__not_in']   = $exclude;

		if ( $tax ) {
			$term_ids = wp_list_pluck( shm_terms( $post_id, $tax ), 'term_id' );
			if ( ! $term_ids ) {
				continue;
			}
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => $tax,
					'field'    => 'term_id',
					'terms'    => $term_ids,
				),
			);
		}

		foreach ( get_posts( $args ) as $p ) {
			$found[]   = $p;
			$exclude[] = $p->ID;
		}
	}

	return apply_filters( 'shm_related_hospitals', $found, $post_id, $lang );
}

/* ═══════════════════════════════════════════════════════════════
 * 5. CONTENT, MEDIA & MAP HELPERS
 * ═══════════════════════════════════════════════════════════════ */

/**
 * Collect hospital gallery images (Media Library IDs -> custom URLs -> post attachments -> curated hospital fallbacks).
 *
 * Ensures every hospital page displays a rich, interactive photo gallery.
 *
 * @param int    $post_id Hospital ID.
 * @param string $lang    Language.
 * @return array[] List of ['full' => string, 'large' => string, 'caption' => string]
 */
function shm_get_gallery_items( $post_id, $lang = '' ) {
	$lang  = $lang ? $lang : shm_post_lang( $post_id );
	$title = get_the_title( $post_id );
	$items = array();
	$seen  = array();

	$default_captions = array(
		shm_t( 'gal_cap_1', $lang ),
		shm_t( 'gal_cap_2', $lang ),
		shm_t( 'gal_cap_3', $lang ),
		shm_t( 'gal_cap_4', $lang ),
		shm_t( 'gal_cap_5', $lang ),
	);

	// 1. Attachment IDs from _hospital_gallery
	$gallery_ids = shm_id_list( $post_id, '_hospital_gallery' );
	foreach ( $gallery_ids as $idx => $att_id ) {
		$full_url  = wp_get_attachment_image_url( $att_id, 'full' );
		$large_url = wp_get_attachment_image_url( $att_id, 'large' );
		if ( ! $full_url ) {
			continue;
		}
		$seen[ $full_url ] = true;
		$cap_idx           = count( $items );
		$short_label       = isset( $default_captions[ $cap_idx ] ) ? $default_captions[ $cap_idx ] : shm_t( 'gallery', $lang );
		$cap               = trim( (string) wp_get_attachment_caption( $att_id ) );
		if ( '' !== $cap ) {
			$short_label = $cap;
		}
		$items[] = array(
			'full'    => $full_url,
			'large'   => $large_url ? $large_url : $full_url,
			'label'   => $short_label,
			'caption' => $title . ' — ' . $short_label,
		);
	}

	// 2. Direct URLs from _hospital_gallery_urls
	$custom_urls = shm_lines( $post_id, '_hospital_gallery_urls' );
	foreach ( $custom_urls as $raw_line ) {
		// Support optional "URL | Caption" syntax
		$parts = array_map( 'trim', explode( '|', $raw_line, 2 ) );
		$url   = esc_url_raw( $parts[0] );
		if ( ! $url || isset( $seen[ $url ] ) ) {
			continue;
		}
		$seen[ $url ] = true;
		$cap_idx      = count( $items );
		$short_label  = ! empty( $parts[1] ) ? $parts[1] : ( isset( $default_captions[ $cap_idx ] ) ? $default_captions[ $cap_idx ] : shm_t( 'gallery', $lang ) );
		$caption      = ! empty( $parts[1] ) ? ( $title . ' — ' . $parts[1] ) : ( $title . ' — ' . $short_label );
		$items[]      = array(
			'full'    => $url,
			'large'   => $url,
			'label'   => $short_label,
			'caption' => $caption,
		);
	}

	// 3. Only if the hospital has no gallery photos configured yet, fallback to curated Safak Medical hospital photos
	if ( empty( $items ) ) {
		$fallback_urls = array(
			'https://safakmedical.com/wp-content/uploads/2024/11/acibadem.webp',
			'https://safakmedical.com/wp-content/uploads/2024/11/memorial.webp',
			'https://safakmedical.com/wp-content/uploads/2024/11/medical-park.webp',
			'https://safakmedical.com/wp-content/uploads/2024/11/liv-e1731920290614.webp',
			'https://safakmedical.com/wp-content/uploads/2024/12/Snapinsta.app_469875971_18364790326141214_8384261363775878112_n_1080-1.webp',
			'https://safakmedical.com/wp-content/uploads/2025/10/1-scaled.webp',
		);

		foreach ( $fallback_urls as $fb_url ) {
			$cap_idx     = count( $items );
			$short_label = isset( $default_captions[ $cap_idx ] ) ? $default_captions[ $cap_idx ] : shm_t( 'gallery', $lang );
			$items[]     = array(
				'full'    => $fb_url,
				'large'   => $fb_url,
				'label'   => $short_label,
				'caption' => $title . ' — ' . $short_label,
			);
		}
	}

	return apply_filters( 'shm_gallery_items', $items, $post_id, $lang );
}

/**
 * Add anchor IDs to H2/H3 headings and build a table of contents.
 *
 * Regex based on purpose: avoids DOMDocument encoding issues with Arabic text.
 *
 * @param string $html Rendered content.
 * @return array{html:string,toc:array}
 */
function shm_process_content_toc( $html ) {
	$toc  = array();
	$used = array();

	$html = preg_replace_callback(
		'#<h([23])([^>]*)>(.*?)</h\1>#is',
		function ( $m ) use ( &$toc, &$used ) {
			$level = (int) $m[1];
			$attrs = $m[2];
			$text  = trim( html_entity_decode( wp_strip_all_tags( $m[3] ), ENT_QUOTES, 'UTF-8' ) );

			if ( '' === $text ) {
				return $m[0];
			}

			if ( preg_match( '/\sid\s*=\s*(["\'])(.*?)\1/i', $attrs, $id_match ) ) {
				$id = $id_match[2];
			} else {
				$id = sanitize_title( $text );
				if ( '' === $id || is_numeric( $id ) || strlen( $id ) > 60 ) {
					$id = 'section-' . ( count( $toc ) + 1 );
				}
				$id    = 'shm-' . $id;
				$base  = $id;
				$i     = 2;
				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $i++;
				}
				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}
			$used[ $id ] = true;

			$toc[] = array(
				'id'    => $id,
				'text'  => $text,
				'level' => $level,
			);

			return '<h' . $level . $attrs . '>' . $m[3] . '</h' . $level . '>';
		},
		(string) $html
	);

	return array(
		'html' => $html,
		'toc'  => $toc,
	);
}

/**
 * Parse a video URL.
 *
 * @param string $url Video URL.
 * @return array|null [provider, id, embed, thumb]
 */
function shm_parse_video( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return null;
	}

	// Support pasted <iframe> embed codes
	if ( preg_match( '/<iframe[^>]+src=["\']([^"\']+)["\']/i', $url, $iframe_m ) ) {
		$url = html_entity_decode( $iframe_m[1], ENT_QUOTES, 'UTF-8' );
	}

	// YouTube (standard, shorts, embed, nocookie, youtu.be)
	if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m ) ) {
		$vid_id = $m[1];
		return array(
			'provider' => 'youtube',
			'id'       => $vid_id,
			'embed'    => 'https://www.youtube-nocookie.com/embed/' . $vid_id . '?autoplay=1&rel=0&modestbranding=1',
			'thumb'    => 'https://i.ytimg.com/vi/' . $vid_id . '/maxresdefault.jpg',
			'thumb_hq' => 'https://i.ytimg.com/vi/' . $vid_id . '/hqdefault.jpg',
		);
	}

	// Vimeo
	if ( preg_match( '~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d+)~i', $url, $m ) ) {
		return array(
			'provider' => 'vimeo',
			'id'       => $m[1],
			'embed'    => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1',
			'thumb'    => 'https://vumbnail.com/' . $m[1] . '.jpg',
			'thumb_hq' => 'https://vumbnail.com/' . $m[1] . '.jpg',
		);
	}

	// Direct MP4 / WebM / OGG video file
	if ( preg_match( '~\.(mp4|webm|ogg)(\?.*)?$~i', $url ) ) {
		return array(
			'provider' => 'file',
			'id'       => '',
			'embed'    => $url,
			'thumb'    => '',
			'thumb_hq' => '',
		);
	}

	return null;
}

/**
 * Map embed iframe src.
 *
 * @param int $post_id Hospital ID.
 * @return string
 */
function shm_map_embed_src( $post_id ) {
	$embed = trim( (string) shm_meta( $post_id, '_hospital_map_embed' ) );
	if ( '' !== $embed ) {
		return $embed;
	}

	$lat = shm_meta( $post_id, '_hospital_lat' );
	$lng = shm_meta( $post_id, '_hospital_lng' );
	$hl  = shm_post_lang( $post_id );

	if ( '' !== $lat && '' !== $lng ) {
		return 'https://maps.google.com/maps?q=' . rawurlencode( $lat . ',' . $lng ) . '&z=15&hl=' . $hl . '&output=embed';
	}

	$query = shm_map_query( $post_id );
	return $query ? 'https://maps.google.com/maps?q=' . rawurlencode( $query ) . '&z=15&hl=' . $hl . '&output=embed' : '';
}

/**
 * Address query string for maps.
 *
 * @param int $post_id Hospital ID.
 * @return string
 */
function shm_map_query( $post_id ) {
	$parts = array_filter(
		array(
			wp_strip_all_tags( get_the_title( $post_id ) ),
			trim( (string) shm_meta( $post_id, '_hospital_address' ) ),
			trim( (string) shm_meta( $post_id, '_hospital_district' ) ),
			shm_get_city( $post_id ),
			'Türkiye',
		)
	);
	return count( $parts ) > 2 ? implode( ', ', $parts ) : '';
}

/**
 * Google Maps directions URL.
 *
 * @param int $post_id Hospital ID.
 * @return string
 */
function shm_directions_url( $post_id ) {
	$lat = shm_meta( $post_id, '_hospital_lat' );
	$lng = shm_meta( $post_id, '_hospital_lng' );
	if ( '' !== $lat && '' !== $lng ) {
		return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $lat . ',' . $lng );
	}
	$query = shm_map_query( $post_id );
	return $query ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $query ) : '';
}

/**
 * Trimmed plain-text description (excerpt → content).
 *
 * @param int $post_id Post ID.
 * @param int $words   Max words.
 * @return string
 */
function shm_description( $post_id, $words = 30 ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}
	$text = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content );
	$text = wp_strip_all_tags( $text, true );
	$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );

	return wp_trim_words( $text, $words, '…' );
}

/**
 * Best image size for doctor cards.
 *
 * @return string
 */
function shm_doctor_image_size() {
	return has_image_size( 'safak_doctor_card' ) ? 'safak_doctor_card' : 'medium_large';
}

/**
 * Load a template part (theme override: yourtheme/safak-hospitals/parts/{name}.php).
 *
 * @param string $name Part name.
 * @param array  $args Variables.
 */
function shm_template_part( $name, $args = array() ) {
	$name     = sanitize_file_name( $name );
	$template = locate_template( array( 'safak-hospitals/parts/' . $name . '.php' ) );
	if ( ! $template ) {
		$template = SHM_PATH . 'templates/parts/' . $name . '.php';
	}
	$template = apply_filters( 'shm_template_part', $template, $name, $args );
	if ( file_exists( $template ) ) {
		extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
		include $template;
	}
}

/**
 * Render a template part to a string.
 *
 * @param string $name Part name.
 * @param array  $args Variables.
 * @return string
 */
function shm_get_template_part( $name, $args = array() ) {
	ob_start();
	shm_template_part( $name, $args );
	return (string) ob_get_clean();
}

/* ═══════════════════════════════════════════════════════════════
 * 6. INLINE SVG ICONS (zero dependencies)
 * ═══════════════════════════════════════════════════════════════ */

/**
 * Inline SVG icon.
 *
 * @param string $name  Icon name.
 * @param string $class Extra classes.
 * @return string
 */
function shm_icon( $name, $class = '' ) {
	static $icons = null;

	if ( null === $icons ) {
		$icons = array(
			'bed'           => '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>',
			'bed-double'    => '<path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><path d="M12 4v6"/><path d="M2 18h20"/>',
			'door'          => '<path d="M13 4h3a2 2 0 0 1 2 2v14"/><path d="M2 20h3"/><path d="M13 20h9"/><path d="M10 12v.01"/><path d="M13 4.56v16.16a1 1 0 0 1-1.24.97L5 20V5.56a2 2 0 0 1 1.52-1.94l4-1A2 2 0 0 1 13 4.56Z"/>',
			'surgery-light' => '<path d="M12 2v5"/><path d="M6 7h12l4 9H2l4-9Z"/><path d="M9.17 16a3 3 0 1 0 5.66 0"/>',
			'heart-pulse'   => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/>',
			'stethoscope'   => '<path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 12 0V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 12 0v-4"/><circle cx="20" cy="10" r="2"/>',
			'activity'      => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
			'award'         => '<circle cx="12" cy="8" r="6"/><path d="M15.48 12.89 17 22l-5-3-5 3 1.52-9.11"/>',
			'calendar'      => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
			'shield-check'  => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
			'badge-check'   => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
			'star'          => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
			'globe'         => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
			'map-pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
			'plane'         => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
			'languages'     => '<path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/>',
			'utensils'      => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
			'clock'         => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
			'wifi'          => '<path d="M12 20h.01"/><path d="M2 8.82a15 15 0 0 1 20 0"/><path d="M5 12.86a10 10 0 0 1 14 0"/><path d="M8.5 16.43a5 5 0 0 1 7 0"/>',
			'phone'         => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
			'upload'        => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
			'check'         => '<path d="M20 6 9 17l-5-5"/>',
			'check-circle'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>',
			'arrow'         => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
			'chevron-prev'  => '<path d="m15 18-6-6 6-6"/>',
			'chevron-next'  => '<path d="m9 18 6-6-6-6"/>',
			'hospital'      => '<path d="M12 6v4"/><path d="M14 14h-4"/><path d="M14 18h-4"/><path d="M14 8h-4"/><path d="M18 12h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"/><path d="M18 22V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v18"/>',
			'list'          => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>',
			'navigation'    => '<polygon points="3 11 22 2 13 21 11 13 3 11"/>',
			'x'             => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
			'lock'          => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
			'image'         => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
			'microscope'    => '<path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 1 0 0-14h-1"/><path d="M9 14h2"/><path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"/><path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"/>',
			'layers'        => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
			'users'         => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
			'message'       => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
			'file'          => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
			'play'          => array( 'fill', '<path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l10.78-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14Z"/>' ),
			'whatsapp'      => array( 'fill', '<path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.47-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.44-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.28-.2-.57-.35m-5.42 7.4h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88a9.8 9.8 0 0 1 6.99 2.9 9.82 9.82 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89a11.82 11.82 0 0 0-3.48-8.41Z"/>' ),
		);
		$icons = apply_filters( 'shm_icons', $icons );
	}

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$icon   = $icons[ $name ];
	$filled = is_array( $icon );
	$paths  = $filled ? $icon[1] : $icon;
	$class  = trim( 'shm-icon shm-icon--' . $name . ' ' . $class );

	if ( $filled ) {
		return '<svg class="' . esc_attr( $class ) . '" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}

	return '<svg class="' . esc_attr( $class ) . '" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

