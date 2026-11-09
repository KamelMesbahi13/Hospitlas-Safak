# Safak Hospitals Manager (`safak-hospitals`)

**Safak Hospitals Manager** is a complete WordPress custom plugin engineered for **Safak Medical** (safakmedical.com). It provides an enterprise-grade hospital management dashboard, custom fields, frontend presentations, interactive statistics strips, associated doctor relationships, automated Schema.org medical JSON-LD SEO, seamless multilingual support (Arabic default with RTL, French, English), and native Elementor integration.

---

## 🌟 Key Features

### 1. Core Architecture & Custom Post Type
- **CPT Slug:** `hospitals` (with clean rewrite support for `/hospitals/`, `/fr/hospitals/`, `/en/hospitals/`).
- **Hierarchical Taxonomies:**
  - `hospital_categories`: Medical classification (General Hospital, Specialized Oncology, Eye Clinic, Dental Hospital, etc.). Includes Schema.org `medicalSpecialty` classification.
  - `hospital_cities`: Geographical location in Turkey (Istanbul, Ankara, Antalya, etc.).

### 2. Admin Dashboard Meta Box
A clean, tabbed administration interface on hospital edit screens:
- **Statistics & Capacity:**
  - Patient beds count (`_hospital_beds_count`)
  - Private suites & rooms count (`_hospital_rooms_count`)
  - Operating theatres (`_hospital_operating_theatres`)
  - ICU beds (`_hospital_icu_beds`)
  - Doctors & medical staff count (`_hospital_doctors_count`)
  - Annual surgeries performed (`_hospital_annual_surgeries`)
  - Year established (`_hospital_established_year`)
- **Accreditations & Certifications:**
  - JCI Accreditation checkbox (`_hospital_is_jci_accredited`)
  - ISO Certification (`_hospital_iso_cert`)
  - Ministry of Health Excellence badge (`_hospital_moh_excellence`)
  - International Patient Center badge (`_hospital_intl_patient_center`)
- **Location & Contact:**
  - Full street address & district
  - City display name
  - Latitude & Longitude coordinates
  - Google Maps embed URL
  - Direct WhatsApp booking phone number (default: `+90 537 691 76 95`)
  - Localized pre-filled WhatsApp inquiry message
- **Media & Virtual Tour:**
  - Video URL (YouTube facade click-to-load, Vimeo, or HTML5 MP4)
  - Photo Gallery (Multi-image uploader with drag-and-drop reordering)
- **Associated Doctors Relationship:**
  - Live AJAX searchable and sortable multi-select connected to the `doctors` CPT posts. Automatically links to doctor profile pages and syncs across translations.
- **Services & Amenities Checklists:**
  - Free Airport VIP Transfer
  - Multilingual Medical Interpreters (Arabic, French, English)
  - Private Patient Suites with Companion Beds
  - Halal Food & Room Service Options
  - 24/7 International Patient Desk
  - Free Wi-Fi & SIM Card Assistance

### 3. Frontend Single Hospital Template (`single-hospitals.php`)
Automatically loaded if the active theme does not have a template, styled with Safak Medical brand identity (`#01416D` Navy Blue, `#D30C1B` Accent Red, `#25D366` WhatsApp Green) while fully inheriting all typography and text styling directly from the website:
- **Hero Banner:** Hospital title, accreditation badges, location pin, quick WhatsApp booking and free consultation CTA buttons.
- **Key Statistics Strip:** Dynamic stat counter cards with animated counter numbers upon scrolling.
- **2:1 Layout Grid:**
  - **Main Column:**
    - Overview & About Hospital with auto-generated Table of Contents (TOC).
    - Responsive Photo Gallery Grid with modal lightbox.
    - Video Tour Embed with performance-friendly facade.
    - Department & Infrastructure Highlights.
    - Associated Doctors Grid linking to individual doctor profile pages.
    - Patient Services & Amenities grid with custom icons.
    - Location Map with directions button.
  - **Sticky Sidebar:**
    - Fast Inquiry Form with AJAX submission and secure medical report file upload (PDF/JPG/PNG up to 10MB) stored in a protected directory.
    - Hospital At a Glance summary card.
    - Related Hospitals in Turkey.

### 4. 100% Medical SEO & Schema.org JSON-LD
Automatically injected into `wp_head`:
- **Schema Types:** `Hospital` & `MedicalOrganization`
- **Properties:**
  - `@context`, `@type`, `@id`, `name`, `description`, `url`, `telephone`, `parentOrganization` (Safak Medical)
  - `address` (`PostalAddress` with `addressLocality`, `addressRegion`, `addressCountry: "TR"`)
  - `geo` (`GeoCoordinates`)
  - `numberOfBeds`
  - `medicalSpecialty` (mapped from category taxonomy)
  - `hasCredential` (JCI, ISO)
  - `availableService` (`MedicalService` items for amenities)
  - `employee` (Array of associated doctors with `@type: Physician`, name, URL, photo, and specialty)
- **Open Graph & Twitter Cards:** Full support for `og:type` (`business.business`), title, description, image, and `og:locale:alternate` for Polylang.

### 5. Shortcodes
Place anywhere in pages, posts, or Elementor:
- `[safak_hospitals columns="3" limit="-1" city="istanbul"]`:
  - Renders a responsive grid of hospital cards with photos, badges, statistics snippet, and direct profile/WhatsApp buttons.
- `[safak_hospital_stats id="123"]`:
  - Renders the statistics counter strip for a specific hospital (or current page if omitted).

### 6. Elementor Integration
Registers the **Safak Medical** widget category and provides two Elementor widgets:
- **Safak Hospitals Grid**: Visual controls for columns, post limit, city filter, category filter, order by, and order.
- **Safak Hospital Stats Strip**: Visual controls for selecting hospital, column layout, and maximum stats to show.

### 7. Multilingual Architecture
- Supports **Arabic (`ar`)** (default, full RTL), **French (`fr`)**, and **English (`en`)**.
- Built-in localized dictionary for all labels and strings.
- Integration with **Polylang** (`pll_register_string`, `pll_get_post_types`, `pll_copy_post_metas`) and **WPML**.
- Automatically synchronizes shared hospital meta and translates doctor relationships between languages.

---

## 📁 File Structure

```
safak-hospitals/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── includes/
│   ├── elementor/
│   │   ├── class-widget-hospital-stats.php
│   │   └── class-widget-hospitals-grid.php
│   ├── class-shm-admin.php
│   ├── class-shm-elementor.php
│   ├── class-shm-frontend.php
│   ├── class-shm-i18n.php
│   ├── class-shm-inquiries.php
│   ├── class-shm-post-type.php
│   ├── class-shm-seo.php
│   ├── class-shm-settings.php
│   ├── class-shm-shortcodes.php
│   └── helpers.php
├── languages/
│   └── safak-hospitals.pot
├── templates/
│   ├── parts/
│   │   ├── amenities.php
│   │   ├── card.php
│   │   ├── departments.php
│   │   ├── doctors.php
│   │   ├── gallery.php
│   │   ├── glance.php
│   │   ├── hero.php
│   │   ├── inquiry-form.php
│   │   ├── map.php
│   │   ├── related.php
│   │   ├── stats.php
│   │   └── video.php
│   ├── archive-hospitals.php
│   └── single-hospitals.php
├── README.md
└── safak-hospitals.php
```

---

## 🚀 Installation

1. Copy the `safak-hospitals` directory to your WordPress `wp-content/plugins/` directory.
2. Navigate to **Plugins > Installed Plugins** in the WordPress admin and click **Activate** on **Safak Hospitals Manager**.
3. Permalinks will be flushed automatically upon activation.
4. Go to **Hospitals > Settings** to verify the global WhatsApp booking phone number and notification email.
5. Create hospital posts under **Hospitals > Add Hospital**.

