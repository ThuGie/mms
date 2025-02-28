<?php
namespace MadaraMangaScraper\Queue;

/**
 * Queue class
 */
class Queue {
    /**
     * Queue item types
     */
    public const TYPE_MANGA = 'manga';
    public const TYPE_CHAPTER = 'chapter';

    /**
     * Queue item statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Queue processing batch size
     */
    public const BATCH_SIZE = 5;

    /**
     * Database instance
     *
     * @var \MadaraMangaScraper\Database\Database
     */
    private $db;

    /**
     * Logger instance
     *
     * @var \MadaraMangaScraper\Logger\Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param \MadaraMangaScraper\Database\Database $db
     * @param \MadaraMangaScraper\Logger\Logger $logger
     */
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Add a manga to the queue
     *
     * @param string $manga_id Manga ID from source
     * @param int $source_id Source ID
     * @param int $priority Priority (1-10, higher is more important)
     * @return bool Success
     */
    public function add_manga($manga_id, $source_id, $priority = 5) {
        $this->logger->info('Adding manga to queue', array(
            'manga_id' => $manga_id,
            'source_id' => $source_id,
            'priority' => $priority,
        ));
        
        // Check if manga is already in queue
        $existing = $this->db->get_row('queue', array(
            'item_type' => self::TYPE_MANGA,
            'item_id' => $manga_id,
            'source_id' => $source_id,
            'status' => self::STATUS_PENDING,
        ));
        
        if ($existing) {
            // Update priority if necessary
            if ($priority > $existing->priority) {
                $this->db->update(
                    'queue',
                    array('priority' => $priority),
                    array('id' => $existing->id)
                );
            }
            
            return true;
        }
        
        // Add to queue
        $data = array(
            'item_type' => self::TYPE_MANGA,
            'item_id' => $manga_id,
            'source_id' => $source_id,
            'priority' => $priority,
            'status' => self::STATUS_PENDING,
        );
        
        return $this->db->insert('queue', $data) !== false;
    }

    /**
     * Add a chapter to the queue
     *
     * @param string $chapter_id Chapter ID from source
     * @param int $source_id Source ID
     * @param int $priority Priority (1-10, higher is more important)
     * @return bool Success
     */
    public function add_chapter($chapter_id, $source_id, $priority = 5) {
        $this->logger->info('Adding chapter to queue', array(
            'chapter_id' => $chapter_id,
            'source_id' => $source_id,
            'priority' => $priority,
        ));
        
        // Check if chapter is already in queue
        $existing = $this->db->get_row('queue', array(
            'item_type' => self::TYPE_CHAPTER,
            'item_id' => $chapter_id,
            'source_id' => $source_id,
            'status' => self::STATUS_PENDING,
        ));
        
        if ($existing) {
            // Update priority if necessary
            if ($priority > $existing->priority) {
                $this->db->update(
                    'queue',
                    array('priority' => $priority),
                    array('id' => $existing->id)
                );
            }
            
            return true;
        }
        
        // Add to queue
        $data = array(
            'item_type' => self::TYPE_CHAPTER,
            'item_id' => $chapter_id,
            'source_id' => $source_id,
            'priority' => $priority,
            'status' => self::STATUS_PENDING,
        );
        
        return $this->db->insert('queue', $data) !== false;
    }

    /**
     * Process queue items
     *
     * @return bool Success
     */
    public function process_queue_items() {
        $this->logger->info('Processing queue items');
        
        // Get next batch of items
        $items = $this->get_next_batch();
        
        if (empty($items)) {
            $this->logger->info('No items in queue to process');
            return true;
        }
        
        $this->logger->info('Found items to process', array('count' => count($items)));
        
        $success = true;
        
        foreach ($items as $item) {
            // Update item status to processing
            $this->update_item_status($item->id, self::STATUS_PROCESSING);
            
            // Process item
            $result = $this->process_item($item);
            
            if ($result) {
                // Update item status to completed
                $this->update_item_status($item->id, self::STATUS_COMPLETED);
            } else {
                // Update item status to failed
                $this->update_item_status(
                    $item->id,
                    self::STATUS_FAILED,
                    'Failed to process item'
                );
                
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Process a single queue item
     *
     * @param object $item Queue item
     * @return bool Success
     */
    private function process_item($item) {
        $this->logger->info('Processing queue item', array(
            'id' => $item->id,
            'type' => $item->item_type,
            'item_id' => $item->item_id,
        ));
        
        try {
            // Update attempt count
            $this->db->update(
                'queue',
                array(
                    'attempts' => $item->attempts + 1,
                    'last_attempt' => current_time('mysql'),
                ),
                array('id' => $item->id)
            );
            
            // Process based on item type
            if ($item->item_type === self::TYPE_MANGA) {
                global $madara_manga_scraper;
                return $madara_manga_scraper->scraper->scrape_manga($item->item_id, $item->source_id);
            } elseif ($item->item_type === self::TYPE_CHAPTER) {
                global $madara_manga_scraper;
                return $madara_manga_scraper->scraper->scrape_chapter($item->item_id, $item->source_id);
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error processing queue item', array(
                'id' => $item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));
            
            return false;
        }
    }

    /**
     * Get next batch of items to process
     *
     * @return array Queue items
     */
    public function get_next_batch() {
        global $wpdb;
        
        $table_name = $this->db->get_table_name('queue');
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE status = %s 
            AND (attempts < max_attempts OR max_attempts = 0)
            ORDER BY priority DESC, created_at ASC
            LIMIT %d",
            self::STATUS_PENDING,
            self::BATCH_SIZE
        );
        
        return $wpdb->get_results($sql);
    }

    /**
     * Update item status
     *
     * @param int $item_id Queue item ID
     * @param string $status New status
     * @param string $error_message Error message (for failed status)
     * @return bool Success
     */
    public function update_item_status($item_id, $status, $error_message = '') {
        $data = array('status' => $status);
        
        if ($status === self::STATUS_FAILED && !empty($error_message)) {
            $data['error_message'] = $error_message;
        }
        
        return $this->db->update('queue', $data, array('id' => $item_id)) !== false;
    }

    /**
     * Get queue items
     *
     * @param array $args Query arguments
     * @return array Queue items
     */
    public function get_items($args = array()) {
        $defaults = array(
            'status' => '',
            'item_type' => '',
            'source_id' => 0,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'priority',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        
        if (!empty($args['status'])) {
            $where['status'] = $args['status'];
        }
        
        if (!empty($args['item_type'])) {
            $where['item_type'] = $args['item_type'];
        }
        
        if (!empty($args['source_id'])) {
            $where['source_id'] = $args['source_id'];
        }
        
        return $this->db->get_results(
            'queue',
            $where,
            $args['orderby'],
            $args['order'],
            $args['limit'],
            $args['offset']
        );
    }

    /**
     * Count queue items
     *
     * @param array $args Query arguments
     * @return int Count
     */
    public function count_items($args = array()) {
        $defaults = array(
            'status' => '',
            'item_type' => '',
            'source_id' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        
        if (!empty($args['status'])) {
            $where['status'] = $args['status'];
        }
        
        if (!empty($args['item_type'])) {
            $where['item_type'] = $args['item_type'];
        }
        
        if (!empty($args['source_id'])) {
            $where['source_id'] = $args['source_id'];
        }
        
        return $this->db->count('queue', $where);
    }

    /**
     * Clear queue
     *
     * @param string $status Status to clear (empty for all)
     * @param string $item_type Item type to clear (empty for all)
     * @return bool Success
     */
    public function clear_queue($status = '', $item_type = '') {
        if (empty($status) && empty($item_type)) {
            return $this->db->truncate('queue');
        } else {
            $where = array();
            
            if (!empty($status)) {
                $where['status'] = $status;
            }
            
            if (!empty($item_type)) {
                $where['item_type'] = $item_type;
            }
            
            return $this->db->delete('queue', $where) !== false;
        }
    }

    /**
     * Retry failed items
     *
     * @return int Number of items reset
     */
    public function retry_failed_items() {
        global $wpdb;
        
        $table_name = $this->db->get_table_name('queue');
        
        $sql = $wpdb->prepare(
            "UPDATE $table_name 
            SET status = %s, error_message = NULL 
            WHERE status = %s",
            self::STATUS_PENDING,
            self::STATUS_FAILED
        );
        
        return $wpdb->query($sql);
    }

    /**
     * Reset processing items
     *
     * @return int Number of items reset
     */
    public function reset_processing_items() {
        global $wpdb;
        
        $table_name = $this->db->get_table_name('queue');
        
        $sql = $wpdb->prepare(
            "UPDATE $table_name 
            SET status = %s 
            WHERE status = %s",
            self::STATUS_PENDING,
            self::STATUS_PROCESSING
        );
        
        return $wpdb->query($sql);
    }

    /**
     * Get queue statistics
     *
     * @return array Queue statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $table_name = $this->db->get_table_name('queue');
        
        $sql = "SELECT 
            status, 
            item_type, 
            COUNT(*) as count 
            FROM $table_name 
            GROUP BY status, item_type";
        
        $results = $wpdb->get_results($sql);
        
        $stats = array(
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'manga' => array(
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
            ),
            'chapter' => array(
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
            ),
        );
        
        foreach ($results as $row) {
            $stats[$row->status] += $row->count;
            $stats[$row->item_type]['total'] += $row->count;
            $stats[$row->item_type][$row->status] += $row->count;
            $stats['total'] += $row->count;
        }
        
        return $stats;
    }

    /**
     * Prioritize item
     *
     * @param int $item_id Queue item ID
     * @param int $priority New priority
     * @return bool Success
     */
    public function set_priority($item_id, $priority) {
        return $this->db->update(
            'queue',
            array('priority' => $priority),
            array('id' => $item_id)
        ) !== false;
    }
}