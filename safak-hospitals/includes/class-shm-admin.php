<?php
/**
 * Admin: tabbed meta box, saving, doctor search, list columns.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI.
 */
class SHM_Admin {

	const NONCE = 'shm_save_hospital';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . SHM_POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_shm_search_doctors', array( __CLASS__, 'ajax_search_doctors' ) );

		add_filter( 'manage_' . SHM_POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . SHM_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . SHM_POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_beds' ) );

		// Completely suppress SCF (Secure Custom Fields) and ACF from hospitals
		add_filter( 'acf/get_field_groups', array( __CLASS__, 'suppress_scf_field_groups' ), 999 );
		add_filter( 'acf/location/rule_match/post_type', array( __CLASS__, 'suppress_scf_location_match' ), 999, 3 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'remove_scf_meta_boxes' ), 999 );

		// Use clean classic layout for hospitals so plugin custom forms are front and center
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 999, 2 );
	}

	/**
	 * Is this one of our admin screens?
	 *
	 * @return bool
	 */
	private static function is_our_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && ( SHM_POST_TYPE === $screen->post_type || SHM_INQUIRY_POST_TYPE === $screen->post_type );
	}

	/**
	 * Admin assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function enqueue( $hook ) {
		if ( ! self::is_our_screen() ) {
			return;
		}

		wp_enqueue_style( 'shm-admin', SHM_URL . 'assets/css/admin.css', array(), SHM_VERSION );

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'shm-admin', SHM_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), SHM_VERSION, true );
			wp_localize_script(
				'shm-admin',
				'shmAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'shm_search_doctors' ),
					'postId'  => get_the_ID(),
					'i18n'    => array(
						'galleryTitle'  => __( 'Hospital photo gallery', 'safak-hospitals' ),
						'galleryButton' => __( 'Add to gallery', 'safak-hospitals' ),
						'remove'        => __( 'Remove', 'safak-hospitals' ),
						'noResults'     => __( 'No doctors found.', 'safak-hospitals' ),
						'searching'     => __( 'Searching…', 'safak-hospitals' ),
						'added'         => __( 'Already added', 'safak-hospitals' ),
					),
				)
			);
		}
	}

	/**
	 * Register meta boxes.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'shm_hospital_details',
			__( 'Hospital Details — Safak Medical', 'safak-hospitals' ),
			array( __CLASS__, 'render_meta_box' ),
			SHM_POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render tabbed meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE, 'shm_nonce' );
		$groups = shm_field_groups();
		$first  = true;
		?>
		<div class="shm-admin">
			<nav class="shm-admin-tabs" role="tablist">
				<?php foreach ( $groups as $group_id => $group ) : ?>
					<button type="button" role="tab" class="shm-admin-tab<?php echo $first ? ' is-active' : ''; ?>" data-tab="<?php echo esc_attr( $group_id ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
						<span class="dashicons <?php echo esc_attr( $group['icon'] ); ?>"></span>
						<span><?php echo esc_html( $group['label'] ); ?></span>
					</button>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</nav>

			<div class="shm-admin-panels">
				<?php $first = true; ?>
				<?php foreach ( $groups as $group_id => $group ) : ?>
					<section class="shm-admin-panel<?php echo $first ? ' is-active' : ''; ?>" data-panel="<?php echo esc_attr( $group_id ); ?>" role="tabpanel">
						<h3 class="shm-admin-panel-title"><?php echo esc_html( $group['label'] ); ?></h3>

						<?php if ( 'general' === $group_id ) : ?>
							<div class="shm-admin-guide-card" style="background:#f0fdf4; border:1px solid #86efac; border-radius:12px; padding:16px 20px; margin-bottom:24px; font-size:13.5px; line-height:1.65; color:#166534;">
								<div style="display:flex; align-items:center; gap:8px; font-size:15px; font-weight:700; color:#15803d; margin-bottom:8px;">
									<span class="dashicons dashicons-format-image" style="font-size:20px; width:20px; height:20px;"></span>
									<span><?php esc_html_e( 'Ideal Image Dimensions & Resolution Guide (دليل الدقة والمقاسات المثالية للصور):', 'safak-hospitals' ); ?></span>
								</div>
								<p style="margin:0 0 10px; color:#14532d;">
									<?php esc_html_e( 'To achieve the highest visual sharpness, elegant presentation, and ultra-fast loading across all mobile, desktop, and Retina screens, use the following recommended dimensions:', 'safak-hospitals' ); ?>
								</p>
								<ul style="margin:0; padding-inline-start:20px; list-style:disc;">
									<li style="margin-bottom:6px;">
										<strong><?php esc_html_e( 'Hero & Main Card Image (صورة الهيرو والبطاقة الرئيسية):', 'safak-hospitals' ); ?></strong>
										<code style="background:#dcfce7; padding:2px 8px; border-radius:4px; font-size:13px; font-weight:700;">1920 × 900 px</code>
										<?php esc_html_e( '(16:9 or 21:9 wide landscape, WebP/JPG, optimal under 400 KB).', 'safak-hospitals' ); ?>
									</li>
									<li style="margin-bottom:6px;">
										<strong><?php esc_html_e( 'Overview Section Photo (صورة قسم الرعاية ونبذة المستشفى):', 'safak-hospitals' ); ?></strong>
										<code style="background:#dcfce7; padding:2px 8px; border-radius:4px; font-size:13px; font-weight:700;">1200 × 1000 px</code>
										<?php esc_html_e( '(approx 6:5 or 4:3 ratio, portrait/compact architectural or medical staff, under 350 KB).', 'safak-hospitals' ); ?>
									</li>
									<li style="margin-bottom:0;">
										<strong><?php esc_html_e( 'Hospital Gallery Photos (صور مرافق وأقسام المستشفى):', 'safak-hospitals' ); ?></strong>
										<code style="background:#dcfce7; padding:2px 8px; border-radius:4px; font-size:13px; font-weight:700;">1400 × 900 px</code>
										<?php esc_html_e( '(or 1200 × 800 px — 3:2 ratio, landscape, crisp WebP/JPG for showcase display and full-screen lightbox).', 'safak-hospitals' ); ?>
									</li>
								</ul>
							</div>
						<?php endif; ?>

						<div class="shm-admin-grid">
							<?php
							foreach ( $group['fields'] as $key => $def ) {
								self::render_field( $post, $key, $def );
							}
							?>
						</div>
					</section>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one field.
	 *
	 * @param WP_Post $post Post.
	 * @param string  $key  Meta key.
	 * @param array   $def  Definition.
	 */
	private static function render_field( $post, $key, $def ) {
		$value = get_post_meta( $post->ID, $key, true );
		$id    = 'shm-field-' . sanitize_html_class( $key );
		$name  = 'shm_meta[' . $key . ']';
		$wide  = ! empty( $def['wide'] ) || in_array( $def['type'], array( 'checkbox' ), true );
		$ph    = isset( $def['placeholder'] ) ? $def['placeholder'] : '';
		?>
		<div class="shm-admin-field shm-admin-field--<?php echo esc_attr( $def['type'] ); ?><?php echo $wide ? ' is-wide' : ''; ?>">
			<?php
			switch ( $def['type'] ) {
				case 'number':
				case 'year':
					$max = 'year' === $def['type'] ? (int) gmdate( 'Y' ) : '';
					$min = 'year' === $def['type'] ? 1800 : 0;
					?>
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $def['label'] ); ?></label>
					<input type="number" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>" min="<?php echo esc_attr( $min ); ?>" <?php echo $max ? 'max="' . esc_attr( $max ) . '"' : ''; ?> step="1" class="small-text">
					<?php
					break;

				case 'checkbox':
					?>
					<label class="shm-admin-switch" for="<?php echo esc_attr( $id ); ?>">
						<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( (bool) $value ); ?>>
						<span class="shm-admin-switch-ui" aria-hidden="true"></span>
						<span><?php echo esc_html( $def['label'] ); ?></span>
					</label>
					<?php
					break;

				case 'textarea':
				case 'lines':
					?>
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $def['label'] ); ?></label>
					<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo 'lines' === $def['type'] ? 6 : 3; ?>" placeholder="<?php echo esc_attr( $ph ); ?>" class="large-text"><?php echo esc_textarea( (string) $value ); ?></textarea>
					<?php
					break;

				case 'image':
					self::render_image_field( $post, $name, $value, $def );
					break;

				case 'gallery':
					self::render_gallery_field( $name, $value, $def );
					break;

				case 'doctors':
					self::render_doctors_field( $post, $name, $value, $def );
					break;

				case 'amenities':
					$value = is_array( $value ) ? $value : array();
					?>
					<span class="shm-admin-label"><?php echo esc_html( $def['label'] ); ?></span>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[]" value="">
					<div class="shm-admin-checklist">
						<?php foreach ( shm_amenity_definitions() as $am_key => $am ) : ?>
							<label class="shm-admin-check">
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $am_key ); ?>" <?php checked( in_array( $am_key, $value, true ) ); ?>>
								<span><?php echo esc_html( $am['admin'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<?php
					break;

				default: // text, url, map, phone, coordinate.
					$type = 'url' === $def['type'] ? 'url' : 'text';
					?>
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $def['label'] ); ?></label>
					<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>" class="widefat" <?php echo 'phone' === $def['type'] || 'coordinate' === $def['type'] ? 'dir="ltr"' : ''; ?>>
					<?php
			}

			if ( ! empty( $def['desc'] ) ) {
				echo '<p class="description">' . esc_html( $def['desc'] ) . '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Single Image Field with media uploader and live preview.
	 *
	 * @param WP_Post $post  Post.
	 * @param string  $name  Input name.
	 * @param mixed   $value Value.
	 * @param array   $def   Definition.
	 */
	private static function render_image_field( $post, $name, $value, $def ) {
		$img_id = absint( $value );
		if ( ! $img_id && false !== strpos( $name, '_hospital_main_photo' ) && has_post_thumbnail( $post->ID ) ) {
			$img_id = get_post_thumbnail_id( $post->ID );
		}
		$thumb = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
		?>
		<span class="shm-admin-label"><?php echo esc_html( $def['label'] ); ?></span>
		<div class="shm-setting-image" data-shm-single-image>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $img_id ? $img_id : '' ); ?>" data-image-id>
			<div class="shm-setting-image-preview" data-image-preview>
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="" style="max-width:280px;height:auto;display:block;margin-bottom:10px;border-radius:8px;border:1px solid #cbd5e1;">
				<?php endif; ?>
			</div>
			<p>
				<button type="button" class="button button-secondary" data-image-select><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Choose / Upload Photo', 'safak-hospitals' ); ?></button>
				<button type="button" class="button-link" data-image-remove <?php echo ! $thumb ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove photo', 'safak-hospitals' ); ?></button>
			</p>
			<?php if ( ! empty( $def['desc'] ) ) : ?>
				<p class="description"><?php echo esc_html( $def['desc'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Gallery field.
	 *
	 * @param string $name  Input name.
	 * @param mixed  $value Value.
	 * @param array  $def   Definition.
	 */
	private static function render_gallery_field( $name, $value, $def ) {
		$ids = array_filter( array_map( 'absint', is_array( $value ) ? $value : explode( ',', (string) $value ) ) );
		?>
		<span class="shm-admin-label"><?php echo esc_html( $def['label'] ); ?></span>
		<div class="shm-gallery" data-shm-gallery>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" data-shm-gallery-input>
			<ul class="shm-gallery-list" data-shm-gallery-list>
				<?php foreach ( $ids as $att_id ) : ?>
					<?php $thumb = wp_get_attachment_image_url( $att_id, 'thumbnail' ); ?>
					<?php if ( $thumb ) : ?>
						<li class="shm-gallery-item" data-id="<?php echo esc_attr( $att_id ); ?>">
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" width="96" height="96">
							<button type="button" class="shm-gallery-remove" aria-label="<?php esc_attr_e( 'Remove', 'safak-hospitals' ); ?>">&times;</button>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
			<p>
				<button type="button" class="button button-secondary" data-shm-gallery-add><span class="dashicons dashicons-format-gallery"></span> <?php esc_html_e( 'Add / select photos', 'safak-hospitals' ); ?></button>
				<button type="button" class="button-link shm-gallery-clear" data-shm-gallery-clear><?php esc_html_e( 'Clear gallery', 'safak-hospitals' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Drag to reorder. The first photo appears as the large tile in the gallery grid.', 'safak-hospitals' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Doctors relationship field.
	 *
	 * @param WP_Post $post  Post.
	 * @param string  $name  Input name.
	 * @param mixed   $value Value.
	 * @param array   $def   Definition.
	 */
	private static function render_doctors_field( $post, $name, $value, $def ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) );

		// Fetch available doctors for 1-click quick selection
		$all_doctors = post_type_exists( SHM_DOCTOR_POST_TYPE ) ? get_posts(
			array(
				'post_type'        => SHM_DOCTOR_POST_TYPE,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'   => 100,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'lang'             => '',
				'suppress_filters' => true,
			)
		) : array();
		?>
		<span class="shm-admin-label"><?php echo esc_html( $def['label'] ); ?></span>
		<?php if ( ! post_type_exists( SHM_DOCTOR_POST_TYPE ) ) : ?>
			<p class="notice notice-warning inline"><?php esc_html_e( 'The "doctors" post type is not registered on this site.', 'safak-hospitals' ); ?></p>
		<?php endif; ?>
		<div class="shm-doctors" data-shm-doctors data-name="<?php echo esc_attr( $name ); ?>[]">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[]" value="">
			<div class="shm-doctors-search">
				<span class="dashicons dashicons-search"></span>
				<input type="search" class="widefat" placeholder="<?php esc_attr_e( 'Search doctors by name…', 'safak-hospitals' ); ?>" data-shm-doctors-search autocomplete="off">
				<ul class="shm-doctors-results" data-shm-doctors-results hidden></ul>
			</div>
			<ul class="shm-doctors-selected" data-shm-doctors-selected>
				<?php foreach ( $ids as $doctor_id ) : ?>
					<?php
					$doctor = get_post( $doctor_id );
					if ( ! $doctor ) {
						continue;
					}
					$thumb = get_the_post_thumbnail_url( $doctor_id, 'thumbnail' );
					?>
					<li class="shm-doctor-chip" data-id="<?php echo esc_attr( $doctor_id ); ?>">
						<input type="hidden" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $doctor_id ); ?>">
						<span class="shm-doctor-chip-handle dashicons dashicons-menu"></span>
						<?php if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" width="36" height="36">
						<?php else : ?>
							<span class="shm-doctor-chip-ph dashicons dashicons-admin-users"></span>
						<?php endif; ?>
						<span class="shm-doctor-chip-name"><?php echo esc_html( get_the_title( $doctor ) ); ?><?php echo 'publish' !== $doctor->post_status ? ' <em>(' . esc_html( $doctor->post_status ) . ')</em>' : ''; ?></span>
						<button type="button" class="shm-doctor-chip-remove" aria-label="<?php esc_attr_e( 'Remove', 'safak-hospitals' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( ! empty( $all_doctors ) ) : ?>
				<div class="shm-doctors-quick-pool" style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #cbd5e1;">
					<p class="description" style="margin: 0 0 8px; font-weight: 600; color: #1e293b;">
						<?php esc_html_e( 'Click any doctor below to add to this hospital (you can select multiple doctors):', 'safak-hospitals' ); ?>
					</p>
					<div style="display: flex; flex-wrap: wrap; gap: 8px; max-height: 220px; overflow-y: auto; padding: 4px;">
						<?php foreach ( $all_doctors as $doc_item ) : ?>
							<?php
							$d_id    = $doc_item->ID;
							$d_title = get_the_title( $d_id );
							$d_thumb = (string) get_the_post_thumbnail_url( $d_id, 'thumbnail' );
							$in_list = in_array( $d_id, $ids, true );
							?>
							<button type="button"
								class="button button-secondary shm-quick-add-doctor"
								data-id="<?php echo esc_attr( $d_id ); ?>"
								data-title="<?php echo esc_attr( $d_title ); ?>"
								data-thumb="<?php echo esc_url( $d_thumb ); ?>"
								style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; height: auto; border-radius: 999px; <?php echo $in_list ? 'opacity:0.55;' : ''; ?>">
								<?php if ( $d_thumb ) : ?>
									<img src="<?php echo esc_url( $d_thumb ); ?>" alt="" width="22" height="22" style="border-radius:50%;object-fit:cover;">
								<?php else : ?>
									<span class="dashicons dashicons-plus-alt2" style="font-size:15px;width:15px;height:15px;"></span>
								<?php endif; ?>
								<span><?php echo esc_html( $d_title ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<p class="description"><?php esc_html_e( 'Search above or click any doctor button to add multiple doctors. Drag selected doctors to reorder.', 'safak-hospitals' ); ?></p>
		</div>
		<?php
	}

	/**
	 * AJAX: search doctors.
	 */
	public static function ajax_search_doctors() {
		check_ajax_referer( 'shm_search_doctors', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(), 403 );
		}

		$term    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$args = array(
			'post_type'        => SHM_DOCTOR_POST_TYPE,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
			's'                => $term,
			'posts_per_page'   => 30,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'lang'             => '',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		);

		$results = array();
		foreach ( get_posts( $args ) as $doctor ) {
			$lang      = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $doctor->ID ) : '';
			$results[] = array(
				'id'        => $doctor->ID,
				'title'     => html_entity_decode( get_the_title( $doctor ), ENT_QUOTES, 'UTF-8' ),
				'thumb'     => (string) get_the_post_thumbnail_url( $doctor->ID, 'thumbnail' ),
				'specialty' => shm_get_doctor_specialty( $doctor->ID ),
				'lang'      => $lang ? strtoupper( $lang ) : '',
				'status'    => $doctor->post_status,
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * Save meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['shm_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['shm_nonce'] ), self::NONCE ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$input = isset( $_POST['shm_meta'] ) && is_array( $_POST['shm_meta'] ) ? wp_unslash( $_POST['shm_meta'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.

		foreach ( shm_all_fields() as $key => $def ) {
			$raw   = isset( $input[ $key ] ) ? $input[ $key ] : null;
			$value = self::sanitize_field( $raw, $def );

			if ( null === $value || '' === $value || array() === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		// Synchronize main photo with standard featured image
		if ( ! empty( $input['_hospital_main_photo'] ) ) {
			$img_id = absint( $input['_hospital_main_photo'] );
			if ( $img_id && wp_attachment_is_image( $img_id ) ) {
				set_post_thumbnail( $post_id, $img_id );
			}
		}

		// Synchronize associated doctors bidirectionally
		if ( isset( $input['_hospital_doctors'] ) ) {
			$doc_ids    = array_filter( array_map( 'absint', (array) $input['_hospital_doctors'] ) );
			$hosp_title = get_the_title( $post_id );
			foreach ( $doc_ids as $doc_id ) {
				update_post_meta( $doc_id, '_hospital_id', $post_id );
				update_post_meta( $doc_id, 'hospital', $hosp_title );
				update_post_meta( $doc_id, 'hospital_name', $hosp_title );
			}
		}

		do_action( 'shm_hospital_saved', $post_id, $post );
	}

	/**
	 * Sanitize one field value.
	 *
	 * @param mixed $raw Raw value.
	 * @param array $def Definition.
	 * @return mixed
	 */
	public static function sanitize_field( $raw, $def ) {
		switch ( $def['type'] ) {
			case 'image':
				$id = absint( $raw );
				return ( $id && wp_attachment_is_image( $id ) ) ? $id : '';

			case 'number':
				if ( null === $raw || '' === trim( (string) $raw ) ) {
					return '';
				}
				return absint( $raw );

			case 'year':
				$year = absint( $raw );
				return ( $year >= 1800 && $year <= (int) gmdate( 'Y' ) + 1 ) ? $year : '';

			case 'checkbox':
				return ! empty( $raw ) ? '1' : '';

			case 'textarea':
				return sanitize_textarea_field( (string) $raw );

			case 'lines':
				$lines = array_filter( array_map( 'sanitize_text_field', preg_split( '/\r\n|\r|\n/', (string) $raw ) ) );
				return implode( "\n", $lines );

			case 'url':
				$raw = trim( (string) $raw );
				if ( preg_match( '/<iframe[^>]+src=["\']([^"\']+)["\']/i', $raw, $m ) ) {
					$raw = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
				}
				return esc_url_raw( $raw, array( 'http', 'https' ) );

			case 'map':
				$raw = trim( (string) $raw );
				if ( preg_match( '/<iframe[^>]+src=["\']([^"\']+)["\']/i', $raw, $m ) ) {
					$raw = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
				}
				$url  = esc_url_raw( $raw, array( 'https' ) );
				$host = (string) wp_parse_url( $url, PHP_URL_HOST );
				$ok   = (bool) preg_match( '/(^|\.)google\.[a-z.]+$|(^|\.)openstreetmap\.org$/i', $host );
				return $ok ? $url : '';

			case 'coordinate':
				$raw = str_replace( ',', '.', trim( (string) $raw ) );
				if ( '' === $raw || ! is_numeric( $raw ) ) {
					return '';
				}
				$num = (float) $raw;
				return ( $num >= -180 && $num <= 180 ) ? (string) round( $num, 7 ) : '';

			case 'phone':
				$phone = preg_replace( '/[^0-9+\s\-()]/', '', (string) $raw );
				return trim( preg_replace( '/\s+/', ' ', $phone ) );

			case 'gallery':
				$ids = array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) );
				$ids = array_filter(
					$ids,
					function ( $id ) {
						return wp_attachment_is_image( $id );
					}
				);
				return array_values( array_unique( $ids ) );

			case 'doctors':
				if ( is_string( $raw ) ) {
					$raw = explode( ',', $raw );
				}
				$ids = array_filter( array_map( 'absint', (array) $raw ) );
				$ids = array_filter(
					$ids,
					function ( $id ) {
						return SHM_DOCTOR_POST_TYPE === get_post_type( $id );
					}
				);
				return array_values( array_unique( $ids ) );

			case 'amenities':
				$allowed = array_keys( shm_amenity_definitions() );
				return array_values( array_intersect( array_map( 'sanitize_key', (array) $raw ), $allowed ) );

			default:
				return sanitize_text_field( (string) $raw );
		}
	}

	/* ─────────────── List table columns ─────────────── */

	/**
	 * Columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['shm_thumb'] = '<span class="dashicons dashicons-format-image" title="' . esc_attr__( 'Photo', 'safak-hospitals' ) . '"></span>';
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['shm_location'] = __( 'Location', 'safak-hospitals' );
				$new['shm_beds']     = __( 'Beds', 'safak-hospitals' );
				$new['shm_gallery']  = __( 'Gallery', 'safak-hospitals' );
				$new['shm_badges']   = __( 'Accreditations', 'safak-hospitals' );
				$new['shm_doctors']  = __( 'Doctors', 'safak-hospitals' );
			}
		}
		return $new;
	}

	/**
	 * Column content.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'shm_thumb':
				echo has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'class' => 'shm-col-thumb' ) ) : '<span class="shm-col-thumb shm-col-thumb--empty dashicons dashicons-building"></span>';
				break;
			case 'shm_location':
				echo esc_html( shm_get_location_line( $post_id, 'en', false ) ? shm_get_location_line( $post_id, 'en', false ) : '—' );
				break;
			case 'shm_beds':
				$beds = shm_int( $post_id, '_hospital_beds_count' );
				echo $beds ? esc_html( number_format_i18n( $beds ) ) : '—';
				break;
			case 'shm_badges':
				$out = array();
				if ( shm_meta( $post_id, '_hospital_is_jci_accredited' ) ) {
					$out[] = '<span class="shm-col-badge shm-col-badge--jci">JCI</span>';
				}
				if ( shm_meta( $post_id, '_hospital_iso_cert' ) ) {
					$out[] = '<span class="shm-col-badge">ISO</span>';
				}
				if ( shm_meta( $post_id, '_hospital_moh_excellence' ) ) {
					$out[] = '<span class="shm-col-badge">MoH</span>';
				}
				echo $out ? implode( ' ', $out ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'shm_doctors':
				$count = count( shm_id_list( $post_id, '_hospital_doctors' ) );
				echo $count ? esc_html( $count ) : '—';
				break;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function sortable_columns( $columns ) {
		$columns['shm_beds'] = 'shm_beds';
		return $columns;
	}

	/**
	 * Sort list by beds.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function sort_by_beds( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'shm_beds' !== $query->get( 'orderby' ) ) {
			return;
		}
		$query->set( 'meta_key', '_hospital_beds_count' ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$query->set( 'orderby', 'meta_value_num' );
	}

	/**
	 * Disable Gutenberg block editor for hospitals so our custom forms take center stage.
	 *
	 * @param bool   $use_block_editor Current setting.
	 * @param string $post_type        Post type.
	 * @return bool
	 */
	public static function disable_block_editor( $use_block_editor, $post_type ) {
		if ( SHM_POST_TYPE === $post_type ) {
			return false;
		}
		return $use_block_editor;
	}

	/**
	 * Completely suppress SCF (Secure Custom Fields) and ACF field groups on hospitals.
	 *
	 * @param array $groups Field groups.
	 * @return array
	 */
	public static function suppress_scf_field_groups( $groups ) {
		global $post, $typenow;
		$current_type = $typenow;
		if ( ! $current_type && ! empty( $post->post_type ) ) {
			$current_type = $post->post_type;
		}
		if ( ! $current_type && isset( $_GET['post_type'] ) ) {
			$current_type = sanitize_key( $_GET['post_type'] );
		}
		if ( ! $current_type && isset( $_GET['post'] ) ) {
			$current_type = get_post_type( absint( $_GET['post'] ) );
		}
		if ( SHM_POST_TYPE === $current_type ) {
			return array();
		}
		return $groups;
	}

	/**
	 * Prevent SCF/ACF location rules from matching hospitals.
	 *
	 * @param bool  $match   Match.
	 * @param array $rule    Rule.
	 * @param array $options Options.
	 * @return bool
	 */
	public static function suppress_scf_location_match( $match, $rule, $options ) {
		if ( isset( $options['post_type'] ) && SHM_POST_TYPE === $options['post_type'] ) {
			return false;
		}
		return $match;
	}

	/**
	 * Remove any remaining SCF / ACF meta boxes from hospitals.
	 */
	public static function remove_scf_meta_boxes() {
		global $wp_meta_boxes;
		if ( isset( $wp_meta_boxes[ SHM_POST_TYPE ] ) ) {
			foreach ( $wp_meta_boxes[ SHM_POST_TYPE ] as $context => $priorities ) {
				foreach ( $priorities as $priority => $boxes ) {
					foreach ( array_keys( $boxes ) as $box_id ) {
						if ( 0 === strpos( $box_id, 'acf-' ) || 0 === strpos( $box_id, 'acf_group' ) || 0 === strpos( $box_id, 'scf-' ) ) {
							remove_meta_box( $box_id, SHM_POST_TYPE, $context );
						}
					}
				}
			}
		}
	}
}

