<?php
namespace MadaraMangaScraper\Scraper;

/**
 * Base Scraper class with common functionality
 */
abstract class BaseScraper {
    /**
     * Queue instance
     *
     * @var \MadaraMangaScraper\Queue\Queue
     */
    protected $queue;

    /**
     * Logger instance
     *
     * @var \MadaraMangaScraper\Logger\Logger
     */
    protected $logger;

    /**
     * Database instance
     *
     * @var \MadaraMangaScraper\Database\Database
     */
    protected $db;

    /**
     * HTTP request timeout
     */
    public const REQUEST_TIMEOUT = 30;

    /**
     * Delay between requests (in seconds)
     */
    protected $request_delay = 2;

    /**
     * User agent
     */
    protected $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

    /**
     * Constructor
     *
     * @param \MadaraMangaScraper\Queue\Queue $queue
     * @param \MadaraMangaScraper\Logger\Logger $logger
     * @param \MadaraMangaScraper\Database\Database $database
     */
    public function __construct($queue, $logger, $database) {
        $this->queue = $queue;
        $this->logger = $logger;
        $this->db = $database;
        
        // Set request delay from settings
        $this->request_delay = get_option('mms_request_delay', 2);
    }

    /**
     * Get database instance
     *
     * @return \MadaraMangaScraper\Database\Database
     */
    public function get_database() {
        return $this->db;
    }

    /**
     * Make an HTTP request
     *
     * @param string $url URL
     * @param string $method HTTP method
     * @param array $data POST data
     * @return array|WP_Error Response
     */
    protected function make_request($url, $method = 'GET', $data = array()) {
        $args = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'user-agent' => $this->user_agent,
            'headers' => array(
                'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST),
            ),
        );
        
        if ($method === 'POST') {
            $args['method'] = 'POST';
            $args['body'] = $data;
        }
        
        return wp_remote_request($url, $args);
    }

    /**
     * Download an image
     *
     * @param string $url Image URL
     * @return string|false Image data or false on failure
     */
    protected function download_image($url) {
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            $this->logger->error('Failed to download image', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
            
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }

    /**
     * Set request delay
     *
     * @param int $delay Delay in seconds
     */
    public function set_request_delay($delay) {
        $this->request_delay = max(1, intval($delay));
        update_option('mms_request_delay', $this->request_delay);
    }

    /**
     * Get request delay
     *
     * @return int Delay in seconds
     */
    public function get_request_delay() {
        return $this->request_delay;
    }

    /**
     * Check if a source is valid
     *
     * @param string $url Source URL
     * @return bool Whether the source is valid
     */
    public function is_valid_source($url) {
        $this->logger->info('Checking if source is valid', array('url' => $url));
        
        // Make request to homepage
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            $this->logger->error('Failed to connect to source', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
            
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Check if it's a Madara theme
        $is_madara = false;
        
        // Check for specific Madara elements or scripts
        if (
            stripos($body, 'madara') !== false ||
            stripos($body, 'wp-manga') !== false ||
            stripos($body, 'c-blog__heading') !== false ||
            stripos($body, 'manga-section') !== false
        ) {
            $is_madara = true;
        }
        
        $this->logger->info('Source validity check result', array(
            'url' => $url,
            'is_madara' => $is_madara,
        ));
        
        return $is_madara;
    }

    /**
     * Add a new source
     *
     * @param string $name Source name
     * @param string $url Source URL
     * @return int|false Source ID or false on failure
     */
    public function add_source($name, $url) {
        $this->logger->info('Adding new source', array(
            'name' => $name,
            'url' => $url,
        ));
        
        // Validate URL
        $url = esc_url_raw($url);
        
        if (empty($url)) {
            $this->logger->error('Invalid URL', array('url' => $url));
            return false;
        }
        
        // Check if URL is valid
        if (!$this->is_valid_source($url)) {
            $this->logger->error('Invalid Madara source', array('url' => $url));
            return false;
        }
        
        // Normalize URL
        $url = trailingslashit($url);
        
        // Check if source already exists
        $existing = $this->db->get_row('sources', array('url' => $url));
        
        if ($existing) {
            $this->logger->warning('Source already exists', array(
                'url' => $url,
                'id' => $existing->id,
            ));
            
            return $existing->id;
        }
        
        // Add source
        $result = $this->db->insert(
            'sources',
            array(
                'name' => $name,
                'url' => $url,
                'status' => 1,
            )
        );
        
        if (!$result) {
            $this->logger->error('Failed to add source', array(
                'name' => $name,
                'url' => $url,
            ));
            
            return false;
        }
        
        $source_id = $this->db->get_row('sources', array('url' => $url))->id;
        
        $this->logger->info('Source added successfully', array(
            'id' => $source_id,
            'name' => $name,
            'url' => $url,
        ));
        
        return $source_id;
    }

    /**
     * Update a source
     *
     * @param int $source_id Source ID
     * @param array $data Source data
     * @return bool Success
     */
    public function update_source($source_id, $data) {
        $this->logger->info('Updating source', array(
            'id' => $source_id,
            'data' => $data,
        ));
        
        return $this->db->update('sources', $data, array('id' => $source_id)) !== false;
    }

    /**
     * Delete a source
     *
     * @param int $source_id Source ID
     * @return bool Success
     */
    public function delete_source($source_id) {
        $this->logger->info('Deleting source', array('id' => $source_id));
        
        return $this->db->delete('sources', array('id' => $source_id)) !== false;
    }

    /**
     * Get sources
     *
     * @param array $args Query arguments
     * @return array Sources
     */
    public function get_sources($args = array()) {
        $defaults = array(
            'status' => 1,
            'orderby' => 'id',
            'order' => 'ASC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        
        if ($args['status'] !== '') {
            $where['status'] = $args['status'];
        }
        
        return $this->db->get_results(
            'sources',
            $where,
            $args['orderby'],
            $args['order']
        );
    }

    /**
     * Get AJAX URL for a source
     *
     * @param string $source_url Source URL
     * @return string AJAX URL
     */
    protected function get_ajax_url($source_url) {
        return trailingslashit($source_url) . 'wp-admin/admin-ajax.php';
    }

    /**
     * Check if there's a next page
     *
     * @param string $html HTML content
     * @return bool Whether there's a next page
     */
    protected function has_next_page($html) {
        // Use DOMDocument to parse HTML
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        
        // Multiple ways to detect pagination in Madara themes
        
        // Method 1: Check for standard Madara "nav-previous" class
        $next_page = $xpath->query('//div[contains(@class, "nav-previous")]//a');
        if ($next_page->length > 0) {
            return true;
        }
        
        // Method 2: Check for WP-PageNavi plugin pagination
        $page_navi = $xpath->query('//div[contains(@class, "wp-pagenavi")]//a[contains(@class, "nextpostslink")]');
        if ($page_navi->length > 0) {
            return true;
        }
        
        // Method 3: Check for standard WordPress next page links
        $next_link = $xpath->query('//a[contains(@class, "next")]');
        if ($next_link->length > 0) {
            return true;
        }
        
        // Method 4: Check for pagination numbers
        $pagination = $xpath->query('//div[contains(@class, "pagination")]//a[contains(@class, "next")]');
        if ($pagination->length > 0) {
            return true;
        }
        
        // Method 5: Check for text content in links
        $next_text_links = $xpath->query('//a[contains(text(), "Next")]');
        if ($next_text_links->length > 0) {
            return true;
        }
        
        // Method 6: Look for fa-angle-right Font Awesome icon (common in pagination)
        $fa_next = $xpath->query('//a[.//i[contains(@class, "fa-angle-right")] or .//i[contains(@class, "fa-chevron-right")]]');
        if ($fa_next->length > 0) {
            return true;
        }
        
        return false;
    }

    /**
     * Extract AJAX data for chapters with enhanced patterns
     *
     * @param string $html HTML content
     * @return array|false AJAX data or false on failure
     */
    public function extract_ajax_data($html) {
        $this->logger->debug('Attempting to extract AJAX data');
        
        // Set of patterns to try for each required parameter
        
        // Manga ID patterns
        $manga_id_patterns = [
            '/manga_id\s*:\s*[\'"]?(\d+)[\'"]?/i',
            '/var\s+manga\s*=\s*{\s*id\s*:\s*(\d+)/is',
            '/data-id=[\'"](\d+)[\'"]/is',
            '/post_id\s*[:=]\s*[\'"]?(\d+)[\'"]?/i',
            '/manga_id[\'"]\s*:\s*[\'"](\d+)[\'"]/i',
            '/manga-id[\'"]?\s*[:=]\s*[\'"]?(\d+)[\'"]?/i',
            '/id[\'"]?\s*[:=]\s*[\'"]?(\d+)[\'"]?.*?manga/is'
        ];
        
        // Chapter type patterns
        $chapter_type_patterns = [
            '/chapter_type\s*:\s*[\'"]([^\'"]+)[\'"]/i',
            '/var\s+chapter_type\s*=\s*[\'"]([^\'"]+)[\'"]/is',
            '/data-chapter-type=[\'"]([^\'"]+)[\'"]/is',
            '/chapter_type\s*[:=]\s*[\'"]([^\'"]+)[\'"]/i',
            '/chapterType[\'"]\s*:\s*[\'"]([^\'"]+)[\'"]/i',
            '/type[\'"]\s*[:=]\s*[\'"]([^\'"]+)[\'"]/i'
        ];
        
        // Nonce patterns
        $nonce_patterns = [
            '/_wpnonce\s*:\s*[\'"]([^\'"]+)[\'"]/i',
            '/nonce\s*[:=]\s*[\'"]([^\'"]+)[\'"]/i',
            '/data-nonce=[\'"]([^\'"]+)[\'"]/is',
            '/wp_manga_ajax_nonce[\'"]\s*:\s*[\'"]([^\'"]+)[\'"]/i',
            '/mangaAjaxNonce[\'"]\s*[:=]\s*[\'"]([^\'"]+)[\'"]/i',
            '/ajax_nonce[\'"]\s*[:=]\s*[\'"]([^\'"]+)[\'"]/i'
        ];
        
        // Extract each parameter
        $manga_id = null;
        foreach ($manga_id_patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $manga_id = $matches[1];
                $this->logger->debug('Found manga_id', ['pattern' => $pattern, 'value' => $manga_id]);
                break;
            }
        }
        
        $chapter_type = null;
        foreach ($chapter_type_patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $chapter_type = $matches[1];
                $this->logger->debug('Found chapter_type', ['pattern' => $pattern, 'value' => $chapter_type]);
                break;
            }
        }
        
        $nonce = null;
        foreach ($nonce_patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $nonce = $matches[1];
                $this->logger->debug('Found nonce', ['pattern' => $pattern, 'value' => $nonce]);
                break;
            }
        }
        
        // If we found all parameters, return the AJAX data
        if ($manga_id && $chapter_type && $nonce) {
            $this->logger->debug('Successfully extracted AJAX data', [
                'manga_id' => $manga_id,
                'type' => $chapter_type,
                'nonce' => $nonce
            ]);
            
            return array(
                'action' => 'manga_get_chapters',
                'manga' => $manga_id,
                'type' => $chapter_type,
                '_wpnonce' => $nonce
            );
        }
        
        // Try to find the parameters in data attributes
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        
        // Find elements with data attributes
        $elements = $xpath->query('//*[@data-id or @data-manga-id or @data-post-id]');
        
        foreach ($elements as $element) {
            $id = $element->getAttribute('data-id') ?: 
                  $element->getAttribute('data-manga-id') ?: 
                  $element->getAttribute('data-post-id');
                  
            $type = $element->getAttribute('data-type') ?: 
                    $element->getAttribute('data-chapter-type') ?: 
                    'manga';
                    
            $data_nonce = $element->getAttribute('data-nonce') ?: 
                          $element->getAttribute('data-wpnonce') ?: 
                          $element->getAttribute('data-wp-nonce');
            
            if ($id && $data_nonce) {
                $this->logger->debug('Found AJAX data in DOM attributes', [
                    'manga_id' => $id,
                    'type' => $type,
                    'nonce' => $data_nonce
                ]);
                
                return array(
                    'action' => 'manga_get_chapters',
                    'manga' => $id,
                    'type' => $type,
                    '_wpnonce' => $data_nonce
                );
            }
        }
        
        // Try for script JSON
        $scripts = $xpath->query('//script[not(@src)]');
        foreach ($scripts as $script) {
            $content = $script->textContent;
            
            // Look for JSON structures
            if (preg_match('/(?:var|let|const)\s+\w+\s*=\s*({[^;]+})/s', $content, $matches)) {
                $json_str = $matches[1];
                
                // Try to fix common JSON issues in JavaScript objects
                $json_str = preg_replace('/([{,])\s*(\w+)\s*:/s', '$1"$2":', $json_str);
                $json_str = preg_replace('/:\s*\'([^\']+)\'/s', ':"$1"', $json_str);
                
                try {
                    $data = json_decode($json_str, true);
                    if (isset($data['manga_id']) && isset($data['nonce'])) {
                        $this->logger->debug('Found AJAX data in script JSON', [
                            'manga_id' => $data['manga_id'],
                            'nonce' => $data['nonce']
                        ]);
                        
                        return array(
                            'action' => 'manga_get_chapters',
                            'manga' => $data['manga_id'],
                            'type' => isset($data['type']) ? $data['type'] : 'manga',
                            '_wpnonce' => $data['nonce']
                        );
                    }
                } catch (\Exception $e) {
                    // Just continue if JSON decode fails
                }
            }
        }
        
        // Log detailed information about the failure for debugging
        $this->logger->warning('Failed to extract AJAX data', [
            'html_excerpt' => substr($html, 0, 500) . '...' // Log first 500 chars for debug purposes
        ]);
        
        return false;
    }

    /**
     * Extract chapter information directly from HTML
     *
     * @param string $html HTML content of the manga page
     * @return array|false Array of chapter data or false on failure
     */
    public function extract_chapters_from_html($html) {
        $this->logger->debug('Attempting to extract chapters directly from HTML');
        
        // Use DOMDocument to parse HTML
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        
        $chapters = array();
        
        // Try different selectors used by various Madara themes
        
        // Method 1: Standard Madara chapter listing
        $chapter_items = $xpath->query('//div[contains(@class, "listing-chapters-wrap")]/ul/li/a');
        
        if ($chapter_items->length === 0) {
            // Method 2: Alternative chapter listing
            $chapter_items = $xpath->query('//div[contains(@class, "chapter-list")]/div[contains(@class, "row")]//a');
        }
        
        if ($chapter_items->length === 0) {
            // Method 3: Volume-based chapter listing
            $chapter_items = $xpath->query('//ul[contains(@class, "main")]/li/a');
        }
        
        if ($chapter_items->length === 0) {
            // Method 4: Another common pattern
            $chapter_items = $xpath->query('//div[contains(@class, "wp-manga-chapter")]/a');
        }
        
        if ($chapter_items->length === 0) {
            $this->logger->warning('No chapter items found in HTML');
            return false;
        }
        
        foreach ($chapter_items as $item) {
            $chapter_url = $item->getAttribute('href');
            $chapter_name = trim($item->textContent);
            
            // Try to extract chapter number from the text
            $chapter_number = '';
            if (preg_match('/chapter\s+([0-9\.]+)/i', $chapter_name, $matches)) {
                $chapter_number = $matches[1];
            } else if (preg_match('/^([0-9\.]+)/', $chapter_name, $matches)) {
                $chapter_number = $matches[1];
            } else {
                // Try from URL
                if (preg_match('/chapter-([0-9\.]+)/i', $chapter_url, $matches)) {
                    $chapter_number = $matches[1];
                }
            }
            
            // Extract chapter ID from URL
            $chapter_id = ScraperUtils::extract_chapter_id_from_url($chapter_url);
            
            if (!$chapter_id) {
                continue;
            }
            
            // Try to get chapter title
            $title = '';
            if (strpos($chapter_name, ':') !== false) {
                $parts = explode(':', $chapter_name, 2);
                $title = trim($parts[1]);
            }
            
            // Get release date if available
            $date = '';
            $date_element = $xpath->query('./parent::*/span[contains(@class, "chapter-release-date")]', $item);
            
            if ($date_element->length > 0) {
                $date = trim($date_element->item(0)->textContent);
            }
            
            $chapters[] = array(
                'chapter' => $chapter_number,
                'title' => $title,
                'url' => $chapter_url,
                'date' => $date,
                'chapter_id' => $chapter_id
            );
        }
        
        $this->logger->info('Extracted chapters directly from HTML', array(
            'count' => count($chapters)
        ));
        
        // Return in a format similar to the AJAX response
        return array(
            'success' => true,
            'data' => $chapters
        );
    }
}