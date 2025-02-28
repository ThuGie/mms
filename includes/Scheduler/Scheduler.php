<?php
namespace MadaraMangaScraper\Scheduler;

/**
 * Scheduler class
 */
class Scheduler {
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
     * Constructor
     *
     * @param \MadaraMangaScraper\Scraper\Scraper $scraper
     * @param \MadaraMangaScraper\Queue\Queue $queue
     * @param \MadaraMangaScraper\Logger\Logger $logger
     */
    public function __construct($scraper, $queue, $logger) {
        $this->scraper = $scraper;
        $this->queue = $queue;
        $this->logger = $logger;
    }

    /**
     * Schedule events
     */
    public function schedule_events() {
        try {
            $this->logger->info('Scheduling events');
            
            // Daily check for updates
            if (!wp_next_scheduled('mms_daily_check_sources')) {
                wp_schedule_event(time(), 'daily', 'mms_daily_check_sources');
                $this->logger->info('Scheduled daily source check');
            }
            
            // Process queue every 5 minutes
            if (!wp_next_scheduled('mms_process_queue')) {
                wp_schedule_event(time(), 'mms_five_minutes', 'mms_process_queue');
                $this->logger->info('Scheduled queue processing');
            }
            
            // Register custom cron intervals
            add_filter('cron_schedules', array($this, 'register_cron_intervals'));
        } catch (\Exception $e) {
            // In case the logger isn't ready during activation, just proceed without logging
            if (!wp_next_scheduled('mms_daily_check_sources')) {
                wp_schedule_event(time(), 'daily', 'mms_daily_check_sources');
            }
            
            if (!wp_next_scheduled('mms_process_queue')) {
                wp_schedule_event(time(), 'mms_five_minutes', 'mms_process_queue');
            }
            
            add_filter('cron_schedules', array($this, 'register_cron_intervals'));
        }
    }

    /**
     * Clear scheduled events
     */
    public function clear_events() {
        $this->logger->info('Clearing scheduled events');
        
        // Clear scheduled events
        wp_clear_scheduled_hook('mms_daily_check_sources');
        wp_clear_scheduled_hook('mms_process_queue');
    }

    /**
     * Register custom cron intervals
     *
     * @param array $schedules Schedules
     * @return array Modified schedules
     */
    public function register_cron_intervals($schedules) {
        // Add a 5 minute schedule
        $schedules['mms_five_minutes'] = array(
            'interval' => 300,
            'display' => __('Every 5 minutes', 'madara-manga-scraper'),
        );
        
        // Add a 15 minute schedule
        $schedules['mms_fifteen_minutes'] = array(
            'interval' => 900,
            'display' => __('Every 15 minutes', 'madara-manga-scraper'),
        );
        
        // Add a 30 minute schedule
        $schedules['mms_thirty_minutes'] = array(
            'interval' => 1800,
            'display' => __('Every 30 minutes', 'madara-manga-scraper'),
        );
        
        // Add a hourly schedule
        $schedules['mms_hourly'] = array(
            'interval' => 3600,
            'display' => __('Every hour', 'madara-manga-scraper'),
        );
        
        return $schedules;
    }

    /**
     * Check sources for updates
     */
    public function check_sources_for_updates() {
        $this->logger->info('Checking sources for updates');
        
        // Get active sources
        $sources = $this->scraper->get_sources(array(
            'status' => 1,
        ));
        
        if (empty($sources)) {
            $this->logger->info('No active sources found');
            return;
        }
        
        // Settings
        $check_new_manga = get_option('mms_check_new_manga', true);
        $check_new_chapters = get_option('mms_check_new_chapters', true);
        
        foreach ($sources as $source) {
            $this->logger->info('Checking source for updates', array(
                'source_id' => $source->id,
                'source_name' => $source->name,
            ));
            
            // Check for new manga
            if ($check_new_manga) {
                $this->check_source_for_new_manga($source);
            }
            
            // Check for new chapters
            if ($check_new_chapters) {
                $this->check_source_for_new_chapters($source);
            }
            
            // Update source last checked
            $db = $this->scraper->get_database();
            $db->update(
                'sources',
                array('last_checked' => current_time('mysql')),
                array('id' => $source->id)
            );
        }
        
        $this->logger->info('Finished checking sources for updates');
    }

    /**
     * Check source for new manga
     *
     * @param object $source Source
     */
    private function check_source_for_new_manga($source) {
        $this->logger->info('Checking source for new manga', array(
            'source_id' => $source->id,
            'source_name' => $source->name,
        ));
        
        // Get manga directory page
        $url = trailingslashit($source->url) . 'manga/page/1/';
        
        // Make request
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ));
        
        if (is_wp_error($response)) {
            $this->logger->error('Failed to get manga directory page', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
            
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Use scraper to parse manga
        $manga_items = $this->scraper->parse_manga_directory($body);
        
        if (empty($manga_items)) {
            $this->logger->info('No manga found on page', array('url' => $url));
            return;
        }
        
        $this->logger->info('Found manga on page', array(
            'url' => $url,
            'count' => count($manga_items),
        ));
        
        // Check for new manga
        $manga_count = 0;
        $max_manga = get_option('mms_max_new_manga', 10);
        
        foreach ($manga_items as $manga) {
            // Extract manga ID from URL
            $manga_id = $this->scraper->extract_manga_id_from_url($manga['url']);
            
            if (!$manga_id) {
                continue;
            }
            
            // Check if manga exists in database
            $db = $this->scraper->get_database();
            $existing = $db->get_row('manga', array(
                'source_id' => $source->id,
                'source_manga_id' => $manga_id,
            ));
            
            if (!$existing) {
                // Add manga to queue
                $this->queue->add_manga($manga_id, $source->id);
                $manga_count++;
                
                if ($manga_count >= $max_manga) {
                    break;
                }
            }
        }
        
        $this->logger->info('Added new manga to queue', array(
            'source_id' => $source->id,
            'count' => $manga_count,
        ));
    }

    /**
     * Check source for new chapters
     *
     * @param object $source Source
     */
    private function check_source_for_new_chapters($source) {
        $this->logger->info('Checking source for new chapters', array(
            'source_id' => $source->id,
            'source_name' => $source->name,
        ));
        
        // Get manga from database
        global $madara_manga_scraper;
        $manga_list = $madara_manga_scraper->database->get_results('manga', array(
            'source_id' => $source->id,
        ));
        
        if (empty($manga_list)) {
            $this->logger->info('No manga found for source', array('source_id' => $source->id));
            return;
        }
        
        $this->logger->info('Found manga for source', array(
            'source_id' => $source->id,
            'count' => count($manga_list),
        ));
        
        // Settings
        $max_manga = get_option('mms_max_manga_updates', 10);
        $manga_count = 0;
        
        // Randomize manga list to distribute updates
        shuffle($manga_list);
        
        foreach ($manga_list as $manga) {
            // Check if we've reached the limit
            if ($manga_count >= $max_manga) {
                break;
            }
            
            // Check for new chapters
            $has_new = $this->check_manga_for_new_chapters($manga, $source);
            
            if ($has_new) {
                $manga_count++;
            }
        }
    }

    /**
     * Check manga for new chapters
     *
     * @param object $manga Manga
     * @param object $source Source
     * @return bool Whether new chapters were found
     */
    private function check_manga_for_new_chapters($manga, $source) {
        $this->logger->info('Checking manga for new chapters', array(
            'manga_id' => $manga->id,
            'manga_title' => $manga->title,
        ));
        
        // Get manga URL
        $manga_url = trailingslashit($source->url) . 'manga/' . $manga->source_manga_id . '/';
        
        // Make request
        $response = wp_remote_get($manga_url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ));
        
        if (is_wp_error($response)) {
            $this->logger->error('Failed to get manga page', array(
                'url' => $manga_url,
                'error' => $response->get_error_message(),
            ));
            
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Extract AJAX data
        $ajax_data = $this->scraper->extract_ajax_data($body);
        
        if (!$ajax_data) {
            $this->logger->error('Failed to extract AJAX data', array(
                'url' => $manga_url,
            ));
            
            return false;
        }
        
        // Make AJAX request for chapters
        $ajax_url = trailingslashit($source->url) . 'wp-admin/admin-ajax.php';
        
        $response = wp_remote_post($ajax_url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'body' => $ajax_data,
        ));
        
        if (is_wp_error($response)) {
            $this->logger->error('Failed to get chapters via AJAX', array(
                'url' => $ajax_url,
                'error' => $response->get_error_message(),
            ));
            
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $chapters = json_decode($body, true);
        
        if (!isset($chapters['data']) || empty($chapters['data'])) {
            $this->logger->warning('No chapters found', array(
                'manga_id' => $manga->id,
            ));
            
            return false;
        }
        
        // Process chapters
        $new_chapters = 0;
        $max_chapters = get_option('mms_max_new_chapters', 5);
        
        foreach ($chapters['data'] as $chapter) {
            // Extract chapter ID
            $chapter_id = $this->scraper->extract_chapter_id_from_url($chapter['url']);
            
            if (!$chapter_id) {
                continue;
            }
            
            // Check if chapter exists in database
            $db = $this->scraper->get_database();
            $existing = $db->get_results(
                'chapters',
                array(
                    'manga_id' => $manga->id,
                ),
                'chapter_number',
                'DESC'
            );
            
            $chapter_exists = false;
            
            foreach ($existing as $exist_chapter) {
                if ($exist_chapter->source_chapter_id === $chapter_id) {
                    $chapter_exists = true;
                    break;
                }
            }
            
            if (!$chapter_exists) {
                // Add chapter to database
                $db->insert(
                    'chapters',
                    array(
                        'manga_id' => $manga->id,
                        'source_chapter_id' => $chapter_id,
                        'chapter_number' => $chapter['chapter'],
                        'title' => isset($chapter['title']) ? $chapter['title'] : '',
                        'slug' => sanitize_title(
                            'chapter-' . $chapter['chapter'] .
                            (isset($chapter['title']) && !empty($chapter['title']) ? '-' . $chapter['title'] : '')
                        ),
                        'published_date' => isset($chapter['date']) ? date('Y-m-d H:i:s', strtotime($chapter['date'])) : null,
                    )
                );
                
                // Add chapter to queue
                $this->queue->add_chapter($chapter_id, $source->id);
                $new_chapters++;
                
                if ($new_chapters >= $max_chapters) {
                    break;
                }
            }
        }
        
        if ($new_chapters > 0) {
            $this->logger->info('Found new chapters', array(
                'manga_id' => $manga->id,
                'count' => $new_chapters,
            ));
            
            // Update manga with latest chapter info
            $latest_chapter = reset($chapters['data']);
            
            $db = $this->scraper->get_database();
            $db->update(
                'manga',
                array(
                    'last_chapter_number' => $latest_chapter['chapter'],
                    'last_chapter_date' => isset($latest_chapter['date']) ? date('Y-m-d H:i:s', strtotime($latest_chapter['date'])) : null,
                ),
                array('id' => $manga->id)
            );
            
            return true;
        }
        
        return false;
    }

    /**
     * Run now
     *
     * @param string $task Task to run (check_sources, process_queue)
     * @return bool Success
     */
    public function run_now($task) {
        $this->logger->info('Running task now', array('task' => $task));
        
        switch ($task) {
            case 'check_sources':
                $this->check_sources_for_updates();
                return true;
            
            case 'process_queue':
                $this->queue->process_queue_items();
                return true;
            
            default:
                $this->logger->error('Invalid task', array('task' => $task));
                return false;
        }
    }

    /**
     * Update schedule settings
     *
     * @param array $settings Schedule settings
     * @return bool Success
     */
    public function update_schedule_settings($settings) {
        $this->logger->info('Updating schedule settings', array('settings' => $settings));
        
        // Clear existing schedules
        $this->clear_events();
        
        // Update settings
        if (isset($settings['check_new_manga'])) {
            update_option('mms_check_new_manga', (bool) $settings['check_new_manga']);
        }
        
        if (isset($settings['check_new_chapters'])) {
            update_option('mms_check_new_chapters', (bool) $settings['check_new_chapters']);
        }
        
        if (isset($settings['max_new_manga'])) {
            update_option('mms_max_new_manga', max(1, intval($settings['max_new_manga'])));
        }
        
        if (isset($settings['max_manga_updates'])) {
            update_option('mms_max_manga_updates', max(1, intval($settings['max_manga_updates'])));
        }
        
        if (isset($settings['max_new_chapters'])) {
            update_option('mms_max_new_chapters', max(1, intval($settings['max_new_chapters'])));
        }
        
        if (isset($settings['check_interval'])) {
            update_option('mms_check_interval', $settings['check_interval']);
        }
        
        if (isset($settings['queue_interval'])) {
            update_option('mms_queue_interval', $settings['queue_interval']);
        }
        
        // Reschedule with new settings
        $this->schedule_events_with_settings();
        
        return true;
    }

    /**
     * Schedule events with settings
     */
    private function schedule_events_with_settings() {
        // Get settings
        $check_interval = get_option('mms_check_interval', 'daily');
        $queue_interval = get_option('mms_queue_interval', 'mms_five_minutes');
        
        // Schedule with settings
        if (!wp_next_scheduled('mms_daily_check_sources')) {
            wp_schedule_event(time(), $check_interval, 'mms_daily_check_sources');
        }
        
        if (!wp_next_scheduled('mms_process_queue')) {
            wp_schedule_event(time(), $queue_interval, 'mms_process_queue');
        }
    }

    /**
     * Get schedule settings
     *
     * @return array Schedule settings
     */
    public function get_schedule_settings() {
        return array(
            'check_new_manga' => get_option('mms_check_new_manga', true),
            'check_new_chapters' => get_option('mms_check_new_chapters', true),
            'max_new_manga' => get_option('mms_max_new_manga', 10),
            'max_manga_updates' => get_option('mms_max_manga_updates', 10),
            'max_new_chapters' => get_option('mms_max_new_chapters', 5),
            'check_interval' => get_option('mms_check_interval', 'daily'),
            'queue_interval' => get_option('mms_queue_interval', 'mms_five_minutes'),
        );
    }

    /**
     * Get schedules
     *
     * @return array Schedules
     */
    public function get_schedules() {
        $schedules = wp_get_schedules();
        $result = array();
        
        foreach ($schedules as $key => $schedule) {
            $result[$key] = $schedule['display'];
        }
        
        return $result;
    }
}