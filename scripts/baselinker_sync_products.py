#!/usr/bin/env python3
"""Sync products from BaseLinker inventory into WooCommerce (staging-safe)."""
import argparse
import base64
import datetime
import html
import json
import math
import os
import re
import sys
import time
import unicodedata
import urllib.error
import urllib.parse
import urllib.request


FEATURE_ATTRIBUTE_MAP = [
    {
        "label": "Materiał",
        "slug": "material",
        "aliases": ["materiał", "material", "materiał wykonania", "material wykonania", "obrabiany materiał"],
    },
    {
        "label": "Zastosowanie",
        "slug": "zastosowanie",
        "aliases": ["zastosowanie", "do czego", "materiał obróbki"],
    },
    {
        "label": "Średnica trzpienia",
        "slug": "srednica-trzpienia",
        "aliases": ["średnica trzpienia", "srednica trzpienia", "trzpień", "trzpien", "shk", "chwyt"],
    },
    {
        "label": "Trzpienie / chwyt",
        "slug": "trzpienie",
        "aliases": ["trzpienie / chwyt", "trzpienie", "chwyt / trzpienie"],
    },
    {
        "label": "Średnica robocza",
        "slug": "srednica",
        "aliases": ["średnica robocza", "srednica robocza", "średnica części roboczej", "srednica", "fi", "d", "ø"],
    },
    {
        "label": "Długość robocza",
        "slug": "dlugosc-robocza-h",
        "aliases": [
            "długość robocza",
            "dlugosc robocza",
            "długość części roboczej",
            "dlugosc czesci roboczej",
            "h",
        ],
    },
    {
        "label": "Długość całkowita",
        "slug": "dlugosc-calkowita-l",
        "aliases": ["długość całkowita", "dlugosc calkowita", "długość całkowita l", "l"],
    },
    {
        "label": "Typ narzędzia",
        "slug": "typ",
        "aliases": ["typ", "typ frezu", "typ pilnika", "typ narzędzia", "rodzaj"],
    },
    {
        "label": "Typ operacji",
        "slug": "typ-operacji",
        "aliases": ["typ operacji", "operacja"],
    },
    {
        "label": "Kształt",
        "slug": "ksztalt",
        "aliases": ["kształt", "ksztalt", "shape"],
    },
    {
        "label": "Liczba ostrzy",
        "slug": "liczba-zebow",
        "aliases": ["liczba ostrzy", "ilość ostrzy", "ilosc ostrzy", "liczba zębów", "liczba zebow"],
    },
    {
        "label": "Powłoka",
        "slug": "pokrycie",
        "aliases": ["powłoka", "powloka", "pokrycie", "coating"],
    },
    {
        "label": "Twardość",
        "slug": "twardosc",
        "aliases": ["twardość", "twardosc", "hrc", "twardość hrc", "twardosc hrc"],
    },
    {
        "label": "Skok gwintu",
        "slug": "skok-gwintu",
        "aliases": ["skok gwintu", "tpi", "pitch"],
    },
]
FEATURE_ATTRIBUTE_MAP.append(
    {
        "label": "Kąt skosu",
        "slug": "kat-skosu",
        "aliases": ["kąt", "kat", "kąt skosu", "kat skosu", "angle", "stopień", "stopien"],
    }
)
FEATURE_ATTRIBUTE_MAP.extend(
    [
        {
            "label": "Promien R",
            "slug": "r",
            "aliases": ["r", "promien", "promien r", "radius"],
        },
        {
            "label": "ER",
            "slug": "er",
            "aliases": ["er", "typ er", "rozmiar er"],
        },
    ]
)


def load_env(env_file):
    env = {}
    if not os.path.isfile(env_file):
        return env
    with open(env_file, encoding="utf-8") as handle:
        for raw in handle:
            line = raw.strip()
            if not line or line.startswith("#"):
                continue
            if "=" not in line:
                continue
            key, value = line.split("=", 1)
            env[key.strip()] = value.strip().strip('"').strip("'")
    return env


def to_int(value, fallback=0):
    try:
        return int(value)
    except (TypeError, ValueError):
        return fallback


def to_price(value):
    if value is None or value == "":
        return None
    try:
        number = float(str(value).replace(",", "."))
    except ValueError:
        return None
    if math.isnan(number) or math.isinf(number):
        return None
    return f"{number:.2f}"


def normalize_key(value):
    text = unicodedata.normalize("NFKD", str(value).strip().lower())
    text = "".join(ch for ch in text if not unicodedata.combining(ch))
    return re.sub(r"[^a-z0-9]+", " ", text).strip()


def format_catalog_tag_name(name):
    """Polish storefront label: capitalize first letter, preserve BL remainder (incl. MDF/PCV)."""
    text = str(name).strip()
    if not text:
        return text
    first = text[0]
    rest = text[1:]
    if first.isalpha():
        first = first.upper()
    return first + rest


def normalize_dimension_value(value):
    text = str(value).strip()
    if not text:
        return ""
    if re.search(r"\bmm\b", text, flags=re.IGNORECASE):
        return text
    if re.fullmatch(r"\d+(?:[,.]\d+)?", text):
        return f"{text} mm"
    return text


def build_variant_group_key(name, features):
    base = normalize_key(name)
    if not base:
        return ""

    # Remove dimensions that form sibling offers, keeping tool family words.
    base = re.sub(r"\btrzpien\s+\d+(?:[,.]\d+)?\s*mm\b", " ", base)
    base = re.sub(r"\bfi\s+\d+(?:[,.]\d+)?(?:\s*x\s*\d+(?:[,.]\d+)?){0,3}\b", " ", base)
    base = re.sub(r"\b\d+(?:[,.]\d+)?\s*mm\s+\d+(?:[,.]\d+)?\s*mm\b", " ", base)
    base = re.sub(r"\br\s*\d+(?:[,.]\d+)?\b", " ", base)
    base = re.sub(r"\btyp\s+[a-z0-9]+\b", " ", base)
    base = re.sub(r"\bpoglebiacz\b", " ", base)
    base = re.sub(r"\b\d+(?:[,.]\d+)?\s*x\s*\d+(?:[,.]\d+)?(?:\s*x\s*\d+(?:[,.]\d+)?){0,2}\b", " ", base)

    material = normalize_key(features.get("material", {}).get("value", ""))
    if material and material not in base:
        base = f"{base} {material}"

    return re.sub(r"\s+", " ", base).strip()


def extract_type_from_name(name):
    match = re.search(r"\btyp\s+([a-z0-9]+)\b", normalize_key(name), flags=re.IGNORECASE)
    if not match:
        return ""
    return f"Typ {match.group(1).upper()}"


FEATURE_ALIAS_INDEX = {
    normalize_key(alias): definition
    for definition in FEATURE_ATTRIBUTE_MAP
    for alias in definition["aliases"]
}
MAPPED_ATTRIBUTE_SLUGS = {definition["slug"] for definition in FEATURE_ATTRIBUTE_MAP}
MAPPED_ATTRIBUTE_LABELS = {normalize_key(definition["label"]) for definition in FEATURE_ATTRIBUTE_MAP}


def slug_to_feature_entry(slug, value, source="title"):
    for definition in FEATURE_ATTRIBUTE_MAP:
        if definition["slug"] == slug:
            return {
                "label": definition["label"],
                "slug": slug,
                "value": value,
                "source": source,
            }
    return None


def merge_title_features(name, features):
    try:
        from product_param_parse import parse_title_params
    except ImportError:
        import sys
        from pathlib import Path

        sys.path.insert(0, str(Path(__file__).resolve().parent))
        from product_param_parse import parse_title_params

    parsed = parse_title_params(name)
    merged = dict(features or {})
    for slug, value in parsed.items():
        if slug in merged:
            continue
        entry = slug_to_feature_entry(slug, normalize_dimension_value(value), source="title")
        if entry:
            merged[slug] = entry
    return merged


def replace_mapped_product_attributes(existing_attrs, mapped_attrs):
    """Keep non-catalog attrs; replace all mapped slugs with fresh BL/title values only."""
    kept = []
    mapped_ids = {item.get("id") for item in mapped_attrs if item.get("id")}
    mapped_names = {normalize_key(item.get("name", "")) for item in mapped_attrs if item.get("name")}
    for attr in existing_attrs or []:
        attr_id = attr.get("id")
        attr_name = normalize_key(attr.get("name", ""))
        if attr_id in mapped_ids or attr_name in MAPPED_ATTRIBUTE_LABELS or attr_name in mapped_names:
            continue
        options = attr.get("options") or []
        if not options or all(not str(option).strip() for option in options):
            continue
        kept.append(attr)
    kept.extend(mapped_attrs)
    return kept


class BaseLinkerClient:
    def __init__(self, token):
        self.token = token
        self.url = "https://api.baselinker.com/connector.php"

    def call(self, method, params):
        payload = urllib.parse.urlencode(
            {
                "method": method,
                "parameters": json.dumps(params, separators=(",", ":")),
            }
        ).encode("utf-8")
        request = urllib.request.Request(
            self.url,
            data=payload,
            headers={
                "X-BLToken": self.token,
                "Content-Type": "application/x-www-form-urlencoded",
                "Accept": "application/json",
            },
            method="POST",
        )
        try:
            with urllib.request.urlopen(request, timeout=30) as response:
                body = response.read().decode("utf-8")
        except urllib.error.HTTPError as error:
            msg = error.read().decode("utf-8", errors="replace")
            raise RuntimeError(f"BaseLinker HTTP {error.code}: {msg[:400]}") from error
        except urllib.error.URLError as error:
            raise RuntimeError(f"BaseLinker network error: {error}") from error

        try:
            data = json.loads(body)
        except json.JSONDecodeError as error:
            raise RuntimeError(f"BaseLinker invalid JSON: {body[:300]}") from error

        if data.get("status") != "SUCCESS":
            raise RuntimeError(
                f"BaseLinker API error in {method}: {data.get('error_message') or data}"
            )
        return data

    def list_inventories(self):
        response = self.call("getInventories", {})
        inventories = response.get("inventories", [])
        return inventories if isinstance(inventories, list) else []

    def list_product_ids(self, inventory_id, limit=None):
        product_ids = []
        page = 1
        while True:
            response = self.call(
                "getInventoryProductsList",
                {
                    "inventory_id": inventory_id,
                    "page": page,
                },
            )
            products = response.get("products", {})
            if isinstance(products, dict):
                page_ids = list(products.keys())
            elif isinstance(products, list):
                page_ids = []
                for row in products:
                    if isinstance(row, dict):
                        candidate = row.get("product_id")
                        if candidate is not None:
                            page_ids.append(candidate)
            else:
                page_ids = []

            if not page_ids:
                break

            product_ids.extend(page_ids)
            if limit and len(product_ids) >= limit:
                return product_ids[:limit]
            page += 1
            time.sleep(0.2)
        return product_ids

    def get_products_data(self, inventory_id, product_ids):
        response = self.call(
            "getInventoryProductsData",
            {
                "inventory_id": inventory_id,
                "products": product_ids,
            },
        )
        products = response.get("products", {})
        if isinstance(products, dict):
            return products
        return {}

    def list_categories(self, inventory_id):
        params = {}
        if inventory_id:
            params["inventory_id"] = int(inventory_id)
        response = self.call("getInventoryCategories", params)
        categories = response.get("categories", [])
        return categories if isinstance(categories, list) else []

    def list_inventory_tags(self, inventory_id):
        response = self.call("getInventoryTags", {"inventory_id": int(inventory_id)})
        tags = response.get("tags", [])
        if not isinstance(tags, list):
            return []
        names = []
        for row in tags:
            name = str(row.get("name", "")).strip()
            if name:
                names.append(name)
        return names

    def update_product_features(self, inventory_id, product_id, sku, feature_map, language="pl"):
        lang_key = f"features|{language}"
        self.call(
            "addInventoryProduct",
            {
                "inventory_id": int(inventory_id),
                "product_id": int(product_id),
                "sku": sku,
                "text_fields": {lang_key: feature_map},
            },
        )


class WooClient:
    def __init__(self, base_url, consumer_key, consumer_secret):
        self.base_url = base_url.rstrip("/")
        self.api = f"{self.base_url}/wp-json/wc/v3"
        token = base64.b64encode(f"{consumer_key}:{consumer_secret}".encode("utf-8")).decode(
            "ascii"
        )
        self.headers = {
            "Authorization": f"Basic {token}",
            "Accept": "application/json",
            "Content-Type": "application/json; charset=utf-8",
        }
        self.attribute_cache = {}
        self.term_cache = {}
        self.category_cache = {}
        self.tag_cache = {}

    def request(self, method, path, payload=None):
        body = None
        if payload is not None:
            body = json.dumps(payload).encode("utf-8")
        request = urllib.request.Request(
            f"{self.api}{path}",
            data=body,
            headers=self.headers,
            method=method,
        )
        try:
            with urllib.request.urlopen(request, timeout=30) as response:
                return json.loads(response.read().decode("utf-8"))
        except urllib.error.HTTPError as error:
            msg = error.read().decode("utf-8", errors="replace")
            raise RuntimeError(f"WooCommerce HTTP {error.code}: {msg[:400]}") from error
        except urllib.error.URLError as error:
            raise RuntimeError(f"WooCommerce network error: {error}") from error

    def find_product_by_sku(self, sku):
        encoded = urllib.parse.quote(str(sku))
        data = self.request("GET", f"/products?sku={encoded}&per_page=100")
        if not isinstance(data, list) or not data:
            return None
        for item in data:
            if str(item.get("sku", "")).strip() == str(sku).strip():
                return item
        return data[0]

    def list_products(self, status="any"):
        products = []
        page = 1
        while True:
            path = f"/products?status={urllib.parse.quote(status)}&per_page=100&page={page}"
            data = self.request("GET", path)
            if not isinstance(data, list) or not data:
                break
            products.extend(data)
            if len(data) < 100:
                break
            page += 1
        return products

    def create_product(self, payload):
        return self.request("POST", "/products", payload)

    def update_product(self, product_id, payload):
        return self.request("PUT", f"/products/{product_id}", payload)

    def update_variation(self, parent_id, variation_id, payload):
        return self.request(
            "PUT",
            f"/products/{int(parent_id)}/variations/{int(variation_id)}",
            payload,
        )

    def delete_product(self, product_id, force=True):
        force_value = "true" if force else "false"
        return self.request("DELETE", f"/products/{product_id}?force={force_value}")

    def list_attributes(self):
        attributes = []
        page = 1
        while True:
            data = self.request("GET", f"/products/attributes?per_page=100&page={page}")
            if not isinstance(data, list) or not data:
                break
            attributes.extend(data)
            if len(data) < 100:
                break
            page += 1
        return attributes

    def get_or_create_attribute(self, label, slug, dry_run=False):
        cache_key = slug
        if cache_key in self.attribute_cache:
            return self.attribute_cache[cache_key]

        attrs = self.list_attributes()
        if isinstance(attrs, list):
            for attr in attrs:
                attr_slug = str(attr.get("slug", "")).strip()
                if attr_slug in {slug, f"pa_{slug}"}:
                    self.attribute_cache[cache_key] = attr
                    return attr

        if dry_run:
            return {"id": None, "name": label, "slug": slug, "dry_run": True}

        attr = self.request(
            "POST",
            "/products/attributes",
            {"name": label, "slug": slug, "type": "select", "order_by": "menu_order", "has_archives": True},
        )
        self.attribute_cache[cache_key] = attr
        return attr

    def list_attribute_terms(self, attribute_id):
        terms = []
        page = 1
        while True:
            data = self.request(
                "GET",
                f"/products/attributes/{attribute_id}/terms?per_page=100&page={page}",
            )
            if not isinstance(data, list) or not data:
                break
            terms.extend(data)
            if len(data) < 100:
                break
            page += 1
        return terms

    def get_or_create_term(self, attribute_id, value, dry_run=False):
        term_name = str(value).strip()
        if not term_name:
            return None
        cache_key = (attribute_id, normalize_key(term_name))
        if cache_key in self.term_cache:
            return self.term_cache[cache_key]

        for term in self.list_attribute_terms(attribute_id):
            if normalize_key(html.unescape(str(term.get("name", "")))) == normalize_key(term_name):
                self.term_cache[cache_key] = term
                return term

        if dry_run:
            return {"id": None, "name": term_name, "dry_run": True}

        term = self.request(
            "POST",
            f"/products/attributes/{attribute_id}/terms",
            {"name": term_name},
        )
        self.term_cache[cache_key] = term
        return term

    def list_product_categories(self):
        categories = []
        page = 1
        while True:
            data = self.request("GET", f"/products/categories?per_page=100&page={page}")
            if not isinstance(data, list) or not data:
                break
            categories.extend(data)
            if len(data) < 100:
                break
            page += 1
        return categories

    def get_or_create_product_category(self, name, parent_id=0, dry_run=False):
        name = str(name).strip()
        if not name:
            return None
        cache_key = (normalize_key(name), int(parent_id or 0))
        if cache_key in self.category_cache:
            return self.category_cache[cache_key]

        for category in self.list_product_categories():
            if normalize_key(html.unescape(str(category.get("name", "")))) == normalize_key(name):
                if int(category.get("parent", 0) or 0) == int(parent_id or 0):
                    self.category_cache[cache_key] = category
                    return category

        if dry_run:
            return {"id": None, "name": name, "dry_run": True}

        payload = {"name": name}
        if parent_id:
            payload["parent"] = int(parent_id)
        category = self.request("POST", "/products/categories", payload)
        self.category_cache[cache_key] = category
        return category

    def list_product_tags(self):
        tags = []
        page = 1
        while True:
            data = self.request("GET", f"/products/tags?per_page=100&page={page}")
            if not isinstance(data, list) or not data:
                break
            tags.extend(data)
            if len(data) < 100:
                break
            page += 1
        return tags

    def get_or_create_product_tag(self, name, dry_run=False):
        name = format_catalog_tag_name(name)
        if not name:
            return None
        cache_key = normalize_key(name)
        if cache_key in self.tag_cache:
            return self.tag_cache[cache_key]

        for tag in self.list_product_tags():
            if normalize_key(html.unescape(str(tag.get("name", "")))) == cache_key:
                tag_id = tag.get("id")
                existing_name = html.unescape(str(tag.get("name", ""))).strip()
                if tag_id and existing_name != name and not dry_run:
                    tag = self.request("PUT", f"/products/tags/{int(tag_id)}", {"name": name})
                self.tag_cache[cache_key] = tag
                return tag

        if dry_run:
            return {"id": None, "name": name, "dry_run": True}

        tag = self.request("POST", "/products/tags", {"name": name})
        self.tag_cache[cache_key] = tag
        return tag


def pick_text_field(text_fields, field_name, lang):
    if not isinstance(text_fields, dict):
        return None
    keys = [f"{field_name}|{lang}", field_name]
    for key in keys:
        value = text_fields.get(key)
        if isinstance(value, str) and value.strip():
            return value.strip()
    for key, value in text_fields.items():
        if key.startswith(f"{field_name}|") and isinstance(value, str) and value.strip():
            return value.strip()
    return None


def parse_feature_rows(raw_features):
    if not raw_features:
        return []
    if isinstance(raw_features, dict):
        return [(key, value) for key, value in raw_features.items()]
    if isinstance(raw_features, list):
        rows = []
        for item in raw_features:
            if isinstance(item, dict):
                key = item.get("name") or item.get("key") or item.get("label") or item.get("feature")
                value = item.get("value")
                if key is not None and value is not None:
                    rows.append((key, value))
            elif isinstance(item, str) and ":" in item:
                key, value = item.split(":", 1)
                rows.append((key, value))
        return rows
    if isinstance(raw_features, str):
        rows = []
        for line in raw_features.splitlines():
            if ":" not in line:
                continue
            key, value = line.split(":", 1)
            rows.append((key, value))
        return rows
    return []


def extract_features(product, language):
    text_fields = product.get("text_fields", {})
    candidates = []
    if isinstance(text_fields, dict):
        candidates.extend(
            [
                text_fields.get(f"features|{language}"),
                text_fields.get("features"),
                text_fields.get(f"params|{language}"),
                text_fields.get("params"),
            ]
        )
        for key, value in text_fields.items():
            if str(key).startswith("features|"):
                candidates.append(value)
    candidates.append(product.get("features"))

    rows = []
    for candidate in candidates:
        rows.extend(parse_feature_rows(candidate))

    features = {}
    unknown = {}
    for key, value in rows:
        label = str(key).strip()
        if not label:
            continue
        value_text = str(value).strip()
        if not value_text:
            continue
        definition = FEATURE_ALIAS_INDEX.get(normalize_key(label))
        if definition:
            features[definition["slug"]] = {
                "label": definition["label"],
                "slug": definition["slug"],
                "value": value_text,
                "source": label,
            }
        else:
            unknown[label] = value_text
    return features, unknown


def normalize_variant_group_value(value):
    text = str(value or "").strip()
    if not text:
        return ""
    slug = re.sub(r"[^a-zA-Z0-9_-]+", "-", text).strip("-").lower()
    return slug or normalize_key(text).replace(" ", "-")


def pick_manual_variant_group(unknown_features):
    if not isinstance(unknown_features, dict):
        return ""
    accepted_keys = {
        "mnk7 grupa wariantu",
        "mnsk7 grupa wariantu",
        "mnk7 model wariantowy",
        "mnsk7 model wariantowy",
        "model",
        "mnk7 model",
        "mnsk7 model",
    }
    for key, value in unknown_features.items():
        if normalize_key(key) in accepted_keys:
            return normalize_variant_group_value(value)
    return ""


def pick_manual_variant_axis(unknown_features):
    if not isinstance(unknown_features, dict):
        return ""

    accepted_keys = {
        "mnk7 os wariantu",
        "mnsk7 os wariantu",
        "mnk7 variant axis",
        "mnsk7 variant axis",
    }
    axis_map = {
        "srednica": "srednica",
        "fi": "srednica",
        "trzpien": "srednica-trzpienia",
        "srednica trzpienia": "srednica-trzpienia",
        "kat": "kat-skosu",
        "kat skosu": "kat-skosu",
        "m": "typ",
        "typ": "typ",
        "ksztalt": "ksztalt",
        "kolor": "kolor",
    }

    for key, value in unknown_features.items():
        if normalize_key(key) not in accepted_keys:
            continue

        raw_axes = re.split(r"[,;/|]+", str(value))
        axes = []
        for raw_axis in raw_axes:
            norm = normalize_key(raw_axis)
            if not norm:
                continue
            mapped = axis_map.get(norm, re.sub(r"\s+", "-", norm))
            if mapped and mapped not in axes:
                axes.append(mapped)
        return ",".join(axes)

    return ""


def infer_variant_axis(features):
    if not isinstance(features, dict) or not features:
        return ""

    axes = []
    if "srednica-trzpienia" in features:
        axes.append("srednica-trzpienia")
    if "srednica" in features:
        axes.append("srednica")
    if "kat-skosu" in features:
        axes.append("kat-skosu")
    if "typ" in features:
        axes.append("typ")
    return ",".join(axes)


def merge_product_attributes(existing_attrs, mapped_attrs):
    merged = []
    replaced_ids = {item.get("id") for item in mapped_attrs if item.get("id")}
    replaced_names = {normalize_key(item.get("name", "")) for item in mapped_attrs if item.get("name")}

    for attr in existing_attrs or []:
        attr_id = attr.get("id")
        attr_name = normalize_key(attr.get("name", ""))
        if attr_id in replaced_ids or attr_name in replaced_names:
            continue
        merged.append(attr)

    merged.extend(mapped_attrs)
    return merged


def merge_meta_data(existing_meta, new_meta):
    merged = []
    replacement_by_key = {item.get("key"): item for item in new_meta if item.get("key")}
    seen_replacement_keys = set()
    for item in existing_meta or []:
        key = item.get("key")
        if key in replacement_by_key:
            replacement = dict(replacement_by_key[key])
            if item.get("id"):
                replacement["id"] = item["id"]
            merged.append(replacement)
            seen_replacement_keys.add(key)
            continue
        merged.append(item)
    for item in new_meta:
        key = item.get("key")
        if key not in seen_replacement_keys:
            merged.append(item)
    return merged


VARIATION_PARENT_KEYS = frozenset({"categories", "tags", "name", "description", "short_description", "images"})
VARIATION_STRIP_KEYS = VARIATION_PARENT_KEYS | frozenset({"type", "status"})


def extract_parent_catalog_payload(payload):
    """Categories/tags belong on the variable parent, not on variations."""
    parent_payload = {}
    for key in VARIATION_PARENT_KEYS:
        value = payload.get(key)
        if value:
            parent_payload[key] = value
    return parent_payload


def adapt_payload_for_wc_existing(existing, payload):
    """Route BL payload fields to the correct Woo product type."""
    product_type = str(existing.get("type") or "simple").strip()
    adapted = dict(payload)
    if product_type == "variation":
        parent_id = int(existing.get("parent_id") or 0)
        variation_payload = {key: value for key, value in adapted.items() if key not in VARIATION_STRIP_KEYS}
        return "variation", parent_id, variation_payload, extract_parent_catalog_payload(adapted)
    if product_type != "simple":
        adapted.pop("type", None)
    return product_type, None, adapted, {}


def upsert_wc_product_from_bl(woo, existing, payload):
    """Update simple/variable parent or variation using the proper Woo REST endpoint."""
    kind, parent_id, adapted, parent_payload = adapt_payload_for_wc_existing(existing, payload)
    if kind == "variation":
        if not parent_id:
            raise RuntimeError(f"variation wc_id={existing.get('id')} missing parent_id")
        result = woo.update_variation(parent_id, existing["id"], adapted)
        if parent_payload:
            woo.update_product(parent_id, parent_payload)
        return result
    return woo.update_product(existing["id"], adapted)


def pick_stock(product, warehouse_id):
    stock = product.get("stock")
    if not isinstance(stock, dict) or not stock:
        return None
    if warehouse_id and str(warehouse_id) in stock:
        return to_int(stock.get(str(warehouse_id)), fallback=0)
    total = 0
    found_any = False
    for value in stock.values():
        if value is None or value == "":
            continue
        total += to_int(value, fallback=0)
        found_any = True
    return total if found_any else None


def pick_price(product, price_group_id):
    prices = product.get("prices")
    if not isinstance(prices, dict) or not prices:
        return None
    if price_group_id and str(price_group_id) in prices:
        return to_price(prices.get(str(price_group_id)))
    first_key = sorted(prices.keys())[0]
    return to_price(prices.get(first_key))


def pick_images(product):
    images = product.get("images")
    if not isinstance(images, dict):
        return []
    urls = []
    for _, value in sorted(images.items(), key=lambda row: str(row[0])):
        if isinstance(value, str) and value.strip():
            urls.append({"src": value.strip()})
    return urls


def build_wc_attributes(woo, features, dry_run=False):
    attributes = []
    for slug, feature in features.items():
        attr = woo.get_or_create_attribute(feature["label"], slug, dry_run=dry_run)
        attr_id = attr.get("id")
        if not attr_id:
            attributes.append(
                {
                    "name": feature["label"],
                    "options": [feature["value"]],
                    "visible": True,
                    "variation": False,
                }
            )
            continue
        term = woo.get_or_create_term(attr_id, feature["value"], dry_run=dry_run)
        if not term:
            continue
        attributes.append(
            {
                "id": attr_id,
                "name": feature["label"],
                "options": [term.get("name") or feature["value"]],
                "visible": True,
                "variation": False,
            }
        )
    return attributes


def build_wc_payload(
    product,
    product_id,
    language,
    price_group_id,
    warehouse_id,
    publish_status,
    category_resolver=None,
    tag_resolver=None,
):
    text_fields = product.get("text_fields", {})
    sku = str(product.get("sku", "")).strip()
    if not sku:
        return None, {}, {}, f"BL#{product_id}: missing SKU"

    name = pick_text_field(text_fields, "name", language) or product.get("name")
    if not isinstance(name, str) or not name.strip():
        return None, {}, {}, f"SKU {sku}: missing name"

    payload = {
        "name": name.strip(),
        "type": "simple",
        "sku": sku,
        "status": publish_status,
    }

    description = pick_text_field(text_fields, "description", language)
    if description:
        payload["description"] = description

    short_description = pick_text_field(text_fields, "description_extra1", language)
    if not short_description:
        short_description = pick_text_field(text_fields, "description_extra", language)
    if short_description:
        payload["short_description"] = short_description

    regular_price = pick_price(product, price_group_id)
    if regular_price:
        payload["regular_price"] = regular_price

    stock_qty = pick_stock(product, warehouse_id)
    if stock_qty is not None:
        payload["manage_stock"] = True
        payload["stock_quantity"] = stock_qty

    meta_data = [
        {"key": "_mnsk7_bl_product_id", "value": str(product_id)},
        {
            "key": "_mnsk7_bl_features_synced_at",
            "value": datetime.datetime.now(datetime.timezone.utc).isoformat(timespec="seconds"),
        },
    ]

    ean = str(product.get("ean", "")).strip()
    if ean:
        meta_data.append({"key": "_ean", "value": ean})

    images = pick_images(product)
    if images:
        payload["images"] = images

    features, unknown_features = extract_features(product, language)
    features = merge_title_features(name.strip(), features)
    if "typ" not in features:
        type_value = extract_type_from_name(name.strip())
        if type_value:
            features["typ"] = {
                "label": "Typ narzędzia",
                "slug": "typ",
                "value": type_value,
                "source": "name",
            }
    for feature in features.values():
        feature["value"] = normalize_dimension_value(feature["value"])
    if features:
        meta_data.append({"key": "_mnsk7_bl_features_raw", "value": json.dumps(features, ensure_ascii=False)})
    variant_group = pick_manual_variant_group(unknown_features) or build_variant_group_key(name.strip(), features)
    if variant_group:
        meta_data.append({"key": "_mnsk7_bl_variant_group", "value": variant_group})
    variant_axis = pick_manual_variant_axis(unknown_features)
    if not variant_axis:
        if isinstance(unknown_features, dict) and any(
            normalize_key(key) == "model" and str(value).strip() for key, value in unknown_features.items()
        ):
            variant_axis = "model"
        else:
            variant_axis = infer_variant_axis(features)
    if variant_axis:
        meta_data.append({"key": "_mnsk7_bl_variant_axis", "value": variant_axis})
    payload["meta_data"] = meta_data

    bl_category_id = product.get("category_id")
    if category_resolver and bl_category_id not in (None, "", 0, "0"):
        woo_cat_id = category_resolver.resolve(bl_category_id)
        if woo_cat_id:
            payload["categories"] = [{"id": int(woo_cat_id)}]

    if tag_resolver is not None:
        payload["tags"] = tag_resolver.resolve_product_tags(product)

    return payload, features, unknown_features, None


def chunked(items, size):
    for idx in range(0, len(items), size):
        yield items[idx : idx + size]


class BlTagResolver:
    """Map BaseLinker inventory tags to Woo product_tag (BL -> Woo only)."""

    def __init__(self, bl, inventory_id):
        self.canonical_by_norm = {}
        for name in bl.list_inventory_tags(inventory_id):
            self.canonical_by_norm[normalize_key(name)] = format_catalog_tag_name(name)

    def resolve_product_tags(self, product):
        raw = product.get("tags") or []
        if not isinstance(raw, list):
            return []
        out = []
        seen = set()
        for item in raw:
            name = str(item).strip()
            if not name:
                continue
            canonical = self.canonical_by_norm.get(normalize_key(name))
            if not canonical:
                continue
            key = normalize_key(canonical)
            if key in seen:
                continue
            seen.add(key)
            out.append({"name": canonical})
        return out

    @property
    def allowed_names(self):
        return list(self.canonical_by_norm.values())


class BlCategoryResolver:
    def __init__(self, bl, woo, inventory_id, dry_run=False):
        self.bl_map = {}
        self.woo_map = {}
        self.dry_run = dry_run
        categories = bl.list_categories(inventory_id)
        for row in categories:
            cid = row.get("category_id")
            if cid is not None:
                self.bl_map[int(cid)] = row

        # Create Woo categories in parent-first order.
        pending = list(self.bl_map.values())
        created = {}
        while pending:
            progress = False
            next_pending = []
            for row in pending:
                bl_id = int(row.get("category_id"))
                parent_bl = int(row.get("parent_id") or 0)
                if parent_bl and parent_bl not in created:
                    next_pending.append(row)
                    continue
                parent_woo = created.get(parent_bl, 0) if parent_bl else 0
                woo_cat = woo.get_or_create_product_category(
                    row.get("name") or f"BL {bl_id}",
                    parent_id=parent_woo,
                    dry_run=dry_run,
                )
                woo_id = woo_cat.get("id") if woo_cat else None
                if woo_id:
                    created[bl_id] = int(woo_id)
                    self.woo_map[bl_id] = int(woo_id)
                progress = True
            if not progress:
                break
            pending = next_pending

    def resolve(self, bl_category_id):
        try:
            key = int(bl_category_id)
        except (TypeError, ValueError):
            return None
        return self.woo_map.get(key)


def resolve_inventory_id(bl, requested_inventory_id):
    if requested_inventory_id:
        return requested_inventory_id
    inventories = bl.list_inventories()
    if len(inventories) == 1:
        return str(inventories[0].get("inventory_id", "")).strip()
    if not inventories:
        raise SystemExit("Missing BASELINKER_INVENTORY_ID and BaseLinker returned no inventories")
    print("Missing BASELINKER_INVENTORY_ID. Available inventories:")
    for inventory in inventories:
        print(f"- {inventory.get('inventory_id')}: {inventory.get('name')}")
    raise SystemExit("Set BASELINKER_INVENTORY_ID before running sync")


def ensure_rebuild_is_explicit(args, woo_base_url):
    host = urllib.parse.urlparse(woo_base_url).hostname or ""
    if args.rebuild_catalog and not args.apply:
        return
    if args.rebuild_catalog and not args.confirm_rebuild:
        raise SystemExit("Refusing rebuild apply without --confirm-rebuild")
    if args.rebuild_catalog and args.apply and args.limit:
        raise SystemExit("Refusing rebuild apply with --limit. Use full import or dry-run first.")
    if args.rebuild_catalog and args.apply and "staging" not in host and not args.allow_production_host:
        raise SystemExit(
            f"Refusing production-like target host '{host}' without --allow-production-host"
        )


def sync_log(message):
    text = str(message)
    try:
        print(text, flush=True)
    except UnicodeEncodeError:
        enc = getattr(sys.stdout, "encoding", None) or "utf-8"
        sys.stdout.buffer.write((text + "\n").encode(enc, errors="replace"))
        sys.stdout.flush()


def main():
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    if hasattr(sys.stderr, "reconfigure"):
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")

    parser = argparse.ArgumentParser(
        description="Sync products from BaseLinker to WooCommerce (update existing by SKU, create missing)."
    )
    parser.add_argument("--env-file", default=".env", help="Path to .env file (default: .env in repo root)")
    parser.add_argument("--limit", type=int, default=0, help="Optional max number of BaseLinker products")
    parser.add_argument("--apply", action="store_true", help="Apply write operations (default is dry-run)")
    parser.add_argument(
        "--rebuild-catalog",
        action="store_true",
        help="Delete Woo products before importing BaseLinker products. Dry-run by default.",
    )
    parser.add_argument(
        "--confirm-rebuild",
        action="store_true",
        help="Required together with --apply --rebuild-catalog before deleting Woo products.",
    )
    parser.add_argument(
        "--allow-production-host",
        action="store_true",
        help="Allow destructive apply when WP_BASE_URL/WOO_BASE_URL does not contain staging.",
    )
    parser.add_argument(
        "--skip-categories",
        action="store_true",
        help="Do not map BaseLinker category_id to Woo product categories.",
    )
    args = parser.parse_args()

    env = {}
    env.update(load_env(args.env_file))
    env.update(os.environ)

    baselinker_token = (
        env.get("BASELINKER_API_TOKEN")
        or env.get("BASE_API_TOKEN")
        or env.get("base_api_token")
        or ""
    ).strip()
    inventory_id = env.get("BASELINKER_INVENTORY_ID", "").strip()
    price_group_id = env.get("BASELINKER_PRICE_GROUP_ID", "").strip()
    warehouse_id = env.get("BASELINKER_WAREHOUSE_ID", "").strip()
    language = env.get("BASELINKER_LANGUAGE", "pl").strip() or "pl"

    woo_base_url = (env.get("WP_BASE_URL") or env.get("WOO_BASE_URL") or "").strip()
    woo_key = (
        env.get("WOO_CONSUMER_KEY")
        or env.get("Woo_Klucz_konsumenta")
        or env.get("WC_CONSUMER_KEY")
        or ""
    ).strip()
    woo_secret = (
        env.get("WOO_CONSUMER_SECRET")
        or env.get("Woo_Tajny_konsumenta")
        or env.get("WC_CONSUMER_SECRET")
        or ""
    ).strip()

    publish_status = (env.get("BL_SYNC_PRODUCT_STATUS") or "publish").strip()
    create_missing = (env.get("BL_SYNC_CREATE_MISSING") or "1").strip() in {"1", "true", "TRUE", "yes", "YES"}
    dry_run = not args.apply

    if not baselinker_token:
        raise SystemExit("Missing BASELINKER_API_TOKEN")
    if not woo_base_url or not woo_key or not woo_secret:
        raise SystemExit("Missing WP_BASE_URL/WOO_BASE_URL and WOO_CONSUMER_KEY + WOO_CONSUMER_SECRET")

    ensure_rebuild_is_explicit(args, woo_base_url)

    bl = BaseLinkerClient(baselinker_token)
    inventory_id = resolve_inventory_id(bl, inventory_id)
    woo = WooClient(woo_base_url, woo_key, woo_secret)
    category_resolver = None
    tag_resolver = None
    if not args.skip_categories:
        print("Syncing BaseLinker categories -> Woo product_cat...")
        category_resolver = BlCategoryResolver(bl, woo, inventory_id, dry_run=dry_run)
        print(f"- woo categories mapped: {len(category_resolver.woo_map)}")
        print("")

    print("Loading BaseLinker inventory tags (BL -> Woo)...")
    tag_resolver = BlTagResolver(bl, inventory_id)
    print(f"- BL tags allowed: {len(tag_resolver.allowed_names)}")
    print("")

    print("BaseLinker -> Woo sync")
    print(f"- inventory_id: {inventory_id}")
    print(f"- language: {language}")
    print(f"- woo_host: {urllib.parse.urlparse(woo_base_url).hostname or woo_base_url}")
    print(f"- dry-run: {dry_run}")
    print(f"- create-missing: {create_missing}")
    print(f"- rebuild-catalog: {args.rebuild_catalog}")
    print("")

    limit = args.limit if args.limit > 0 else None
    product_ids = bl.list_product_ids(inventory_id, limit=limit)
    if not product_ids:
        print("No products returned from BaseLinker list call.")
        return

    created = 0
    updated = 0
    skipped = 0
    failed = 0
    features_synced = 0
    unknown_feature_counts = {}

    existing_products = woo.list_products(status="any")
    if args.rebuild_catalog:
        print(f"Woo products scheduled for deletion: {len(existing_products)}")
        if dry_run:
            for product in existing_products[:20]:
                print(
                    f"[DRY] DELETE wc_id={product.get('id')} "
                    f"sku={product.get('sku') or '-'} name={product.get('name') or '-'}"
                )
            if len(existing_products) > 20:
                print(f"[DRY] DELETE ... {len(existing_products) - 20} more products")
        else:
            for product in existing_products:
                woo.delete_product(product.get("id"), force=True)
                print(f"[OK] DELETE wc_id={product.get('id')} sku={product.get('sku') or '-'}")
                time.sleep(0.1)
        existing_products = []

    print(f"Fetched IDs: {len(product_ids)}")
    for id_batch in chunked(product_ids, 100):
        products = bl.get_products_data(inventory_id, id_batch)
        for product_id, product in products.items():
            payload, features, unknown_features, skip_reason = build_wc_payload(
                product=product,
                product_id=product_id,
                language=language,
                price_group_id=price_group_id,
                warehouse_id=warehouse_id,
                publish_status=publish_status,
                category_resolver=category_resolver,
                tag_resolver=tag_resolver,
            )
            if skip_reason:
                print(f"[SKIP] {skip_reason}")
                skipped += 1
                continue

            sku = payload["sku"]
            try:
                existing = None if args.rebuild_catalog else woo.find_product_by_sku(sku)
                wc_attributes = build_wc_attributes(woo, features, dry_run=dry_run) if features else []
                if wc_attributes:
                    base_attrs = existing.get("attributes", []) if existing else []
                    payload["attributes"] = replace_mapped_product_attributes(base_attrs, wc_attributes)
                    features_synced += len(wc_attributes)
                if existing:
                    payload["meta_data"] = merge_meta_data(existing.get("meta_data", []), payload["meta_data"])
                for label in unknown_features:
                    unknown_feature_counts[label] = unknown_feature_counts.get(label, 0) + 1

                if existing:
                    product_id_wc = existing.get("id")
                    if dry_run:
                        print(
                            f"[DRY] UPDATE sku={sku} wc_id={product_id_wc} "
                            f"features={len(wc_attributes)} unknown_features={len(unknown_features)}"
                        )
                    else:
                        upsert_wc_product_from_bl(woo, existing, payload)
                        print(
                            f"[OK] UPDATE sku={sku} wc_id={product_id_wc} type={existing.get('type')} "
                            f"features={len(wc_attributes)} unknown_features={len(unknown_features)}"
                        )
                    updated += 1
                else:
                    if not create_missing:
                        print(f"[SKIP] sku={sku}: not found in Woo and BL_SYNC_CREATE_MISSING=0")
                        skipped += 1
                    elif dry_run:
                        print(
                            f"[DRY] CREATE sku={sku} "
                            f"features={len(wc_attributes)} unknown_features={len(unknown_features)}"
                        )
                        created += 1
                    else:
                        result = woo.create_product(payload)
                        print(
                            f"[OK] CREATE sku={sku} wc_id={result.get('id')} "
                            f"features={len(wc_attributes)} unknown_features={len(unknown_features)}"
                        )
                        created += 1
            except Exception as error:
                print(f"[ERR] sku={sku}: {error}")
                failed += 1

            time.sleep(0.15)

    print("")
    print("Summary")
    print(f"- created: {created}")
    print(f"- updated: {updated}")
    print(f"- skipped: {skipped}")
    print(f"- failed: {failed}")
    print(f"- mapped feature attributes: {features_synced}")
    if unknown_feature_counts:
        print("- unknown BaseLinker features:")
        for label, count in sorted(unknown_feature_counts.items(), key=lambda row: (-row[1], row[0]))[:30]:
            print(f"  - {label}: {count}")
    if dry_run:
        print("Dry-run finished. Use --apply to execute updates.")


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        sys.exit(130)
