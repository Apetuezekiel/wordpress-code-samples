# 02 — Custom REST API Endpoint

Demonstrates building a versioned, schema-driven REST API resource in WordPress without relying on the default posts/terms infrastructure.

## Route map

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/wp-json/ezekiel/v1/items` | Paginated list with optional `status` and `search` query params |
| GET | `/wp-json/ezekiel/v1/items/{id}` | Single item by ID |
| POST | `/wp-json/ezekiel/v1/items` | Create a new item (requires `publish_posts` capability) |

## Key patterns shown

- **Namespace + versioning** — `ezekiel/v1` keeps routes isolated and allows breaking changes in `v2` without affecting existing consumers.
- **Separate permission callbacks** — read is public; write requires authentication, returning `rest_authorization_required_code()` (401 vs 403) correctly.
- **Sanitization at ingress** — `sanitize_text_field`, `absint`, `wp_kses_post` applied via `sanitize_callback` on every arg definition.
- **Custom validator** — `validate_item_id` performs a DB existence check so handlers never need to handle missing records.
- **Prepared statements** — all `$wpdb` queries use `$wpdb->prepare()`.
- **Pagination headers** — `X-WP-Total` and `X-WP-TotalPages` match core WordPress API conventions.
- **JSON Schema** — `get_item_schema()` returns a draft-4 schema that the WP REST API uses for documentation and validation.

## Registration

```php
add_action( 'rest_api_init', function () {
    $endpoint = new \EzekielApetu\RestApi\Custom_Endpoint();
    $endpoint->register_routes();
} );
```
