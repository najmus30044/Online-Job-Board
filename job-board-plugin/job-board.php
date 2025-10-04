<?php
/**
 * Plugin Name: Job Board Naj
 * Plugin URI: https://example.com/job-board-plugin
 * Description: A simple job board plugin for WordPress allowing job postings and applications.
 * Version: 1.0.0
 * Author: MD Najmus Shadat
 * Author URI: https://github.com/devnajmus
 * License: GPL-2.0+
 * Text Domain: job-board-plugin
 */

defined('ABSPATH') || exit;

define('JOB_BOARD_PLUGIN_VERSION', '1.0.0');
define('JOB_BOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JOB_BOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JOB_BOARD_PLUGIN_FILE', __FILE__);

// Include the singleton trait and loader.
// Load composer autoload if available 
if (file_exists(JOB_BOARD_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once JOB_BOARD_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once JOB_BOARD_PLUGIN_DIR . 'includes/Base.php';

// if Composer PSR-4 didn't autoload classes 
// require the core include files directly so the plugin still works.
add_action('plugins_loaded', function () {

    if (!trait_exists('JobBoardPlugin\\Traits\\Singleton') && file_exists(JOB_BOARD_PLUGIN_DIR . 'includes/traits/Singleton.php')) {
        require_once JOB_BOARD_PLUGIN_DIR . 'includes/traits/Singleton.php';
    }

    if (!class_exists('\\JobBoardPlugin\\Job_CPT') && file_exists(JOB_BOARD_PLUGIN_DIR . 'includes/Job_CPT.php')) {
        require_once JOB_BOARD_PLUGIN_DIR . 'includes/Job_CPT.php';
    }
    if (!class_exists('\\JobBoardPlugin\\Job_Application') && file_exists(JOB_BOARD_PLUGIN_DIR . 'includes/Job_Application.php')) {
        require_once JOB_BOARD_PLUGIN_DIR . 'includes/Job_Application.php';
    }
    if (!class_exists('\\JobBoardPlugin\\Job_Board') && file_exists(JOB_BOARD_PLUGIN_DIR . 'includes/Job_Board.php')) {
        require_once JOB_BOARD_PLUGIN_DIR . 'includes/Job_Board.php';
    }

    // initialize the plugin.
    \JobBoardPlugin\Base::get_instance();
});