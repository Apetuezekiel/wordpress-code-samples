<?php
/**
 * Hook Loader — queues and registers all WordPress actions and filters.
 *
 * @package EzekielApetu\PluginBoilerplate
 */

namespace EzekielApetu\PluginBoilerplate;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Maintains two queues (actions, filters) and registers them with WordPress
 * in a single pass when run() is called.
 *
 * This keeps hook registration decoupled from the classes that own the
 * callbacks, making the plugin trivially testable without booting WordPress.
 */
class Loader {

    /**
     * Queued action hooks.
     *
     * Each entry: [ hook, component, callback, priority, accepted_args ].
     *
     * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
     */
    private array $actions = array();

    /**
     * Queued filter hooks.
     *
     * Each entry: [ hook, component, callback, priority, accepted_args ].
     *
     * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
     */
    private array $filters = array();

    /**
     * Add an action to the collection for later registration.
     *
     * @param string $hook          The name of the WordPress action.
     * @param object $component     The object instance that owns the callback.
     * @param string $callback      The method name on $component to invoke.
     * @param int    $priority      Hook priority (default 10).
     * @param int    $accepted_args Number of arguments passed to the callback (default 1).
     * @return void
     */
    public function add_action(
        string $hook,
        object $component,
        string $callback,
        int $priority = 10,
        int $accepted_args = 1
    ): void {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Add a filter to the collection for later registration.
     *
     * @param string $hook          The name of the WordPress filter.
     * @param object $component     The object instance that owns the callback.
     * @param string $callback      The method name on $component to invoke.
     * @param int    $priority      Hook priority (default 10).
     * @param int    $accepted_args Number of arguments passed to the callback (default 1).
     * @return void
     */
    public function add_filter(
        string $hook,
        object $component,
        string $callback,
        int $priority = 10,
        int $accepted_args = 1
    ): void {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Internal helper — appends a hook descriptor to the given queue.
     *
     * @param array  $hooks         The existing hook queue.
     * @param string $hook          WordPress hook name.
     * @param object $component     Callback owner.
     * @param string $callback      Method name.
     * @param int    $priority      Hook priority.
     * @param int    $accepted_args Argument count.
     * @return array Updated hook queue.
     */
    private function add(
        array $hooks,
        string $hook,
        object $component,
        string $callback,
        int $priority,
        int $accepted_args
    ): array {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    /**
     * Register all queued actions and filters with WordPress.
     *
     * Called once by the main Plugin class after all hooks have been queued.
     *
     * @return void
     */
    public function run(): void {
        foreach ( $this->filters as $hook ) {
            add_filter(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
