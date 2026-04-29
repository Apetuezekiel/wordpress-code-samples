# WordPress Code Samples

These are standalone code samples demonstrating patterns used in production WordPress development. They are not tied to any specific client project — most of my plugin and theme work is private/client-owned under NDA. These samples reflect the architecture, coding standards, and PHP discipline I apply in real engagements.

## What Each Folder Demonstrates

| Folder | What it shows |
|--------|--------------|
| `01-plugin-architecture/` | OOP plugin structure with PSR-4 autoloading, a hook Loader, and separate Activator/Deactivator classes |
| `02-rest-api-endpoint/` | Custom REST API route (`ezekiel/v1`) with versioning, permission callbacks, sanitization, validation, and proper HTTP responses |
| `03-gutenberg-block/` | Custom Gutenberg block (Testimonial) built with `@wordpress/scripts`, `block.json`, `InspectorControls`, `RichText`, and `ColorPicker` |
| `04-woocommerce-extension/` | Custom WooCommerce shipping method extending `WC_Shipping_Method` with settings API and rate calculation |
| `05-acf-integration/` | Programmatic ACF block registration with render callbacks, field retrieval, and template rendering |
| `06-multisite-helper/` | Static utility class for WordPress Multisite — iterating sites, switching blog context, and reading network-wide options |

## Background

I am a senior WordPress and fullstack developer with 7 years of PHP experience and 5 years specialising in WordPress plugin development, custom theme engineering, WooCommerce extensions, and Multisite architecture. My production stack also includes React/Next.js, Laravel, Node.js, Supabase, and PostgreSQL.

- **GitHub:** https://github.com/Apetuezekiel
- **Portfolio:** *(to be added)*
- **Contact:** apetudevbeast@gmail.com
