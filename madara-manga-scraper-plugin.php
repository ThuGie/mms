<?php
/**
 * Plugin Name: Madara Manga Scraper
 * Plugin URI: https://yourwebsite.com/madara-manga-scraper
 * Description: A comprehensive manga scraping plugin for WordPress sites using the Madara theme
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: madara-manga-scraper
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MMS_VERSION', '1.0.0');
define('MMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MMS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MMS_ADMIN_URL', admin_url('admin.php?page=madara-manga-scraper'));
define('MMS_LOG_DIR', WP_CONTENT_DIR . '/madara-manga-scraper-logs/');

// Autoloader
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'MadaraMangaScraper\\';
    
    // Base directory for the namespace prefix
    $base_dir = MMS_PLUGIN_DIR . 'includes/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Main plugin class
class Madara_Manga_Scraper {
    /**
     * Plugin instance
     *
     * @var Madara_Manga_Scraper
     */
    private static $instance = null;

    /**
     * Plugin components
     */
    public $admin;
    public $scraper;
    public $queue;
    public $logger;
    public $scheduler;
    public $image_processor;
    public $database;

    /**
     * Get plugin instance
     *
     * @return Madara_Manga_Scraper
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Load plugin dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Add action hooks
        $this->add_actions();
        
        // Register activation/deactivation hooks (after components are initialized)
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Include Composer autoloader if it exists
        if (file_exists(MMS_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once MMS_PLUGIN_DIR . 'vendor/autoload.php';
        }
        
        // Core plugin files
        require_once MMS_PLUGIN_DIR . 'includes/Admin/Admin.php';
        require_once MMS_PLUGIN_DIR . 'includes/Scraper/Scraper.php';
        require_once MMS_PLUGIN_DIR . 'includes/Queue/Queue.php';
        require_once MMS_PLUGIN_DIR . 'includes/Logger/Logger.php';
        require_once MMS_PLUGIN_DIR . 'includes/Scheduler/Scheduler.php';
        require_once MMS_PLUGIN_DIR . 'includes/ImageProcessor/ImageProcessor.php';
        require_once MMS_PLUGIN_DIR . 'includes/Database/Database.php';
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // First, create the database
        $this->database = new \MadaraMangaScraper\Database\Database();
        
        // Set the instance to make it available globally before creating other components
        self::$instance = $this;
        
        // Now create other components in the right order
        $this->logger = new \MadaraMangaScraper\Logger\Logger($this->database);
        $this->queue = new \MadaraMangaScraper\Queue\Queue($this->database, $this->logger);
        $this->image_processor = new \MadaraMangaScraper\ImageProcessor\ImageProcessor($this->logger);
        
        // Create the scraper with all required dependencies
        $this->scraper = new \MadaraMangaScraper\Scraper\Scraper(
            $this->queue, 
            $this->logger, 
            $this->database,
            $this->image_processor
        );
        
        $this->scheduler = new \MadaraMangaScraper\Scheduler\Scheduler($this->scraper, $this->queue, $this->logger);
        $this->admin = new \MadaraMangaScraper\Admin\Admin($this->scraper, $this->queue, $this->logger, $this->scheduler, $this->image_processor, $this->database);
    }

    /**
     * Plugin activation
     */
    public function activate() {
        try {
            // Create necessary database tables
            $this->database->create_tables();
            
            // Create log directory
            if (!file_exists(MMS_LOG_DIR)) {
                wp_mkdir_p(MMS_LOG_DIR);
            }
            
            // Schedule cron events
            $this->scheduler->schedule_events();
            
            // Add version to database
            update_option('mms_version', MMS_VERSION);
            
            // Flush rewrite rules
            flush_rewrite_rules();
        } catch (\Exception $e) {
            // Log the error to WordPress error log
            error_log('Error activating Madara Manga Scraper: ' . $e->getMessage());
            
            // If we can't activate properly, deactivate the plugin
            deactivate_plugins(plugin_basename(__FILE__));
            
            // Show admin notice
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Error activating Madara Manga Scraper:</strong> ' . esc_html($e->getMessage()) . '</p>';
                echo '</div>';
            });
            
            // Throw to prevent activation
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        $this->scheduler->clear_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Add action hooks
     */
    private function add_actions() {
        // Check for Madara theme and core plugin
        add_action('admin_init', array($this, 'check_requirements'));
        
        // Initialize admin
        if (is_admin()) {
            add_action('admin_menu', array($this->admin, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_assets'));
        }
        
        // Add ajax handlers
        add_action('wp_ajax_mms_start_manga_scrape', array($this->admin, 'start_manga_scrape'));
        add_action('wp_ajax_mms_clear_queue', array($this->admin, 'clear_queue'));
        add_action('wp_ajax_mms_clear_logs', array($this->admin, 'clear_logs'));
        add_action('wp_ajax_mms_scrape_now', array($this->admin, 'scrape_now'));
        add_action('wp_ajax_mms_retry_failed', array($this->admin, 'retry_failed'));
        add_action('wp_ajax_mms_reset_processing', array($this->admin, 'reset_processing'));
        add_action('wp_ajax_mms_process_queue_now', array($this->admin, 'process_queue_now'));
        add_action('wp_ajax_mms_run_task_now', array($this->admin, 'run_task_now'));
        add_action('wp_ajax_mms_get_manga_details', array($this->admin, 'get_manga_details'));
        add_action('wp_ajax_mms_get_manga_chapters', array($this->admin, 'get_manga_chapters'));
        add_action('wp_ajax_mms_remove_manga', array($this->admin, 'remove_manga'));
        add_action('wp_ajax_mms_remove_queue_item', array($this->admin, 'remove_queue_item'));
        
        // Add cron action handlers
        add_action('mms_daily_check_sources', array($this->scheduler, 'check_sources_for_updates'));
        add_action('mms_process_queue', array($this->queue, 'process_queue_items'));
    }

    /**
     * Check plugin requirements
     */
    public function check_requirements() {
        $theme = wp_get_theme();
        $parent_theme = $theme->parent();
        
        $using_madara = (
            ($theme->get('Template') === 'madara' || $theme->get('TextDomain') === 'madara') || 
            ($parent_theme && ($parent_theme->get('Template') === 'madara' || $parent_theme->get('TextDomain') === 'madara'))
        );
        
        $madara_core_active = is_plugin_active('madara-core/wp-manga.php');
        
        if (!$using_madara || !$madara_core_active) {
            add_action('admin_notices', array($this, 'requirements_notice'));
            
            // Deactivate plugin
            deactivate_plugins(MMS_PLUGIN_BASENAME);
            
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

    /**
     * Display requirements notice
     */
    public function requirements_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Madara Manga Scraper requires the Madara theme and Madara Core plugin to be active.', 'madara-manga-scraper'); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
function madara_manga_scraper() {
    return Madara_Manga_Scraper::get_instance();
}

// Start the plugin
madara_manga_scraper();