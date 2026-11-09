<?php
/**
 * Inquiries: submissions CPT, secure file upload handling, email notifications.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inquiries management.
 */
class SHM_Inquiries {

	const UPLOAD_SUBDIR = 'safak-hospitals-reports';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'wp_ajax_shm_submit_inquiry', array( __CLASS__, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_shm_submit_inquiry', array( __CLASS__, 'ajax_submit' ) );
		add_action( 'admin_init', array( __CLASS__, 'download_file' ) );

		add_filter( 'manage_' . SHM_INQUIRY_POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . SHM_INQUIRY_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );

		add_action( 'manage_posts_extra_tablenav', array( __CLASS__, 'add_export_button' ) );
		add_action( 'admin_init', array( __CLASS__, 'export_csv' ) );
	}

	/**
	 * Register the inquiries CPT.
	 */
	public static function register_post_type() {
		register_post_type(
			SHM_INQUIRY_POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Hospital Inquiries', 'safak-hospitals' ),
					'singular_name' => __( 'Hospital Inquiry', 'safak-hospitals' ),
					'menu_name'     => __( 'Inquiries', 'safak-hospitals' ),
					'all_items'     => __( 'All Inquiries', 'safak-hospitals' ),
					'edit_item'     => __( 'View Inquiry', 'safak-hospitals' ),
					'search_items'  => __( 'Search Inquiries', 'safak-hospitals' ),
					'not_found'     => __( 'No inquiries found.', 'safak-hospitals' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=' . SHM_POST_TYPE,
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'supports'        => array( 'title' ),
			)
		);
	}

	/**
	 * Ensure the protected upload directory has .htaccess and index.php.
	 *
	 * @return string Directory path.
	 */
	public static function ensure_protected_dir() {
		$upload_dir = wp_upload_dir();
		$path       = trailingslashit( $upload_dir['basedir'] ) . self::UPLOAD_SUBDIR;

		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		$htaccess = $path . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			@file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n<Files ~ \"^.*$\">\nDeny from all\n</Files>\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		$index = $path . '/index.php';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		return $path;
	}

	/**
	 * Process form submission via AJAX.
	 */
	public static function ajax_submit() {
		check_ajax_referer( 'shm_inquiry_action', 'nonce' );

		// Honeypot.
		if ( ! empty( $_POST['hospital_notes'] ) ) {
			wp_send_json_success( array( 'message' => shm_t( 'success' ) ) );
		}

		$lang        = isset( $_POST['lang'] ) ? shm_normalize_lang( wp_unslash( $_POST['lang'] ) ) : shm_lang();
		$hospital_id = isset( $_POST['hospital_id'] ) ? absint( $_POST['hospital_id'] ) : 0;
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$contact     = isset( $_POST['contact'] ) ? sanitize_text_field( wp_unslash( $_POST['contact'] ) ) : '';
		$message     = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$consent     = ! empty( $_POST['consent'] );

		// Validation.
		if ( '' === $name || '' === $contact ) {
			wp_send_json_error( array( 'message' => shm_t( 'error_required', $lang ) ), 400 );
		}
		if ( ! $consent ) {
			wp_send_json_error( array( 'message' => shm_t( 'error_consent', $lang ) ), 400 );
		}

		// Rate limit: max 5 submissions per IP every 10 minutes.
		$ip         = sanitize_text_field( isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '' );
		$ip_key     = 'shm_rate_' . md5( $ip );
		$rate_count = (int) get_transient( $ip_key );
		if ( $rate_count >= 5 ) {
			wp_send_json_error( array( 'message' => shm_t( 'error_rate', $lang ) ), 429 );
		}
		set_transient( $ip_key, $rate_count + 1, 10 * MINUTE_IN_SECONDS );

		// File upload.
		$file_info = null;
		if ( ! empty( $_FILES['medical_report']['name'] ) ) {
			$file_info = self::handle_upload( $_FILES['medical_report'], $lang ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( is_wp_error( $file_info ) ) {
				wp_send_json_error( array( 'message' => $file_info->get_error_message() ), 400 );
			}
		}

		// Save CPT entry.
		$hospital_name = $hospital_id ? get_the_title( $hospital_id ) : __( 'General inquiry', 'safak-hospitals' );
		$post_id       = wp_insert_post(
			array(
				'post_type'   => SHM_INQUIRY_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s — %s (%s)', $name, $hospital_name, wp_date( 'Y-m-d H:i' ) ),
			)
		);

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => shm_t( 'error_generic', $lang ) ), 500 );
		}

		update_post_meta( $post_id, '_inquiry_name', $name );
		update_post_meta( $post_id, '_inquiry_contact', $contact );
		update_post_meta( $post_id, '_inquiry_message', $message );
		update_post_meta( $post_id, '_inquiry_hospital_id', $hospital_id );
		update_post_meta( $post_id, '_inquiry_hospital_name', $hospital_name );
		update_post_meta( $post_id, '_inquiry_lang', $lang );
		update_post_meta( $post_id, '_inquiry_ip', $ip );
		update_post_meta( $post_id, '_inquiry_user_agent', isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' );

		if ( $file_info ) {
			update_post_meta( $post_id, '_inquiry_file_path', $file_info['path'] );
			update_post_meta( $post_id, '_inquiry_file_name', $file_info['name'] );
			update_post_meta( $post_id, '_inquiry_file_size', $file_info['size'] );
			update_post_meta( $post_id, '_inquiry_file_mime', $file_info['mime'] );
		}

		// Notification email.
		self::send_notification( $post_id );

		wp_send_json_success( array( 'message' => shm_t( 'success', $lang ) ) );
	}

	/**
	 * Handle file upload securely.
	 *
	 * @param array  $file File entry from $_FILES.
	 * @param string $lang Language.
	 * @return array|WP_Error
	 */
	private static function handle_upload( $file, $lang ) {
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_NO_FILE !== $file['error'] ) {
			return new WP_Error( 'upload_error', shm_t( 'error_file', $lang ) );
		}

		// Max size: 10 MB.
		$max_size = 10 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', shm_t( 'error_file', $lang ) );
		}

		// Validate extension & MIME.
		$allowed_exts = array( 'pdf', 'jpg', 'jpeg', 'png' );
		$ext          = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_exts, true ) ) {
			return new WP_Error( 'file_invalid_ext', shm_t( 'error_file', $lang ) );
		}

		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$real_mime = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );

		$allowed_mimes = array( 'application/pdf', 'image/jpeg', 'image/png' );
		if ( ! in_array( $real_mime, $allowed_mimes, true ) ) {
			return new WP_Error( 'file_invalid_mime', shm_t( 'error_file', $lang ) );
		}

		$target_dir = self::ensure_protected_dir();
		$safe_name  = sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) );
		$token      = wp_generate_password( 24, false );
		$filename   = sprintf( 'report_%s_%s.%s', $token, $safe_name, $ext );
		$target     = trailingslashit( $target_dir ) . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $target ) ) {
			return new WP_Error( 'move_failed', shm_t( 'error_generic', $lang ) );
		}

		@chmod( $target, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		return array(
			'path' => $target,
			'name' => $file['name'],
			'size' => $file['size'],
			'mime' => $real_mime,
		);
	}

	/**
	 * Send email notification.
	 *
	 * @param int $post_id Inquiry ID.
	 */
	public static function send_notification( $post_id ) {
		$to = shm_setting( 'notification_email' );
		if ( ! is_email( $to ) ) {
			return;
		}

		$name          = get_post_meta( $post_id, '_inquiry_name', true );
		$contact       = get_post_meta( $post_id, '_inquiry_contact', true );
		$hospital_name = get_post_meta( $post_id, '_inquiry_hospital_name', true );
		$message       = get_post_meta( $post_id, '_inquiry_message', true );
		$lang          = get_post_meta( $post_id, '_inquiry_lang', true );
		$file_name     = get_post_meta( $post_id, '_inquiry_file_name', true );
		$admin_url     = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

		$subject = sprintf( '[Safak Medical] New Hospital Inquiry: %s (%s)', $name, $hospital_name );

		$body  = "A new patient inquiry has been submitted via Safak Hospitals Manager:\n\n";
		$body .= "• Hospital: " . $hospital_name . "\n";
		$body .= "• Patient Name: " . $name . "\n";
		$body .= "• Contact: " . $contact . "\n";
		$body .= "• Language: " . strtoupper( $lang ) . "\n";
		if ( $message ) {
			$body .= "• Message:\n" . $message . "\n";
		}
		if ( $file_name ) {
			$body .= "• Medical Report: " . $file_name . " (download from WP Admin)\n";
		}
		$body .= "\nView in WordPress: " . $admin_url . "\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( is_email( $contact ) ) {
			$headers[] = 'Reply-To: ' . $name . ' <' . $contact . '>';
		}

		wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Secure download endpoint for reports in WP Admin.
	 */
	public static function download_file() {
		if ( ! isset( $_GET['shm_download_report'] ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'safak-hospitals' ), 403 );
		}

		$post_id = absint( $_GET['shm_download_report'] );
		check_admin_referer( 'shm_download_' . $post_id );

		$path = get_post_meta( $post_id, '_inquiry_file_path', true );
		$name = get_post_meta( $post_id, '_inquiry_file_name', true );
		$mime = get_post_meta( $post_id, '_inquiry_file_mime', true );

		if ( ! $path || ! file_exists( $path ) ) {
			wp_die( esc_html__( 'File not found.', 'safak-hospitals' ), 404 );
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . ( $mime ? $mime : 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $name ? $name : basename( $path ) ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path );
		exit;
	}

	/* ─────────────── Admin columns & meta box ─────────────── */

	/**
	 * Columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		return array(
			'cb'            => '<input type="checkbox" />',
			'title'         => __( 'Patient Name', 'safak-hospitals' ),
			'shm_contact'   => __( 'Contact', 'safak-hospitals' ),
			'shm_hospital'  => __( 'Hospital', 'safak-hospitals' ),
			'shm_report'    => __( 'Report', 'safak-hospitals' ),
			'shm_lang'      => __( 'Lang', 'safak-hospitals' ),
			'date'          => __( 'Date', 'safak-hospitals' ),
		);
	}

	/**
	 * Column content.
	 *
	 * @param string $col     Column.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $col, $post_id ) {
		switch ( $col ) {
			case 'shm_contact':
				echo esc_html( get_post_meta( $post_id, '_inquiry_contact', true ) );
				break;
			case 'shm_hospital':
				$hid  = get_post_meta( $post_id, '_inquiry_hospital_id', true );
				$name = get_post_meta( $post_id, '_inquiry_hospital_name', true );
				if ( $hid && get_post( $hid ) ) {
					echo '<a href="' . esc_url( get_edit_post_link( $hid ) ) . '">' . esc_html( $name ) . '</a>';
				} else {
					echo esc_html( $name ? $name : '—' );
				}
				break;
			case 'shm_report':
				$file = get_post_meta( $post_id, '_inquiry_file_name', true );
				if ( $file ) {
					$url = wp_nonce_url( admin_url( 'index.php?shm_download_report=' . $post_id ), 'shm_download_' . $post_id );
					echo '<a href="' . esc_url( $url ) . '" class="button button-small"><span class="dashicons dashicons-download"></span> ' . esc_html( $file ) . '</a>';
				} else {
					echo '—';
				}
				break;
			case 'shm_lang':
				echo '<span class="shm-col-badge">' . esc_html( strtoupper( (string) get_post_meta( $post_id, '_inquiry_lang', true ) ) ) . '</span>';
				break;
		}
	}

	/**
	 * Inquiry details meta box.
	 */
	public static function add_meta_box() {
		add_meta_box(
			'shm_inquiry_meta',
			__( 'Inquiry Details', 'safak-hospitals' ),
			array( __CLASS__, 'render_details' ),
			SHM_INQUIRY_POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render inquiry details.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_details( $post ) {
		$name      = get_post_meta( $post->ID, '_inquiry_name', true );
		$contact   = get_post_meta( $post->ID, '_inquiry_contact', true );
		$hospital  = get_post_meta( $post->ID, '_inquiry_hospital_name', true );
		$hid       = get_post_meta( $post->ID, '_inquiry_hospital_id', true );
		$msg       = get_post_meta( $post->ID, '_inquiry_message', true );
		$lang      = get_post_meta( $post->ID, '_inquiry_lang', true );
		$file_name = get_post_meta( $post->ID, '_inquiry_file_name', true );
		$file_size = get_post_meta( $post->ID, '_inquiry_file_size', true );
		$ip        = get_post_meta( $post->ID, '_inquiry_ip', true );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Patient Name', 'safak-hospitals' ); ?></th>
				<td><strong><?php echo esc_html( $name ); ?></strong></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Contact info', 'safak-hospitals' ); ?></th>
				<td>
					<?php if ( is_email( $contact ) ) : ?>
						<a href="mailto:<?php echo esc_attr( $contact ); ?>"><?php echo esc_html( $contact ); ?></a>
					<?php else : ?>
						<?php
						$digits = preg_replace( '/\D+/', '', $contact );
						if ( 0 === strpos( $digits, '00' ) ) {
							$digits = substr( $digits, 2 );
						}
						?>
						<a href="tel:<?php echo esc_attr( $contact ); ?>"><?php echo esc_html( $contact ); ?></a>
						<?php if ( $digits ) : ?>
							&nbsp;|&nbsp;
							<a href="https://wa.me/<?php echo esc_attr( $digits ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small"><span class="dashicons dashicons-whatsapp"></span> WhatsApp</a>
						<?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Hospital', 'safak-hospitals' ); ?></th>
				<td>
					<?php if ( $hid && get_post( $hid ) ) : ?>
						<a href="<?php echo esc_url( get_edit_post_link( $hid ) ); ?>"><?php echo esc_html( $hospital ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $hospital ? $hospital : '—' ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Language', 'safak-hospitals' ); ?></th>
				<td><?php echo esc_html( strtoupper( (string) $lang ) ); ?></td>
			</tr>
			<?php if ( $msg ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Message', 'safak-hospitals' ); ?></th>
					<td><?php echo nl2br( esc_html( $msg ) ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( $file_name ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Medical report', 'safak-hospitals' ); ?></th>
					<td>
						<?php
						$url = wp_nonce_url( admin_url( 'index.php?shm_download_report=' . $post->ID ), 'shm_download_' . $post->ID );
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="button button-primary">
							<span class="dashicons dashicons-download"></span>
							<?php echo esc_html( sprintf( __( 'Download %s (%s)', 'safak-hospitals' ), $file_name, size_format( (int) $file_size ) ) ); ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'IP Address', 'safak-hospitals' ); ?></th>
				<td><code><?php echo esc_html( $ip ? $ip : '—' ); ?></code></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Add "Export Inquiries to CSV" button on inquiries list table.
	 *
	 * @param string $which Top or bottom tablenav.
	 */
	public static function add_export_button( $which ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( 'top' !== $which || ! $screen || SHM_INQUIRY_POST_TYPE !== $screen->post_type ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'edit.php?post_type=' . SHM_INQUIRY_POST_TYPE . '&shm_export_inquiries=1' ), 'shm_export_csv' );
		?>
		<div class="alignleft actions">
			<a href="<?php echo esc_url( $url ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-media-spreadsheet" style="vertical-align: middle; margin-top: -2px;"></span>
				<?php esc_html_e( 'Export Inquiries to CSV', 'safak-hospitals' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Export inquiries to CSV (with UTF-8 BOM for clean Arabic and French rendering in Excel).
	 */
	public static function export_csv() {
		if ( ! isset( $_GET['shm_export_inquiries'] ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'safak-hospitals' ), 403 );
		}
		check_admin_referer( 'shm_export_csv' );

		$filename = sprintf( 'safak-hospital-inquiries-%s.csv', wp_date( 'Y-m-d' ) );

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// UTF-8 BOM so Excel opens Arabic and accented French characters cleanly
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		fputcsv(
			$output,
			array(
				'ID',
				'Date',
				'Patient Name',
				'Contact Info',
				'Hospital Name',
				'Language',
				'Message',
				'Medical Report Attached',
				'IP Address',
			)
		);

		$inquiries = get_posts(
			array(
				'post_type'      => SHM_INQUIRY_POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $inquiries as $inq ) {
			$post_id   = $inq->ID;
			$has_file  = get_post_meta( $post_id, '_inquiry_file_name', true ) ? 'Yes (' . get_post_meta( $post_id, '_inquiry_file_name', true ) . ')' : 'No';

			fputcsv(
				$output,
				array(
					$post_id,
					get_the_date( 'Y-m-d H:i:s', $inq ),
					get_post_meta( $post_id, '_inquiry_name', true ),
					get_post_meta( $post_id, '_inquiry_contact', true ),
					get_post_meta( $post_id, '_inquiry_hospital_name', true ),
					strtoupper( (string) get_post_meta( $post_id, '_inquiry_lang', true ) ),
					get_post_meta( $post_id, '_inquiry_message', true ),
					$has_file,
					get_post_meta( $post_id, '_inquiry_ip', true ),
				)
			);
		}

		fclose( $output );
		exit;
	}
}

