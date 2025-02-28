<?php
namespace MadaraMangaScraper\Database;

/**
 * Database handler class
 */
class Database {
    /**
     * Table names
     */
    private $tables = array();

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        // Define table names
        $this->tables = array(
            'sources' => $wpdb->prefix . 'mms_sources',
            'manga' => $wpdb->prefix . 'mms_manga',
            'chapters' => $wpdb->prefix . 'mms_chapters',
            'queue' => $wpdb->prefix . 'mms_queue',
            'logs' => $wpdb->prefix . 'mms_logs',
            'errors' => $wpdb->prefix . 'mms_errors',
        );
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Sources table
        $sql = "CREATE TABLE {$this->tables['sources']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(255) NOT NULL,
            status tinyint(1) NOT NULL DEFAULT 1,
            last_checked datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY url (url)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Manga table
        $sql = "CREATE TABLE {$this->tables['manga']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_id bigint(20) NOT NULL,
            source_manga_id varchar(255) NOT NULL,
            wp_post_id bigint(20) DEFAULT NULL,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description longtext DEFAULT NULL,
            cover_url varchar(255) DEFAULT NULL,
            status varchar(50) DEFAULT NULL,
            genres text DEFAULT NULL,
            authors text DEFAULT NULL,
            artists text DEFAULT NULL,
            last_chapter_number varchar(50) DEFAULT NULL,
            last_chapter_date datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY source_manga (source_id, source_manga_id),
            KEY wp_post_id (wp_post_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Chapters table
        $sql = "CREATE TABLE {$this->tables['chapters']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            manga_id bigint(20) NOT NULL,
            source_chapter_id varchar(255) NOT NULL,
            wp_post_id bigint(20) DEFAULT NULL,
            chapter_number varchar(50) NOT NULL,
            title varchar(255) DEFAULT NULL,
            slug varchar(255) NOT NULL,
            images_path varchar(255) DEFAULT NULL,
            merged_image varchar(255) DEFAULT NULL,
            published_date datetime DEFAULT NULL,
            downloaded tinyint(1) NOT NULL DEFAULT 0,
            processed tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY manga_chapter (manga_id, source_chapter_id),
            KEY wp_post_id (wp_post_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Queue table
        $sql = "CREATE TABLE {$this->tables['queue']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            item_type enum('manga', 'chapter') NOT NULL,
            item_id varchar(255) NOT NULL,
            source_id bigint(20) NOT NULL,
            priority tinyint(2) NOT NULL DEFAULT 5,
            status enum('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
            attempts smallint(5) NOT NULL DEFAULT 0,
            max_attempts smallint(5) NOT NULL DEFAULT 3,
            last_attempt datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY priority (priority),
            KEY item_type (item_type)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Logs table
        $sql = "CREATE TABLE {$this->tables['logs']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level enum('info', 'warning', 'error', 'debug') NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY level (level)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Errors table
        $sql = "CREATE TABLE {$this->tables['errors']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            item_type enum('manga', 'chapter', 'source') NOT NULL,
            item_id varchar(255) NOT NULL,
            error_message text NOT NULL,
            error_trace text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY item_type (item_type)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Get table name
     *
     * @param string $table Table key
     * @return string Table name
     */
    public function get_table_name($table) {
        return isset($this->tables[$table]) ? $this->tables[$table] : '';
    }

    /**
     * Insert data into a table
     *
     * @param string $table Table key
     * @param array $data Data to insert
     * @return int|false The number of rows inserted, or false on error
     */
    public function insert($table, $data) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table);
        
        if (empty($table_name)) {
            return false;
        }
        
        return $wpdb->insert($table_name, $data);
    }

    /**
     * Update data in a table
     *
     * @param string $table Table key
     * @param array $data Data to update
     * @param array $where Where clause
     * @return int|false The number of rows updated, or false on error
     */
    public function update($table, $data, $where) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table);
        
        if (empty($table_name)) {
            return false;
        }
        
        return $wpdb->update($table_name, $data, $where);
    }

    /**
     * Delete data from a table
     *
     * @param string $table Table key
     * @param array $where Where clause
     * @return int|false The number of rows deleted, or false on error
     */
    public function delete($table, $where) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table);
        
        if (empty($table_name)) {
            return false;
        }
        
        return $wpdb->delete($table_name, $where);
    }

    /**
     * Get a single row from a table
     *
     * @param string $table Table key
     * @param array $where Where clause
     * @param string $output_type Output type
     * @return object|array|null Database query result
     */
    public function get_row($table, $where, $output_type = OBJECT) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table);
        
        if (empty($table_name)) {
            return null;
        }
        
        $conditions = array();
        $values = array();
        
        foreach ($where as $field => $value) {
            $conditions[] = "`$field` = %s";
            $values[] = $value;
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE " . implode(' AND ', $conditions),
            $values
        );
        
        return $wpdb->get_row($sql, $output_type);
    }

    /**
     * Get multiple rows from a table
     *
     * @param string $table Table key
     * @param array $where Where clause
     * @param string $orderby Order by
     * @param string $order Order
     * @param int $limit Limit
     * @param int $offset Offset
     * @param string $output_type Output type
     * @return array Database query results
     */
    public function get_results($table, $where = array(), $orderby = 'id', $order = 'DESC', $limit = 0, $offset = 0, $output_type = OBJECT) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table);
        
        if (empty($table_name)) {
            return array();
        }
        
        $sql = "SELECT * FROM $table_name";
        
        // Where clause
        if (!empty($where)) {
            $conditions = array();
            $values = array();
            
            foreach ($where as $field => $value) {
                $conditions[] = "`$field` = %s";
                $values[] = $value;
            }
            
            $sql .= $wpdb->prepare(" WHERE " . implode(' AND ', $conditions), $values);
        }
        
        // Order
        $sql .= " ORDER BY $orderby $order";
        
        // Limit
        if ($limit > 0) {
            $sql .= " LIMIT $offset, $limit";
        }
        
        return $wpdb->get_results($sql, $output_type);
    }

    /**
     * Count rows in a table
     *
     * @param string $table Table key
     * @param array $where Where clause
     * @return int Count
     */
    public function count($table, $where = array()) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table);
        
        if (empty($table_name)) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) FROM $table_name";
        
        // Where clause
        if (!empty($where)) {
            $conditions = array();
            $values = array();
            
            foreach ($where as $field => $value) {
                $conditions[] = "`$field` = %s";
                $values[] = $value;
            }
            
            $sql .= $wpdb->prepare(" WHERE " . implode(' AND ', $conditions), $values);
        }
        
        return (int) $wpdb->get_var($sql);
    }

    /**
     * Execute a custom query
     *
     * @param string $sql SQL query
     * @param string $output_type Output type
     * @return mixed Query results
     */
    public function query($sql, $output_type = OBJECT) {
        global $wpdb;
        
        if (stripos($sql, 'SELECT') === 0) {
            return $wpdb->get_results($sql, $output_type);
        } else {
            return $wpdb->query($sql);
        }
    }

    /**
     * Truncate a table
     *
     * @param string $table Table key
     * @return bool Success
     */
    public function truncate($table) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table);
        
        if (empty($table_name)) {
            return false;
        }
        
        return $wpdb->query("TRUNCATE TABLE $table_name");
    }
}
