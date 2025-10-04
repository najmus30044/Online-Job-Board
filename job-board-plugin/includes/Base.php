<?php
namespace JobBoardPlugin;

defined('ABSPATH') || exit;

// Use the trait from the Traits namespace.
use JobBoardPlugin\Traits\Singleton;

class Base
{
    use Singleton;

    public function __construct()
    {
        // Classes are autoloaded Composer (vendor/autoload.php).
        if (class_exists('\JobBoardPlugin\Job_CPT')) {
            new Job_CPT();
        }
        if (class_exists('\JobBoardPlugin\Job_Application')) {
            \JobBoardPlugin\Job_Application::get_instance();
        }
        if (class_exists('\JobBoardPlugin\Job_Board')) {
            \JobBoardPlugin\Job_Board::get_instance();
        }
        // Activation hook for DB table.
        register_activation_hook(JOB_BOARD_PLUGIN_FILE, ['\JobBoardPlugin\Job_Application', 'create_table']);
    }
}
