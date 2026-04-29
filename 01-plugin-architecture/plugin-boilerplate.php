<?php
/**
 * Plugin Name:       Ezekiel Apetu Plugin Boilerplate
 * Plugin URI:        https://github.com/Apetuezekiel/wordpress-code-samples
 * Description:       A production-quality plugin boilerplate demonstrating OOP architecture, PSR-4 autoloading, and clean hook management.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Ezekiel Apetu
 * Author URI:        https://github.com/Apetuezekiel
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ezekiel-plugin-boilerplate
 * Domain Path:       /languages
 *
 * @package EzekielApetu\PluginBoilerplate
 */

namespace EzekielApetu\PluginBoilerplate;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'EZEKIEL_BOILERPLATE_VERSION', '1.0.0' );
define( 'EZEKIEL_BOILERPLATE_FILE', __FILE__ );
define( 'EZEKIEL_BOILERPLATE_DIR', plugin_dir_path( __FILE__ ) );
define( 'EZEKIEL_BOILERPLATE_URL', plugin_dir_url( __FILE__ ) );

// Autoload via Composer if available, otherwise fall back to manual requires.
if ( file_exists( EZEKIEL_BOILERPLATE_DIR . 'vendor/autoload.php' ) ) {
    require_once EZEKIEL_BOILERPLATE_DIR . 'vendor/autoload.php';
} else {
    require_once EZEKIEL_BOILERPLATE_DIR . 'includes/class-loader.php';
    require_once EZEKIEL_BOILERPLATE_DIR . 'includes/class-activator.php';
    require_once EZEKIEL_BOILERPLATE_DIR . 'includes/class-deactivator.php';
}

// Register activation and deactivation hooks before any instance is created.
register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

/**
 * Main plugin class.
 *
 * Orchestrates all sub-components by wiring hooks through the Loader.
 */
final class Plugin {

    /**
     * Shared hook loader instance.
     *
     * @var Loader
     */
    private Loader $loader;

    /**
     * Plugin version.
     *
     * @var string
     */
    private string $version;

    /**
     * Text domain used for translations.
     *
     * @var string
     */
    private string $text_domain;

    /**
     * Initialise the plugin.
     */
    public function __construct() {
        $this->version     = EZEKIEL_BOILERPLATE_VERSION;
        $this->text_domain = 'ezekiel-plugin-boilerplate';
        $this->loader      = new Loader();

        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the plugin text domain for i18n.
     *
     * @return void
     */
    private function set_locale(): void {
        $this->loader->add_action(
            'plugins_loaded',
            $this,
            'load_plugin_textdomain'
        );
    }

    /**
     * Register all hooks for the admin area.
     *
     * @return void
     */
    private function define_admin_hooks(): void {
        $this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_assets' );
        $this->loader->add_filter( 'plugin_action_links_' . plugin_basename( EZEKIEL_BOILERPLATE_FILE ), $this, 'add_action_links' );
    }

    /**
     * Register all hooks for the public-facing side.
     *
     * @return void
     */
    private function define_public_hooks(): void {
        $this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_assets' );
        $this->loader->add_filter( 'the_content', $this, 'filter_content' );
    }

    /**
     * Load the plugin text domain.
     *
     * @return void
     */
    public function load_plugin_textdomain(): void {
        load_plugin_textdomain(
            $this->text_domain,
            false,
            dirname( plugin_basename( EZEKIEL_BOILERPLATE_FILE ) ) . '/languages/'
        );
    }

    /**
     * Enqueue admin-side scripts and styles.
     *
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function enqueue_admin_assets( string $hook_suffix ): void {
        // Scope assets to this plugin's pages only.
        if ( strpos( $hook_suffix, 'ezekiel-boilerplate' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'ezekiel-boilerplate-admin',
            EZEKIEL_BOILERPLATE_URL . 'assets/css/admin.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            'ezekiel-boilerplate-admin',
            EZEKIEL_BOILERPLATE_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            $this->version,
            true
        );
    }

    /**
     * Enqueue public-facing scripts and styles.
     *
     * @return void
     */
    public function enqueue_public_assets(): void {
        wp_enqueue_style(
            'ezekiel-boilerplate-public',
            EZEKIEL_BOILERPLATE_URL . 'assets/css/public.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            'ezekiel-boilerplate-public',
            EZEKIEL_BOILERPLATE_URL . 'assets/js/public.js',
            array(),
            $this->version,
            true
        );

        wp_localize_script(
            'ezekiel-boilerplate-public',
            'ezekielBoilerplate',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ezekiel_boilerplate_nonce' ),
            )
        );
    }

    /**
     * Add a Settings link to the plugin action links on the Plugins screen.
     *
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    public function add_action_links( array $links ): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'options-general.php?page=ezekiel-boilerplate' ) ),
            esc_html__( 'Settings', 'ezekiel-plugin-boilerplate' )
        );

        array_unshift( $links, $settings_link );

        return $links;
    }

    /**
     * Example content filter — demonstrates filter hook wiring.
     *
     * @param string $content The post content.
     * @return string Optionally modified content.
     */
    public function filter_content( string $content ): string {
        // Only act on singular post views within The Loop.
        if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        return $content;
    }

    /**
     * Execute all queued hooks via the Loader.
     *
     * @return void
     */
    public function run(): void {
        $this->loader->run();
    }

    /**
     * Return the Loader instance for external use (e.g. in tests).
     *
     * @return Loader
     */
    public function get_loader(): Loader {
        return $this->loader;
    }

    /**
     * Return the plugin version string.
     *
     * @return string
     */
    public function get_version(): string {
        return $this->version;
    }
}

/**
 * Bootstrap the plugin and return the running instance.
 *
 * @return Plugin
 */
function run_ezekiel_boilerplate(): Plugin {
    $plugin = new Plugin();
    $plugin->run();
    return $plugin;
}

run_ezekiel_boilerplate();
