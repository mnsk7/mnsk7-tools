#!/usr/bin/env python3
"""Sync products from BaseLinker inventory into WooCommerce (staging-safe)."""
import argparse
import base64
import datetime
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
        "aliases": ["materiał", "material", "obrabiany materiał", "zastosowanie"],
    },
    {
        "label": "Średnica trzpienia",
        "slug": "srednica-trzpienia",
        "aliases": ["średnica trzpienia", "srednica trzpienia", "trzpień", "trzpien", "shk"],
    },
    {
        "label": "Średnica robocza",
        "slug": "srednica",
        "aliases": ["średnica robocza", "srednica robocza", "średnica", "srednica", "fi", "d"],
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
        "aliases": ["typ", "typ frezu", "typ pilnika", "rodzaj"],
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
]


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


FEATURE_ALIAS_INDEX = {
    normalize_key(alias): definition
    for definition in FEATURE_ATTRIBUTE_MAP
    for alias in definition["aliases"]
}


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

    def create_product(self, payload):
        return self.request("POST", "/products", payload)

    def update_product(self, product_id, payload):
        return self.request("PUT", f"/products/{product_id}", payload)

    def list_attributes(self):
        return self.request("GET", "/products/attributes?per_page=100")

    def get_or_create_attribute(self, label, slug, dry_run=False):
        cache_key = slug
        if cache_key in self.attribute_cache:
            return self.attribute_cache[cache_key]

        attrs = self.list_attributes()
        if isinstance(attrs, list):
            for attr in attrs:
                if str(attr.get("slug", "")).strip() == slug:
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
            if normalize_key(term.get("name", "")) == normalize_key(term_name):
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


def build_wc_payload(product, product_id, language, price_group_id, warehouse_id, publish_status):
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
    if features:
        meta_data.append({"key": "_mnsk7_bl_features_raw", "value": json.dumps(features, ensure_ascii=False)})
    payload["meta_data"] = meta_data

    return payload, features, unknown_features, None


def chunked(items, size):
    for idx in range(0, len(items), size):
        yield items[idx : idx + size]


def main():
    parser = argparse.ArgumentParser(
        description="Sync products from BaseLinker to WooCommerce (update existing by SKU, create missing)."
    )
    parser.add_argument("--env-file", default=".env", help="Path to .env file (default: .env in repo root)")
    parser.add_argument("--limit", type=int, default=0, help="Optional max number of BaseLinker products")
    parser.add_argument("--apply", action="store_true", help="Apply write operations (default is dry-run)")
    args = parser.parse_args()

    env = {}
    env.update(load_env(args.env_file))
    env.update(os.environ)

    baselinker_token = env.get("BASELINKER_API_TOKEN", "").strip()
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
    if not inventory_id:
        raise SystemExit("Missing BASELINKER_INVENTORY_ID")
    if not woo_base_url or not woo_key or not woo_secret:
        raise SystemExit("Missing WP_BASE_URL/WOO_BASE_URL and WOO_CONSUMER_KEY + WOO_CONSUMER_SECRET")

    print("BaseLinker -> Woo sync")
    print(f"- inventory_id: {inventory_id}")
    print(f"- language: {language}")
    print(f"- dry-run: {dry_run}")
    print(f"- create-missing: {create_missing}")
    print("")

    bl = BaseLinkerClient(baselinker_token)
    woo = WooClient(woo_base_url, woo_key, woo_secret)

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
            )
            if skip_reason:
                print(f"[SKIP] {skip_reason}")
                skipped += 1
                continue

            sku = payload["sku"]
            try:
                existing = woo.find_product_by_sku(sku)
                wc_attributes = build_wc_attributes(woo, features, dry_run=dry_run) if features else []
                if wc_attributes:
                    base_attrs = existing.get("attributes", []) if existing else []
                    payload["attributes"] = merge_product_attributes(base_attrs, wc_attributes)
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
                        woo.update_product(product_id_wc, payload)
                        print(
                            f"[OK] UPDATE sku={sku} wc_id={product_id_wc} "
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
