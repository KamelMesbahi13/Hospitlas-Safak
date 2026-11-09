<?php
/**
 * Custom post type, taxonomies, multilingual registration & meta registration.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hospitals CPT.
 */
class SHM_Post_Type {

	/**
	 * Schema.org MedicalSpecialty enumeration (for category → medicalSpecialty mapping).
	 *
	 * @return string[]
	 */
	public static function medical_specialties() {
		return array(
			'Anesthesia', 'Cardiovascular', 'CommunityHealth', 'Dentistry', 'Dermatology', 'DietNutrition',
			'Emergency', 'Endocrine', 'Gastroenterologic', 'Genetic', 'Geriatric', 'Gynecologic', 'Hematologic',
			'Infectious', 'LaboratoryScience', 'Midwifery', 'Musculoskeletal', 'Neurologic', 'Nursing', 'Obstetric',
			'Oncologic', 'Optometric', 'Otolaryngologic', 'Pathology', 'Pediatric', 'PharmacySpecialty',
			'Physiotherapy', 'PlasticSurgery', 'Podiatric', 'PrimaryCare', 'Psychiatric', 'PublicHealth',
			'Pulmonary', 'Radiography', 'Renal', 'RespiratoryTherapy', 'Rheumatologic', 'SpeechPathology',
			'Surgical', 'Toxicologic', 'Urologic',
		);
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		add_action( 'init', array( __CLASS__, 'ensure_supports' ), 99 );
		add_action( 'init', array( __CLASS__, 'register_meta' ), 20 );

		// Polylang.
		add_filter( 'pll_get_post_types', array( __CLASS__, 'pll_post_types' ), 10, 2 );
		add_filter( 'pll_get_taxonomies', array( __CLASS__, 'pll_taxonomies' ), 10, 2 );
		add_filter( 'pll_copy_post_metas', array( __CLASS__, 'pll_copy_metas' ), 10, 2 );
		add_filter( 'pll_translate_post_meta', array( __CLASS__, 'pll_translate_meta' ), 10, 3 );

		// Category term meta (Schema.org specialty mapping).
		add_action( SHM_TAX_CATEGORY . '_add_form_fields', array( __CLASS__, 'term_add_field' ) );
		add_action( SHM_TAX_CATEGORY . '_edit_form_fields', array( __CLASS__, 'term_edit_field' ) );
		add_action( 'created_' . SHM_TAX_CATEGORY, array( __CLASS__, 'term_save' ) );
		add_action( 'edited_' . SHM_TAX_CATEGORY, array( __CLASS__, 'term_save' ) );
	}

	/**
	 * Register the hospitals CPT (skipped if another plugin already registers it).
	 */
	public static function register_post_types() {
		if ( post_type_exists( SHM_POST_TYPE ) || ! apply_filters( 'shm_register_post_type', true ) ) {
			return;
		}

		$labels = array(
			'name'                  => __( 'Hospitals', 'safak-hospitals' ),
			'singular_name'         => __( 'Hospital', 'safak-hospitals' ),
			'menu_name'             => __( 'Hospitals', 'safak-hospitals' ),
			'add_new'               => __( 'Add Hospital', 'safak-hospitals' ),
			'add_new_item'          => __( 'Add New Hospital', 'safak-hospitals' ),
			'edit_item'             => __( 'Edit Hospital', 'safak-hospitals' ),
			'new_item'              => __( 'New Hospital', 'safak-hospitals' ),
			'view_item'             => __( 'View Hospital', 'safak-hospitals' ),
			'view_items'            => __( 'View Hospitals', 'safak-hospitals' ),
			'search_items'          => __( 'Search Hospitals', 'safak-hospitals' ),
			'not_found'             => __( 'No hospitals found.', 'safak-hospitals' ),
			'not_found_in_trash'    => __( 'No hospitals found in Trash.', 'safak-hospitals' ),
			'all_items'             => __( 'All Hospitals', 'safak-hospitals' ),
			'featured_image'        => __( 'Hospital main photo', 'safak-hospitals' ),
			'set_featured_image'    => __( 'Set hospital photo', 'safak-hospitals' ),
			'remove_featured_image' => __( 'Remove hospital photo', 'safak-hospitals' ),
			'use_featured_image'    => __( 'Use as hospital photo', 'safak-hospitals' ),
			'archives'              => __( 'Hospital Archives', 'safak-hospitals' ),
			'items_list'            => __( 'Hospitals list', 'safak-hospitals' ),
		);

		register_post_type(
			SHM_POST_TYPE,
			apply_filters(
				'shm_post_type_args',
				array(
					'labels'          => $labels,
					'public'          => true,
					'show_in_rest'    => true,
					'menu_icon'       => 'dashicons-building',
					'menu_position'   => 21,
					'has_archive'     => true,
					'hierarchical'    => false,
					'rewrite'         => array(
						'slug'       => apply_filters( 'shm_rewrite_slug', 'hospitals' ),
						'with_front' => false,
					),
					'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes', 'elementor' ),
					'taxonomies'      => array( SHM_TAX_CATEGORY, SHM_TAX_CITY ),
					'capability_type' => 'post',
					'map_meta_cap'    => true,
				)
			)
		);
	}

	/**
	 * Guarantee required features when the CPT is registered by a third party.
	 */
	public static function ensure_supports() {
		if ( ! post_type_exists( SHM_POST_TYPE ) ) {
			return;
		}
		add_post_type_support( SHM_POST_TYPE, array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'elementor' ) );
		register_taxonomy_for_object_type( SHM_TAX_CATEGORY, SHM_POST_TYPE );
		register_taxonomy_for_object_type( SHM_TAX_CITY, SHM_POST_TYPE );
	}

	/**
	 * Register taxonomies.
	 */
	public static function register_taxonomies() {
		if ( ! taxonomy_exists( SHM_TAX_CATEGORY ) ) {
			register_taxonomy(
				SHM_TAX_CATEGORY,
				array( SHM_POST_TYPE ),
				apply_filters(
					'shm_category_taxonomy_args',
					array(
						'labels'            => array(
							'name'          => __( 'Hospital Categories', 'safak-hospitals' ),
							'singular_name' => __( 'Hospital Category', 'safak-hospitals' ),
							'menu_name'     => __( 'Categories', 'safak-hospitals' ),
							'all_items'     => __( 'All Categories', 'safak-hospitals' ),
							'edit_item'     => __( 'Edit Category', 'safak-hospitals' ),
							'add_new_item'  => __( 'Add New Category', 'safak-hospitals' ),
							'search_items'  => __( 'Search Categories', 'safak-hospitals' ),
							'not_found'     => __( 'No categories found.', 'safak-hospitals' ),
						),
						'public'            => true,
						'hierarchical'      => true,
						'show_in_rest'      => true,
						'show_admin_column' => true,
						'rewrite'           => array(
							'slug'       => 'hospital-category',
							'with_front' => false,
						),
					)
				)
			);
		}

		if ( ! taxonomy_exists( SHM_TAX_CITY ) ) {
			register_taxonomy(
				SHM_TAX_CITY,
				array( SHM_POST_TYPE ),
				apply_filters(
					'shm_city_taxonomy_args',
					array(
						'labels'            => array(
							'name'          => __( 'Hospital Cities', 'safak-hospitals' ),
							'singular_name' => __( 'Hospital City', 'safak-hospitals' ),
							'menu_name'     => __( 'Cities', 'safak-hospitals' ),
							'all_items'     => __( 'All Cities', 'safak-hospitals' ),
							'edit_item'     => __( 'Edit City', 'safak-hospitals' ),
							'add_new_item'  => __( 'Add New City', 'safak-hospitals' ),
							'search_items'  => __( 'Search Cities', 'safak-hospitals' ),
							'not_found'     => __( 'No cities found.', 'safak-hospitals' ),
						),
						'public'            => true,
						'hierarchical'      => true,
						'show_in_rest'      => true,
						'show_admin_column' => true,
						'rewrite'           => array(
							'slug'       => 'hospital-city',
							'with_front' => false,
						),
					)
				)
			);
		}
	}

	/**
	 * Register meta for REST / block editor (scalar fields only).
	 */
	public static function register_meta() {
		$auth = function () {
			return current_user_can( 'edit_posts' );
		};

		foreach ( shm_all_fields() as $key => $def ) {
			switch ( $def['type'] ) {
				case 'number':
				case 'year':
					$type = 'integer';
					break;
				case 'checkbox':
					$type = 'string';
					break;
				case 'gallery':
				case 'doctors':
				case 'amenities':
					$type = 'array';
					break;
				default:
					$type = 'string';
			}

			$args = array(
				'type'          => $type,
				'single'        => true,
				'auth_callback' => $auth,
				'show_in_rest'  => true,
			);

			if ( 'array' === $type ) {
				$args['show_in_rest'] = array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'gallery' === $def['type'] || 'doctors' === $def['type'] ? 'integer' : 'string' ),
					),
				);
			}

			register_post_meta( SHM_POST_TYPE, $key, $args );
		}
	}

	/* ─────────────── Polylang integration ─────────────── */

	/**
	 * Make hospitals translatable.
	 *
	 * @param array $post_types  Post types.
	 * @param bool  $is_settings Settings screen.
	 * @return array
	 */
	public static function pll_post_types( $post_types, $is_settings ) {
		if ( $is_settings ) {
			unset( $post_types[ SHM_POST_TYPE ] ); // Always translated: hide from settings list.
		} else {
			$post_types[ SHM_POST_TYPE ] = SHM_POST_TYPE;
		}
		return $post_types;
	}

	/**
	 * Make taxonomies translatable.
	 *
	 * @param array $taxonomies  Taxonomies.
	 * @param bool  $is_settings Settings screen.
	 * @return array
	 */
	public static function pll_taxonomies( $taxonomies, $is_settings ) {
		foreach ( array( SHM_TAX_CATEGORY, SHM_TAX_CITY ) as $tax ) {
			if ( $is_settings ) {
				unset( $taxonomies[ $tax ] );
			} else {
				$taxonomies[ $tax ] = $tax;
			}
		}
		return $taxonomies;
	}

	/**
	 * Language-independent meta copied/synchronised between translations.
	 *
	 * @return string[]
	 */
	public static function shared_meta_keys() {
		return apply_filters(
			'shm_shared_meta_keys',
			array(
				'_hospital_main_photo', '_hospital_featured',
				'_hospital_beds_count', '_hospital_rooms_count', '_hospital_operating_theatres', '_hospital_icu_beds',
				'_hospital_doctors_count', '_hospital_annual_surgeries', '_hospital_established_year',
				'_hospital_is_jci_accredited', '_hospital_iso_cert', '_hospital_moh_excellence', '_hospital_intl_patient_center',
				'_hospital_lat', '_hospital_lng', '_hospital_map_embed', '_hospital_whatsapp',
				'_hospital_video_url', '_hospital_gallery', '_hospital_doctors', '_hospital_amenities',
			)
		);
	}

	/**
	 * Polylang: copy shared meta when creating/syncing translations.
	 *
	 * @param string[] $metas Meta keys.
	 * @param bool     $sync  Sync (true) or initial copy (false).
	 * @return string[]
	 */
	public static function pll_copy_metas( $metas, $sync ) {
		return array_values( array_unique( array_merge( (array) $metas, self::shared_meta_keys() ) ) );
	}

	/**
	 * Polylang: translate doctor IDs when copying the relationship.
	 *
	 * @param mixed  $value Meta value.
	 * @param string $key   Meta key.
	 * @param string $lang  Target language.
	 * @return mixed
	 */
	public static function pll_translate_meta( $value, $key, $lang ) {
		if ( '_hospital_doctors' === $key && function_exists( 'pll_get_post' ) && is_array( $value ) ) {
			$out = array();
			foreach ( $value as $id ) {
				$translated = pll_get_post( (int) $id, $lang );
				$out[]      = $translated ? (int) $translated : (int) $id;
			}
			return $out;
		}
		return $value;
	}

	/* ─────────────── Category term meta ─────────────── */

	/**
	 * Specialty select options HTML.
	 *
	 * @param string $current Current value.
	 * @return string
	 */
	private static function specialty_options( $current ) {
		$html = '<option value="">' . esc_html__( '— None —', 'safak-hospitals' ) . '</option>';
		foreach ( self::medical_specialties() as $spec ) {
			$html .= '<option value="' . esc_attr( $spec ) . '"' . selected( $current, $spec, false ) . '>' . esc_html( $spec ) . '</option>';
		}
		return $html;
	}

	/**
	 * Add-term field.
	 */
	public static function term_add_field() {
		wp_nonce_field( 'shm_term_meta', 'shm_term_nonce' );
		?>
		<div class="form-field">
			<label for="shm_schema_specialty"><?php esc_html_e( 'Schema.org medical specialty', 'safak-hospitals' ); ?></label>
			<select name="shm_schema_specialty" id="shm_schema_specialty"><?php echo self::specialty_options( '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			<p><?php esc_html_e( 'Used for the "medicalSpecialty" property in the hospital JSON-LD structured data.', 'safak-hospitals' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Edit-term field.
	 *
	 * @param WP_Term $term Term.
	 */
	public static function term_edit_field( $term ) {
		$current = get_term_meta( $term->term_id, '_shm_schema_specialty', true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="shm_schema_specialty"><?php esc_html_e( 'Schema.org medical specialty', 'safak-hospitals' ); ?></label></th>
			<td>
				<?php wp_nonce_field( 'shm_term_meta', 'shm_term_nonce' ); ?>
				<select name="shm_schema_specialty" id="shm_schema_specialty"><?php echo self::specialty_options( $current ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
				<p class="description"><?php esc_html_e( 'Used for the "medicalSpecialty" property in the hospital JSON-LD structured data. When empty, the plugin tries to detect it from the category name.', 'safak-hospitals' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term meta.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function term_save( $term_id ) {
		if ( ! isset( $_POST['shm_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['shm_term_nonce'] ), 'shm_term_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$value = isset( $_POST['shm_schema_specialty'] ) ? sanitize_text_field( wp_unslash( $_POST['shm_schema_specialty'] ) ) : '';
		if ( in_array( $value, self::medical_specialties(), true ) ) {
			update_term_meta( $term_id, '_shm_schema_specialty', $value );
		} else {
			delete_term_meta( $term_id, '_shm_schema_specialty' );
		}
	}
}
