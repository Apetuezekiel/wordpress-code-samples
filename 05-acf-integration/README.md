# 05 — ACF Block Integration

Demonstrates registering multiple ACF-powered Gutenberg blocks programmatically — no GUI block registration, no database-stored field groups for the block declarations themselves.

## Blocks included

| Block name | ACF name | Description |
|------------|----------|-------------|
| Hero Section | `ezekiel-hero` | Full-width hero with headline, subtext, CTA, and background image |
| Feature Grid | `ezekiel-feature-grid` | Repeater-based icon + title + description cards |
| Team Member | `ezekiel-team-member` | Photo, name, role, bio, and social links |
| CTA Banner | `ezekiel-cta-banner` | Full-width call-to-action with one or two buttons |

## Key patterns shown

- **ACF guard** — `function_exists( 'acf_register_block_type' )` prevents fatal errors when ACF is deactivated.
- **Programmatic registration** — all blocks are registered in PHP rather than the ACF GUI, so they are version-controlled and portable across environments.
- **Render callbacks** — each block has a dedicated method; no PHP template files are scattered across the theme.
- **Output escaping at render time** — every field value is escaped with the appropriate function (`esc_html`, `esc_url`, `wp_kses_post`, `sanitize_hex_color`) directly in the render callback.
- **Empty state UI** — each render callback gracefully handles missing data in the editor preview.
- **BEM class structure** — `build_block_classes()` merges ACF-provided `className` and `align` with the block's own BEM root class.

## Registration

```php
$blocks = new \EzekielApetu\AcfBlocks\ACF_Blocks(
    plugin_dir_path( __FILE__ ) . 'templates/blocks/',
    plugin_dir_url( __FILE__ ) . 'assets/'
);

add_action( 'acf/init', array( $blocks, 'register_blocks' ) );
```

## ACF field groups

Field groups for each block should be created in ACF > Field Groups and assigned to the corresponding block location rule:

```
Show this field group if:  Block  ==  Hero Section
```

Or exported to PHP via **ACF > Tools > Export** and loaded via `acf/include_fields`.
