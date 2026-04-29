# 03 — Custom Gutenberg Block (Testimonial)

A production-quality Gutenberg block built with `@wordpress/scripts`. It renders a testimonial with a quote, author, role, star rating, optional avatar, and full colour control — all without jQuery or custom build tooling beyond the standard WP scripts pipeline.

## Features

- `block.json` — single source of truth for name, attributes, supports, and asset handles
- `InspectorControls` sidebar with `ColorPicker`, `RangeControl`, and `MediaUpload`
- `RichText` for inline editing of quote, author, and role
- `RichText.Content` in save for correct serialisation
- `useBlockProps` / `useBlockProps.save` for proper wrapper attribute merging
- Star rating rendered accessibly with `aria-label`
- `align: ['wide', 'full']` support wired through `supports`

## Build

```bash
npm install
npm run build       # production build → build/
npm run start       # watch mode for development
npm run lint:js     # ESLint via wp-scripts
npm run lint:css    # Stylelint via wp-scripts
```

## Registration (PHP side)

```php
add_action( 'init', function () {
    register_block_type( __DIR__ . '/block.json' );
} );
```

`block.json` declares `editorScript`, `editorStyle`, `style`, and `viewScript` paths — WordPress resolves and enqueues them automatically.

## Attribute storage

| Attribute | Source | Selector |
|-----------|--------|----------|
| `quote` | `html` | `blockquote p` |
| `author` | `html` | `cite` |
| `role` | `html` | `.testimonial__role` |
| `backgroundColor` | stored in attributes JSON | — |
| `textColor` | stored in attributes JSON | — |
| `avatarUrl` | stored in attributes JSON | — |
| `rating` | stored in attributes JSON | — |
