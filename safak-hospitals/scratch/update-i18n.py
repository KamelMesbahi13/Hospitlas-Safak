# -*- coding: utf-8 -*-
path = r"safak-hospitals\includes\class-shm-i18n.php"
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = """\t\t\t'hero_trust_1'          => array( 'ar' => 'اعتماد دولي لأعلى معايير الجودة والسلامة (JCI)', 'fr' => 'Accréditation internationale de qualité et sécurité (JCI)', 'en' => 'Internationally Accredited Quality & Safety (JCI)' ),
\t\t\t'hero_trust_2'          => array( 'ar' => 'نخبة من كبار البروفيسورات والأطباء الاستشاريين', 'fr' => 'Professeurs de renom et médecins spécialistes', 'en' => 'World-Renowned Professors & Specialist Surgeons' ),
\t\t\t'hero_trust_3'          => array( 'ar' => 'مرافقة طبية شاملة وترجمة فورية على مدار الساعة', 'fr' => 'Accompagnement complet et interprétariat 24h/24', 'en' => '24/7 Care Coordination & Medical Interpretation' ),
\t\t\t'jci'                   => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),
\t\t\t'jci_full'              => array( 'ar' => 'اعتماد دولي لأعلى معايير الجودة والسلامة (JCI)', 'fr' => 'Accréditation internationale JCI', 'en' => 'Internationally Accredited Quality & Safety (JCI)' ),"""

replacement = """\t\t\t'hero_trust_1'          => array( 'ar' => 'اعتماد دولي للجودة والسلامة (JCI)', 'fr' => 'Qualité & sécurité accréditées (JCI)', 'en' => 'Internationally Accredited Quality & Safety (JCI)' ),
\t\t\t'hero_trust_2'          => array( 'ar' => 'نخبة من كبار الأطباء والاستشاريين', 'fr' => 'Professeurs renommés & spécialistes', 'en' => 'World-Renowned Professors & Specialists' ),
\t\t\t'hero_trust_3'          => array( 'ar' => 'مرافقة طبية شاملة وترجمة فورية 24/7', 'fr' => 'Coordination des soins & interprétariat 24/7', 'en' => '24/7 Care Coordination & Medical Translation' ),
\t\t\t'jci'                   => array( 'ar' => 'معتمد من JCI', 'fr' => 'Accrédité JCI', 'en' => 'JCI Accredited' ),
\t\t\t'jci_full'              => array( 'ar' => 'اعتماد دولي للجودة والسلامة (JCI)', 'fr' => 'Qualité & sécurité accréditées (JCI)', 'en' => 'Internationally Accredited Quality & Safety (JCI)' ),"""

if target in content:
    content = content.replace(target, replacement)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Successfully updated class-shm-i18n.php")
else:
    print("Target string not found in class-shm-i18n.php")
