<?php
namespace MadaraMangaScraper\Admin;

/**
 * Admin class
 */
class Admin {
    /**
     * Scraper instance
     *
     * @var \MadaraMangaScraper\Scraper\Scraper
     */
    private $scraper;

    /**
     * Queue instance
     *
     * @var \MadaraMangaScraper\Queue\Queue
     */
    private $queue;

    /**
     * Logger instance
     *
     * @var \MadaraMangaScraper\Logger\Logger
     */
    private $logger;

    /**
     * Scheduler instance
     *
     * @var \MadaraMangaScraper\Scheduler\Scheduler
     */
    private $scheduler;

    /**
     * Image processor instance
     *
     * @var \MadaraMangaScraper\ImageProcessor\ImageProcessor
     */
    private $image_processor;
    
    /**
     * Database instance
     *
     * @var \MadaraMangaScraper\Database\Database
     */
    private $db;

    /**
     * Constructor
     *
     * @param \MadaraMangaScraper\Scraper\Scraper $scraper
     * @param \MadaraMangaScraper\Queue\Queue $queue
     * @param \MadaraMangaScraper\Logger\Logger $logger
     * @param \MadaraMangaScraper\Scheduler\Scheduler $scheduler
     * @param \MadaraMangaScraper\ImageProcessor\ImageProcessor $image_processor
     * @param \MadaraMangaScraper\Database\Database $database
     */
    public function __construct($scraper, $queue, $logger, $scheduler, $image_processor, $database) {
        $this->scraper = $scraper;
        $this->queue = $queue;
        $this->logger = $logger;
        $this->scheduler = $scheduler;
        $this->image_processor = $image_processor;
        $this->db = $database;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Madara Manga Scraper', 'madara-manga-scraper'),
            __('Manga Scraper', 'madara-manga-scraper'),
            'manage_options',
            'madara-manga-scraper',
            array($this, 'render_dashboard'),
            'dashicons-download',
            100
        );
        
        add_submenu_page(
            'madara-manga-scraper',
            __('Dashboard', 'madara-manga-scraper'),
            __('Dashboard', 'madara-manga-scraper'),
            'manage_options',
            'madara-manga-scraper',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'madara-manga-scraper',
            __('Sources', 'madara-manga-scraper'),
            __('Sources', 'madara-manga-scraper'),
            'manage_options',
            'mms-sources',
            array($this, 'render_sources')
        );
        
        add_submenu_page(
            'madara-manga-scraper',
            __('Queue', 'madara-manga-scraper'),
            __('Queue', 'madara-manga-scraper'),
            'manage_options',
            'mms-queue',
            array($this, 'render_queue')
        );
        
        add_submenu_page(
            'madara-manga-scraper',
            __('Manga', 'madara-manga-scraper'),
            __('Manga', 'madara-manga-scraper'),
            'manage_options',
            'mms-manga',
            array($this, 'render_manga')
        );
        
        add_submenu_page(
            'madara-manga-scraper',
            __('Logs', 'madara-manga-scraper'),
            __('Logs', 'madara-manga-scraper'),
            'manage_options',
            'mms-logs',
            array($this, 'render_logs')
        );
        
        add_submenu_page(
            'madara-manga-scraper',
            __('Settings', 'madara-manga-scraper'),
            __('Settings', 'madara-manga-scraper'),
            'manage_options',
            'mms-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook_suffix Hook suffix
     */
    public function enqueue_assets($hook_suffix) {
        // Only load on plugin pages
        if (strpos($hook_suffix, 'madara-manga-scraper') !== false || strpos($hook_suffix, 'mms-') !== false) {
            // Styles
            wp_enqueue_style(
                'mms-admin',
                MMS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                MMS_VERSION
            );
            
            // Scripts
            wp_enqueue_script(
                'mms-admin',
                MMS_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                MMS_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('mms-admin', 'mms_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mms_nonce'),
                'admin_url' => admin_url('admin.php'),
            ));
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        // Get stats
        $stats = array(
            'sources' => $this->db->count('sources'),
            'manga' => $this->db->count('manga'),
            'chapters' => $this->db->count('chapters'),
            'queue' => $this->queue->get_stats(),
            'log_count' => $this->db->count('logs'),
            'error_count' => $this->db->count('errors'),
        );
        
        // Get recent manga
        $recent_manga = $this->db->get_results(
            'manga',
            array(),
            'created_at',
            'DESC',
            5
        );
        
        // Get recent chapters
        $recent_chapters = $this->db->get_results(
            'chapters',
            array(),
            'created_at',
            'DESC',
            5
        );
        
        // Get recent logs
        $recent_logs = $this->db->get_results(
            'logs',
            array(),
            'created_at',
            'DESC',
            5
        );
        
        // Pass db to view
        $db = $this->db;
        
        include MMS_PLUGIN_DIR . 'includes/Admin/views/dashboard.php';
    }

    /**
     * Render sources page
     */
    public function render_sources() {
        // Handle form submissions
        $this->handle_source_form();
        
        // Get sources
        $sources = $this->scraper->get_sources(array(
            'status' => '',
        ));
        
        // Pass db to view
        $db = $this->db;
        
        include MMS_PLUGIN_DIR . 'includes/Admin/views/sources.php';
    }

    /**
     * Render queue page
     */
    public function render_queue() {
        // Get queue items
        $queue_items = $this->queue->get_items(array(
            'limit' => 50,
        ));
        
        // Get queue stats
        $queue_stats = $this->queue->get_stats();
        
        // Pass db to view
        $db = $this->db;
        
        include MMS_PLUGIN_DIR . 'includes/Admin/views/queue.php';
    }

    /**
     * Render manga page
     */
    public function render_manga() {
        // Get sources for filter
        $sources = $this->scraper->get_sources(array(
            'status' => 1,
        ));
        
        // Get source filter
        $source_id = isset($_GET['source']) ? intval($_GET['source']) : 0;
        
        // Get manga
        $manga_list = $this->scraper->get_manga(array(
            'source_id' => $source_id,
            'limit' => 50,
        ));
        
        // Pass db to view
        $db = $this->db;
        
        include MMS_PLUGIN_DIR . 'includes/Admin/views/manga.php';
    }

    /**
     * Render logs page
     */
    public function render_logs() {
        // Get log filter
        $log_level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
        
        // Get logs
        $logs = $this->logger->get_logs(array(
            'level' => $log_level,
            'limit' => 100,
        ));
        
        // Get errors
        $errors = $this->logger->get_errors(array(
            'limit' => 100,
        ));
        
        // Pass db to view
        $db = $this->db;
        
        include MMS_PLUGIN_DIR . 'includes/Admin/views/logs.php';
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        // Handle form submissions
        $this->handle_settings_form();
        
        // Get settings
        $general_settings = array(
            'request_delay' => get_option('mms_request_delay', 2),
            'merge_images' => get_option('mms_merge_images', true),
            'storage_type' => get_option('mms_storage_type', 'local'),
            'debug_mode' => get_option('mms_debug_mode', false),
            'file_logging' => get_option('mms_file_logging', true),
        );
        
        // Get image processor settings
        $image_settings = array(
            'default_format' => $this->image_processor->get_default_format(),
            'supported_formats' => $this->image_processor->get_supported_formats(),
            'quality' => $this->image_processor->get_quality(),
        );
        
        // Get schedule settings
        $schedule_settings = $this->scheduler->get_schedule_settings();
        $schedules = $this->scheduler->get_schedules();
        
        // Pass db to view
        $db = $this->db;
        
        include MMS_PLUGIN_DIR . 'includes/Admin/views/settings.php';
    }

    /**
     * Handle source form
     */
    private function handle_source_form() {
        // Check if form was submitted
        if (!isset($_POST['mms_source_action'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['mms_nonce']) || !wp_verify_nonce($_POST['mms_nonce'], 'mms_source_form')) {
            add_settings_error('mms_sources', 'invalid_nonce', __('Invalid nonce, please try again.', 'madara-manga-scraper'), 'error');
            return;
        }
        
        // Handle action
        $action = sanitize_text_field($_POST['mms_source_action']);
        
        switch ($action) {
            case 'add':
                $this->handle_add_source();
                break;
            
            case 'edit':
                $this->handle_edit_source();
                break;
            
            case 'delete':
                $this->handle_delete_source();
                break;
        }
    }

    /**
     * Handle add source
     */
    private function handle_add_source() {
        // Validate input
        $name = isset($_POST['source_name']) ? sanitize_text_field($_POST['source_name']) : '';
        $url = isset($_POST['source_url']) ? esc_url_raw($_POST['source_url']) : '';
        
        if (empty($name)) {
            add_settings_error('mms_sources', 'empty_name', __('Source name cannot be empty.', 'madara-manga-scraper'), 'error');
            return;
        }
        
        if (empty($url)) {
            add_settings_error('mms_sources', 'empty_url', __('Source URL cannot be empty.', 'madara-manga-scraper'), 'error');
            return;
        }
        
        // Add source
        $result = $this->scraper->add_source($name, $url);
        
        if ($result) {
            add_settings_error('mms_sources', 'source_added', __('Source added successfully.', 'madara-manga-scraper'), 'success');
        } else {
            add_settings_error('mms_sources', 'add_failed', __('Failed to add source. Please check if the URL is valid and uses the Madara theme.', 'madara-manga-scraper'), 'error');
        }
    }

    /**
     * Handle edit source
     */
    private function handle_edit_source() {
        // Validate input
        $id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        $name = isset($_POST['source_name']) ? sanitize_text_field($_POST['source_name']) : '';
        $status = isset($_POST['source_status']) ? intval($_POST['source_status']) : 0;
        
        if (empty($id)) {
            add_settings_error('mms_sources', 'invalid_id', __('Invalid source ID.', 'madara-manga-scraper'), 'error');
            return;
        }
        
        if (empty($name)) {
            add_settings_error('mms_sources', 'empty_name', __('Source name cannot be empty.', 'madara-manga-scraper'), 'error');
            return;
        }
        
        // Update source
        $data = array(
            'name' => $name,
            'status' => $status,
        );
        
        $result = $this->scraper->update_source($id, $data);
        
        if ($result) {
            add_settings_error('mms_sources', 'source_updated', __('Source updated successfully.', 'madara-manga-scraper'), 'success');
        } else {
            add_settings_error('mms_sources', 'update_failed', __('Failed to update source.', 'madara-manga-scraper'), 'error');
        }
    }

    /**
     * Handle delete source
     */
    private function handle_delete_source() {
        // Validate input
        $id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        
        if (empty($id)) {
            add_settings_error('mms_sources', 'invalid_id', __('Invalid source ID.', 'madara-manga-scraper'), 'error');
            return;
        }
        
        // Delete source
        $result = $this->scraper->delete_source($id);
        
        if ($result) {
            add_settings_error('mms_sources', 'source_deleted', __('Source deleted successfully.', 'madara-manga-scraper'), 'success');
        } else {
            add_settings_error('mms_sources', 'delete_failed', __('Failed to delete source.', 'madara-manga-scraper'), 'error');
        }
    }

    /**
     * Handle settings form
     */
    private function handle_settings_form() {
        // Check if form was submitted
        if (!isset($_POST['mms_settings_action'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['mms_nonce']) || !wp_verify_nonce($_POST['mms_nonce'], 'mms_settings_form')) {
            add_settings_error('mms_settings', 'invalid_nonce', __('Invalid nonce, please try again.', 'madara-manga-scraper'), 'error');
            return;
        }
        
        // Handle action
        $action = sanitize_text_field($_POST['mms_settings_action']);
        
        switch ($action) {
            case 'general':
                $this->handle_general_settings();
                break;
            
            case 'image':
                $this->handle_image_settings();
                break;
            
            case 'schedule':
                $this->handle_schedule_settings();
                break;
        }
    }

    /**
     * Handle general settings
     */
    private function handle_general_settings() {
        // Get settings
        $request_delay = isset($_POST['request_delay']) ? intval($_POST['request_delay']) : 2;
        $merge_images = isset($_POST['merge_images']) ? (bool) $_POST['merge_images'] : false;
        $storage_type = isset($_POST['storage_type']) ? sanitize_text_field($_POST['storage_type']) : 'local';
        $debug_mode = isset($_POST['debug_mode']) ? (bool) $_POST['debug_mode'] : false;
        $file_logging = isset($_POST['file_logging']) ? (bool) $_POST['file_logging'] : false;
        
        // Validate settings
        $request_delay = max(1, $request_delay);
        
        // Save settings
        update_option('mms_request_delay', $request_delay);
        update_option('mms_merge_images', $merge_images);
        update_option('mms_storage_type', $storage_type);
        update_option('mms_debug_mode', $debug_mode);
        update_option('mms_file_logging', $file_logging);
        
        // Update scraper delay
        $this->scraper->set_request_delay($request_delay);
        
        add_settings_error('mms_settings', 'settings_updated', __('General settings updated successfully.', 'madara-manga-scraper'), 'success');
    }

    /**
     * Handle image settings
     */
    private function handle_image_settings() {
        // Get settings
        $default_format = isset($_POST['default_format']) ? sanitize_text_field($_POST['default_format']) : 'avif';
        $quality_webp = isset($_POST['quality_webp']) ? intval($_POST['quality_webp']) : 85;
        $quality_avif = isset($_POST['quality_avif']) ? intval($_POST['quality_avif']) : 70;
        
        // Validate settings
        $quality_webp = max(0, min(100, $quality_webp));
        $quality_avif = max(0, min(100, $quality_avif));
        
        // Save settings
        $this->image_processor->set_default_format($default_format);
        $this->image_processor->set_quality($quality_webp, $quality_avif);
        
        add_settings_error('mms_settings', 'settings_updated', __('Image settings updated successfully.', 'madara-manga-scraper'), 'success');
    }

    /**
     * Handle schedule settings
     */
    private function handle_schedule_settings() {
        // Get settings
        $settings = array(
            'check_new_manga' => isset($_POST['check_new_manga']) ? (bool) $_POST['check_new_manga'] : false,
            'check_new_chapters' => isset($_POST['check_new_chapters']) ? (bool) $_POST['check_new_chapters'] : false,
            'max_new_manga' => isset($_POST['max_new_manga']) ? intval($_POST['max_new_manga']) : 10,
            'max_manga_updates' => isset($_POST['max_manga_updates']) ? intval($_POST['max_manga_updates']) : 10,
            'max_new_chapters' => isset($_POST['max_new_chapters']) ? intval($_POST['max_new_chapters']) : 5,
            'check_interval' => isset($_POST['check_interval']) ? sanitize_text_field($_POST['check_interval']) : 'daily',
            'queue_interval' => isset($_POST['queue_interval']) ? sanitize_text_field($_POST['queue_interval']) : 'mms_five_minutes',
        );
        
        // Save settings
        $result = $this->scheduler->update_schedule_settings($settings);
        
        if ($result) {
            add_settings_error('mms_settings', 'settings_updated', __('Schedule settings updated successfully.', 'madara-manga-scraper'), 'success');
        } else {
            add_settings_error('mms_settings', 'update_failed', __('Failed to update schedule settings.', 'madara-manga-scraper'), 'error');
        }
    }

    /**
     * AJAX handler for starting manga scrape
     */
    public function start_manga_scrape() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get source ID
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        
        if (empty($source_id)) {
            wp_send_json_error(array('message' => __('Invalid source ID.', 'madara-manga-scraper')));
        }
        
        // Start scrape
        $result = $this->scraper->scrape_all_manga($source_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Manga scrape started successfully. Check the queue for progress.', 'madara-manga-scraper')));
        } else {
            wp_send_json_error(array('message' => __('Failed to start manga scrape.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for clearing queue
     */
    public function clear_queue() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get queue type
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        
        // Clear queue
        $result = $this->queue->clear_queue($status, $type);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Queue cleared successfully.', 'madara-manga-scraper')));
        } else {
            wp_send_json_error(array('message' => __('Failed to clear queue.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for clearing logs
     */
    public function clear_logs() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get log type
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        
        // Clear logs
        if ($type === 'logs') {
            $level = isset($_POST['level']) ? sanitize_text_field($_POST['level']) : '';
            $result = $this->logger->clear_logs($level);
        } elseif ($type === 'errors') {
            $item_type = isset($_POST['item_type']) ? sanitize_text_field($_POST['item_type']) : '';
            $result = $this->logger->clear_errors($item_type);
        } elseif ($type === 'files') {
            $result = $this->logger->clear_log_files();
        } else {
            $result = false;
        }
        
        if ($result) {
            wp_send_json_success(array('message' => __('Logs cleared successfully.', 'madara-manga-scraper')));
        } else {
            wp_send_json_error(array('message' => __('Failed to clear logs.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for scraping now
     */
    public function scrape_now() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get item type and ID
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        
        if (empty($type) || empty($id) || empty($source_id)) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'madara-manga-scraper')));
        }
        
        // Scrape now
        if ($type === 'manga') {
            $result = $this->scraper->scrape_manga($id, $source_id);
        } elseif ($type === 'chapter') {
            $result = $this->scraper->scrape_chapter($id, $source_id);
        } else {
            $result = false;
        }
        
        if ($result) {
            wp_send_json_success(array('message' => __('Scraping completed successfully.', 'madara-manga-scraper')));
        } else {
            wp_send_json_error(array('message' => __('Failed to scrape item. Check logs for details.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for retrying failed items
     */
    public function retry_failed() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Retry failed items
        $count = $this->queue->retry_failed_items();
        
        if ($count !== false) {
            wp_send_json_success(array('message' => sprintf(__('%d failed items reset successfully.', 'madara-manga-scraper'), $count)));
        } else {
            wp_send_json_error(array('message' => __('Failed to retry failed items.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for resetting processing items
     */
    public function reset_processing() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Reset processing items
        $count = $this->queue->reset_processing_items();
        
        if ($count !== false) {
            wp_send_json_success(array('message' => sprintf(__('%d processing items reset successfully.', 'madara-manga-scraper'), $count)));
        } else {
            wp_send_json_error(array('message' => __('Failed to reset processing items.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for processing queue now
     */
    public function process_queue_now() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Process queue
        $result = $this->queue->process_queue_items();
        
        if ($result) {
            wp_send_json_success(array('message' => __('Queue processed successfully.', 'madara-manga-scraper')));
        } else {
            wp_send_json_error(array('message' => __('Some queue items failed to process. Check logs for details.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for running tasks now
     */
    public function run_task_now() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get task
        $task = isset($_POST['task']) ? sanitize_text_field($_POST['task']) : '';
        
        if (empty($task)) {
            wp_send_json_error(array('message' => __('Invalid task.', 'madara-manga-scraper')));
        }
        
        // Run task
        $result = $this->scheduler->run_now($task);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Task executed successfully.', 'madara-manga-scraper')));
        } else {
            wp_send_json_error(array('message' => __('Failed to execute task. Check logs for details.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for getting manga details
     */
    public function get_manga_details() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get manga ID
        $manga_id = isset($_POST['manga_id']) ? intval($_POST['manga_id']) : 0;
        
        if (empty($manga_id)) {
            wp_send_json_error(array('message' => __('Invalid manga ID.', 'madara-manga-scraper')));
        }
        
        // Get manga details
        $manga = $this->db->get_row('manga', array('id' => $manga_id));
        
        if (!$manga) {
            wp_send_json_error(array('message' => __('Manga not found.', 'madara-manga-scraper')));
        }
        
        // Get chapter count
        $chapter_count = $this->db->count('chapters', array('manga_id' => $manga_id));
        $downloaded_chapters = $this->db->count('chapters', array('manga_id' => $manga_id, 'downloaded' => 1));
        
        // Get source
        $source = $this->db->get_row('sources', array('id' => $manga->source_id));
        $source_name = $source ? $source->name : __('Unknown', 'madara-manga-scraper');
        
        // Prepare HTML
        ob_start();
        ?>
        <div class="mms-manga-details-cover">
            <?php if (!empty($manga->cover_url)) : ?>
                <img src="<?php echo esc_url($manga->cover_url); ?>" alt="<?php echo esc_attr($manga->title); ?>" style="max-width: 200px;">
            <?php endif; ?>
        </div>
        
        <div class="mms-manga-details-info">
            <table>
                <tr>
                    <th><?php _e('Title', 'madara-manga-scraper'); ?></th>
                    <td><?php echo esc_html($manga->title); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Source', 'madara-manga-scraper'); ?></th>
                    <td><?php echo esc_html($source_name); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Status', 'madara-manga-scraper'); ?></th>
                    <td><?php echo esc_html($manga->status); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Chapters', 'madara-manga-scraper'); ?></th>
                    <td><?php echo $downloaded_chapters; ?>/<?php echo $chapter_count; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Last Chapter', 'madara-manga-scraper'); ?></th>
                    <td><?php echo esc_html($manga->last_chapter_number); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Genres', 'madara-manga-scraper'); ?></th>
                    <td><?php echo esc_html($manga->genres); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Authors', 'madara-manga-scraper'); ?></th>
                    <td><?php echo esc_html($manga->authors); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Artists', 'madara-manga-scraper'); ?></th>
                    <td><?php echo esc_html($manga->artists); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Added', 'madara-manga-scraper'); ?></th>
                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($manga->created_at)); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Updated', 'madara-manga-scraper'); ?></th>
                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($manga->updated_at)); ?></td>
                </tr>
                <?php if ($manga->wp_post_id) : ?>
                    <tr>
                        <th><?php _e('WordPress Post', 'madara-manga-scraper'); ?></th>
                        <td>
                            <a href="<?php echo get_permalink($manga->wp_post_id); ?>" target="_blank"><?php _e('View Post', 'madara-manga-scraper'); ?></a> |
                            <a href="<?php echo admin_url('post.php?post=' . $manga->wp_post_id . '&action=edit'); ?>" target="_blank"><?php _e('Edit Post', 'madara-manga-scraper'); ?></a>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
            
            <div class="mms-manga-details-description">
                <h3><?php _e('Description', 'madara-manga-scraper'); ?></h3>
                <div><?php echo wpautop(esc_html($manga->description)); ?></div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX handler for getting manga chapters
     */
    public function get_manga_chapters() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get manga ID
        $manga_id = isset($_POST['manga_id']) ? intval($_POST['manga_id']) : 0;
        
        if (empty($manga_id)) {
            wp_send_json_error(array('message' => __('Invalid manga ID.', 'madara-manga-scraper')));
        }
        
        // Get manga
        $manga = $this->db->get_row('manga', array('id' => $manga_id));
        
        if (!$manga) {
            wp_send_json_error(array('message' => __('Manga not found.', 'madara-manga-scraper')));
        }
        
        // Get chapters
        $chapters = $this->scraper->get_chapters(array(
            'manga_id' => $manga_id,
            'orderby' => 'chapter_number',
            'order' => 'DESC',
        ));
        
        // Prepare HTML
        ob_start();
        ?>
        <h3><?php echo esc_html($manga->title); ?> - <?php _e('Chapters', 'madara-manga-scraper'); ?></h3>
        
        <?php if (empty($chapters)) : ?>
            <div class="notice notice-info inline">
                <p><?php _e('No chapters found.', 'madara-manga-scraper'); ?></p>
            </div>
        <?php else : ?>
            <table class="mms-chapters-table">
                <thead>
                    <tr>
                        <th><?php _e('Chapter', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Title', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Downloaded', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Processed', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Published', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Actions', 'madara-manga-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chapters as $chapter) : ?>
                        <tr>
                            <td><?php echo esc_html($chapter->chapter_number); ?></td>
                            <td><?php echo esc_html($chapter->title); ?></td>
                            <td>
                                <?php if ($chapter->downloaded) : ?>
                                    <span class="mms-status mms-status-completed"><?php _e('Yes', 'madara-manga-scraper'); ?></span>
                                <?php else : ?>
                                    <span class="mms-status mms-status-pending"><?php _e('No', 'madara-manga-scraper'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($chapter->processed) : ?>
                                    <span class="mms-status mms-status-completed"><?php _e('Yes', 'madara-manga-scraper'); ?></span>
                                <?php else : ?>
                                    <span class="mms-status mms-status-pending"><?php _e('No', 'madara-manga-scraper'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($chapter->published_date) : ?>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($chapter->published_date)); ?>
                                <?php else : ?>
                                    <?php _e('Unknown', 'madara-manga-scraper'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$chapter->downloaded) : ?>
                                    <button class="button mms-scrape-now" data-id="<?php echo esc_attr($chapter->source_chapter_id); ?>" data-type="chapter" data-source="<?php echo intval($manga->source_id); ?>"><?php _e('Download', 'madara-manga-scraper'); ?></button>
                                <?php elseif ($chapter->wp_post_id) : ?>
                                    <a href="<?php echo get_permalink($chapter->wp_post_id); ?>" target="_blank" class="button"><?php _e('View', 'madara-manga-scraper'); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX handler for removing manga
     */
    public function remove_manga() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get manga ID
        $manga_id = isset($_POST['manga_id']) ? intval($_POST['manga_id']) : 0;
        
        if (empty($manga_id)) {
            wp_send_json_error(array('message' => __('Invalid manga ID.', 'madara-manga-scraper')));
        }
        
        // Get chapters
        $chapters = $this->scraper->get_chapters(array(
            'manga_id' => $manga_id,
        ));
        
        // Delete chapters
        foreach ($chapters as $chapter) {
            // Delete chapter files
            if ($chapter->images_path) {
                $chapter_dir = WP_CONTENT_DIR . $chapter->images_path;
                $this->delete_directory($chapter_dir);
            }
            
            // Delete from database
            $this->db->delete('chapters', array('id' => $chapter->id));
        }
        
        // Delete manga
        $result = $this->db->delete('manga', array('id' => $manga_id));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Manga and all associated chapters deleted successfully.', 'madara-manga-scraper')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete manga.', 'madara-manga-scraper')));
        }
    }

    /**
     * AJAX handler for removing queue item
     */
    public function remove_queue_item() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mms_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce, please refresh the page.', 'madara-manga-scraper')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'madara-manga-scraper')));
        }
        
        // Get queue item ID
        $queue_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (empty($queue_id)) {
            wp_send_json_error(array('message' => __('Invalid queue item ID.', 'madara-manga-scraper')));
        }
        
        // Delete queue item
        $result = $this->db->delete('queue', array('id' => $queue_id));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Queue item removed successfully.', 'madara-manga-scraper')));
        } else {
            wp_send_json_error(array('message' => __('Failed to remove queue item.', 'madara-manga-scraper')));
        }
    }

    /**
     * Helper function to delete a directory and its contents
     *
     * @param string $dir Directory to delete
     * @return bool Success
     */
    private function delete_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
}
