<?php
/**
 * Plugin settings: global WhatsApp number, fallback banner, notification email, SEO toggles.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings screen.
 */
class SHM_Settings {

	const OPTION = 'safak_hospitals_settings';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_tools' ) );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'whatsapp_number'   => SHM_DEFAULT_WHATSAPP,
			'consultation_url'  => '',
			'notification_email'=> get_option( 'admin_email' ),
			'enable_schema'     => 1,
			'enable_og'         => 1,
			'fallback_image_id' => 0,
		);
	}

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Add admin submenu.
	 */
	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . SHM_POST_TYPE,
			__( 'Hospital Settings — Safak Medical', 'safak-hospitals' ),
			__( 'Settings', 'safak-hospitals' ),
			'manage_options',
			'safak-hospitals-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting(
			'shm_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize input.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$out = self::defaults();
		if ( ! is_array( $input ) ) {
			return $out;
		}

		$phone = preg_replace( '/[^0-9+\s\-()]/', '', (string) ( isset( $input['whatsapp_number'] ) ? $input['whatsapp_number'] : '' ) );
		$out['whatsapp_number']   = trim( preg_replace( '/\s+/', ' ', $phone ) );
		$out['consultation_url']  = esc_url_raw( trim( (string) ( isset( $input['consultation_url'] ) ? $input['consultation_url'] : '' ) ) );
		$email = sanitize_email( (string) ( isset( $input['notification_email'] ) ? $input['notification_email'] : '' ) );
		$out['notification_email']= is_email( $email ) ? $email : get_option( 'admin_email' );
		$out['enable_schema']     = ! empty( $input['enable_schema'] ) ? 1 : 0;
		$out['enable_og']         = ! empty( $input['enable_og'] ) ? 1 : 0;
		$out['fallback_image_id'] = absint( isset( $input['fallback_image_id'] ) ? $input['fallback_image_id'] : 0 );

		return $out;
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$opts = self::all();
		?>
		<div class="wrap shm-admin-wrap">
			<h1><?php esc_html_e( 'Safak Hospitals Manager — Settings', 'safak-hospitals' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Global settings for partner hospitals, inquiry notifications and SEO markup.', 'safak-hospitals' ); ?></p>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'shm_settings_group' );
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="shm_whatsapp"><?php esc_html_e( 'Default WhatsApp number', 'safak-hospitals' ); ?></label></th>
						<td>
							<input type="text" id="shm_whatsapp" name="<?php echo esc_attr( self::OPTION ); ?>[whatsapp_number]" value="<?php echo esc_attr( $opts['whatsapp_number'] ); ?>" class="regular-text" dir="ltr">
							<p class="description"><?php esc_html_e( 'Used when a hospital does not have its own WhatsApp number configured. Default: +90 537 691 76 95', 'safak-hospitals' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="shm_consult_url"><?php esc_html_e( 'Free consultation URL', 'safak-hospitals' ); ?></label></th>
						<td>
							<input type="url" id="shm_consult_url" name="<?php echo esc_attr( self::OPTION ); ?>[consultation_url]" value="<?php echo esc_attr( $opts['consultation_url'] ); ?>" class="large-text" placeholder="https://safakmedical.com/contact/">
							<p class="description"><?php esc_html_e( 'Target of the "Request Free Consultation" button. On single hospital pages, leaving this blank scrolls smoothly to the on-page inquiry form.', 'safak-hospitals' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="shm_email"><?php esc_html_e( 'Inquiry notification email', 'safak-hospitals' ); ?></label></th>
						<td>
							<input type="email" id="shm_email" name="<?php echo esc_attr( self::OPTION ); ?>[notification_email]" value="<?php echo esc_attr( $opts['notification_email'] ); ?>" class="regular-text" dir="ltr">
							<p class="description"><?php esc_html_e( 'New patient inquiries sent from the single hospital sidebar will be emailed to this address.', 'safak-hospitals' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'SEO & Structured Data', 'safak-hospitals' ); ?></th>
						<td>
							<fieldset>
								<label for="shm_enable_schema">
									<input type="checkbox" id="shm_enable_schema" name="<?php echo esc_attr( self::OPTION ); ?>[enable_schema]" value="1" <?php checked( $opts['enable_schema'] ); ?>>
									<?php esc_html_e( 'Enable Schema.org JSON-LD structured data (Hospital & MedicalOrganization)', 'safak-hospitals' ); ?>
								</label>
								<br>
								<label for="shm_enable_og">
									<input type="checkbox" id="shm_enable_og" name="<?php echo esc_attr( self::OPTION ); ?>[enable_og]" value="1" <?php checked( $opts['enable_og'] ); ?>>
									<?php esc_html_e( 'Output Open Graph and Twitter Card meta tags on single hospital pages', 'safak-hospitals' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Fallback social share image', 'safak-hospitals' ); ?></th>
						<td>
							<?php
							$img_id = absint( $opts['fallback_image_id'] );
							$thumb  = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
							?>
							<div class="shm-setting-image" data-shm-single-image>
								<input type="hidden" name="<?php echo esc_attr( self::OPTION ); ?>[fallback_image_id]" value="<?php echo esc_attr( $img_id ); ?>" data-image-id>
								<div class="shm-setting-image-preview" data-image-preview>
									<?php if ( $thumb ) : ?>
										<img src="<?php echo esc_url( $thumb ); ?>" alt="" style="max-width:240px;height:auto;display:block;margin-bottom:8px;border-radius:8px;">
									<?php endif; ?>
								</div>
								<button type="button" class="button button-secondary" data-image-select><?php esc_html_e( 'Select image', 'safak-hospitals' ); ?></button>
								<button type="button" class="button-link" data-image-remove <?php echo ! $thumb ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'safak-hospitals' ); ?></button>
								<p class="description"><?php esc_html_e( 'Used when a hospital has no featured image.', 'safak-hospitals' ); ?></p>
							</div>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr style="margin: 40px 0 24px 0; border: none; border-top: 1px solid #cbd5e1;">

			<!-- TOOLS & ACTIONS -->
			<div class="shm-admin-tools">
				<h2><?php esc_html_e( 'Tools & Data Management', 'safak-hospitals' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Manage hospital data independently from third-party plugins (like SCF / ACF).', 'safak-hospitals' ); ?></p>

				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 16px;">
					<!-- 1. Demo Importer -->
					<div class="card" style="max-width: none; margin: 0; padding: 18px;">
						<h3 style="margin-top: 0;"><?php esc_html_e( 'Import Safak Medical Partner Hospitals', 'safak-hospitals' ); ?></h3>
						<p><?php esc_html_e( 'Quickly populate or update the 4 verified partner hospitals (Acibadem Fulya, Medical Park Florya, Memorial Bahcelievler, Medipol Bahcelievler) with their official stats, Arabic descriptions, and amenities directly into this plugin.', 'safak-hospitals' ); ?></p>
						<form method="post">
							<?php wp_nonce_field( 'shm_tools_action', 'shm_tools_nonce' ); ?>
							<input type="hidden" name="shm_action" value="import_demo">
							<button type="submit" class="button button-primary">
								<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -2px;"></span>
								<?php esc_html_e( 'Import / Update 4 Partner Hospitals', 'safak-hospitals' ); ?>
							</button>
						</form>
					</div>

					<!-- 2. SCF Migration -->
					<div class="card" style="max-width: none; margin: 0; padding: 18px;">
						<h3 style="margin-top: 0;"><?php esc_html_e( 'Migrate from SCF / ACF Fields', 'safak-hospitals' ); ?></h3>
						<p><?php esc_html_e( 'If your website previously stored hospital information in Secure Custom Fields (SCF / ACF), click below to copy all legacy fields into this plugin\'s custom meta fields.', 'safak-hospitals' ); ?></p>
						<form method="post" onsubmit="return confirm('Migrate legacy SCF fields into Safak Hospitals Manager?');">
							<?php wp_nonce_field( 'shm_tools_action', 'shm_tools_nonce' ); ?>
							<input type="hidden" name="shm_action" value="migrate_scf">
							<button type="submit" class="button button-secondary">
								<span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -2px;"></span>
								<?php esc_html_e( 'Migrate Old SCF Fields to Plugin', 'safak-hospitals' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle admin tools actions (demo import & SCF migration).
	 */
	public static function handle_tools() {
		if ( ! isset( $_POST['shm_tools_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['shm_tools_nonce'] ), 'shm_tools_action' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_POST['shm_action'] ) ? sanitize_key( $_POST['shm_action'] ) : '';

		if ( 'import_demo' === $action ) {
			self::seed_demo_hospitals();
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Safak Medical partner hospitals successfully imported and updated!', 'safak-hospitals' ) . '</strong></p></div>';
			} );
		}

		if ( 'migrate_scf' === $action ) {
			$count = self::migrate_legacy_scf();
			add_action( 'admin_notices', function() use ( $count ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( sprintf( __( 'Successfully migrated SCF data for %d hospital(s)!', 'safak-hospitals' ), $count ) ) . '</strong></p></div>';
			} );
		}
	}

	/**
	 * Migrate legacy SCF / ACF fields to plugin custom fields.
	 *
	 * @return int Number of posts migrated.
	 */
	public static function migrate_legacy_scf() {
		$posts = get_posts( array( 'post_type' => SHM_POST_TYPE, 'posts_per_page' => -1, 'post_status' => 'any' ) );
		$count = 0;

		foreach ( $posts as $p ) {
			$post_id = $p->ID;
			$migrated = false;

			// City / Location
			foreach ( array( 'hospital_location', 'location', 'city', 'hospital_city' ) as $k ) {
				$val = get_post_meta( $post_id, $k, true );
				if ( ! empty( $val ) && empty( get_post_meta( $post_id, '_hospital_city', true ) ) ) {
					update_post_meta( $post_id, '_hospital_city', sanitize_text_field( $val ) );
					$migrated = true;
					break;
				}
			}

			// Address
			$addr = get_post_meta( $post_id, 'address', true );
			if ( ! empty( $addr ) && empty( get_post_meta( $post_id, '_hospital_address', true ) ) ) {
				update_post_meta( $post_id, '_hospital_address', sanitize_text_field( $addr ) );
				$migrated = true;
			}

			// Accreditation
			foreach ( array( 'hospital_accreditation', 'accreditation' ) as $k ) {
				$acc = (string) get_post_meta( $post_id, $k, true );
				if ( ! empty( $acc ) ) {
					if ( false !== stripos( $acc, 'jci' ) ) {
						update_post_meta( $post_id, '_hospital_is_jci_accredited', '1' );
					}
					if ( false !== stripos( $acc, 'iso' ) ) {
						update_post_meta( $post_id, '_hospital_iso_cert', 'ISO 9001:2015' );
					}
					$migrated = true;
				}
			}

			// Beds
			foreach ( array( 'beds', 'beds_count', 'number_of_beds' ) as $k ) {
				$beds = get_post_meta( $post_id, $k, true );
				if ( ! empty( $beds ) && empty( get_post_meta( $post_id, '_hospital_beds_count', true ) ) ) {
					update_post_meta( $post_id, '_hospital_beds_count', absint( $beds ) );
					$migrated = true;
				}
			}

			if ( $migrated ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Seed the 4 official Safak Medical partner hospitals.
	 */
	public static function seed_demo_hospitals() {
		// Ensure city taxonomy term exists
		$city_term = term_exists( 'Istanbul', SHM_TAX_CITY );
		if ( ! $city_term ) {
			$city_term = wp_insert_term( 'Istanbul', SHM_TAX_CITY, array( 'slug' => 'istanbul' ) );
		}
		$city_id = is_array( $city_term ) ? $city_term['term_id'] : (int) $city_term;

		// Ensure category terms
		$cat_general = term_exists( 'General Hospital', SHM_TAX_CATEGORY );
		if ( ! $cat_general ) {
			$cat_general = wp_insert_term( 'General Hospital', SHM_TAX_CATEGORY, array( 'slug' => 'general-hospital' ) );
		}
		$cat_general_id = is_array( $cat_general ) ? $cat_general['term_id'] : (int) $cat_general;

		$hospitals_data = array(
			array(
				'slug'        => 'ajiabad-foulia-hospital',
				'title'       => 'مستشفى أجيبادم فوليا',
				'desc'        => 'مستشفى أكيبادم فوليا، الواقع في أحد أكثر مناطق إسطنبول مركزية، يوفر وصولاً سهلاً داخل المدينة. يقدم المستشفى خدمات طبية في جميع مجالات الطب، ويعمل بنهج موجه نحو المرضى في جميع الفروع بدءاً من مبناه وحتى تنوع خدماته. يضم المستشفى أحدث مراكز جراحة العظام والمفاصل والطب الرياضي في تركيا.',
				'excerpt'     => 'صرح طبي رائد يتميز بأحدث تقنيات التشخيص، وجراحة العظام، والطب الرياضي، مع سعة 104 أسرة وأحدث غرف العمليات بنظام الفلترة الجراحية الهوائية فائقة النقاء.',
				'district'    => 'بيشكتاش (Beşiktaş)',
				'city'        => 'إسطنبول (Istanbul)',
				'address'     => 'Dikilitaş Mah. Hakkı Yeten Cad. No:19, 34349 Beşiktaş/İstanbul',
				'lat'         => '41.0538',
				'lng'         => '29.0019',
				'beds'        => 104,
				'rooms'       => 80,
				'theatres'    => 7,
				'icu'         => 16,
				'doctors'     => 120,
				'surgeries'   => 4500,
				'year'        => 2010,
				'jci'         => '1',
				'iso'         => 'ISO 9001:2015',
				'moh'         => '1',
				'intl'        => '1',
				'departments' => "جراحة العظام والمفاصل\nالطب الرياضي وإصابات الملاعب\nجراحة العمود الفقري\nعلاج العقم وأطفال الأنابيب\nالأشعة التشخيصية المتقدمة\nالعلاج الطبيعي والتأهيل",
				'infra'       => "جهاز رنين مغناطيسي 1.5 تسلا\nأجهزة الماموغرافي ثلاثية الأبعاد\nغرف عمليات بنظام الفلترة الهوائية Laminar Flow\nمختبر تحاليل متكامل معتمد دولياً",
			),
			array(
				'slug'        => 'medical-floria-hospital',
				'title'       => 'مستشفى ميديكال فلوريا',
				'desc'        => 'مستشفى ميديكال بارك فلوريا مستشفى جامعي متكامل يتميز برعاية صحية رفيعة المستوى وفق المعايير الطبية الدولية. يضم المستشفى كوكبة من كبار الجراحين والاستشاريين في زراعة الأعضاء ونخاع العظم وعلاج الأورام وجراحة القلب المفتوح.',
				'excerpt'     => 'مستشفى جامعي متكامل يضم نخبة من كبار البروفيسورات وأحدث مراكز علاج الأورام المتقدمة، وزراعة الأعضاء، وجراحة القلب المفتوح والأوعية الدموية.',
				'district'    => 'فلوريا (Florya)',
				'city'        => 'إسطنبول (Istanbul)',
				'address'     => 'Beşyol Mah. Akasya Sok. No:4, 34295 Küçükçekmece/İstanbul',
				'lat'         => '40.9882',
				'lng'         => '28.7958',
				'beds'        => 300,
				'rooms'       => 220,
				'theatres'    => 13,
				'icu'         => 50,
				'doctors'     => 250,
				'surgeries'   => 11000,
				'year'        => 2012,
				'jci'         => '1',
				'iso'         => 'ISO 9001:2015',
				'moh'         => '1',
				'intl'        => '1',
				'departments' => "علاج وجراحة الأورام المتكاملة\nزراعة الأعضاء ونخاع العظم\nجراحة القلب والشرايين للأطفال والبالغين\nجراحة المخ والأعصاب المجهرية\nطب الأطفال وحديثي الولادة",
				'infra'       => "مسرع خطي لعلاج الأورام بالليزر ثلاثي الأبعاد\nقسطرة قلبية رقمية ثنائية المستوى\nوحدات عناية مركزة متطورة لكل الأعمار",
			),
			array(
				'slug'        => 'memorial-bahschilevler-2',
				'title'       => 'مستشفى ميموريال باهشيلفلر',
				'desc'        => 'أحد أحدث وأفخم المجمعات الطبية العالمية في تركيا، تم تصميمه كأول مستشفى في العالم يحصل على شهادة LEED البلاتينية للأبنية الصديقة للبيئة. يجمع المستشفى بين التكنولوجيا الطبية المتقدمة والخدمات الفندقية الفاخرة ذات السبع نجوم.',
				'excerpt'     => 'أحد أحدث وأفخم المجمعات الطبية العالمية الصديقة للبيئة الحاصلة على شهادة LEED البلاتينية، مجهز بأحدث تكنولوجيا الجراحة الروبوتية دافنشي.',
				'district'    => 'باهشيلفلر (Bahçelievler)',
				'city'        => 'إسطنبول (Istanbul)',
				'address'     => 'Bahçelievler Mah. E-5 Yanyol Cad. No:1, 34180 Bahçelievler/İstanbul',
				'lat'         => '41.0016',
				'lng'         => '28.8603',
				'beds'        => 320,
				'rooms'       => 240,
				'theatres'    => 15,
				'icu'         => 60,
				'doctors'     => 280,
				'surgeries'   => 12500,
				'year'        => 2018,
				'jci'         => '1',
				'iso'         => 'LEED Platinum',
				'moh'         => '1',
				'intl'        => '1',
				'departments' => "الجراحة الروبوتية دافنشي\nزراعة الكبد والكلى والبنكرياس\nأمراض القلب التداخلية والقسطرة\nأمراض الذكورة والعقم وأطفال الأنابيب\nجراحة التجميل والترميم",
				'infra'       => "جهاز الروبوت الجراحي دافنشي (Da Vinci Xi)\nرنين مغناطيسي 3 تسلا المفتوح واسع التجويف\nغرف عمليات ذكية بنظام التحكم الرقمي المتكامل",
			),
			array(
				'slug'        => 'medipol-bahcelievler-hospital',
				'title'       => 'مستشفى ميديپول باهشيلفلر',
				'desc'        => 'مستشفى ميديپول الجامعي في باهشيلفلر يوفر رعاية طبية متكاملة لجميع أفراد الأسرة مع أقسام متخصصة لعلاج الأورام وأمراض الدم وزراعة النخاع العظمي، إلى جانب مركز متخصص للمرضى الدوليين يضم مترجمين فوريين على مدار الساعة.',
				'excerpt'     => 'صرح طبي جامعي متكامل يتميز برعاية متقدمة للأورام، وزراعة نخاع العظم، وجراحة الأعصاب، بالإضافة إلى قسم دولي مخصص لاستقبال ومرافقة المرضى.',
				'district'    => 'باهشيلفلر (Bahçelievler)',
				'city'        => 'إسطنبول (Istanbul)',
				'address'     => 'Kocasinan Merkez Mah. Mahmutbey Cad. No:44, Bahçelievler/İstanbul',
				'lat'         => '41.0189',
				'lng'         => '28.8475',
				'beds'        => 250,
				'rooms'       => 180,
				'theatres'    => 10,
				'icu'         => 42,
				'doctors'     => 200,
				'surgeries'   => 8000,
				'year'        => 2016,
				'jci'         => '1',
				'iso'         => 'ISO 9001:2015',
				'moh'         => '1',
				'intl'        => '1',
				'departments' => "زراعة نخاع العظم للبالغين والأطفال\nجراحة المخ والأعصاب والعمود الفقري\nطب وجراحة العيون المتقدمة\nالعناية المركزة لحديثي الولادة\nجراحة السمنة والتمثيل الغذائي",
				'infra'       => "أحدث أجهزة التصوير المقطعي المحوسب متعدد الشرائح\nوحدات عزل خاصة لمرضى زراعة نخاع العظم\nمركز دولي مخصص للمرضى الأجانب",
			),
		);

		foreach ( $hospitals_data as $h ) {
			$existing = get_page_by_path( $h['slug'], OBJECT, SHM_POST_TYPE );
			$post_id  = $existing ? $existing->ID : 0;

			$post_args = array(
				'post_type'    => SHM_POST_TYPE,
				'post_title'   => $h['title'],
				'post_name'    => $h['slug'],
				'post_content' => $h['desc'],
				'post_excerpt' => $h['excerpt'],
				'post_status'  => 'publish',
			);

			if ( $post_id ) {
				$post_args['ID'] = $post_id;
				wp_update_post( $post_args );
			} else {
				$post_id = wp_insert_post( $post_args );
			}

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				continue;
			}

			// Taxonomies
			if ( $city_id ) {
				wp_set_object_terms( $post_id, array( (int) $city_id ), SHM_TAX_CITY, false );
			}
			if ( $cat_general_id ) {
				wp_set_object_terms( $post_id, array( (int) $cat_general_id ), SHM_TAX_CATEGORY, false );
			}

			// Custom Plugin Meta
			update_post_meta( $post_id, '_hospital_beds_count', $h['beds'] );
			update_post_meta( $post_id, '_hospital_rooms_count', $h['rooms'] );
			update_post_meta( $post_id, '_hospital_operating_theatres', $h['theatres'] );
			update_post_meta( $post_id, '_hospital_icu_beds', $h['icu'] );
			update_post_meta( $post_id, '_hospital_doctors_count', $h['doctors'] );
			update_post_meta( $post_id, '_hospital_annual_surgeries', $h['surgeries'] );
			update_post_meta( $post_id, '_hospital_established_year', $h['year'] );

			update_post_meta( $post_id, '_hospital_is_jci_accredited', $h['jci'] );
			update_post_meta( $post_id, '_hospital_iso_cert', $h['iso'] );
			update_post_meta( $post_id, '_hospital_moh_excellence', $h['moh'] );
			update_post_meta( $post_id, '_hospital_intl_patient_center', $h['intl'] );

			update_post_meta( $post_id, '_hospital_address', $h['address'] );
			update_post_meta( $post_id, '_hospital_district', $h['district'] );
			update_post_meta( $post_id, '_hospital_city', $h['city'] );
			update_post_meta( $post_id, '_hospital_lat', $h['lat'] );
			update_post_meta( $post_id, '_hospital_lng', $h['lng'] );

			update_post_meta( $post_id, '_hospital_departments', $h['departments'] );
			update_post_meta( $post_id, '_hospital_infrastructure', $h['infra'] );
			update_post_meta( $post_id, '_hospital_amenities', array( 'airport_transfer', 'interpreters', 'private_suites', 'halal_food', 'intl_desk', 'wifi_sim' ) );
			update_post_meta( $post_id, '_hospital_whatsapp', SHM_DEFAULT_WHATSAPP );
			update_post_meta( $post_id, '_hospital_featured', '1' );
			if ( empty( get_post_meta( $post_id, '_hospital_gallery_urls', true ) ) ) {
				update_post_meta(
					$post_id,
					'_hospital_gallery_urls',
					"https://safakmedical.com/wp-content/uploads/2024/11/acibadem.webp\nhttps://safakmedical.com/wp-content/uploads/2024/11/memorial.webp\nhttps://safakmedical.com/wp-content/uploads/2024/11/medical-park.webp\nhttps://safakmedical.com/wp-content/uploads/2024/11/liv-e1731920290614.webp\nhttps://safakmedical.com/wp-content/uploads/2024/12/Snapinsta.app_469875971_18364790326141214_8384261363775878112_n_1080-1.webp"
				);
			}
		}
	}
}

