<?php
/**
 * Front-end strings in Arabic (default), French and English.
 *
 * Built-in translations are shipped for all three languages. Every string is
 * also registered in Polylang (Languages → Translations, group "Safak Hospitals")
 * and WPML String Translation, so editors can override any wording without code.
 *
 * @package SafakHospitals
 */

defined( 'ABSPATH' ) || exit;

/**
 * Translation registry.
 */
class SHM_I18n {

	const GROUP = 'Safak Hospitals';

	/**
	 * Cached dictionary.
	 *
	 * @var array|null
	 */
	private static $strings = null;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_strings' ), 20 );
	}

	/**
	 * Register strings with Polylang / WPML (admin only, where translation UIs live).
	 */
	public static function register_strings() {
		if ( ! is_admin() ) {
			return;
		}
		foreach ( self::strings() as $key => $values ) {
			$source = $values['ar'];
			if ( function_exists( 'pll_register_string' ) ) {
				pll_register_string( 'shm_' . $key, $source, self::GROUP, mb_strlen( $source ) > 60 );
			}
			do_action( 'wpml_register_single_string', 'safak-hospitals', 'shm_' . $key, $source );
		}
	}

	/**
	 * Check whether a string contains Arabic script characters.
	 *
	 * Used to prevent Arabic overrides or database fallbacks from leaking into
	 * French or English pages.
	 *
	 * @param string $text Text to inspect.
	 * @return bool
	 */
	public static function has_arabic_chars( $text ) {
		if ( ! is_string( $text ) || '' === $text ) {
			return false;
		}
		return (bool) preg_match( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text );
	}

	/**
	 * Translated string.
	 *
	 * Resolution order: Polylang/WPML override (validated) → built-in dictionary → key.
	 *
	 * @param string $key  String key.
	 * @param string $lang Language (defaults to current).
	 * @return string
	 */
	public static function get( $key, $lang = '' ) {
		$strings = self::strings();
		$lang    = $lang ? shm_normalize_lang( $lang ) : shm_lang();

		if ( ! isset( $strings[ $key ] ) ) {
			return $key;
		}

		$source  = isset( $strings[ $key ]['ar'] ) ? $strings[ $key ]['ar'] : '';
		$builtin = isset( $strings[ $key ][ $lang ] ) ? $strings[ $key ][ $lang ] : $source;
		$value   = $builtin;

		// Editor override through Polylang string translations.
		$override = null;
		if ( function_exists( 'pll_translate_string' ) && '' !== $source ) {
			$raw_override = pll_translate_string( $source, $lang );
			if ( is_string( $raw_override ) && '' !== trim( $raw_override ) && $raw_override !== $source ) {
				$override = $raw_override;
			}
		} elseif ( has_filter( 'wpml_translate_single_string' ) && '' !== $source ) {
			$raw_override = apply_filters( 'wpml_translate_single_string', $source, 'safak-hospitals', 'shm_' . $key, $lang );
			if ( is_string( $raw_override ) && '' !== trim( $raw_override ) && $raw_override !== $source ) {
				$override = $raw_override;
			}
		}

		// Strictly reject Arabic string overrides when requesting French or English.
		if ( is_string( $override ) ) {
			if ( 'ar' !== $lang && self::has_arabic_chars( $override ) ) {
				// Discard contaminated override; retain builtin translation.
			} else {
				$value = $override;
			}
		}

		// Final safeguard: if French or English is requested but the value still contains Arabic,
		// fall back to the built-in non-Arabic translation or English.
		if ( 'ar' !== $lang && self::has_arabic_chars( $value ) ) {
			if ( isset( $strings[ $key ][ $lang ] ) && ! self::has_arabic_chars( $strings[ $key ][ $lang ] ) ) {
				$value = $strings[ $key ][ $lang ];
			} elseif ( isset( $strings[ $key ]['en'] ) && ! self::has_arabic_chars( $strings[ $key ]['en'] ) ) {
				$value = $strings[ $key ]['en'];
			}
		}

		$filtered = apply_filters( 'shm_string', $value, $key, $lang );
		if ( 'ar' !== $lang && self::has_arabic_chars( $filtered ) ) {
			return $builtin;
		}

		return (string) $filtered;
	}

	/**
	 * All strings for one language (used for JS localisation).
	 *
	 * @param string[] $keys Keys.
	 * @param string   $lang Language.
	 * @return array
	 */
	public static function subset( $keys, $lang ) {
		$out = array();
		foreach ( $keys as $key ) {
			$out[ $key ] = self::get( $key, $lang );
		}
		return $out;
	}

	/**
	 * Dictionary.
	 *
	 * @return array key => [ar, fr, en]
	 */
	public static function strings() {
		if ( null !== self::$strings ) {
			return self::$strings;
		}

		$s = array(
			/* Hero & CTAs */
			'book_whatsapp'         => array( 'ar' => 'احجز عبر واتساب', 'fr' => 'Réserver via WhatsApp', 'en' => 'Book via WhatsApp' ),
			'free_consultation'     => array( 'ar' => 'اطلب استشارة مجانية', 'fr' => 'Consultation gratuite', 'en' => 'Request Free Consultation' ),
			'partner_badge'         => array( 'ar' => 'مستشفى شريك معتمد لصفاك ميديكال', 'fr' => 'Hôpital partenaire accrédité de Safak Medical', 'en' => 'Safak Medical Accredited Partner Hospital' ),
			'hero_eyebrow_default'  => array( 'ar' => 'شريككم الموثوق للرعاية الطبية المتقدمة في تركيا', 'fr' => 'Votre partenaire de confiance pour les soins médicaux de pointe en Turquie', 'en' => 'Your Trusted Partner for Advanced Medical Care in Turkey' ),
			'hero_trust_1'          => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),
			'hero_trust_2'          => array( 'ar' => 'نخبة الأطباء والاستشاريين', 'fr' => 'Spécialistes de renom', 'en' => 'Top Specialist Doctors' ),
			'hero_trust_3'          => array( 'ar' => 'مرافقة وترجمة 24/7', 'fr' => 'Assistance & traduction 24/7', 'en' => '24/7 Care & Translation' ),
			'jci'                   => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),
			'jci_full'              => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),
			'moh'                   => array( 'ar' => 'تميّز وزارة الصحة', 'fr' => 'Excellence ministère de la Santé', 'en' => 'Ministry of Health Excellence' ),
			'moh_full'              => array( 'ar' => 'شارة التميّز من وزارة الصحة التركية', 'fr' => 'Label d’excellence du ministère turc de la Santé', 'en' => 'Turkish Ministry of Health Excellence Badge' ),
			'intl_center'           => array( 'ar' => 'مركز المرضى الدوليين', 'fr' => 'Centre patients internationaux', 'en' => 'International Patient Center' ),
			'turkey'                => array( 'ar' => 'تركيا', 'fr' => 'Turquie', 'en' => 'Turkey' ),
			'home'                  => array( 'ar' => 'الرئيسية', 'fr' => 'Accueil', 'en' => 'Home' ),
			'hospitals'             => array( 'ar' => 'المستشفيات', 'fr' => 'Hôpitaux', 'en' => 'Hospitals' ),
			'breadcrumb'            => array( 'ar' => 'مسار التنقل', 'fr' => 'Fil d’Ariane', 'en' => 'Breadcrumb' ),
			'hospitals_in_turkey'   => array( 'ar' => 'أفضل المستشفيات في تركيا', 'fr' => 'Les meilleurs hôpitaux en Turquie', 'en' => 'Top Hospitals in Turkey' ),
			'hospitals_archive_sub' => array( 'ar' => 'مستشفيات شريكة معتمدة دولياً، مختارة بعناية لضمان أعلى معايير الجودة والرعاية الطبية.', 'fr' => 'Des hôpitaux partenaires accrédités à l’international, sélectionnés avec soin pour garantir les plus hauts standards de qualité et de soins.', 'en' => 'Internationally accredited partner hospitals, carefully selected to guarantee the highest standards of quality and care.' ),

			/* Presentation & Split Story */
			'pres_eyebrow'          => array( 'ar' => 'نبذة عن المستشفى', 'fr' => 'PRÉSENTATION DE L’HÔPITAL', 'en' => 'ABOUT THE HOSPITAL' ),
			'pres_title_em'         => array( 'ar' => 'للرعاية الطبية المتقدمة في تركيا.', 'fr' => 'pour les soins médicaux de pointe en Turquie.', 'en' => 'for advanced medical care in Turkey.' ),
			'pres_sub_default'      => array( 'ar' => 'نصل المرضى الدوليين بنخبة كبار الأساتذة والأطباء الاستشاريين، مع إزالة كافة العوائق الطبية واللغوية واللوجستية أمام المريض وعائلته، بدءاً من التقييم الدقيق للتقارير الطبية قبل السفر وحتى المتابعة الطبية المستمرة بعد العودة إلى أرض الوطن.', 'fr' => 'De l’analyse initiale de votre dossier médical avant le départ jusqu’à votre suivi post-opératoire après votre retour à domicile, une équipe médicale unifiée coordonne chaque étape de votre parcours avec clarté, rigueur et bienveillance.', 'en' => 'From the initial evaluation of your medical reports prior to travel to post-operative recovery and long-term follow-up after returning home, a unified medical team coordinates every step of your journey with clarity and peace of mind.' ),
			'story_eyebrow'         => array( 'ar' => 'التميز السريري والرعاية المتكاملة', 'fr' => 'EXCELLENCE CLINIQUE & SOINS INTÉGRÉS', 'en' => 'CLINICAL EXCELLENCE & INTEGRATED CARE' ),
			'story_title'           => array( 'ar' => 'رعاية طبية عالمية المستوى بأحدث التقنيات التشخيصية والجراحية.', 'fr' => 'Des soins médicaux de classe mondiale avec les technologies chirurgicales les plus avancées.', 'en' => 'World-class medical care powered by the latest diagnostic and surgical technologies.' ),
			'editorial_kicker'      => array( 'ar' => 'رؤيتنا والتزامنا الطبي', 'fr' => 'Notre Vision & Engagement Médical', 'en' => 'Our Vision & Clinical Commitment' ),
			'editorial_title'       => array( 'ar' => 'رعاية طبية تبدأ بالاستماع إليك وترافقك في كل خطوة', 'fr' => 'Des soins médicaux qui commencent par l’écoute et vous accompagnent à chaque étape', 'en' => 'Medical care that starts with listening to you and supports you at every step' ),
			'editorial_quote'       => array( 'ar' => '"وعدنا في شفق ميديكال أن لا يشعر المريض أبداً بالغربة؛ فنحن عائلته الطبية ومستشاره الأمين من لحظة إرسال التقارير وحتى تمام الشفاء."', 'fr' => '"Notre engagement chez Safak Medical est qu’aucun patient ne se sente jamais seul loin de chez lui : nous sommes votre famille médicale et votre conseiller de confiance."', 'en' => '"Our promise at Safak Medical is that no patient ever feels alone abroad—we are your medical family and trusted advocate from your first report to full recovery."' ),
			'dept_kicker'           => array( 'ar' => 'التخصصات والتقنيات الطبية', 'fr' => 'Spécialités & Technologies Médicales', 'en' => 'Medical Specialties & Technology' ),
			'dept_headline'         => array( 'ar' => 'أقسام تخصصية متكاملة وبنية تحتية طبية متطورة.', 'fr' => 'Des départements spécialisés intégrés et un plateau technique de pointe.', 'en' => 'Integrated specialized departments and state-of-the-art clinical infrastructure.' ),
			'dept_header_desc'      => array( 'ar' => 'تضم المستشفى مراكز تميز طبية مجهزة بأحدث الأنظمة الروبوتية والتشخيصية لإجراء أدق العمليات الجراحية وفق أعلى المعايير العالمية.', 'fr' => 'L’hôpital regroupe des centres d’excellence équipés des systèmes robotiques et diagnostiques les plus récents pour les interventions complexes.', 'en' => 'The hospital houses specialized centers of excellence equipped with advanced robotic and diagnostic systems for complex surgical procedures.' ),
			'doctors_kicker'        => array( 'ar' => 'الطاقم الطبي والاستشاريون', 'fr' => 'Corps Médical & Professeurs', 'en' => 'Medical Team & Specialists' ),
			'doctors_headline'      => array( 'ar' => 'نخبة من كبار البروفيسورات والأطباء المتخصصين.', 'fr' => 'Une élite de professeurs et de médecins spécialistes.', 'en' => 'Leading professors and board-certified specialist doctors.' ),
			'doctors_header_desc'   => array( 'ar' => 'يشرف على علاجكم أطباء استشاريون ذوو خبرة دولية واسعة وسجل حافل بالعمليات الدقيقة الناجحة.', 'fr' => 'Votre prise en charge est assurée par des spécialistes de renommée internationale au parcours clinique éprouvé.', 'en' => 'Your care is led by internationally recognized consultants with a proven track record in complex procedures.' ),
			'gallery_kicker'        => array( 'ar' => 'مرافق المستشفى من الداخل', 'fr' => 'Installations de l’hôpital', 'en' => 'Hospital Facilities' ),
			'gallery_headline'      => array( 'ar' => 'جولة مصورة داخل أقسام وأجنحة المستشفى.', 'fr' => 'Visite en images des départements et suites de l’hôpital.', 'en' => 'A visual tour inside the hospital’s departments and patient suites.' ),
			'gallery_sub_desc'      => array( 'ar' => 'بيئة استشفائية عصرية تجمع بين أعلى معايير الأمان الطبي والراحة الفندقية المتكاملة للمريض ومرافقيه.', 'fr' => 'Un environnement hospitalier moderne alliant sécurité clinique maximale et confort hôtelier pour le patient et ses proches.', 'en' => 'A modern healing environment combining maximum clinical safety with hotel-grade comfort for patients and companions.' ),
			'view_all_photos'       => array( 'ar' => 'عرض جميع الصور', 'fr' => 'Voir toutes les photos', 'en' => 'View All Photos' ),
			'gal_cap_1'             => array( 'ar' => 'المبنى الرئيسي والواجهة الخارجية', 'fr' => 'Bâtiment principal & façade', 'en' => 'Main Hospital Building & Exterior' ),
			'gal_cap_2'             => array( 'ar' => 'أجنحة وغرف المرضى الخاصة (VIP)', 'fr' => 'Suites & chambres privées VIP', 'en' => 'VIP Private Patient Suites' ),
			'gal_cap_3'             => array( 'ar' => 'غرف العمليات الجراحية المتطورة', 'fr' => 'Blocs opératoires de pointe', 'en' => 'Advanced Operating Theatres' ),
			'gal_cap_4'             => array( 'ar' => 'مركز التشخيص والأشعة المتقدمة', 'fr' => 'Centre d’imagerie & diagnostic avancé', 'en' => 'Advanced Diagnostic & Imaging Center' ),
			'gal_cap_5'             => array( 'ar' => 'الاستقبال ومكتب المرضى الدوليين', 'fr' => 'Accueil & bureau patients internationaux', 'en' => 'Reception & International Patient Desk' ),
			'services_tag'          => array( 'ar' => 'خدمات المرضى الدوليين', 'fr' => 'SERVICES AUX PATIENTS INTERNATIONAUX', 'en' => 'INTERNATIONAL PATIENT SERVICES' ),
			'services_title_1'      => array( 'ar' => 'أكثر من مجرد علاج.', 'fr' => 'Bien plus qu’un traitement.', 'en' => 'More than just treatment.' ),
			'services_title_2'      => array( 'ar' => 'دعم مستمر طوال فترة إقامتك.', 'fr' => 'Un soutien continu durant tout votre séjour.', 'en' => 'Continuous support throughout your stay.' ),
			'services_desc'         => array( 'ar' => 'يساعدك منسقو المرضى لدينا في تيسير كافة الجوانب العملية واللوجستية لرحلتك العلاجية بكل راحة وسلاسة واطمئنان.', 'fr' => 'Nos coordinateurs de patients vous accompagnent dans tous les aspects pratiques et logistiques de votre séjour médical en toute sérénité.', 'en' => 'Our patient coordinators help facilitate all practical and logistical aspects of your medical journey with comfort, ease, and peace of mind.' ),
			'cta_tag'               => array( 'ar' => 'ابدأ رحلتك العلاجية', 'fr' => 'COMMENCEZ VOTRE PARCOURS DE SOINS', 'en' => 'START YOUR MEDICAL JOURNEY' ),
			'cta_heading'           => array( 'ar' => 'لنخطُ الخطوة التالية معاً.', 'fr' => 'Franchissons la prochaine étape ensemble.', 'en' => 'Let’s take the next step together.' ),
			'cta_desc'              => array( 'ar' => 'أرسل تقاريرك الطبية وسيقوم فريقنا الاستشاري بمساعدتك في فهم خياراتك العلاجية وترتيب رحلتك خطوة بخطوة.', 'fr' => 'Envoyez vos rapports médicaux et notre équipe consultative vous aidera à comprendre vos options de traitement et à organiser votre séjour étape par étape.', 'en' => 'Send your medical reports and our advisory team will help you understand your treatment options and organize your journey step by step.' ),
			'cta_btn'               => array( 'ar' => 'طلب استشارة طبية', 'fr' => 'Demander une Consultation', 'en' => 'Request a Medical Consultation' ),

			/* Statistics */
			'stats_title'           => array( 'ar' => 'المستشفى بالأرقام', 'fr' => 'L’hôpital en chiffres', 'en' => 'The Hospital in Numbers' ),
			'stat_beds'             => array( 'ar' => 'سرير', 'fr' => 'Lits', 'en' => 'Beds' ),
			'stat_rooms'            => array( 'ar' => 'غرفة وجناح خاص', 'fr' => 'Chambres & suites privées', 'en' => 'Private Rooms & Suites' ),
			'stat_icu'              => array( 'ar' => 'سرير عناية مركزة', 'fr' => 'Lits de réanimation', 'en' => 'ICU Beds' ),
			'stat_theatres'         => array( 'ar' => 'غرفة عمليات', 'fr' => 'Blocs opératoires', 'en' => 'Operating Theatres' ),
			'stat_doctors'          => array( 'ar' => 'طبيب', 'fr' => 'Médecins', 'en' => 'Doctors' ),
			'stat_experience'       => array( 'ar' => 'عاماً من الخبرة', 'fr' => 'Années d’expérience', 'en' => 'Years of Experience' ),
			'stat_surgeries'        => array( 'ar' => 'عملية جراحية سنوياً', 'fr' => 'Chirurgies par an', 'en' => 'Surgeries per Year' ),

			/* Sections */
			'overview'              => array( 'ar' => 'نبذة عن المستشفى', 'fr' => 'Présentation de l’hôpital', 'en' => 'About the Hospital' ),
			'toc'                   => array( 'ar' => 'محتويات الصفحة', 'fr' => 'Sommaire', 'en' => 'Table of Contents' ),
			'gallery'               => array( 'ar' => 'معرض الصور', 'fr' => 'Galerie photos', 'en' => 'Photo Gallery' ),
			'more_photos'           => array( 'ar' => 'صور إضافية', 'fr' => 'photos de plus', 'en' => 'more photos' ),
			'video_kicker'          => array( 'ar' => 'جولة افتراضية', 'fr' => 'Visite virtuelle', 'en' => 'Virtual Tour' ),
			'video'                 => array( 'ar' => 'جولة افتراضية بالفيديو', 'fr' => 'Visite virtuelle en vidéo', 'en' => 'Virtual Video Tour' ),
			'play_video'            => array( 'ar' => 'مشاهدة جولة الفيديو', 'fr' => 'Voir la visite vidéo', 'en' => 'Watch Video Tour' ),
			'related_kicker'        => array( 'ar' => 'مستشفيات معتمدة', 'fr' => 'Hôpitaux partenaires', 'en' => 'Partner Hospitals' ),
			'departments'           => array( 'ar' => 'الأقسام والبنية التحتية', 'fr' => 'Départements & infrastructures', 'en' => 'Departments & Infrastructure' ),
			'departments_list'      => array( 'ar' => 'الأقسام الطبية', 'fr' => 'Départements médicaux', 'en' => 'Medical Departments' ),
			'infrastructure_list'   => array( 'ar' => 'التجهيزات والتقنيات الطبية', 'fr' => 'Équipements & technologies', 'en' => 'Equipment & Technology' ),
			'specialties'           => array( 'ar' => 'التخصصات', 'fr' => 'Spécialités', 'en' => 'Specialties' ),
			'doctors'               => array( 'ar' => 'أطباء المستشفى', 'fr' => 'Médecins de l’hôpital', 'en' => 'Doctors at This Hospital' ),
			'view_profile'          => array( 'ar' => 'عرض الملف الشخصي', 'fr' => 'Voir le profil', 'en' => 'View Profile' ),
			'amenities'             => array( 'ar' => 'خدمات ومرافق المرضى', 'fr' => 'Services & commodités patients', 'en' => 'Patient Services & Amenities' ),
			'included'              => array( 'ar' => 'متوفر', 'fr' => 'Inclus', 'en' => 'Included' ),
			/* Location & Directions */
			'location'              => array( 'ar' => 'الموقع والاتجاهات', 'fr' => 'Localisation & itinéraire', 'en' => 'Location & Directions' ),
			'get_directions'        => array( 'ar' => 'احصل على الاتجاهات', 'fr' => 'Obtenir l’itinéraire', 'en' => 'Get Directions' ),
			'map_title'             => array( 'ar' => 'خريطة موقع المستشفى', 'fr' => 'Carte de localisation de l’hôpital', 'en' => 'Hospital location map' ),
			'chat_whatsapp'         => array( 'ar' => 'تواصل فوراً عبر واتساب', 'fr' => 'Discuter sur WhatsApp', 'en' => 'Chat on WhatsApp' ),

			/* Sidebar */
			'glance'                => array( 'ar' => 'المستشفى في لمحة', 'fr' => 'L’hôpital en bref', 'en' => 'Hospital at a Glance' ),
			'city'                  => array( 'ar' => 'المدينة', 'fr' => 'Ville', 'en' => 'City' ),
			'district'              => array( 'ar' => 'المنطقة', 'fr' => 'Quartier', 'en' => 'District' ),
			'address'               => array( 'ar' => 'العنوان', 'fr' => 'Adresse', 'en' => 'Address' ),
			'category'              => array( 'ar' => 'نوع المستشفى', 'fr' => 'Type d’hôpital', 'en' => 'Hospital type' ),
			'established'           => array( 'ar' => 'سنة التأسيس', 'fr' => 'Fondé en', 'en' => 'Established' ),
			'beds'                  => array( 'ar' => 'عدد الأسرّة', 'fr' => 'Nombre de lits', 'en' => 'Beds' ),
			'accreditations'        => array( 'ar' => 'الاعتمادات', 'fr' => 'Accréditations', 'en' => 'Accreditations' ),
			'languages'             => array( 'ar' => 'لغات الترجمة', 'fr' => 'Langues d’interprétariat', 'en' => 'Interpreting languages' ),
			'languages_value'       => array( 'ar' => 'العربية، الفرنسية، الإنجليزية', 'fr' => 'Arabe, français, anglais', 'en' => 'Arabic, French, English' ),
			'whatsapp'              => array( 'ar' => 'واتساب', 'fr' => 'WhatsApp', 'en' => 'WhatsApp' ),
			'related'               => array( 'ar' => 'مستشفيات أخرى في تركيا', 'fr' => 'Autres hôpitaux en Turquie', 'en' => 'Other Hospitals in Turkey' ),

			/* Cards / grid */
			'view_hospital'         => array( 'ar' => 'تفاصيل المستشفى', 'fr' => 'Voir l’hôpital', 'en' => 'View Hospital' ),
			'no_hospitals'          => array( 'ar' => 'لا توجد مستشفيات متاحة حالياً.', 'fr' => 'Aucun hôpital disponible pour le moment.', 'en' => 'No hospitals available at the moment.' ),
			'beds_short'            => array( 'ar' => 'سرير', 'fr' => 'lits', 'en' => 'beds' ),
			'doctors_short'         => array( 'ar' => 'طبيب', 'fr' => 'médecins', 'en' => 'doctors' ),
			'theatres_short'        => array( 'ar' => 'غرفة عمليات', 'fr' => 'blocs', 'en' => 'ORs' ),
			'since'                 => array( 'ar' => 'منذ', 'fr' => 'Depuis', 'en' => 'Since' ),

			/* Lightbox */
			'close'                 => array( 'ar' => 'إغلاق', 'fr' => 'Fermer', 'en' => 'Close' ),
			'prev'                  => array( 'ar' => 'السابق', 'fr' => 'Précédent', 'en' => 'Previous' ),
			'next'                  => array( 'ar' => 'التالي', 'fr' => 'Suivant', 'en' => 'Next' ),

			/* WhatsApp */
			'whatsapp_message'      => array( 'ar' => 'مرحباً صفاك ميديكال، أرغب في الاستفسار عن العلاج في {hospital}.', 'fr' => 'Bonjour Safak Medical, je souhaite me renseigner sur un traitement à {hospital}.', 'en' => 'Hello Safak Medical, I would like to inquire about treatment at {hospital}.' ),

			/* Amenities */
			'am_airport_transfer'      => array( 'ar' => 'نقل VIP مجاني من المطار', 'fr' => 'Transfert VIP gratuit depuis l’aéroport', 'en' => 'Free Airport VIP Transfer' ),
			'am_airport_transfer_desc' => array( 'ar' => 'استقبال شخصي في المطار ونقل خاص إلى المستشفى والفندق.', 'fr' => 'Accueil personnalisé à l’aéroport et transfert privé vers l’hôpital et l’hôtel.', 'en' => 'Personal airport meet & greet with private transfer to the hospital and hotel.' ),
			'am_interpreters'          => array( 'ar' => 'مترجمون طبيون (العربية، الفرنسية، الإنجليزية)', 'fr' => 'Interprètes médicaux (arabe, français, anglais)', 'en' => 'Medical Interpreters (Arabic, French, English)' ),
			'am_interpreters_desc'     => array( 'ar' => 'مترجم طبي يرافقك في كل استشارة وفحص ومراجعة.', 'fr' => 'Un interprète médical vous accompagne à chaque consultation et examen.', 'en' => 'A medical interpreter accompanies you to every consultation and check-up.' ),
			'am_private_suites'        => array( 'ar' => 'أجنحة خاصة مع سرير للمرافق', 'fr' => 'Suites privées avec lit accompagnant', 'en' => 'Private Suites with Companion Beds' ),
			'am_private_suites_desc'   => array( 'ar' => 'غرف بمستوى فندقي تتيح لمرافقك البقاء بجانبك طوال فترة العلاج.', 'fr' => 'Des chambres de standing hôtelier pour que votre accompagnant reste à vos côtés.', 'en' => 'Hotel-style rooms so your companion can stay by your side throughout treatment.' ),
			'am_halal_food'            => array( 'ar' => 'طعام حلال وخدمة الغرف', 'fr' => 'Repas halal & service en chambre', 'en' => 'Halal Food & Room Service' ),
			'am_halal_food_desc'       => array( 'ar' => 'قوائم طعام حلال متنوعة تُقدَّم في غرفتك حسب نظامك الغذائي.', 'fr' => 'Des menus halal variés servis en chambre selon votre régime.', 'en' => 'Varied halal menus served in your room according to your diet.' ),
			'am_intl_desk'             => array( 'ar' => 'مكتب المرضى الدوليين 24/7', 'fr' => 'Bureau patients internationaux 24h/24', 'en' => '24/7 International Patient Desk' ),
			'am_intl_desk_desc'        => array( 'ar' => 'فريق مخصص متاح على مدار الساعة لمتابعة جميع احتياجاتك.', 'fr' => 'Une équipe dédiée disponible jour et nuit pour tous vos besoins.', 'en' => 'A dedicated team available around the clock for all your needs.' ),
			'am_wifi_sim'              => array( 'ar' => 'واي فاي مجاني ومساعدة في شريحة الاتصال', 'fr' => 'Wi-Fi gratuit & aide carte SIM', 'en' => 'Free Wi-Fi & SIM Card Assistance' ),
			'am_wifi_sim_desc'         => array( 'ar' => 'ابقَ على تواصل مع عائلتك بإنترنت مجاني وشريحة اتصال محلية.', 'fr' => 'Restez en contact avec vos proches grâce au Wi-Fi gratuit et à une carte SIM locale.', 'en' => 'Stay connected with family through free Wi-Fi and a local SIM card.' ),
		);

		self::$strings = apply_filters( 'shm_strings', $s );
		return self::$strings;
	}
}

