<?php
/**
 * Реєстратор усіх хуків (actions та filters) плагіна.
 *
 * Version:     1.0.0
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Loader {

    /**
     * Масив зареєстрованих дій (actions) WordPress.
     * @var array
     */
    protected $actions;

    /**
     * Масив зареєстрованих фільтрів (filters) WordPress.
     * @var array
     */
    protected $filters;

    /**
     * Ініціалізація колекцій.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Додавання нового action до колекції.
     *
     * @param string $hook          Назва хука WordPress.
     * @param object $component     Об'єкт класу, що містить callback.
     * @param string $callback      Назва методу в об'єкті.
     * @param int    $priority      Пріоритет (за замовчуванням 10).
     * @param int    $accepted_args Кількість аргументів (за замовчуванням 1).
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Додавання нового filter до колекції.
     *
     * @param string $hook          Назва хука WordPress.
     * @param object $component     Об'єкт класу, що містить callback.
     * @param string $callback      Назва методу в об'єкті.
     * @param int    $priority      Пріоритет (за замовчуванням 10).
     * @param int    $accepted_args Кількість аргументів (за замовчуванням 1).
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Утиліта для реєстрації хука у масив.
     */
    private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        return $hooks;
    }

    /**
     * Реєстрація всіх зібраних хуків у системі WordPress.
     */
    public function run() {
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }

        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }
    }
}