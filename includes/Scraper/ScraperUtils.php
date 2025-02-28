<?php
namespace MadaraMangaScraper\Scraper;

/**
 * Scraper Utilities class
 */
class ScraperUtils {
    /**
     * Extract manga ID from URL
     *
     * @param string $url Manga URL
     * @return string|false Manga ID or false on failure
     */
    public static function extract_manga_id_from_url($url) {
        // Typical Madara URL structure: /manga/manga-slug/
        if (preg_match('#/manga/([^/]+)/?#', $url, $matches)) {
            return $matches[1];
        }
        
        return false;
    }

    /**
     * Extract chapter ID from URL
     *
     * @param string $url Chapter URL
     * @return string|false Chapter ID or false on failure
     */
    public static function extract_chapter_id_from_url($url) {
        // Typical Madara URL structure: /manga/manga-slug/chapter-X/
        if (preg_match('#/manga/[^/]+/([^/]+)/?#', $url, $matches)) {
            return $matches[1];
        }
        
        return false;
    }

    /**
     * Get manga URL
     *
     * @param string $source_url Source URL
     * @param string $manga_id Manga ID
     * @return string Manga URL
     */
    public static function get_manga_url($source_url, $manga_id) {
        return trailingslashit($source_url) . 'manga/' . $manga_id . '/';
    }

    /**
     * Get chapter URL
     *
     * @param string $source_url Source URL
     * @param string $manga_id Manga ID
     * @param string $chapter_id Chapter ID
     * @return string Chapter URL
     */
    public static function get_chapter_url($source_url, $manga_id, $chapter_id) {
        return trailingslashit($source_url) . 'manga/' . $manga_id . '/' . $chapter_id . '/';
    }

    /**
     * Set manga terms (taxonomy)
     *
     * @param int $post_id Post ID
     * @param array $terms Terms
     * @param string $taxonomy Taxonomy
     * @return bool Success
     */
    public static function set_manga_terms($post_id, $terms, $taxonomy) {
        $term_ids = array();
        
        foreach ($terms as $term_name) {
            $term_name = trim($term_name);
            
            if (empty($term_name)) {
                continue;
            }
            
            // Get or create term
            $term = get_term_by('name', $term_name, $taxonomy);
            
            if (!$term) {
                $term = wp_insert_term($term_name, $taxonomy);
                
                if (is_wp_error($term)) {
                    continue;
                }
                
                $term_id = $term['term_id'];
            } else {
                $term_id = $term->term_id;
            }
            
            $term_ids[] = $term_id;
        }
        
        if (empty($term_ids)) {
            return false;
        }
        
        // Set terms
        wp_set_object_terms($post_id, $term_ids, $taxonomy);
        
        return true;
    }

    /**
     * Set featured image
     *
     * @param int $post_id Post ID
     * @param string $image_data Image data
     * @param string $title Image title
     * @return bool Success
     */
    public static function set_featured_image($post_id, $image_data, $title) {
        // Check if post already has a featured image
        if (has_post_thumbnail($post_id)) {
            return true;
        }
        
        if (!$image_data) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $filename = sanitize_file_name($title . '.jpg');
        
        // Generate unique filename
        $filename = wp_unique_filename($upload_dir['path'], $filename);
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Save image
        file_put_contents($file_path, $image_data);
        
        // Check image file type
        $filetype = wp_check_filetype($filename);
        
        if (!$filetype['type']) {
            return false;
        }
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name($title),
            'post_content' => '',
            'post_status' => 'inherit',
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);
        
        if (is_wp_error($attachment_id)) {
            return false;
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);
        
        return true;
    }

    /**
     * Map manga status to Madara status terms
     *
     * @param string $status Status from source
     * @return string Mapped status
     */
    public static function map_manga_status($status) {
        $status_map = array(
            'ongoing' => 'on-going',
            'completed' => 'completed',
            'on-going' => 'on-going',
            'on going' => 'on-going',
            'dropped' => 'canceled',
            'canceled' => 'canceled',
            'hiatus' => 'on-hold',
            'on-hold' => 'on-hold',
            'on hold' => 'on-hold',
        );
        
        $status = strtolower($status);
        return isset($status_map[$status]) ? $status_map[$status] : 'on-going';
    }

    /**
     * Clean HTML content
     *
     * @param string $html HTML content
     * @return string Cleaned HTML
     */
    public static function clean_html($html) {
        // Remove excessive whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        
        // Remove script and style tags
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        
        return trim($html);
    }
    
    /**
     * Sanitize file path for safe use in file operations
     *
     * @param string $path File path
     * @return string Sanitized path
     */
    public static function sanitize_path($path) {
        // Remove any characters that could be problematic in file paths
        $path = preg_replace('/[^\w\s\d\.\-_\/\\\\]/', '', $path);
        
        // Remove any double slashes
        $path = preg_replace('#/+#', '/', $path);
        
        return $path;
    }
    
    /**
     * Get file extension from URL
     *
     * @param string $url Image URL
     * @return string File extension
     */
    public static function get_file_extension_from_url($url) {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        
        // Default to jpg if no extension found
        if (empty($extension)) {
            $extension = 'jpg';
        }
        
        return strtolower($extension);
    }
    
    /**
     * Generate a chapter number label
     *
     * @param string $chapter_number Chapter number
     * @param string $chapter_title Chapter title (optional)
     * @return string Chapter label
     */
    public static function generate_chapter_label($chapter_number, $chapter_title = '') {
        $label = sprintf(__('Chapter %s', 'madara-manga-scraper'), $chapter_number);
        
        if (!empty($chapter_title)) {
            $label .= ': ' . $chapter_title;
        }
        
        return $label;
    }
    
    /**
     * Generate a chapter slug
     *
     * @param string $chapter_number Chapter number
     * @param string $chapter_title Chapter title (optional)
     * @return string Chapter slug
     */
    public static function generate_chapter_slug($chapter_number, $chapter_title = '') {
        $slug = 'chapter-' . $chapter_number;
        
        if (!empty($chapter_title)) {
            $slug .= '-' . sanitize_title($chapter_title);
        }
        
        return $slug;
    }
}