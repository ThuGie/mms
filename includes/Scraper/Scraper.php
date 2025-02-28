<?php
namespace MadaraMangaScraper\Scraper;

/**
 * Scraper Facade class
 * 
 * This class provides a simplified interface to the scraper components.
 */
class Scraper {
    /**
     * MangaScraper instance
     *
     * @var \MadaraMangaScraper\Scraper\MangaScraper
     */
    private $manga_scraper;

    /**
     * ChapterScraper instance
     *
     * @var \MadaraMangaScraper\Scraper\ChapterScraper
     */
    private $chapter_scraper;

    /**
     * Database instance
     *
     * @var \MadaraMangaScraper\Database\Database
     */
    private $db;

    /**
     * Constructor
     *
     * @param \MadaraMangaScraper\Queue\Queue $queue
     * @param \MadaraMangaScraper\Logger\Logger $logger
     * @param \MadaraMangaScraper\Database\Database $database
     * @param \MadaraMangaScraper\ImageProcessor\ImageProcessor $image_processor
     */
    public function __construct($queue, $logger, $database, $image_processor) {
        $this->db = $database;
        $this->manga_scraper = new MangaScraper($queue, $logger, $database);
        $this->chapter_scraper = new ChapterScraper($queue, $logger, $database, $image_processor);
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
     * Scrape all manga from a source
     *
     * @param int $source_id Source ID
     * @return bool Success
     */
    public function scrape_all_manga($source_id) {
        return $this->manga_scraper->scrape_all_manga($source_id);
    }

    /**
     * Scrape a single manga
     *
     * @param string $manga_id Manga ID
     * @param int $source_id Source ID
     * @return bool Success
     */
    public function scrape_manga($manga_id, $source_id) {
        return $this->manga_scraper->scrape_manga($manga_id, $source_id);
    }

    /**
     * Scrape manga chapters
     *
     * @param string $manga_id Manga ID
     * @param int $source_id Source ID
     * @param int $manga_db_id Manga database ID
     * @return bool Success
     */
    public function scrape_manga_chapters($manga_id, $source_id, $manga_db_id) {
        return $this->chapter_scraper->scrape_manga_chapters($manga_id, $source_id, $manga_db_id);
    }

    /**
     * Scrape a single chapter
     *
     * @param string $chapter_id Chapter ID
     * @param int $source_id Source ID
     * @return bool Success
     */
    public function scrape_chapter($chapter_id, $source_id) {
        return $this->chapter_scraper->scrape_chapter($chapter_id, $source_id);
    }

    /**
     * Parse manga directory page
     *
     * @param string $html HTML content
     * @return array Manga items
     */
    public function parse_manga_directory($html) {
        return $this->manga_scraper->parse_manga_directory($html);
    }

    /**
     * Extract AJAX data for chapters
     *
     * @param string $html HTML content
     * @return array|false AJAX data or false on failure
     */
    public function extract_ajax_data($html) {
        return $this->manga_scraper->extract_ajax_data($html);
    }

    /**
     * Extract manga ID from URL
     *
     * @param string $url Manga URL
     * @return string|false Manga ID or false on failure
     */
    public function extract_manga_id_from_url($url) {
        return ScraperUtils::extract_manga_id_from_url($url);
    }

    /**
     * Extract chapter ID from URL
     *
     * @param string $url Chapter URL
     * @return string|false Chapter ID or false on failure
     */
    public function extract_chapter_id_from_url($url) {
        return ScraperUtils::extract_chapter_id_from_url($url);
    }

    /**
     * Add a new source
     *
     * @param string $name Source name
     * @param string $url Source URL
     * @return int|false Source ID or false on failure
     */
    public function add_source($name, $url) {
        return $this->manga_scraper->add_source($name, $url);
    }

    /**
     * Update a source
     *
     * @param int $source_id Source ID
     * @param array $data Source data
     * @return bool Success
     */
    public function update_source($source_id, $data) {
        return $this->manga_scraper->update_source($source_id, $data);
    }

    /**
     * Delete a source
     *
     * @param int $source_id Source ID
     * @return bool Success
     */
    public function delete_source($source_id) {
        return $this->manga_scraper->delete_source($source_id);
    }

    /**
     * Get sources
     *
     * @param array $args Query arguments
     * @return array Sources
     */
    public function get_sources($args = array()) {
        return $this->manga_scraper->get_sources($args);
    }

    /**
     * Get manga with optional filtering
     *
     * @param array $args Query arguments
     * @return array Manga
     */
    public function get_manga($args = array()) {
        return $this->manga_scraper->get_manga($args);
    }

    /**
     * Get chapters with optional filtering
     *
     * @param array $args Query arguments
     * @return array Chapters
     */
    public function get_chapters($args = array()) {
        return $this->chapter_scraper->get_chapters($args);
    }

    /**
     * Set request delay
     *
     * @param int $delay Delay in seconds
     */
    public function set_request_delay($delay) {
        $this->manga_scraper->set_request_delay($delay);
        $this->chapter_scraper->set_request_delay($delay);
    }

    /**
     * Get request delay
     *
     * @return int Delay in seconds
     */
    public function get_request_delay() {
        return $this->manga_scraper->get_request_delay();
    }
}