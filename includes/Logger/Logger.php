<?php
namespace MadaraMangaScraper\Logger;

/**
 * Logger class
 */
class Logger {
    /**
     * Log levels
     */
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const DEBUG = 'debug';

    /**
     * Database instance
     *
     * @var \MadaraMangaScraper\Database\Database
     */
    private $db;

    /**
     * Constructor
     * 
     * @param \MadaraMangaScraper\Database\Database $database Database instance
     */
    public function __construct($database) {
        // Initialize database
        $this->db = $database;
    }

    /**
     * Log a message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @return bool Success
     */
    public function log($level, $message, $context = array()) {
        // Validate log level
        if (!in_array($level, array(self::INFO, self::WARNING, self::ERROR, self::DEBUG))) {
            $level = self::INFO;
        }
        
        // Format context as JSON
        $context_json = !empty($context) ? json_encode($context) : null;
        
        // Insert log into database
        $data = array(
            'level' => $level,
            'message' => $message,
            'context' => $context_json,
        );
        
        $inserted = $this->db->insert('logs', $data);
        
        // Write to file if enabled
        $this->write_to_file($level, $message, $context);
        
        return $inserted !== false;
    }

    /**
     * Log an info message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return bool Success
     */
    public function info($message, $context = array()) {
        return $this->log(self::INFO, $message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return bool Success
     */
    public function warning($message, $context = array()) {
        return $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return bool Success
     */
    public function error($message, $context = array()) {
        return $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log a debug message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return bool Success
     */
    public function debug($message, $context = array()) {
        // Only log debug messages if debug mode is enabled
        if ($this->is_debug_enabled()) {
            return $this->log(self::DEBUG, $message, $context);
        }
        
        return true;
    }

    /**
     * Log an error to the errors table
     *
     * @param string $item_type Item type (manga, chapter, source)
     * @param string $item_id Item ID
     * @param string $message Error message
     * @param string $trace Error trace
     * @return bool Success
     */
    public function log_error($item_type, $item_id, $message, $trace = '') {
        // Log to regular log
        $this->error($message, array(
            'item_type' => $item_type,
            'item_id' => $item_id,
            'trace' => $trace,
        ));
        
        // Insert error into database
        $data = array(
            'item_type' => $item_type,
            'item_id' => $item_id,
            'error_message' => $message,
            'error_trace' => $trace,
        );
        
        return $this->db->insert('errors', $data) !== false;
    }

    /**
     * Write log to file
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     */
    private function write_to_file($level, $message, $context = array()) {
        // Check if file logging is enabled
        if (!$this->is_file_logging_enabled()) {
            return;
        }
        
        // Create log directory if it doesn't exist
        if (!file_exists(MMS_LOG_DIR)) {
            wp_mkdir_p(MMS_LOG_DIR);
        }
        
        // Generate log file name with date
        $date = date('Y-m-d');
        $file = MMS_LOG_DIR . "mms-{$date}.log";
        
        // Format log entry
        $time = date('Y-m-d H:i:s');
        $level_upper = strtoupper($level);
        $context_str = !empty($context) ? ' ' . json_encode($context) : '';
        $log_entry = "[{$time}] [{$level_upper}] {$message}{$context_str}" . PHP_EOL;
        
        // Write to file
        file_put_contents($file, $log_entry, FILE_APPEND);
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool Whether debug mode is enabled
     */
    private function is_debug_enabled() {
        return get_option('mms_debug_mode', false);
    }

    /**
     * Check if file logging is enabled
     *
     * @return bool Whether file logging is enabled
     */
    private function is_file_logging_enabled() {
        return get_option('mms_file_logging', true);
    }

    /**
     * Get logs
     *
     * @param array $args Query arguments
     * @return array Logs
     */
    public function get_logs($args = array()) {
        $defaults = array(
            'level' => '',
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        
        if (!empty($args['level'])) {
            $where['level'] = $args['level'];
        }
        
        return $this->db->get_results(
            'logs',
            $where,
            $args['orderby'],
            $args['order'],
            $args['limit'],
            $args['offset']
        );
    }

    /**
     * Get errors
     *
     * @param array $args Query arguments
     * @return array Errors
     */
    public function get_errors($args = array()) {
        $defaults = array(
            'item_type' => '',
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        
        if (!empty($args['item_type'])) {
            $where['item_type'] = $args['item_type'];
        }
        
        return $this->db->get_results(
            'errors',
            $where,
            $args['orderby'],
            $args['order'],
            $args['limit'],
            $args['offset']
        );
    }

    /**
     * Clear logs
     *
     * @param string $level Log level to clear (empty for all)
     * @return bool Success
     */
    public function clear_logs($level = '') {
        if (empty($level)) {
            return $this->db->truncate('logs');
        } else {
            return $this->db->delete('logs', array('level' => $level)) !== false;
        }
    }

    /**
     * Clear errors
     *
     * @param string $item_type Item type to clear (empty for all)
     * @return bool Success
     */
    public function clear_errors($item_type = '') {
        if (empty($item_type)) {
            return $this->db->truncate('errors');
        } else {
            return $this->db->delete('errors', array('item_type' => $item_type)) !== false;
        }
    }

    /**
     * Clear log files
     *
     * @return bool Success
     */
    public function clear_log_files() {
        if (!file_exists(MMS_LOG_DIR)) {
            return true;
        }
        
        $files = glob(MMS_LOG_DIR . '*.log');
        
        if (empty($files)) {
            return true;
        }
        
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }
}