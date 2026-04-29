<?php
/**
 * WordPress Multisite utility class.
 *
 * @package EzekielApetu\Multisite
 */

namespace EzekielApetu\Multisite;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Static utility class for common WordPress Multisite operations.
 *
 * All methods are static — instantiation is not required or useful.
 * Call methods directly: Multisite_Manager::get_all_sites().
 *
 * Methods that iterate sites use switch_to_blog() / restore_current_blog()
 * correctly so that global state (current blog, loaded textdomains, rewrite
 * rules, etc.) is always fully restored even when a callable throws.
 */
final class Multisite_Manager {

    /**
     * Prevent instantiation — this is a static utility class.
     */
    private function __construct() {}

    // -------------------------------------------------------------------------
    // Site enumeration
    // -------------------------------------------------------------------------

    /**
     * Return metadata for every site in the network.
     *
     * @param array{
     *     number?:    int,
     *     offset?:    int,
     *     archived?:  bool,
     *     deleted?:   bool,
     *     spam?:      bool,
     *     public?:    int,
     *     network_id?: int,
     * } $args Optional arguments forwarded to get_sites().
     *
     * @return array<int, array{
     *     blog_id:    int,
     *     domain:     string,
     *     path:       string,
     *     site_url:   string,
     *     home_url:   string,
     *     blogname:   string,
     *     is_main:    bool,
     *     registered: string,
     * }>
     */
    public static function get_all_sites( array $args = array() ): array {
        if ( ! is_multisite() ) {
            return array();
        }

        $defaults = array(
            'number'   => 500,
            'archived' => false,
            'deleted'  => false,
            'spam'     => false,
        );

        $sites = get_sites( wp_parse_args( $args, $defaults ) );

        return array_map(
            static function ( \WP_Site $site ): array {
                return array(
                    'blog_id'    => (int) $site->blog_id,
                    'domain'     => $site->domain,
                    'path'       => $site->path,
                    'site_url'   => get_site_url( (int) $site->blog_id ),
                    'home_url'   => get_home_url( (int) $site->blog_id ),
                    'blogname'   => get_blog_option( (int) $site->blog_id, 'blogname' ),
                    'is_main'    => is_main_site( (int) $site->blog_id ),
                    'registered' => $site->registered,
                );
            },
            $sites
        );
    }

    /**
     * Return only the blog IDs of all active sites in the network.
     *
     * Useful when you need a lightweight list for iteration rather than
     * the full metadata payload returned by get_all_sites().
     *
     * @return int[]
     */
    public static function get_all_site_ids(): array {
        if ( ! is_multisite() ) {
            return array();
        }

        $sites = get_sites(
            array(
                'fields'   => 'ids',
                'number'   => 500,
                'archived' => false,
                'deleted'  => false,
                'spam'     => false,
            )
        );

        return array_map( 'intval', $sites );
    }

    // -------------------------------------------------------------------------
    // Cross-site execution
    // -------------------------------------------------------------------------

    /**
     * Execute a callable in the context of every site in the network.
     *
     * The callable receives the blog ID as its first argument. Results are
     * collected into an array keyed by blog ID.
     *
     * switch_to_blog() / restore_current_blog() are always called in balanced
     * pairs — a try/finally block guarantees restoration even when the callable
     * throws an exception or returns early.
     *
     * Example:
     *   $counts = Multisite_Manager::run_for_all_sites(
     *       fn( int $blog_id ) => wp_count_posts()->publish
     *   );
     *
     * @param callable $callback   Callable to run; receives (int $blog_id).
     * @param array    $site_ids   Optional explicit list of blog IDs. Defaults to all active sites.
     * @return array<int, mixed>   Results keyed by blog ID.
     */
    public static function run_for_all_sites( callable $callback, array $site_ids = array() ): array {
        if ( ! is_multisite() ) {
            return array();
        }

        if ( empty( $site_ids ) ) {
            $site_ids = static::get_all_site_ids();
        }

        $results = array();

        foreach ( $site_ids as $blog_id ) {
            $blog_id = (int) $blog_id;

            switch_to_blog( $blog_id );

            try {
                $results[ $blog_id ] = $callback( $blog_id );
            } finally {
                // Guaranteed to run even if $callback throws.
                restore_current_blog();
            }
        }

        return $results;
    }

    /**
     * Execute a callable in the context of a single specific site.
     *
     * Prefer this over calling switch_to_blog() inline — it guarantees
     * restoration and keeps calling code free of try/finally boilerplate.
     *
     * @param int      $blog_id  The site to switch to.
     * @param callable $callback Callable to execute; receives (int $blog_id).
     * @return mixed The return value of $callback.
     */
    public static function run_in_site_context( int $blog_id, callable $callback ): mixed {
        if ( ! is_multisite() ) {
            return $callback( $blog_id );
        }

        switch_to_blog( $blog_id );

        try {
            return $callback( $blog_id );
        } finally {
            restore_current_blog();
        }
    }

    // -------------------------------------------------------------------------
    // Site identity checks
    // -------------------------------------------------------------------------

    /**
     * Determine whether the given blog ID (or the current blog) is the main site.
     *
     * @param int|null $blog_id Blog ID to check, or null for the current site.
     * @return bool
     */
    public static function is_main_site( ?int $blog_id = null ): bool {
        if ( ! is_multisite() ) {
            return true;
        }

        return is_main_site( $blog_id ?? get_current_blog_id() );
    }

    /**
     * Determine whether the given blog ID belongs to the current network.
     *
     * @param int $blog_id The blog ID to check.
     * @return bool
     */
    public static function site_exists( int $blog_id ): bool {
        if ( ! is_multisite() ) {
            return false;
        }

        return null !== get_site( $blog_id );
    }

    // -------------------------------------------------------------------------
    // Network-wide option reads
    // -------------------------------------------------------------------------

    /**
     * Retrieve a specific option from every site in the network.
     *
     * Returns a sparse array — sites that have no value for the option are
     * excluded unless $include_empty is true.
     *
     * @param string $option_name    The option key to read from each site.
     * @param mixed  $default        Value to use when the option is not set.
     * @param bool   $include_empty  Whether to include sites whose value equals $default.
     * @param array  $site_ids       Optional explicit list of blog IDs.
     * @return array<int, mixed>     Values keyed by blog ID.
     */
    public static function get_site_option_across_network(
        string $option_name,
        mixed $default = false,
        bool $include_empty = false,
        array $site_ids = array()
    ): array {
        if ( ! is_multisite() ) {
            return array();
        }

        if ( empty( $site_ids ) ) {
            $site_ids = static::get_all_site_ids();
        }

        $results = array();

        foreach ( $site_ids as $blog_id ) {
            $blog_id = (int) $blog_id;
            $value   = get_blog_option( $blog_id, $option_name, $default );

            if ( ! $include_empty && $value === $default ) {
                continue;
            }

            $results[ $blog_id ] = $value;
        }

        return $results;
    }

    /**
     * Set an option on every site in the network.
     *
     * This is a write operation — it calls update_blog_option() for each site.
     * Use carefully; there is no rollback if the process is interrupted mid-way.
     *
     * @param string $option_name The option key to update.
     * @param mixed  $value       The value to store.
     * @param array  $site_ids    Optional explicit list of blog IDs. Defaults to all active sites.
     * @return array<int, bool>   Results keyed by blog ID (true = updated, false = no change or failure).
     */
    public static function set_site_option_across_network(
        string $option_name,
        mixed $value,
        array $site_ids = array()
    ): array {
        if ( ! is_multisite() ) {
            return array();
        }

        if ( empty( $site_ids ) ) {
            $site_ids = static::get_all_site_ids();
        }

        $results = array();

        foreach ( $site_ids as $blog_id ) {
            $blog_id             = (int) $blog_id;
            $results[ $blog_id ] = update_blog_option( $blog_id, $option_name, $value );
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // WP-CLI compatibility
    // -------------------------------------------------------------------------

    /**
     * Return a summary table of all sites suitable for WP-CLI table output.
     *
     * Each row contains the fields that WP-CLI's --format=table expects:
     * blog_id, url, last_updated, registered, public, archived, mature, spam, deleted.
     *
     * @return array<int, array<string, string|int>>
     */
    public static function get_cli_site_list(): array {
        if ( ! is_multisite() ) {
            return array();
        }

        $sites = get_sites( array( 'number' => 500 ) );

        return array_map(
            static function ( \WP_Site $site ): array {
                return array(
                    'blog_id'      => (int) $site->blog_id,
                    'url'          => trailingslashit( $site->domain . $site->path ),
                    'last_updated' => $site->last_updated,
                    'registered'   => $site->registered,
                    'public'       => (int) $site->public,
                    'archived'     => (int) $site->archived,
                    'mature'       => (int) $site->mature,
                    'spam'         => (int) $site->spam,
                    'deleted'      => (int) $site->deleted,
                );
            },
            $sites
        );
    }
}
