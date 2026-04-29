<?php
/**
 * Plugin activation logic.
 *
 * @package EzekielApetu\PluginBoilerplate
 */

namespace EzekielApetu\PluginBoilerplate;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles everything that must happen when the plugin is activated.
 *
 * Called via register_activation_hook() — runs in a separate HTTP request,
 * before the plugin's normal bootstrap, so no assumptions about loaded classes.
 */
class Activator {

    /**
     * Database table version key stored in wp_options.
     *
     * Bump this constant whenever the schema changes so upgrade logic can
     * detect and migrate existing installs.
     *
     * @var string
     */
    const DB_VERSION = '1.0.0';

    /**
     * Run all activation routines.
     *
     * WordPress calls this as a static callback, so it is intentionally static.
     *
     * @return void
     */
    public static function activate(): void {
        self::check_requirements();
        self::create_tables();
        self::set_default_options();
        self::flush_rewrite_rules();
    }

    /**
     * Abort activation with an admin notice if minimum requirements are not met.
     *
     * @return void
     */
    private static function check_requirements(): void {
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            deactivate_plugins( plugin_basename( EZEKIEL_BOILERPLATE_FILE ) );
            wp_die(
                esc_html__(
                    'Ezekiel Plugin Boilerplate requires PHP 8.0 or higher. Please upgrade PHP and try again.',
                    'ezekiel-plugin-boilerplate'
                ),
                esc_html__( 'Plugin Activation Error', 'ezekiel-plugin-boilerplate' ),
                array( 'back_link' => true )
            );
        }

        if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
            deactivate_plugins( plugin_basename( EZEKIEL_BOILERPLATE_FILE ) );
            wp_die(
                esc_html__(
                    'Ezekiel Plugin Boilerplate requires WordPress 6.0 or higher.',
                    'ezekiel-plugin-boilerplate'
                ),
                esc_html__( 'Plugin Activation Error', 'ezekiel-plugin-boilerplate' ),
                array( 'back_link' => true )
            );
        }
    }

    /**
     * Create any custom database tables required by the plugin.
     *
     * Uses dbDelta() so this method is safe to call on upgrades as well —
     * it only makes changes when the schema differs from what exists.
     *
     * @return void
     */
    private static function create_tables(): void {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'ezekiel_boilerplate_items';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            title       VARCHAR(255)        NOT NULL DEFAULT '',
            content     LONGTEXT            NOT NULL,
            status      VARCHAR(20)         NOT NULL DEFAULT 'active',
            created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id  (user_id),
            KEY status   (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'ezekiel_boilerplate_db_version', self::DB_VERSION );
    }

    /**
     * Populate default plugin options if they do not yet exist.
     *
     * add_option() is a no-op when the key already exists, making this
     * idempotent across repeated activations.
     *
     * @return void
     */
    private static function set_default_options(): void {
        add_option(
            'ezekiel_boilerplate_settings',
            array(
                'enable_feature_x' => false,
                'items_per_page'   => 10,
                'api_endpoint'     => '',
            )
        );

        add_option( 'ezekiel_boilerplate_version', EZEKIEL_BOILERPLATE_VERSION );
    }

    /**
     * Schedule a single flush of rewrite rules on the next admin page load.
     *
     * Calling flush_rewrite_rules() directly during activation is expensive;
     * the option approach defers the cost to one admin request.
     *
     * @return void
     */
    private static function flush_rewrite_rules(): void {
        // The plugin registers custom post types / taxonomies on init, so we
        // need rules flushed after those are registered. Setting this option
        // signals our init callback to flush once and then delete the option.
        add_option( 'ezekiel_boilerplate_flush_rewrite_rules', true );
    }
}
