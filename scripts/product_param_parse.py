#!/usr/bin/env python3
"""Parse Woo/BaseLinker product titles into canonical attribute slugs."""
import re
import unicodedata

# Canonical slugs align with mnsk7_get_key_param_attributes() / FEATURE_ATTRIBUTE_MAP.
FI_TRIPLE = re.compile(
    r"\bfi\s+(\d+(?:[,.]\d+)?)\s*[x×]\s*(\d+(?:[,.]\d+)?)\s*[x×]\s*(\d+(?:[,.]\d+)?)\b",
    re.IGNORECASE,
)
D_TRIPLE = re.compile(
    r"(?:Ø|ø|fi)\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\s*[x×]\s*(\d+(?:[,.]\d+)?)\s*(?:mm)?\s*[x×]\s*(\d+(?:[,.]\d+)?)\b",
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


def parse_title_params(title):
    """Return dict slug -> display value (non-empty only)."""
    if not title or not str(title).strip():
        return {}

    text = str(title).strip()
    out = {}

    match = FI_TRIPLE.search(text) or D_TRIPLE.search(text)
    if match:
        shank, height, length = match.group(1), match.group(2), match.group(3)
        out["srednica-trzpienia"] = fmt_mm(shank)
        out["dlugosc-robocza-h"] = fmt_mm(height)
        out["dlugosc-calkowita-l"] = fmt_mm(length)
    else:
        pair = D_H_PAIR.search(text)
        if pair:
            out["srednica"] = fmt_mm(pair.group(1))
            out["dlugosc-robocza-h"] = fmt_mm(pair.group(2))

    shk = SHK.search(text)
    if shk and "srednica-trzpienia" not in out:
        out["srednica-trzpienia"] = fmt_mm(shk.group(1))

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
        out["typ"] = f"Typ {typ.group(1).upper()}"

    lowered = normalize_key(text)
    material_map = {
        "vhm": "VHM",
        "hss": "HSS",
        "hm": "HM",
        "drewno": "drewno",
        "mdf": "MDF",
        "aluminium": "aluminium",
        "stal": "stal",
    }
    for needle, label in material_map.items():
        if needle in lowered.split():
            out.setdefault("material", label)
            break

    if "drewn" in lowered:
        out.setdefault("zastosowanie", "drewno")
    if "mdf" in lowered.split():
        out.setdefault("zastosowanie", "MDF")

    return {slug: value for slug, value in out.items() if str(value).strip()}


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
}


def parsed_to_bl_features(parsed):
    features = {}
    for slug, value in parsed.items():
        label = SLUG_TO_BL_LABEL.get(slug)
        if label and str(value).strip():
            features[label] = str(value).strip()
    return features
