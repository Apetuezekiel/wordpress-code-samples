<?php
/**
 * Custom WooCommerce Shipping Method — Flat Rate with Threshold Free Shipping.
 *
 * @package EzekielApetu\WooCommerce
 */

namespace EzekielApetu\WooCommerce;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Guard: only load when WooCommerce is active and WC_Shipping_Method exists.
if ( ! class_exists( 'WC_Shipping_Method' ) ) {
    return;
}

/**
 * Provides a flat-rate shipping option that automatically becomes free
 * when the cart subtotal meets a configurable threshold.
 *
 * Registration:
 *
 *   add_action( 'woocommerce_shipping_init', function () {
 *       require_once 'class-custom-shipping-method.php';
 *   } );
 *
 *   add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
 *       $methods['ezekiel_flat_rate'] = \EzekielApetu\WooCommerce\Flat_Rate_Shipping::class;
 *       return $methods;
 *   } );
 */
class Flat_Rate_Shipping extends \WC_Shipping_Method {

    /**
     * Unique method identifier used by WooCommerce internally.
     *
     * @var string
     */
    const METHOD_ID = 'ezekiel_flat_rate';

    /**
     * Constructor — set method identity and load settings.
     *
     * @param int $instance_id Shipping zone instance ID (0 when not yet zoned).
     */
    public function __construct( int $instance_id = 0 ) {
        $this->id                 = self::METHOD_ID;
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'Ezekiel Flat Rate', 'ezekiel-wc' );
        $this->method_description = __( 'A flat shipping rate that converts to free shipping when the cart subtotal meets a configurable threshold.', 'ezekiel-wc' );
        $this->supports           = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();

        // Pull instance settings into public properties that WC reads.
        $this->enabled = $this->get_option( 'enabled' );
        $this->title   = $this->get_option( 'title' );
    }

    /**
     * Load form fields and bind settings.
     *
     * @return void
     */
    public function init(): void {
        $this->init_form_fields();
        $this->init_settings();

        // Persist settings whenever they change in the admin.
        add_action(
            'woocommerce_update_options_shipping_' . $this->id,
            array( $this, 'process_admin_options' )
        );
    }

    /**
     * Define the settings fields shown in the shipping zone admin modal.
     *
     * @return void
     */
    public function init_form_fields(): void {
        $this->instance_form_fields = array(
            'enabled'            => array(
                'title'   => __( 'Enable / Disable', 'ezekiel-wc' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this shipping method', 'ezekiel-wc' ),
                'default' => 'yes',
            ),
            'title'              => array(
                'title'       => __( 'Method title', 'ezekiel-wc' ),
                'type'        => 'text',
                'description' => __( 'Label shown to the customer at checkout.', 'ezekiel-wc' ),
                'default'     => __( 'Standard Shipping', 'ezekiel-wc' ),
                'desc_tip'    => true,
            ),
            'cost'               => array(
                'title'             => __( 'Flat rate cost', 'ezekiel-wc' ),
                'type'              => 'price',
                'description'       => __( 'Enter the base shipping cost. Supports WC cost variables, e.g. [qty] * 1.50.', 'ezekiel-wc' ),
                'default'           => '5.00',
                'desc_tip'          => true,
                'sanitize_callback' => array( $this, 'sanitize_cost' ),
            ),
            'free_threshold'     => array(
                'title'       => __( 'Free shipping threshold', 'ezekiel-wc' ),
                'type'        => 'price',
                'description' => __( 'Cart subtotal above which shipping becomes free. Leave blank to disable.', 'ezekiel-wc' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'tax_status'         => array(
                'title'   => __( 'Tax status', 'ezekiel-wc' ),
                'type'    => 'select',
                'default' => 'taxable',
                'options' => array(
                    'taxable' => __( 'Taxable', 'ezekiel-wc' ),
                    'none'    => __( 'Not taxable', 'ezekiel-wc' ),
                ),
            ),
            'requires_class'     => array(
                'title'       => __( 'Restrict to shipping class', 'ezekiel-wc' ),
                'type'        => 'select',
                'description' => __( 'Optionally limit this method to orders containing items from a specific shipping class.', 'ezekiel-wc' ),
                'default'     => '',
                'options'     => $this->get_shipping_class_options(),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Evaluate shipping eligibility and add a rate to the session.
     *
     * @param array $package The WooCommerce shipping package (destination + contents).
     * @return void
     */
    public function calculate_shipping( $package = array() ): void {
        // Check shipping class restriction before going further.
        if ( ! $this->package_meets_class_requirement( $package ) ) {
            return;
        }

        $subtotal  = (float) WC()->cart->get_displayed_subtotal();
        $threshold = (float) $this->get_option( 'free_threshold' );
        $cost      = $this->get_option( 'cost' );

        // Evaluate WC cost variables ([qty], [cost], etc.) via parent helper.
        $evaluated_cost = $this->evaluate_cost(
            $cost,
            array(
                'qty'  => $this->get_package_item_qty( $package ),
                'cost' => $this->get_package_cost( $package ),
            )
        );

        // Override to free when threshold is set and cart meets it.
        if ( $threshold > 0 && $subtotal >= $threshold ) {
            $evaluated_cost = 0;
        }

        $label = $this->title;
        if ( $threshold > 0 && $evaluated_cost > 0 ) {
            $label .= ' ' . sprintf(
                /* translators: %s formatted cart total needed for free shipping */
                __( '(free over %s)', 'ezekiel-wc' ),
                wc_price( $threshold )
            );
        }

        $this->add_rate(
            array(
                'id'        => $this->get_rate_id(),
                'label'     => $label,
                'cost'      => $evaluated_cost,
                'taxes'     => false,       // Let WC calculate tax from tax_status.
                'calc_tax'  => 'per_order',
                'meta_data' => array(
                    'free_threshold' => $threshold,
                    'base_cost'      => $evaluated_cost,
                ),
            )
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build the total cost of items in the shipping package.
     *
     * @param array $package WooCommerce shipping package.
     * @return float
     */
    private function get_package_cost( array $package ): float {
        $cost = 0.0;

        foreach ( $package['contents'] as $item ) {
            $cost += (float) $item['line_total'];
        }

        return $cost;
    }

    /**
     * Count total quantity of items in the package.
     *
     * @param array $package WooCommerce shipping package.
     * @return int
     */
    private function get_package_item_qty( array $package ): int {
        $qty = 0;

        foreach ( $package['contents'] as $item ) {
            $qty += (int) $item['quantity'];
        }

        return $qty;
    }

    /**
     * Determine whether the package satisfies the shipping class restriction.
     *
     * @param array $package WooCommerce shipping package.
     * @return bool
     */
    private function package_meets_class_requirement( array $package ): bool {
        $required_class = $this->get_option( 'requires_class' );

        if ( empty( $required_class ) ) {
            return true;
        }

        foreach ( $package['contents'] as $item ) {
            $product = $item['data'];

            if ( $product instanceof \WC_Product && (string) $product->get_shipping_class() === $required_class ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return an assoc array of shipping class slug → name for the settings select.
     *
     * @return array<string, string>
     */
    private function get_shipping_class_options(): array {
        $options  = array( '' => __( '— Any shipping class —', 'ezekiel-wc' ) );
        $classes  = WC()->shipping()->get_shipping_classes();

        foreach ( $classes as $class ) {
            $options[ $class->slug ] = $class->name;
        }

        return $options;
    }

    /**
     * Sanitize the cost field value.
     *
     * Strips currency symbols and whitespace; allows WC cost variables
     * like [qty] through untouched.
     *
     * @param string $value Raw field value.
     * @return string Sanitized value.
     */
    public function sanitize_cost( string $value ): string {
        $value = trim( $value );

        // Preserve WC cost variables — only sanitize plain numeric strings.
        if ( ! preg_match( '/\[/', $value ) ) {
            $value = wc_format_decimal( $value );
        }

        return $value;
    }
}
