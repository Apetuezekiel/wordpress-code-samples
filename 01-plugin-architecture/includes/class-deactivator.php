<?php
/**
 * Plugin deactivation logic.
 *
 * @package EzekielApetu\PluginBoilerplate
 */

namespace EzekielApetu\PluginBoilerplate;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles everything that must happen when the plugin is deactivated.
 *
 * Note: deactivation is NOT uninstallation. Data and options are intentionally
 * preserved here. Destructive cleanup belongs in uninstall.php.
 */
class Deactivator {

    /**
     * Run all deactivation routines.
     *
     * WordPress calls this as a static callback from register_deactivation_hook().
     *
     * @return void
     */
    public static function deactivate(): void {
        self::clear_scheduled_events();
        self::flush_rewrite_rules();
    }

    /**
     * Remove all WP-Cron events registered by this plugin.
     *
     * Leaving orphaned cron hooks after deactivation would cause PHP notices
     * on every cron run because the callback no longer exists.
     *
     * @return void
     */
    private static function clear_scheduled_events(): void {
        $hooks = array(
            'ezekiel_boilerplate_daily_cleanup',
            'ezekiel_boilerplate_hourly_sync',
        );

        foreach ( $hooks as $hook ) {
            $timestamp = wp_next_scheduled( $hook );

            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook );
            }

            // Ensure no recurrences remain regardless of timestamp.
            wp_clear_scheduled_hook( $hook );
        }
    }

    /**
     * Flush rewrite rules so any custom endpoints registered by the plugin
     * are removed from the rewrite table immediately.
     *
     * @return void
     */
    private static function flush_rewrite_rules(): void {
        flush_rewrite_rules();
    }
}
