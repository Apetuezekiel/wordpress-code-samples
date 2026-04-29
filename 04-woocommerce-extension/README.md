# 04 — WooCommerce Shipping Method Extension

A custom WooCommerce shipping method that provides a flat rate with an optional free-shipping threshold. Built by extending `WC_Shipping_Method` and integrating with the WooCommerce Settings API.

## Features

- Extends `WC_Shipping_Method` — plays nicely with shipping zones, rates, and the tax engine
- `instance-settings` support — configurable per shipping zone in WooCommerce > Settings > Shipping
- Flat rate with support for WC cost variables (`[qty]`, `[cost]`)
- Auto-converts to free shipping when cart subtotal ≥ configurable threshold
- Optional restriction to a specific shipping class
- `sanitize_cost()` handles both plain decimals and WC cost expressions
- Correct `add_rate()` call with `meta_data` for downstream filtering

## Registration

Add both hooks to your plugin's bootstrap file (or a dedicated file loaded on `plugins_loaded`):

```php
// 1. Load the class after WC shipping is initialised.
add_action( 'woocommerce_shipping_init', function () {
    require_once plugin_dir_path( __FILE__ ) . 'class-custom-shipping-method.php';
} );

// 2. Register the method with WooCommerce.
add_filter( 'woocommerce_shipping_methods', function ( array $methods ): array {
    $methods['ezekiel_flat_rate'] = \EzekielApetu\WooCommerce\Flat_Rate_Shipping::class;
    return $methods;
} );
```

After activation go to **WooCommerce > Settings > Shipping**, add or edit a zone, and click **Add shipping method** to see _Ezekiel Flat Rate_ in the list.

## Settings available per zone

| Setting | Type | Description |
|---------|------|-------------|
| Enable/Disable | Checkbox | Toggle the method on/off within the zone |
| Method title | Text | Label shown to customers at checkout |
| Flat rate cost | Price | Base cost; supports WC variables like `[qty] * 1.50` |
| Free shipping threshold | Price | Cart subtotal above which cost drops to zero |
| Tax status | Select | Taxable / Not taxable |
| Restrict to shipping class | Select | Limit to orders containing items of a given class |
