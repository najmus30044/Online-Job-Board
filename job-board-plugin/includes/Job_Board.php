<?php
/**
 * Main Job Board Class
 *
 * @package JobBoardPlugin
 */

namespace JobBoardPlugin;

defined( 'ABSPATH' ) || exit;

use JobBoardPlugin\Traits\Singleton;

/**
 * Class Job_Board
 *
 * Handles job board initialization, assets, shortcodes, and templates.
 */
class Job_Board {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * Initializes custom post types, applications, assets, shortcodes and templates.
	 */
	final private function __construct() {
		// Initialize CPT.
		new Job_CPT();

		// Initialize Application handling.
		\JobBoardPlugin\Job_Application::get_instance();

		// Enqueue assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Shortcode for job board.
		add_shortcode( 'job_board', [ $this, 'render_job_board' ] );

		// Template override for single job_listing.
		add_filter( 'single_template', [ $this, 'single_job_template' ] );

		// Stronger fallback for single template.
		add_filter( 'template_include', [ $this, 'maybe_single_template' ] );
	}

	/**
	 * Enqueue plugin assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'job-board-style',
			esc_url( JOB_BOARD_PLUGIN_URL . 'assets/css/style.css' ),
			[],
			JOB_BOARD_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'job-board-script',
			esc_url( JOB_BOARD_PLUGIN_URL . 'assets/js/jobboard.js' ),
			[ 'jquery' ],
			JOB_BOARD_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'job-board-script',
			'jobBoardAjax',
			[
				'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
				'nonce'    => wp_create_nonce( 'job_application_nonce' ),
			]
		);
	}

	/**
	 * Render job board shortcode.
	 *
	 * @return string
	 */
	public function render_job_board(): string {
		ob_start();

		$template = trailingslashit( JOB_BOARD_PLUGIN_DIR ) . 'includes/templates/job-listings.php';
		if ( file_exists( $template ) ) {
			include $template;
		}

		return ob_get_clean();
	}

	/**
	 * Override single job listing template.
	 *
	 * @param string $template Current template path.
	 *
	 * @return string
	 */
	public function single_job_template( string $template ): string {
		if ( is_singular( 'job_listing' ) ) {
			$custom_template = trailingslashit( JOB_BOARD_PLUGIN_DIR ) . 'includes/templates/single-job_listing.php';

			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		return $template;
	}

	/**
	 * Stronger template override as a fallback.
	 *
	 * @param string $template Current template path.
	 *
	 * @return string
	 */
	public function maybe_single_template( string $template ): string {
		if ( is_singular( 'job_listing' ) ) {
			$custom_template = trailingslashit( JOB_BOARD_PLUGIN_DIR ) . 'includes/templates/single-job_listing.php';

			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		return $template;
	}
}
