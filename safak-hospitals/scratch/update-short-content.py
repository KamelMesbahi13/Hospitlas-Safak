# -*- coding: utf-8 -*-
path = r"safak-hospitals\includes\class-shm-i18n.php"
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace hero_trust and jci strings
target_trust = """\t\t\t'hero_trust_1'          => array( 'ar' => 'اعتماد دولي للجودة والسلامة (JCI)', 'fr' => 'Qualité & sécurité accréditées (JCI)', 'en' => 'Internationally Accredited Quality & Safety (JCI)' ),
\t\t\t'hero_trust_2'          => array( 'ar' => 'نخبة من كبار الأطباء والاستشاريين', 'fr' => 'Professeurs renommés & spécialistes', 'en' => 'World-Renowned Professors & Specialists' ),
\t\t\t'hero_trust_3'          => array( 'ar' => 'مرافقة طبية شاملة وترجمة فورية 24/7', 'fr' => 'Coordination des soins & interprétariat 24/7', 'en' => '24/7 Care Coordination & Medical Translation' ),
\t\t\t'jci'                   => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),
\t\t\t'jci_full'              => array( 'ar' => 'اعتماد دولي للجودة والسلامة (JCI)', 'fr' => 'Qualité & sécurité accréditées (JCI)', 'en' => 'Internationally Accredited Quality & Safety (JCI)' ),"""

replacement_trust = """\t\t\t'hero_trust_1'          => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),
\t\t\t'hero_trust_2'          => array( 'ar' => 'نخبة الأطباء والاستشاريين', 'fr' => 'Spécialistes de renom', 'en' => 'Top Specialist Doctors' ),
\t\t\t'hero_trust_3'          => array( 'ar' => 'مرافقة وترجمة 24/7', 'fr' => 'Assistance & traduction 24/7', 'en' => '24/7 Care & Translation' ),
\t\t\t'jci'                   => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),
\t\t\t'jci_full'              => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),"""

target_stats = """\t\t\t'stat_beds'             => array( 'ar' => 'سرير للمرضى', 'fr' => 'Lits patients', 'en' => 'Patient Beds' ),
\t\t\t'stat_rooms'            => array( 'ar' => 'غرفة وجناح خاص', 'fr' => 'Chambres & suites privées', 'en' => 'Private Rooms & Suites' ),
\t\t\t'stat_icu'              => array( 'ar' => 'سرير عناية مركزة', 'fr' => 'Lits de réanimation', 'en' => 'ICU Beds' ),
\t\t\t'stat_theatres'         => array( 'ar' => 'غرفة عمليات', 'fr' => 'Blocs opératoires', 'en' => 'Operating Theatres' ),
\t\t\t'stat_doctors'          => array( 'ar' => 'طبيب وأخصائي', 'fr' => 'Médecins & spécialistes', 'en' => 'Doctors & Specialists' ),"""

replacement_stats = """\t\t\t'stat_beds'             => array( 'ar' => 'سرير', 'fr' => 'Lits', 'en' => 'Beds' ),
\t\t\t'stat_rooms'            => array( 'ar' => 'غرفة وجناح خاص', 'fr' => 'Chambres & suites privées', 'en' => 'Private Rooms & Suites' ),
\t\t\t'stat_icu'              => array( 'ar' => 'سرير عناية مركزة', 'fr' => 'Lits de réanimation', 'en' => 'ICU Beds' ),
\t\t\t'stat_theatres'         => array( 'ar' => 'غرفة عمليات', 'fr' => 'Blocs opératoires', 'en' => 'Operating Theatres' ),
\t\t\t'stat_doctors'          => array( 'ar' => 'طبيب', 'fr' => 'Médecins', 'en' => 'Doctors' ),"""

if target_trust in content:
    content = content.replace(target_trust, replacement_trust)
    print("Replaced trust strings")
else:
    print("Target trust not found")

if target_stats in content:
    content = content.replace(target_stats, replacement_stats)
    print("Replaced stat strings")
else:
    print("Target stats not found")

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Saved class-shm-i18n.php")

