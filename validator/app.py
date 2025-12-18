import json
import requests
import extruct
from flask import Flask, request, jsonify
from jsonschema import validate, ValidationError
import logging

app = Flask(__name__)

# Basic Logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Common Schema.org Definitions (Simplified)
# These schemas enforce that specific types have at least their core properties.

SCHEMAS = {
    "Article": {
        "type": "object",
        "properties": {
            "@context": {"type": "string"},
            "@type": {"type": "string", "pattern": "^Article$"},
            "headline": {"type": "string"},
            "image": {"type": ["string", "array", "object"]},
            "datePublished": {"type": "string"},
            "author": {"type": ["object", "array", "string"]}
        },
        "required": ["@context", "@type", "headline", "image", "author", "datePublished"]
    },
    "Recipe": {
        "type": "object",
        "properties": {
            "@context": {"type": "string"},
            "@type": {"type": "string", "pattern": "^Recipe$"},
            "name": {"type": "string"},
            "image": {"type": ["string", "array", "object"]},
            "author": {"type": ["object", "array", "string"]},
            "description": {"type": "string"},
            "recipeIngredient": {"type": "array"}
        },
        "required": ["@context", "@type", "name", "image", "author", "recipeIngredient"]
    },
     "Product": {
        "type": "object",
        "properties": {
            "@context": {"type": "string"},
            "@type": {"type": "string", "pattern": "^Product$"},
            "name": {"type": "string"},
            "description": {"type": "string"},
            "image": {"type": ["string", "array", "object"]},
             "offers": {"type": ["object", "array"]}
        },
        "required": ["@context", "@type", "name"]
    },
    "Organization": {
        "type": "object",
         "properties": {
            "@context": {"type": "string"},
            "@type": {"type": "string", "pattern": "^Organization$"},
            "name": {"type": "string"},
            "url": {"type": "string"},
            "logo": {"type": ["string", "object"]}
        },
        "required": ["@context", "@type", "name", "url"]
    },
    "WebSite": {
        "type": "object",
         "properties": {
            "@context": {"type": "string"},
            "@type": {"type": "string", "pattern": "^WebSite$"},
            "url": {"type": "string"}
        },
        "required": ["@context", "@type", "url"]
    }
}

# Fallback schema for unknown types
GENERIC_SCHEMA = {
    "type": "object",
    "properties": {
        "@context": {"type": "string"},
        "@type": {"type": "string"}
    },
    "required": ["@context", "@type"]
}

@app.route('/validate', methods=['POST'])
def validate_url():
    data = request.get_json()
    if not data or 'url' not in data:
        return jsonify({'status': 'FAILED', 'reason': 'Missing "url" in request body'}), 400

    target_url = data['url']
    logger.info(f"Validating URL: {target_url}")

    try:
        # Fetch the URL (Insecure as requested)
        # Verify=False is used because local environments might have self-signed certs
        response = requests.get(target_url, verify=False, timeout=10, headers={'User-Agent': 'Mozilla/5.0 (compatible; LocalValidator/1.0)'})
        response.raise_for_status()

        # Extract JSON-LD
        metadata = extruct.extract(response.text, base_url=target_url)
        json_ld_data = metadata.get('json-ld', [])

        if not json_ld_data:
             return jsonify({
                'status': 'FAILED',
                'reason': 'No JSON-LD data found on page',
                'url': target_url
            }), 200

        # Validate each JSON-LD block
        errors = []
        valid_count = 0

        for index, item in enumerate(json_ld_data):
            try:
                item_type = item.get('@type')

                # Handle cases where @type is a list
                if isinstance(item_type, list):
                    item_type = item_type[0]

                schema = SCHEMAS.get(item_type, GENERIC_SCHEMA)

                # Validate against the selected schema
                validate(instance=item, schema=schema)

                # Check context is schema.org
                context = item.get("@context", "")
                if isinstance(context, str) and "schema.org" not in context:
                     errors.append(f"Item {index} ({item_type}): @context is not schema.org")
                elif isinstance(context, list):
                    pass # Complex context handling skipped for now
                elif isinstance(context, dict):
                     pass # Complex context handling skipped for now

                valid_count += 1

            except ValidationError as ve:
                errors.append(f"Item {index} ({item_type}): Validation Failed - {ve.message} in path {ve.path}")
            except Exception as e:
                errors.append(f"Item {index}: Unexpected error {str(e)}")

        if errors:
            return jsonify({
                'status': 'FAILED',
                'reason': 'Validation errors found',
                'errors': errors,
                'data': json_ld_data
            }), 200

        return jsonify({
            'status': 'OK',
            'message': f"Found {valid_count} valid JSON-LD blocks",
            'data': json_ld_data
        }), 200

    except requests.exceptions.RequestException as e:
        return jsonify({'status': 'FAILED', 'reason': f"Network error: {str(e)}"}), 200
    except Exception as e:
        logger.error(f"Error validating {target_url}: {e}")
        return jsonify({'status': 'FAILED', 'reason': f"Internal error: {str(e)}"}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=3344)
