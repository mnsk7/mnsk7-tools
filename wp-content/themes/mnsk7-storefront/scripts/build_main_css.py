#!/usr/bin/env python3
"""Build main.css from parts (Windows-friendly alternative to build-main-css.sh)."""
from datetime import datetime, timezone
from pathlib import Path

PARTS = [
    "00-fonts-inter",
    "01-tokens",
    "02-reset-typography",
    "03-storefront-overrides",
    "04-header",
    "05-plp-cards",
    "06-single-product",
    "07-mnsk7-blocks",
    "08-home-sections",
    "09-footer",
    "10-cookie-bar",
    "11-hidden",
    "12-related-products",
    "13-seo-landing",
    "14-faq",
    "15-delivery-contact",
    "16-woo-notices",
    "17-buttons",
    "18-cart-checkout",
    "19-breadcrumbs",
    "20-responsive-tablet",
    "21-responsive-mobile",
    "22-touch-targets",
    "23-print",
    "24-plp-table",
    "25-global-layout",
]

def main():
    parts_dir = Path(__file__).resolve().parent.parent / "assets" / "css" / "parts"
    out = parts_dir.parent / "main.css"
    build_date = datetime.now(timezone.utc).strftime("%Y-%m-%d")
    chunks = [
        "/* =================================================================",
        "   MNK7 Storefront — main.css (built from parts)",
        f"   Build: {build_date}",
        "   Run: scripts/build-main-css.sh or scripts/build_main_css.py",
        "   ================================================================= */",
        "",
    ]
    for part in PARTS:
        path = parts_dir / f"{part}.css"
        if not path.is_file():
            continue
        chunks.append(f"/* === {part}.css === */")
        chunks.append(path.read_text(encoding="utf-8"))
        chunks.append("")
    out.write_text("\n".join(chunks), encoding="utf-8")
    line_count = out.read_text(encoding="utf-8").count("\n") + 1
    print(f"Built {out} ({line_count} lines)")


if __name__ == "__main__":
    main()
