# 06 — Multisite Helper

A static utility class for common WordPress Multisite operations — enumerating sites, running code in the context of each site, and reading or writing options network-wide.

## Key patterns shown

- **Static utility design** — no instantiation needed; private constructor prevents misuse.
- **`switch_to_blog` / `restore_current_blog` in `try/finally`** — guarantees restoration of global state even when the callable throws, avoiding the common bug of leaving WordPress "stuck" in the wrong blog context.
- **`get_sites()` over deprecated `wp_get_sites()`** — uses the modern API with sane defaults (excludes archived, deleted, spam).
- **`get_blog_option()` for cross-site reads** — avoids manual context switching just to read an option.
- **Sparse returns** — `get_site_option_across_network()` excludes sites with default values by default, keeping the result set small.
- **WP-CLI friendly** — `get_cli_site_list()` returns a normalized array that maps directly to `WP_CLI\Utils\format_items()`.

## Usage examples

```php
use EzekielApetu\Multisite\Multisite_Manager;

// List all sites.
$sites = Multisite_Manager::get_all_sites();

// Run a callback in the context of every site.
$post_counts = Multisite_Manager::run_for_all_sites(
    fn( int $blog_id ) => wp_count_posts()->publish
);

// Read an option from every site.
$maintenance_flags = Multisite_Manager::get_site_option_across_network( 'maintenance_mode' );

// Run something in a single specific site's context.
$theme = Multisite_Manager::run_in_site_context( 3, fn() => get_option( 'stylesheet' ) );

// Write an option to every site.
Multisite_Manager::set_site_option_across_network( 'analytics_id', 'UA-XXXXXXX-1' );
```

## WP-CLI integration example

```php
// In a WP_CLI command class:
$rows = Multisite_Manager::get_cli_site_list();
WP_CLI\Utils\format_items( 'table', $rows, array( 'blog_id', 'url', 'registered' ) );
```
