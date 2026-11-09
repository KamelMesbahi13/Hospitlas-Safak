# -*- coding: utf-8 -*-
path = r"safak-hospitals\assets\css\frontend.css"
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = """.sv-hero__features {
\tlist-style: none;
\tmargin: 0;
\tpadding: 6px 18px;
\tdisplay: inline-flex;
\talign-items: center;
\tgap: 6px 18px;
\tflex-wrap: nowrap !important;
\tjustify-content: center;
\tmax-width: 96vw;
\tborder-radius: 999px;
\tborder: 1px solid color-mix(in srgb, var(--sv-accent) 70%, transparent);
\tbackground: rgba(255, 255, 255, 0.08);
\t-webkit-backdrop-filter: blur(8px);
\t        backdrop-filter: blur(8px);
}

.sv-hero__features li {
\tdisplay: inline-flex;
\talign-items: center;
\tgap: 6px;
\tmargin: 0;
\tpadding: 0;
\tfont-family: inherit;
\tfont-size: clamp(11.5px, 0.8vw, 13px) !important;
\tfont-weight: 500;
\tcolor: rgba(255, 255, 255, 0.95);
\twhite-space: nowrap !important;
\tflex-shrink: 0;
}

.sv-hero__check {
\tflex: 0 0 22px;
\twidth: 22px;
\theight: 22px;
\tdisplay: inline-flex;
\talign-items: center;
\tjustify-content: center;
\tborder-radius: 50%;
\tcolor: #ffffff;
\tborder: 1px solid color-mix(in srgb, var(--sv-accent) 80%, transparent);
\tbackground: color-mix(in srgb, var(--sv-accent) 25%, transparent);
}

.sv-hero__check svg {
\twidth: 10px;
\theight: 10px;
}

/* Tablet & Smaller Devices (wraps only on narrow screens below 820px) */
@media (max-width: 820px) {
\t.sv-hero {
\t\tmin-height: 75vh;
\t\tpadding: 100px 20px 48px;
\t}
\t.sv-hero .sv-hero__title {
\t\tfont-size: clamp(40px, 6vw, 52px) !important;
\t\tline-height: 1.2;
\t}
\t.sv-hero__features {
\t\tflex-wrap: wrap !important;
\t\tgap: 10px 16px;
\t\tpadding: 10px 16px;
\t\tborder-radius: 20px;
\t}
\t.sv-hero__features li {
\t\tfont-size: 12.5px !important;
\t\twhite-space: normal !important;
\t}
}

/* Phone */
@media (max-width: 640px) {
\t.sv-hero {
\t\tmin-height: 80vh;
\t\tpadding: 85px 16px 36px;
\t}
\t.sv-hero .sv-hero__eyebrow {
\t\tfont-size: 13px;
\t\tmargin-bottom: 6px;
\t\tline-height: 1.4;
\t}
\t.sv-hero .sv-hero__title {
\t\tfont-size: clamp(30px, 8vw, 38px) !important;
\t\tline-height: 1.2;
\t}
\t.sv-hero__row {
\t\tmargin-top: 20px;
\t}
\t.sv-hero__features {
\t\tflex-direction: column;
\t\talign-items: flex-start;
\t\tborder-radius: 16px;
\t\twidth: 100%;
\t\tmax-width: 330px;
\t\tpadding: 12px 14px;
\t\tgap: 8px;
\t}
\t.sv-hero__features li {
\t\tfont-size: 12px !important;
\t\twhite-space: normal !important;
\t\ttext-align: start;
\t}
\t.sv-hero__check {
\t\twidth: 20px;
\t\theight: 20px;
\t\tflex: 0 0 20px;
\t}
\t.sv-hero__check svg {
\t\twidth: 9px;
\t\theight: 9px;
\t}
}"""

replacement = """.sv-hero__features {
\tlist-style: none;
\tmargin: 0;
\tpadding: 5px 16px;
\tdisplay: inline-flex !important;
\talign-items: center;
\tgap: 6px 16px;
\tflex-wrap: nowrap !important;
\tjustify-content: center;
\tmax-width: 98vw;
\tborder-radius: 999px;
\tborder: 1px solid color-mix(in srgb, var(--sv-accent) 70%, transparent);
\tbackground: rgba(255, 255, 255, 0.08);
\t-webkit-backdrop-filter: blur(8px);
\t        backdrop-filter: blur(8px);
}

.sv-hero__features li {
\tdisplay: inline-flex !important;
\talign-items: center;
\tgap: 6px;
\tmargin: 0;
\tpadding: 0;
\tfont-family: inherit;
\tfont-size: clamp(11px, 0.8vw, 12.5px) !important;
\tfont-weight: 500;
\tcolor: rgba(255, 255, 255, 0.95);
\twhite-space: nowrap !important;
\tflex-shrink: 0 !important;
}

.sv-hero__check {
\tflex: 0 0 20px;
\twidth: 20px;
\theight: 20px;
\tdisplay: inline-flex;
\talign-items: center;
\tjustify-content: center;
\tborder-radius: 50%;
\tcolor: #ffffff;
\tborder: 1px solid color-mix(in srgb, var(--sv-accent) 80%, transparent);
\tbackground: color-mix(in srgb, var(--sv-accent) 25%, transparent);
}

.sv-hero__check svg {
\twidth: 9px;
\theight: 9px;
}

/* Tablet & Mobile Devices */
@media (max-width: 720px) {
\t.sv-hero {
\t\tmin-height: 75vh;
\t\tpadding: 90px 18px 40px;
\t}
\t.sv-hero .sv-hero__eyebrow {
\t\tfont-size: 13px;
\t\tmargin-bottom: 6px;
\t\tline-height: 1.4;
\t}
\t.sv-hero .sv-hero__title {
\t\tfont-size: clamp(30px, 7.5vw, 40px) !important;
\t\tline-height: 1.2;
\t}
\t.sv-hero__row {
\t\tmargin-top: 20px;
\t}
\t.sv-hero__features {
\t\tflex-direction: column !important;
\t\talign-items: flex-start !important;
\t\tborder-radius: 16px !important;
\t\twidth: 100% !important;
\t\tmax-width: 330px !important;
\t\tpadding: 12px 14px !important;
\t\tgap: 8px !important;
\t\tflex-wrap: nowrap !important;
\t}
\t.sv-hero__features li {
\t\tfont-size: 12px !important;
\t\twhite-space: normal !important;
\t\ttext-align: start !important;
\t}
\t.sv-hero__check {
\t\twidth: 18px;
\t\theight: 18px;
\t\tflex: 0 0 18px;
\t}
\t.sv-hero__check svg {
\t\twidth: 8px;
\t\theight: 8px;
\t}
}"""

if target in content:
    content = content.replace(target, replacement)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Successfully updated frontend.css")
else:
    print("Target block not found in frontend.css")
