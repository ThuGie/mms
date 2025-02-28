<?php
namespace MadaraMangaScraper\Scraper;

/**
 * Manga Scraper class
 */
class MangaScraper extends BaseScraper {
    /**
     * Scrape all manga from a source
     *
     * @param int $source_id Source ID
     * @return bool Success
     */
    public function scrape_all_manga($source_id) {
        $this->logger->info('Scraping all manga', array('source_id' => $source_id));
        
        // Get source
        $source = $this->db->get_row('sources', array('id' => $source_id));
        
        if (!$source) {
            $this->logger->error('Source not found', array('source_id' => $source_id));
            return false;
        }
        
        try {
            // Update source last checked
            $this->db->update(
                'sources',
                array('last_checked' => current_time('mysql')),
                array('id' => $source_id)
            );
            
            // Get manga directory page
            $page = 1;
            $has_next_page = true;
            $manga_count = 0;
            $consecutive_empty_pages = 0;
            $max_pages = get_option('mms_max_pages', 100); // Prevent infinite loops
            
            while ($has_next_page && $page <= $max_pages) {
                $this->logger->info('Scraping manga directory page', array(
                    'source_id' => $source_id,
                    'page' => $page,
                ));
                
                // Get manga list URL (typical Madara structure)
                $url = trailingslashit($source->url) . 'manga/page/' . $page . '/';
                
                // Get page content
                $response = $this->make_request($url);
                
                if (is_wp_error($response)) {
                    $this->logger->error('Failed to get manga directory page', array(
                        'url' => $url,
                        'error' => $response->get_error_message(),
                    ));
                    
                    // Check if we've already tried too many pages without success
                    if (++$consecutive_empty_pages >= 3) {
                        $this->logger->warning('Too many consecutive empty pages, stopping', array(
                            'page' => $page,
                            'consecutive_empty' => $consecutive_empty_pages,
                        ));
                        break;
                    }
                    
                    // Try next page
                    $page++;
                    continue;
                }
                
                $body = wp_remote_retrieve_body($response);
                
                // Parse manga items
                $manga_items = $this->parse_manga_directory($body);
                
                if (empty($manga_items)) {
                    $this->logger->info('No manga found on page', array('page' => $page));
                    
                    // Check if we've already tried too many pages without finding manga
                    if (++$consecutive_empty_pages >= 3) {
                        $this->logger->warning('Too many consecutive empty pages, stopping', array(
                            'page' => $page,
                            'consecutive_empty' => $consecutive_empty_pages,
                        ));
                        break;
                    }
                    
                    // But we might still want to check the next page
                    $page++;
                    continue;
                }
                
                // Reset the consecutive empty pages counter since we found manga
                $consecutive_empty_pages = 0;
                
                $this->logger->info('Found manga on page', array(
                    'page' => $page,
                    'count' => count($manga_items),
                ));
                
                // Add manga to queue
                foreach ($manga_items as $manga) {
                    // Extract manga ID from URL
                    $manga_id = ScraperUtils::extract_manga_id_from_url($manga['url']);
                    
                    if ($manga_id) {
                        $this->queue->add_manga($manga_id, $source_id);
                        $manga_count++;
                    }
                }
                
                // Check if there's a next page
                $has_next_page = $this->has_next_page($body);
                
                $this->logger->info('Pagination check result', array(
                    'page' => $page,
                    'has_next_page' => $has_next_page ? 'Yes' : 'No',
                ));
                
                // Increment page
                $page++;
                
                // Delay between requests
                sleep($this->request_delay);
            }
            
            $this->logger->info('Finished scraping all manga', array(
                'source_id' => $source_id,
                'manga_count' => $manga_count,
                'pages_checked' => $page - 1,
            ));
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error scraping all manga', array(
                'source_id' => $source_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));
            
            return false;
        }
    }

    /**
     * Scrape a single manga
     *
     * @param string $manga_id Manga ID
     * @param int $source_id Source ID
     * @return bool Success
     */
    public function scrape_manga($manga_id, $source_id) {
        $this->logger->info('Scraping manga', array(
            'manga_id' => $manga_id,
            'source_id' => $source_id,
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
            
            // Get manga page content
            $response = $this->make_request($manga_url);
            
            if (is_wp_error($response)) {
                $this->logger->error('Failed to get manga page', array(
                    'url' => $manga_url,
                    'error' => $response->get_error_message(),
                ));
                
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            
            // Parse manga details
            $manga_data = $this->parse_manga_details($body, $manga_url);
            
            if (!$manga_data) {
                $this->logger->error('Failed to parse manga details', array(
                    'url' => $manga_url,
                ));
                
                return false;
            }
            
            // Add manga to database
            $existing_manga = $this->db->get_row('manga', array(
                'source_id' => $source_id,
                'source_manga_id' => $manga_id,
            ));
            
            if ($existing_manga) {
                // Update existing manga
                $this->db->update(
                    'manga',
                    array(
                        'title' => $manga_data['title'],
                        'description' => $manga_data['description'],
                        'cover_url' => $manga_data['cover_url'],
                        'status' => $manga_data['status'],
                        'genres' => $manga_data['genres'],
                        'authors' => $manga_data['authors'],
                        'artists' => $manga_data['artists'],
                    ),
                    array('id' => $existing_manga->id)
                );
                
                $manga_db_id = $existing_manga->id;
                $wp_post_id = $existing_manga->wp_post_id;
            } else {
                // Insert new manga
                $this->db->insert(
                    'manga',
                    array(
                        'source_id' => $source_id,
                        'source_manga_id' => $manga_id,
                        'title' => $manga_data['title'],
                        'slug' => sanitize_title($manga_data['title']),
                        'description' => $manga_data['description'],
                        'cover_url' => $manga_data['cover_url'],
                        'status' => $manga_data['status'],
                        'genres' => $manga_data['genres'],
                        'authors' => $manga_data['authors'],
                        'artists' => $manga_data['artists'],
                    )
                );
                
                $manga_db_id = $this->db->get_row('manga', array(
                    'source_id' => $source_id,
                    'source_manga_id' => $manga_id,
                ))->id;
                
                $wp_post_id = null;
            }
            
            // Queue chapters for scraping
            $this->queue_manga_chapters($manga_id, $source_id, $manga_db_id, $source, $body);
            
            // Check for already downloaded chapters
            $has_chapters = $this->db->count('chapters', array(
                'manga_id' => $manga_db_id,
                'downloaded' => 1,
            )) > 0;
            
            // Create or update WordPress post if at least one chapter is downloaded
            if ($has_chapters) {
                $wp_post_id = $this->create_or_update_manga_post($manga_db_id, $wp_post_id, $manga_data);
                
                if ($wp_post_id) {
                    // Update manga with post ID
                    $this->db->update(
                        'manga',
                        array('wp_post_id' => $wp_post_id),
                        array('id' => $manga_db_id)
                    );
                }
            }
            
            $this->logger->info('Finished scraping manga', array(
                'manga_id' => $manga_id,
                'source_id' => $source_id,
                'wp_post_id' => $wp_post_id,
            ));
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error scraping manga', array(
                'manga_id' => $manga_id,
                'source_id' => $source_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));
            
            return false;
        }
    }

    /**
     * Parse manga directory page
     *
     * @param string $html HTML content
     * @return array Manga items
     */
    public function parse_manga_directory($html) {
        $manga_items = array();
        
        // Use DOMDocument to parse HTML
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        
        // Madara theme typically uses .c-tabs-item__content for manga items in directory
        $manga_nodes = $xpath->query('//div[contains(@class, "c-tabs-item__content")]');
        
        if (!$manga_nodes || $manga_nodes->length === 0) {
            // Try alternative class for some Madara themes
            $manga_nodes = $xpath->query('//div[contains(@class, "page-item-detail")]');
        }
        
        if (!$manga_nodes || $manga_nodes->length === 0) {
            return $manga_items;
        }
        
        foreach ($manga_nodes as $manga_node) {
            // Get title and URL
            $title_node = $xpath->query('.//h3[contains(@class, "h4")]/a', $manga_node)->item(0);
            
            if (!$title_node) {
                // Try alternative title selector
                $title_node = $xpath->query('.//h3/a', $manga_node)->item(0);
            }
            
            if (!$title_node) {
                continue;
            }
            
            $title = trim($title_node->textContent);
            $url = $title_node->getAttribute('href');
            
            $manga_items[] = array(
                'title' => $title,
                'url' => $url,
            );
        }
        
        return $manga_items;
    }

    /**
     * Parse manga details
     *
     * @param string $html HTML content
     * @param string $url Manga URL
     * @return array|false Manga data or false on failure
     */
    public function parse_manga_details($html, $url) {
        // Use DOMDocument to parse HTML
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        
        // Get title
        $title_node = $xpath->query('//div[contains(@class, "post-title")]/h1')->item(0);
        
        if (!$title_node) {
            return false;
        }
        
        $title = trim($title_node->textContent);
        
        // Get cover image
        $cover_node = $xpath->query('//div[contains(@class, "summary_image")]//img')->item(0);
        $cover_url = $cover_node ? $cover_node->getAttribute('src') : '';
        
        // Get alternative cover source if needed
        if (empty($cover_url) && $cover_node) {
            $cover_url = $cover_node->getAttribute('data-src');
        }
        
        if (empty($cover_url) && $cover_node) {
            $cover_url = $cover_node->getAttribute('data-lazy-src');
        }
        
        // Get description
        $description_node = $xpath->query('//div[contains(@class, "description-summary")]//div[contains(@class, "summary__content")]')->item(0);
        $description = $description_node ? trim($description_node->textContent) : '';
        
        // Clean up description
        $description = ScraperUtils::clean_html($description);
        
        // Get status
        $status = '';
        $status_nodes = $xpath->query('//div[contains(@class, "post-status")]//div[contains(@class, "summary-heading")]');
        
        foreach ($status_nodes as $node) {
            if (stripos($node->textContent, 'status') !== false) {
                $status_value_node = $xpath->query('.//following-sibling::div[contains(@class, "summary-content")]', $node)->item(0);
                $status = $status_value_node ? trim($status_value_node->textContent) : '';
                break;
            }
        }
        
        // Get genres
        $genres = array();
        $genre_nodes = $xpath->query('//div[contains(@class, "genres-content")]//a');
        
        foreach ($genre_nodes as $node) {
            $genres[] = trim($node->textContent);
        }
        
        // Get authors
        $authors = array();
        $author_nodes = $xpath->query('//div[contains(@class, "author-content")]//a');
        
        foreach ($author_nodes as $node) {
            $authors[] = trim($node->textContent);
        }
        
        // Get artists
        $artists = array();
        $artist_nodes = $xpath->query('//div[contains(@class, "artist-content")]//a');
        
        foreach ($artist_nodes as $node) {
            $artists[] = trim($node->textContent);
        }
        
        return array(
            'title' => $title,
            'cover_url' => $cover_url,
            'description' => $description,
            'status' => $status,
            'genres' => implode(',', $genres),
            'authors' => implode(',', $authors),
            'artists' => implode(',', $artists),
        );
    }

    /**
     * Queue manga chapters for scraping
     *
     * @param string $manga_id Manga ID
     * @param int $source_id Source ID
     * @param int $manga_db_id Manga database ID
     * @param object $source Source object
     * @param string $body HTML body of manga page
     * @return bool Success
     */
    private function queue_manga_chapters($manga_id, $source_id, $manga_db_id, $source, $body) {
        // Extract AJAX data for chapters
        $ajax_data = $this->extract_ajax_data($body);
        
        if (!$ajax_data) {
            $this->logger->error('Failed to extract AJAX data for chapters', array(
                'manga_id' => $manga_id,
                'source_id' => $source_id,
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
        
        $this->logger->info('Queued manga chapters for scraping', array(
            'manga_id' => $manga_id,
            'source_id' => $source_id,
            'chapter_count' => $chapter_count,
        ));
        
        return true;
    }

    /**
     * Create or update manga post in WordPress
     *
     * @param int $manga_id Manga database ID
     * @param int $wp_post_id WordPress post ID (if updating)
     * @param array $manga_data Manga data
     * @return int|false WordPress post ID or false on failure
     */
    private function create_or_update_manga_post($manga_id, $wp_post_id = null, $manga_data = null) {
        // Get manga data if not provided
        if (!$manga_data) {
            $manga = $this->db->get_row('manga', array('id' => $manga_id));
            
            if (!$manga) {
                return false;
            }
            
            $manga_data = array(
                'title' => $manga->title,
                'description' => $manga->description,
                'cover_url' => $manga->cover_url,
                'status' => $manga->status,
                'genres' => $manga->genres,
                'authors' => $manga->authors,
                'artists' => $manga->artists,
            );
        } else {
            $manga = $this->db->get_row('manga', array('id' => $manga_id));
        }
        
        // Prepare post data
        $post_data = array(
            'post_title' => $manga_data['title'],
            'post_content' => $manga_data['description'],
            'post_status' => 'publish',
            'post_type' => 'wp-manga',
            'post_name' => $manga->slug,
        );
        
        // Create or update post
        if ($wp_post_id) {
            $post_data['ID'] = $wp_post_id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id)) {
            $this->logger->error('Failed to create/update manga post', array(
                'manga_id' => $manga_id,
                'error' => $post_id->get_error_message(),
            ));
            
            return false;
        }
        
        // Set featured image
        if (!empty($manga_data['cover_url'])) {
            // Download cover image
            $cover_image = $this->download_image($manga_data['cover_url']);
            ScraperUtils::set_featured_image($post_id, $cover_image, $manga_data['title']);
        }
        
        // Set taxonomies
        if (!empty($manga_data['genres'])) {
            $genres = explode(',', $manga_data['genres']);
            ScraperUtils::set_manga_terms($post_id, $genres, 'wp-manga-genre');
        }
        
        if (!empty($manga_data['authors'])) {
            $authors = explode(',', $manga_data['authors']);
            ScraperUtils::set_manga_terms($post_id, $authors, 'wp-manga-author');
        }
        
        if (!empty($manga_data['artists'])) {
            $artists = explode(',', $manga_data['artists']);
            ScraperUtils::set_manga_terms($post_id, $artists, 'wp-manga-artist');
        }
        
        // Set manga status
        if (!empty($manga_data['status'])) {
            $mapped_status = ScraperUtils::map_manga_status($manga_data['status']);
            ScraperUtils::set_manga_terms($post_id, array($mapped_status), 'wp-manga-status');
        }
        
        // Set manga meta
        update_post_meta($post_id, '_manga_import_source', 'madara-manga-scraper');
        
        return $post_id;
    }

    /**
     * Get manga with optional filtering
     *
     * @param array $args Query arguments
     * @return array Manga
     */
    public function get_manga($args = array()) {
        $defaults = array(
            'source_id' => 0,
            'orderby' => 'title',
            'order' => 'ASC',
            'limit' => 50,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        
        if (!empty($args['source_id'])) {
            $where['source_id'] = $args['source_id'];
        }
        
        return $this->db->get_results(
            'manga',
            $where,
            $args['orderby'],
            $args['order'],
            $args['limit'],
            $args['offset']
        );
    }
}