<?php
/**
 * SEO: Automated Schema.org JSON-LD (Hospital & MedicalOrganization),
 * Open Graph, Twitter Cards, and Core Web Vitals optimizations.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO Manager for Hospitals.
 */
class SHM_SEO {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output_schema_json_ld' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'output_open_graph' ), 22 );
	}

	/**
	 * Output JSON-LD structured data on single hospital pages.
	 */
	public static function output_schema_json_ld() {
		if ( ! is_singular( SHM_POST_TYPE ) || ! shm_setting( 'enable_schema' ) ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$schema = self::build_hospital_schema( $post_id );
		if ( empty( $schema ) ) {
			return;
		}

		echo "\n<!-- Safak Hospitals Manager - Schema.org Medical SEO -->\n";
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "</script>\n";
	}

	/**
	 * Build Schema.org array for single hospital.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function build_hospital_schema( $post_id ) {
		$lang       = shm_post_lang( $post_id );
		$title      = get_the_title( $post_id );
		$permalink  = get_permalink( $post_id );
		$thumb_url  = get_the_post_thumbnail_url( $post_id, 'full' );
		if ( ! $thumb_url ) {
			$fallback_id = absint( shm_setting( 'fallback_image_id' ) );
			$thumb_url   = $fallback_id ? wp_get_attachment_image_url( $fallback_id, 'full' ) : '';
		}

		$description = shm_description( $post_id, 45 );
		$telephone   = shm_phone_e164( $post_id );
		$beds_count  = shm_int( $post_id, '_hospital_beds_count' );

		// Address
		$street   = trim( (string) shm_meta( $post_id, '_hospital_address' ) );
		$district = trim( (string) shm_meta( $post_id, '_hospital_district' ) );
		$city     = shm_get_city( $post_id );

		$address = array(
			'@type'          => 'PostalAddress',
			'addressCountry' => 'TR',
		);
		if ( $street ) {
			$address['streetAddress'] = $street;
		}
		if ( $district ) {
			$address['addressLocality'] = $district;
		}
		if ( $city ) {
			$address['addressRegion'] = $city;
		}

		// Geo coordinates
		$lat = shm_meta( $post_id, '_hospital_lat' );
		$lng = shm_meta( $post_id, '_hospital_lng' );
		$geo = null;
		if ( '' !== $lat && '' !== $lng ) {
			$geo = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $lat,
				'longitude' => (float) $lng,
			);
		}

		// Medical specialties from hospital_categories
		$specialties = array();
		$cat_terms   = shm_terms( $post_id, SHM_TAX_CATEGORY );
		foreach ( $cat_terms as $cterm ) {
			$specialty = get_term_meta( $cterm->term_id, '_shm_schema_specialty', true );
			if ( $specialty ) {
				$specialties[] = 'https://schema.org/' . $specialty;
			} else {
				$specialties[] = $cterm->name;
			}
		}

		// Accreditations / Credentials
		$credentials = array();
		if ( shm_meta( $post_id, '_hospital_is_jci_accredited' ) ) {
			$credentials[] = array(
				'@type'                   => 'EducationalOccupationalCredential',
				'credentialCategory'      => 'Accreditation',
				'name'                    => 'Joint Commission International (JCI) Accreditation',
				'recognizedBy'            => array(
					'@type' => 'Organization',
					'name'  => 'Joint Commission International',
					'url'   => 'https://www.jointcommissioninternational.org',
				),
			);
		}

		$iso_cert = trim( (string) shm_meta( $post_id, '_hospital_iso_cert' ) );
		if ( '' !== $iso_cert ) {
			$credentials[] = array(
				'@type'              => 'EducationalOccupationalCredential',
				'credentialCategory' => 'Certification',
				'name'               => $iso_cert,
			);
		}

		// Amenities as Medical Services
		$available_services = array();
		$amenity_keys       = shm_meta( $post_id, '_hospital_amenities', array() );
		if ( is_array( $amenity_keys ) ) {
			foreach ( $amenity_keys as $am_key ) {
				$name = shm_t( 'am_' . $am_key, $lang );
				$desc = shm_t( 'am_' . $am_key . '_desc', $lang );
				$available_services[] = array(
					'@type'       => 'MedicalService',
					'name'        => $name,
					'description' => $desc,
				);
			}
		}

		// Connected doctors as Physician employees
		$employees = array();
		$doctors   = shm_get_doctors( $post_id, $lang );
		foreach ( $doctors as $doc ) {
			$doc_spec = shm_get_doctor_specialty( $doc->ID );
			$doc_item = array(
				'@type' => 'Physician',
				'name'  => get_the_title( $doc->ID ),
				'url'   => get_permalink( $doc->ID ),
			);
			if ( has_post_thumbnail( $doc->ID ) ) {
				$doc_item['image'] = get_the_post_thumbnail_url( $doc->ID, 'full' );
			}
			if ( $doc_spec ) {
				$doc_item['medicalSpecialty'] = $doc_spec;
			}
			$employees[] = $doc_item;
		}

		// Construct primary Hospital schema
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => array( 'Hospital', 'MedicalOrganization' ),
			'@id'         => $permalink . '#hospital',
			'name'        => $title,
			'url'         => $permalink,
			'description' => $description,
			'telephone'   => $telephone,
			'address'     => $address,
			'parentOrganization' => array(
				'@type' => 'MedicalOrganization',
				'name'  => 'Safak Medical',
				'url'   => 'https://safakmedical.com',
			),
		);

		if ( $thumb_url ) {
			$schema['image'] = $thumb_url;
		}

		if ( $geo ) {
			$schema['geo'] = $geo;
		}

		if ( $beds_count > 0 ) {
			$schema['numberOfBeds'] = $beds_count;
		}

		if ( ! empty( $specialties ) ) {
			$schema['medicalSpecialty'] = $specialties;
		}

		if ( ! empty( $credentials ) ) {
			$schema['hasCredential'] = $credentials;
		}

		if ( ! empty( $available_services ) ) {
			$schema['availableService'] = $available_services;
		}

		if ( ! empty( $employees ) ) {
			$schema['employee'] = $employees;
		}

		return apply_filters( 'shm_schema_data', $schema, $post_id, $lang );
	}

	/**
	 * Output Open Graph and Twitter Card tags.
	 */
	public static function output_open_graph() {
		if ( ! is_singular( SHM_POST_TYPE ) || ! shm_setting( 'enable_og' ) ) {
			return;
		}

		// Don't duplicate if Yoast or Rank Math is active and already outputs OG
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) ) {
			return;
		}

		$post_id   = get_the_ID();
		$title     = get_the_title( $post_id ) . ' - Safak Medical';
		$desc      = shm_description( $post_id, 35 );
		$url       = get_permalink( $post_id );
		$image_url = get_the_post_thumbnail_url( $post_id, 'large' );
		$lang      = shm_post_lang( $post_id );

		if ( ! $image_url ) {
			$fallback_id = absint( shm_setting( 'fallback_image_id' ) );
			$image_url   = $fallback_id ? wp_get_attachment_image_url( $fallback_id, 'large' ) : '';
		}

		$locale_map = array(
			'ar' => 'ar_AR',
			'fr' => 'fr_FR',
			'en' => 'en_US',
		);
		$og_locale = isset( $locale_map[ $lang ] ) ? $locale_map[ $lang ] : 'ar_AR';

		echo "\n<!-- Safak Hospitals Open Graph & Twitter Cards -->\n";
		echo '<meta property="og:type" content="business.business" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="Safak Medical" />' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( $og_locale ) . '" />' . "\n";

		// Alternate locales for Polylang
		if ( function_exists( 'pll_the_languages' ) ) {
			foreach ( shm_languages() as $l ) {
				if ( $l !== $lang && isset( $locale_map[ $l ] ) ) {
					echo '<meta property="og:locale:alternate" content="' . esc_attr( $locale_map[ $l ] ) . '" />' . "\n";
				}
			}
		}

		if ( $image_url ) {
			echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";
		}

		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '" />' . "\n";
	}
}

