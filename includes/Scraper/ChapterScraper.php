<?php
namespace MadaraMangaScraper\Scraper;

/**
 * Chapter Scraper class
 */
class ChapterScraper extends BaseScraper {
    /**
     * Image processor instance
     *
     * @var \MadaraMangaScraper\ImageProcessor\ImageProcessor
     */
    private $image_processor;

    /**
     * Constructor
     *
     * @param \MadaraMangaScraper\Queue\Queue $queue
     * @param \MadaraMangaScraper\Logger\Logger $logger
     * @param \MadaraMangaScraper\Database\Database $database
     * @param \MadaraMangaScraper\ImageProcessor\ImageProcessor $image_processor
     */
    public function __construct($queue, $logger, $database, $image_processor) {
        parent::__construct($queue, $logger, $database);
        $this->image_processor = $image_processor;
    }

    /**
     * Scrape manga chapters (metadata only)
     *
     * @param string $manga_id Manga ID
     * @param int $source_id Source ID
     * @param int $manga_db_id Manga database ID
     * @return bool Success
     */
    public function scrape_manga_chapters($manga_id, $source_id, $manga_db_id) {
        $this->logger->info('Scraping manga chapters', array(
            'manga_id' => $manga_id,
            'source_id' => $source_id,
            'manga_db_id' => $manga_db_id,
        ));
        
        // Get source
        $source = $this->db->get_row('sources', array('id' => $source_id));
        
        if (!$source) {
            $this->logger->error('Source not found', array('source_id' => $source_id));
            return false;
        }
        
        try {
            // Get manga URL
            $manga_url = ScraperUtils::get_manga_url($source->url, $manga_id);
            
            // Get manga page content for AJAX info
            $response = $this->make_request($manga_url);
            
            if (is_wp_error($response)) {
                $this->logger->error('Failed to get manga page', array(
                    'url' => $manga_url,
                    'error' => $response->get_error_message(),
                ));
                
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            
            // Extract AJAX data for chapters
            $ajax_data = $this->extract_ajax_data($body);
            
            if (!$ajax_data) {
                $this->logger->error('Failed to extract AJAX data', array(
                    'url' => $manga_url,
                ));
                
                return false;
            }
            
            // Make AJAX request for chapters
            $ajax_url = $this->get_ajax_url($source->url);
            
            $response = $this->make_request(
                $ajax_url,
                'POST',
                $ajax_data
            );
            
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
                    'manga_id' => $manga_id,
                    'source_id' => $source_id,
                ));
                
                return true;
            }
            
            $chapter_count = 0;
            
            // Process chapters
            foreach ($chapters['data'] as $chapter) {
                // Extract chapter ID
                $chapter_id = ScraperUtils::extract_chapter_id_from_url($chapter['url']);
                
                if (!$chapter_id) {
                    continue;
                }
                
                // Add chapter to database
                $existing_chapter = $this->db->get_row('chapters', array(
                    'manga_id' => $manga_db_id,
                    'source_chapter_id' => $chapter_id,
                ));
                
                if (!$existing_chapter) {
                    $this->db->insert(
                        'chapters',
                        array(
                            'manga_id' => $manga_db_id,
                            'source_chapter_id' => $chapter_id,
                            'chapter_number' => $chapter['chapter'],
                            'title' => isset($chapter['title']) ? $chapter['title'] : '',
                            'slug' => ScraperUtils::generate_chapter_slug($chapter['chapter'], isset($chapter['title']) ? $chapter['title'] : ''),
                            'published_date' => isset($chapter['date']) ? date('Y-m-d H:i:s', strtotime($chapter['date'])) : null,
                        )
                    );
                    
                    // Add chapter to queue
                    $this->queue->add_chapter($chapter_id, $source_id);
                    $chapter_count++;
                }
            }
            
            // Update manga with latest chapter info
            if (!empty($chapters['data'])) {
                $latest_chapter = reset($chapters['data']);
                
                $this->db->update(
                    'manga',
                    array(
                        'last_chapter_number' => $latest_chapter['chapter'],
                        'last_chapter_date' => isset($latest_chapter['date']) ? date('Y-m-d H:i:s', strtotime($latest_chapter['date'])) : null,
                    ),
                    array('id' => $manga_db_id)
                );
            }
            
            $this->logger->info('Finished scraping manga chapters', array(
                'manga_id' => $manga_id,
                'source_id' => $source_id,
                'chapter_count' => $chapter_count,
            ));
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error scraping manga chapters', array(
                'manga_id' => $manga_id,
                'source_id' => $source_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));
            
            return false;
        }
    }

    /**
     * Scrape a single chapter (download content)
     *
     * @param string $chapter_id Chapter ID
     * @param int $source_id Source ID
     * @return bool Success
     */
    public function scrape_chapter($chapter_id, $source_id) {
        $this->logger->info('Scraping chapter', array(
            'chapter_id' => $chapter_id,
            'source_id' => $source_id,
        ));
        
        // Get source
        $source = $this->db->get_row('sources', array('id' => $source_id));
        
        if (!$source) {
            $this->logger->error('Source not found', array('source_id' => $source_id));
            return false;
        }
        
        try {
            // Get chapter from database with manga info
            global $wpdb;
            $chapter_table = $this->db->get_table_name('chapters');
            $manga_table = $this->db->get_table_name('manga');
            
            $sql = $wpdb->prepare(
                "SELECT c.*, m.source_manga_id, m.wp_post_id, m.title as manga_title, m.slug as manga_slug
                FROM $chapter_table c
                JOIN $manga_table m ON c.manga_id = m.id
                WHERE c.source_chapter_id = %s",
                $chapter_id
            );
            
            $chapter = $wpdb->get_row($sql);
            
            if (!$chapter) {
                $this->logger->error('Chapter not found in database', array(
                    'chapter_id' => $chapter_id,
                ));
                
                return false;
            }
            
            // Get chapter URL
            $chapter_url = ScraperUtils::get_chapter_url($source->url, $chapter->source_manga_id, $chapter_id);
            
            // Get chapter page content
            $response = $this->make_request($chapter_url);
            
            if (is_wp_error($response)) {
                $this->logger->error('Failed to get chapter page', array(
                    'url' => $chapter_url,
                    'error' => $response->get_error_message(),
                ));
                
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            
            // Parse chapter images
            $images = $this->parse_chapter_images($body);
            
            if (empty($images)) {
                $this->logger->error('No images found in chapter', array(
                    'url' => $chapter_url,
                ));
                
                return false;
            }
            
            $this->logger->info('Found images in chapter', array(
                'count' => count($images),
                'chapter_id' => $chapter_id,
            ));
            
            // Create directory for chapter images
            $upload_dir = wp_upload_dir();
            $manga_dir = $upload_dir['basedir'] . '/manga/' . $chapter->manga_slug;
            $chapter_dir = $manga_dir . '/' . $chapter->slug;
            
            if (!file_exists($manga_dir)) {
                wp_mkdir_p($manga_dir);
            }
            
            if (!file_exists($chapter_dir)) {
                wp_mkdir_p($chapter_dir);
            }
            
            // Download images
            $downloaded_images = array();
            
            foreach ($images as $index => $image_url) {
                $extension = ScraperUtils::get_file_extension_from_url($image_url);
                $image_filename = sprintf('%03d.%s', $index + 1, $extension);
                $image_path = $chapter_dir . '/' . $image_filename;
                
                // Download image
                $image_data = $this->download_image($image_url);
                
                if ($image_data) {
                    file_put_contents($image_path, $image_data);
                    $downloaded_images[] = $image_path;
                    
                    // Delay between requests
                    usleep(500000); // 0.5 seconds
                }
            }
            
            if (empty($downloaded_images)) {
                $this->logger->error('Failed to download any images', array(
                    'chapter_id' => $chapter_id,
                ));
                
                return false;
            }
            
            // Update chapter in database
            $this->db->update(
                'chapters',
                array(
                    'downloaded' => 1,
                    'images_path' => str_replace($upload_dir['basedir'], '', $chapter_dir),
                ),
                array('id' => $chapter->id)
            );
            
            // Create merged image if enabled
            if (get_option('mms_merge_images', true)) {
                $merged_image = $this->merge_chapter_images($downloaded_images, $chapter_dir);
                
                if ($merged_image) {
                    $this->db->update(
                        'chapters',
                        array('merged_image' => basename($merged_image)),
                        array('id' => $chapter->id)
                    );
                }
            }
            
            // Create or update WordPress post if manga has post
            if ($chapter->wp_post_id) {
                $chapter_post_id = $this->create_or_update_chapter_post($chapter->id, $chapter->wp_post_id);
                
                if ($chapter_post_id) {
                    $this->db->update(
                        'chapters',
                        array(
                            'wp_post_id' => $chapter_post_id,
                            'processed' => 1,
                        ),
                        array('id' => $chapter->id)
                    );
                }
            }
            
            $this->logger->info('Finished scraping chapter', array(
                'chapter_id' => $chapter_id,
                'source_id' => $source_id,
                'images_count' => count($downloaded_images),
            ));
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error scraping chapter', array(
                'chapter_id' => $chapter_id,
                'source_id' => $source_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));
            
            return false;
        }
    }

    /**
     * Parse chapter images
     *
     * @param string $html HTML content
     * @return array Image URLs
     */
    public function parse_chapter_images($html) {
        $images = array();
        
        // Use DOMDocument to parse HTML
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        
        // Get image containers
        $image_containers = $xpath->query('//div[contains(@class, "reading-content")]//img');
        
        foreach ($image_containers as $img) {
            $src = $img->getAttribute('src');
            $data_src = $img->getAttribute('data-src');
            
            // Use data-src if src is empty or a placeholder
            if (empty($src) || strpos($src, 'data:image') === 0) {
                $src = $data_src;
            }
            
            // Check for lazy loading
            if (empty($src)) {
                $src = $img->getAttribute('data-lazy-src');
            }
            
            if (!empty($src)) {
                $images[] = $src;
            }
        }
        
        return $images;
    }

    /**
     * Merge chapter images into a single file
     *
     * @param array $images Array of image paths
     * @param string $chapter_dir Chapter directory
     * @return string|false Path to merged image or false on failure
     */
    private function merge_chapter_images($images, $chapter_dir) {
        // Use image processor to merge images
        $format = get_option('mms_default_format', 'avif');
        $merged_path = $chapter_dir . '/merged';
        
        return $this->image_processor->merge_images($images, $merged_path, $format);
    }

    /**
     * Create or update chapter post in WordPress
     *
     * @param int $chapter_id Chapter database ID
     * @param int $manga_post_id Manga WordPress post ID
     * @return int|false Chapter post ID or false on failure
     */
    private function create_or_update_chapter_post($chapter_id, $manga_post_id) {
        // Get chapter data
        $chapter = $this->db->get_row('chapters', array('id' => $chapter_id));
        
        if (!$chapter) {
            return false;
        }
        
        // Get manga data
        $manga = $this->db->get_row('manga', array('id' => $chapter->manga_id));
        
        if (!$manga) {
            return false;
        }
        
        // Check if chapter already exists in Madara
        $existing_chapter = $this->get_madara_chapter($manga_post_id, $chapter->chapter_number);
        
        if ($existing_chapter) {
            // Update existing chapter
            $wp_post_id = $existing_chapter['chapter_id'];
        } else {
            // Create new chapter
            $upload_dir = wp_upload_dir();
            $chapter_dir = $upload_dir['basedir'] . $chapter->images_path;
            $chapter_url = $upload_dir['baseurl'] . $chapter->images_path;
            
            $images = glob($chapter_dir . '/*.{jpg,jpeg,png,gif,webp,avif}', GLOB_BRACE);
            
            if (empty($images)) {
                $this->logger->error('No images found for chapter', array(
                    'chapter_id' => $chapter_id,
                    'directory' => $chapter_dir,
                ));
                
                return false;
            }
            
            // Sort images by name
            natsort($images);
            
            // Create chapter in Madara
            $result = $this->create_madara_chapter(
                $manga_post_id,
                $chapter->chapter_number,
                $chapter->title,
                $images,
                $chapter_url,
                $chapter->merged_image ? $chapter_dir . '/' . $chapter->merged_image : null
            );
            
            if (!$result) {
                $this->logger->error('Failed to create chapter in Madara', array(
                    'chapter_id' => $chapter_id,
                    'manga_post_id' => $manga_post_id,
                ));
                
                return false;
            }
            
            $wp_post_id = $result;
        }
        
        return $wp_post_id;
    }

    /**
     * Get Madara chapter
     *
     * @param int $manga_post_id Manga post ID
     * @param string $chapter_number Chapter number
     * @return array|false Chapter data or false if not found
     */
    private function get_madara_chapter($manga_post_id, $chapter_number) {
        if (!function_exists('madara_get_manga_chapters')) {
            return false;
        }
        
        $chapters = madara_get_manga_chapters($manga_post_id);
        
        if (empty($chapters)) {
            return false;
        }
        
        foreach ($chapters as $chapter) {
            if ($chapter['chapter_name'] == $chapter_number) {
                return $chapter;
            }
        }
        
        return false;
    }

    /**
     * Create Madara chapter
     *
     * @param int $manga_post_id Manga post ID
     * @param string $chapter_number Chapter number
     * @param string $chapter_title Chapter title
     * @param array $images Image paths
     * @param string $chapter_url Chapter URL
     * @param string $merged_image Merged image path
     * @return int|false Chapter post ID or false on failure
     */
    private function create_madara_chapter($manga_post_id, $chapter_number, $chapter_title, $images, $chapter_url, $merged_image = null) {
        // Check if required Madara functions exist
        if (!function_exists('wp_manga_storage_upload_action') || !class_exists('WP_MANGA_STORAGE')) {
            $this->logger->error('Required Madara functions not found');
            return false;
        }
        
        // Create chapter slug
        $chapter_slug = ScraperUtils::generate_chapter_slug($chapter_number, $chapter_title);
        
        // Format images for Madara
        $formatted_images = array();
        
        foreach ($images as $image) {
            $formatted_images[] = array(
                'src' => str_replace(ABSPATH, site_url('/'), $image),
                'file' => basename($image),
            );
        }
        
        // Create chapter
        $storage = new \WP_MANGA_STORAGE();
        $chapter_args = array(
            'post_id' => $manga_post_id,
            'chapter_name' => $chapter_number,
            'chapter_name_extend' => $chapter_title,
            'volume_id' => 0,
            'chapter_slug' => $chapter_slug,
        );
        
        // Check if we should use server or cloud storage
        $storage_type = get_option('mms_storage_type', 'local');
        
        // Local storage
        if ($storage_type === 'local') {
            $chapter_id = $storage->create_chapter($chapter_args);
            
            if (!$chapter_id) {
                $this->logger->error('Failed to create chapter');
                return false;
            }
            
            // Use merged image if available
            if ($merged_image && file_exists($merged_image) && function_exists('wp_manga_upload_single_chapter')) {
                // Single file chapter
                $result = wp_manga_upload_single_chapter($chapter_id, $manga_post_id, $merged_image);
                
                if (!$result) {
                    // Fallback to multiple images
                    $result = $storage->wp_manga_upload_action($formatted_images, $manga_post_id, $chapter_id, $storage_type);
                }
            } else {
                // Multiple images chapter
                $result = $storage->wp_manga_upload_action($formatted_images, $manga_post_id, $chapter_id, $storage_type);
            }
            
            if (!$result) {
                $this->logger->error('Failed to upload chapter images');
                
                // Delete chapter
                $storage->delete_chapter($manga_post_id, $chapter_id);
                
                return false;
            }
            
            return $chapter_id;
        } else {
            // Cloud storage - currently only local is supported
            $this->logger->error('Only local storage is supported');
            return false;
        }
    }

    /**
     * Get chapters with optional filtering
     *
     * @param array $args Query arguments
     * @return array Chapters
     */
    public function get_chapters($args = array()) {
        $defaults = array(
            'manga_id' => 0,
            'downloaded' => null,
            'processed' => null,
            'orderby' => 'chapter_number',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        
        if (!empty($args['manga_id'])) {
            $where['manga_id'] = $args['manga_id'];
        }
        
        if ($args['downloaded'] !== null) {
            $where['downloaded'] = $args['downloaded'];
        }
        
        if ($args['processed'] !== null) {
            $where['processed'] = $args['processed'];
        }
        
        return $this->db->get_results(
            'chapters',
            $where,
            $args['orderby'],
            $args['order'],
            $args['limit'],
            $args['offset']
        );
    }
}