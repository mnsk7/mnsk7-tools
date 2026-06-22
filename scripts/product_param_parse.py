#!/usr/bin/env python3
"""Parse Woo/BaseLinker product titles into canonical attribute slugs."""
import html
import re
import unicodedata

# Canonical slugs align with mnsk7_get_key_param_attributes() / FEATURE_ATTRIBUTE_MAP.
FI_TRIPLE = re.compile(
    r"\bfi\s+(\d+(?:[,.]\d+)?)\s*(?:mm)?\s*[x×]\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\s*[x×]\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\b",
    re.IGNORECASE,
)
D_TRIPLE = re.compile(
    r"(?:Ø|ø|fi)\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\s*[x×]\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\s*[x×]\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\b",
    re.IGNORECASE,
)
D_H_PAIR = re.compile(
    r"(?:Ø|ø)\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\s*[x×]\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\b",
    re.IGNORECASE,
)
PIORY = re.compile(r"\b(\d+)\s*P\b", re.IGNORECASE)
RADIUS = re.compile(r"\bR\s*(\d+(?:[,.]\d+)?)\b", re.IGNORECASE)
ER = re.compile(r"\bER\s*(\d+)\b", re.IGNORECASE)
KAT = re.compile(r"\b(\d+(?:[,.]\d+)?)\s*°\b")
TYP = re.compile(r"\btyp\s+([A-Za-z0-9]+)\b", re.IGNORECASE)
SHK = re.compile(r"\bSHK\s*(\d+(?:[,.]\d+)?)\b", re.IGNORECASE)
HRC = re.compile(r"\bHRC\s*(\d+)\b", re.IGNORECASE)
EXCERPT_KV = re.compile(r"^([^=\n]+?)\s*=\s*(.+)$", re.MULTILINE)

COATING_TOKENS = (
    "TiSiN",
    "TiAlN",
    "AlTiN",
    "TiN",
    "AlCrN",
    "CrN",
    "DLC",
    "ZrN",
    "nACo",
    "nACRo",
)

OPERATION_TOKENS = {
    "cnc": "CNC",
    "wiercenie": "wiercenie",
    "frezowanie": "frezowanie",
    "gwintowanie": "gwintowanie",
    "cietie": "cięcie",
}

MATERIAL_TOKENS = {
    "vhm": "VHM",
    "hss": "HSS",
    "hm": "HM",
    "stal": "stal",
    "aluminium": "aluminium",
    "drewno": "drewno",
    "mdf": "MDF",
}


def normalize_key(value):
    text = unicodedata.normalize("NFKD", str(value).strip().lower())
    text = "".join(ch for ch in text if not unicodedata.combining(ch))
    return re.sub(r"[^a-z0-9]+", " ", text).strip()


def fmt_mm(value):
    text = str(value).strip().replace(",", ".")
    if not text:
        return ""
    if re.search(r"\bmm\b", text, flags=re.IGNORECASE):
        return text
    return f"{text} mm"


def strip_html(text):
    if not text:
        return ""
    raw = html.unescape(str(text))
    raw = re.sub(r"(?i)<br\s*/?>", "\n", raw)
    raw = re.sub(r"(?i)</p>", "\n", raw)
    raw = re.sub(r"<[^>]+>", " ", raw)
    raw = re.sub(r"&#8211;|&ndash;|–", "-", raw)
    return re.sub(r"[ \t]+", " ", raw).strip()


def is_wiertlo_tool(text):
    lowered = normalize_key(text)
    return "wiert" in lowered or "frez wiert" in lowered


def parse_coating(text):
    for token in COATING_TOKENS:
        if re.search(rf"\b{re.escape(token)}\b", text, flags=re.IGNORECASE):
            return token
    return ""


def parse_material_and_hardness(text):
    out = {}
    lowered = normalize_key(text)
    for needle, label in MATERIAL_TOKENS.items():
        if re.search(rf"\b{re.escape(needle)}\b", lowered):
            out["material"] = label
            break
    hrc = HRC.search(text)
    if hrc:
        out["twardosc"] = f"HRC{hrc.group(1)}"
    return out


def parse_operation_token(text):
    lowered = normalize_key(text)
    for needle, label in OPERATION_TOKENS.items():
        if needle in lowered.split() or needle in lowered:
            return label
    return ""


def parse_tool_type_from_segment(segment):
    lowered = normalize_key(segment)
    if "frez wiert" in lowered or "frez-wiert" in lowered:
        base = "Frez wiertło"
        op = parse_operation_token(segment)
        return base, op
    if "frez" in lowered and "wiert" not in lowered:
        return "Frez", parse_operation_token(segment)
    if "wiert" in lowered:
        return "Wiertło", parse_operation_token(segment)
    if "gwint" in lowered:
        return "Gwintownik", parse_operation_token(segment)
    return "", parse_operation_token(segment)


def apply_fi_triple(out, text, wiertlo=False):
    match = FI_TRIPLE.search(text) or D_TRIPLE.search(text)
    if not match:
        return out
    a, b, c = match.group(1), match.group(2), match.group(3)
    if wiertlo:
        out["srednica"] = fmt_mm(a)
        out["srednica-trzpienia"] = fmt_mm(b)
        out["dlugosc-calkowita-l"] = fmt_mm(c)
    else:
        out["srednica-trzpienia"] = fmt_mm(a)
        out["dlugosc-robocza-h"] = fmt_mm(b)
        out["dlugosc-calkowita-l"] = fmt_mm(c)
    return out


def parse_pipe_segments(title):
    """Split title on | and map each segment to params."""
    if not title or "|" not in str(title):
        return {}

    wiertlo = is_wiertlo_tool(title)
    out = {}
    segments = [seg.strip() for seg in str(title).split("|") if seg.strip()]

    for segment in segments:
        if FI_TRIPLE.search(segment) or D_TRIPLE.search(segment):
            apply_fi_triple(out, segment, wiertlo=wiertlo)
            continue

        coating = parse_coating(segment)
        if coating:
            out["pokrycie"] = coating
            out.update({k: v for k, v in parse_material_and_hardness(segment).items() if k not in out})
            continue

        mat_hard = parse_material_and_hardness(segment)
        if mat_hard:
            out.update({k: v for k, v in mat_hard.items() if k not in out})
            continue

        tool_type, operation = parse_tool_type_from_segment(segment)
        if tool_type:
            out.setdefault("typ", tool_type)
        if operation:
            out.setdefault("typ-operacji", operation)

    return out


def parse_short_description_params(short_description):
    """Parse Woo short description lines like 'Trzpien (SHK) = 6 mm'."""
    text = strip_html(short_description)
    if not text:
        return {}

    label_map = {
        "trzpien": "srednica-trzpienia",
        "shk": "srednica-trzpienia",
        "srednica czesci roboczej": "srednica",
        "srednica robocza": "srednica",
        "dlugosc czesci roboczej": "dlugosc-robocza-h",
        "dlugosc robocza": "dlugosc-robocza-h",
        "dlugosc calkowita": "dlugosc-calkowita-l",
    }
    code_map = {
        "SHK": "srednica-trzpienia",
        "D": "srednica",
        "H": "dlugosc-robocza-h",
        "L": "dlugosc-calkowita-l",
    }

    out = {}
    for line in re.split(r"[\n\r]+", text):
        line = line.strip(" -–—")
        if not line or "=" not in line:
            continue
        match = EXCERPT_KV.match(line)
        if not match:
            continue
        label = normalize_key(re.sub(r"\s*\([a-z]+\)\s*$", "", match.group(1), flags=re.IGNORECASE))
        value = match.group(2).strip()
        slug = label_map.get(label)
        if not slug:
            code_match = re.search(r"\(([A-Z])\)", match.group(1), flags=re.IGNORECASE)
            if code_match:
                slug = code_map.get(code_match.group(1).upper())
        if slug and value:
            out[slug] = fmt_mm(value) if re.search(r"\d", value) else value

    return out


def parse_description_params(description):
    """Extract marketing/use-case params from long description."""
    text = strip_html(description)
    if not text:
        return {}

    out = {}
    lowered = normalize_key(text)

    if "wiercen" in lowered:
        out.setdefault("typ-operacji", "wiercenie")
    if "frez wiert" in lowered or "frez-wiert" in lowered:
        out.setdefault("typ", "Frez wiertło")

    materials = []
    for needle, label in (
        ("narzedziow", "stal narzędziowa"),
        ("nierdzewn", "stal nierdzewna"),
        ("aluminium", "aluminium"),
        ("zeliwa", "żeliwo"),
        ("metali", "metale"),
    ):
        if needle in lowered:
            materials.append(label)
    if materials:
        out["zastosowanie"] = ", ".join(dict.fromkeys(materials))

    return out


def merge_parsed(*maps):
    merged = {}
    for item in maps:
        for slug, value in (item or {}).items():
            if str(value).strip():
                merged[slug] = str(value).strip()
    return merged


def parse_title_params(title):
    """Return dict slug -> display value (non-empty only)."""
    if not title or not str(title).strip():
        return {}

    text = str(title).strip()
    wiertlo = is_wiertlo_tool(text)
    out = {}

    if "|" in text:
        out.update(parse_pipe_segments(text))

    if not any(k in out for k in ("srednica", "srednica-trzpienia", "dlugosc-calkowita-l", "dlugosc-robocza-h")):
        apply_fi_triple(out, text, wiertlo=wiertlo)
        if not wiertlo:
            pair = D_H_PAIR.search(text)
            if pair:
                out.setdefault("srednica", fmt_mm(pair.group(1)))
                out.setdefault("dlugosc-robocza-h", fmt_mm(pair.group(2)))

    shk = SHK.search(text)
    if shk:
        out.setdefault("srednica-trzpienia", fmt_mm(shk.group(1)))

    pior = PIORY.search(text)
    if pior:
        out["liczba-zebow"] = f"{pior.group(1)}P"

    radius = RADIUS.search(text)
    if radius:
        out["r"] = fmt_mm(radius.group(1))

    er = ER.search(text)
    if er:
        out["er"] = f"ER{er.group(1)}"

    kat = KAT.search(text)
    if kat:
        out["kat-skosu"] = f"{kat.group(1)}°"

    typ = TYP.search(text)
    if typ:
        out.setdefault("typ", f"Typ {typ.group(1).upper()}")

    out.update({k: v for k, v in parse_material_and_hardness(text).items() if k not in out})
    coating = parse_coating(text)
    if coating:
        out.setdefault("pokrycie", coating)

    if not out.get("typ-operacji"):
        op = parse_operation_token(text)
        if op:
            out["typ-operacji"] = op

    if not out.get("typ") and wiertlo:
        out["typ"] = "Frez wiertło"

    lowered = normalize_key(text)
    if "drewn" in lowered:
        out.setdefault("zastosowanie", "drewno")
    if "mdf" in lowered.split():
        out.setdefault("zastosowanie", "MDF")

    return {slug: value for slug, value in out.items() if str(value).strip()}


def parse_product_params(title, short_description="", description=""):
    """Merge title + short + long description into one param map."""
    return merge_parsed(
        parse_description_params(description),
        parse_title_params(title),
        parse_short_description_params(short_description),
    )


# BL feature label by slug (for push to BaseLinker text_fields.features).
SLUG_TO_BL_LABEL = {
    "material": "Materiał",
    "srednica-trzpienia": "Średnica trzpienia",
    "srednica": "Średnica robocza",
    "dlugosc-robocza-h": "Długość robocza",
    "dlugosc-calkowita-l": "Długość całkowita",
    "typ": "Typ narzędzia",
    "liczba-zebow": "Liczba ostrzy",
    "pokrycie": "Powłoka",
    "kat-skosu": "Kąt skosu",
    "r": "Promień R",
    "er": "ER",
    "ksztalt": "Kształt",
    "zastosowanie": "Zastosowanie",
    "typ-operacji": "Typ operacji",
    "chwyt": "Chwyt / trzpienie",
    "trzpienie": "Trzpienie / chwyt",
    "skok-gwintu": "Skok gwintu",
    "twardosc": "Twardość",
}


def parsed_to_bl_features(parsed):
    features = {}
    for slug, value in parsed.items():
        label = SLUG_TO_BL_LABEL.get(slug)
        if label and str(value).strip():
            features[label] = str(value).strip()
    return features


# Display order for offer preview / tables.
PARAM_DISPLAY_ORDER = [
    "typ",
    "typ-operacji",
    "material",
    "twardosc",
    "pokrycie",
    "srednica",
    "srednica-trzpienia",
    "dlugosc-robocza-h",
    "dlugosc-calkowita-l",
    "liczba-zebow",
    "kat-skosu",
    "r",
    "er",
    "ksztalt",
    "zastosowanie",
    "chwyt",
    "trzpienie",
    "skok-gwintu",
]


def param_label(slug):
    return SLUG_TO_BL_LABEL.get(slug, slug)


# Chat/raw-file SKU typos -> BaseLinker inventory SKU (inventory 19398, verified 2026-06).
OFFER13_SKU_ALIASES = {
    "H030901": "H0300901",
    "H040901": "H0400901",
    "H060901": "H0600901",
    "H080901": "H0800901",
}

OFFER13_SKUS = list(OFFER13_SKU_ALIASES.values())
OFFER13_SKUS_BL = set(OFFER13_SKUS)
OFFER13_GROUP = "frezwiertlo_h"
OFFER13_AXIS = "model"


def normalize_offer_sku(sku):
    text = str(sku or "").strip().upper()
    if not text:
        return ""
    return OFFER13_SKU_ALIASES.get(text, text)
