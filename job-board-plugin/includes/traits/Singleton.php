<?php
/**
 * Singleton Trait
 *
 * Provides a singleton pattern for classes.
 *
 * @package JobBoardPlugin\Traits
 */

namespace JobBoardPlugin\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait Singleton
 */
trait Singleton {

	/**
	 * Holds the singleton instance.
	 *
	 * @var static|null
	 */
	private static $instance = null;

	/**
	 * Protected constructor to prevent direct object creation.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of the instance.
	 */
	final private function __clone() {}

	/**
	 * Get the singleton instance.
	 *
	 * @return static
	 */
	final public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}
}
