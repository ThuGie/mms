<?php
namespace MadaraMangaScraper\ImageProcessor;

/**
 * Image processor class
 */
class ImageProcessor {
    /**
     * Logger instance
     *
     * @var \MadaraMangaScraper\Logger\Logger
     */
    private $logger;

    /**
     * Image formats
     */
    const FORMAT_WEBP = 'webp';
    const FORMAT_AVIF = 'avif';

    /**
     * Default quality settings
     */
    private $quality_webp = 85;
    private $quality_avif = 70;

    /**
     * Default image format
     */
    private $default_format = self::FORMAT_AVIF;

    /**
     * Constructor
     *
     * @param \MadaraMangaScraper\Logger\Logger $logger
     */
    public function __construct($logger) {
        $this->logger = $logger;
        
        // Load settings
        $this->quality_webp = get_option('mms_quality_webp', 85);
        $this->quality_avif = get_option('mms_quality_avif', 70);
        $this->default_format = get_option('mms_default_format', self::FORMAT_AVIF);
        
        // Check if AVIF is supported
        if ($this->default_format === self::FORMAT_AVIF && !$this->is_avif_supported()) {
            $this->default_format = self::FORMAT_WEBP;
            update_option('mms_default_format', self::FORMAT_WEBP);
            
            $this->logger->warning('AVIF format not supported, falling back to WebP');
        }
    }

    /**
     * Check if AVIF is supported
     *
     * @return bool Whether AVIF is supported
     */
    public function is_avif_supported() {
        // Check PHP version (AVIF support was added in PHP 8.1)
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            return false;
        }
        
        // Check if GD or Imagick supports AVIF
        if (function_exists('imageavif')) {
            return true;
        }
        
        if (extension_loaded('imagick')) {
            $formats = \Imagick::queryFormats();
            return in_array('AVIF', $formats);
        }
        
        return false;
    }

    /**
     * Check if WebP is supported
     *
     * @return bool Whether WebP is supported
     */
    public function is_webp_supported() {
        // Check if GD or Imagick supports WebP
        if (function_exists('imagewebp')) {
            return true;
        }
        
        if (extension_loaded('imagick')) {
            $formats = \Imagick::queryFormats();
            return in_array('WEBP', $formats);
        }
        
        return false;
    }

    /**
     * Merge images into a single file
     *
     * @param array $images Array of image paths
     * @param string $output_path Output path (without extension)
     * @param string $format Image format (webp or avif)
     * @return string|false Output path or false on failure
     */
    public function merge_images($images, $output_path, $format = null) {
        if (empty($images)) {
            $this->logger->error('No images to merge');
            return false;
        }
        
        $this->logger->info('Merging images', array(
            'count' => count($images),
            'output' => $output_path,
        ));
        
        // Use default format if not specified
        if ($format === null) {
            $format = $this->default_format;
        }
        
        // Check if format is supported
        if ($format === self::FORMAT_AVIF && !$this->is_avif_supported()) {
            $this->logger->warning('AVIF format not supported, falling back to WebP');
            $format = self::FORMAT_WEBP;
        }
        
        if ($format === self::FORMAT_WEBP && !$this->is_webp_supported()) {
            $this->logger->error('WebP format not supported');
            return false;
        }
        
        try {
            // Determine total dimensions
            $total_width = 0;
            $total_height = 0;
            $image_details = array();
            
            foreach ($images as $image_path) {
                // Get image size
                $size = getimagesize($image_path);
                
                if (!$size) {
                    $this->logger->warning('Failed to get image size', array('path' => $image_path));
                    continue;
                }
                
                $width = $size[0];
                $height = $size[1];
                $mime = $size['mime'];
                
                $image_details[] = array(
                    'path' => $image_path,
                    'width' => $width,
                    'height' => $height,
                    'mime' => $mime,
                );
                
                // Update total dimensions
                $total_width = max($total_width, $width);
                $total_height += $height;
            }
            
            if (empty($image_details)) {
                $this->logger->error('No valid images to merge');
                return false;
            }
            
            // Create destination image (use GD or Imagick based on availability)
            $use_imagick = extension_loaded('imagick');
            
            if ($use_imagick) {
                return $this->merge_images_imagick($image_details, $output_path, $format, $total_width, $total_height);
            } else {
                return $this->merge_images_gd($image_details, $output_path, $format, $total_width, $total_height);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error merging images', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));
            
            return false;
        }
    }

    /**
     * Merge images using GD
     *
     * @param array $image_details Image details
     * @param string $output_path Output path
     * @param string $format Image format
     * @param int $total_width Total width
     * @param int $total_height Total height
     * @return string|false Output path or false on failure
     */
    private function merge_images_gd($image_details, $output_path, $format, $total_width, $total_height) {
        // Create destination image
        $dest = imagecreatetruecolor($total_width, $total_height);
        
        // Set background color to white
        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);
        
        // Merge images
        $current_height = 0;
        
        foreach ($image_details as $image) {
            // Load source image
            $src = $this->load_image_gd($image['path'], $image['mime']);
            
            if (!$src) {
                continue;
            }
            
            // Calculate position (center horizontally)
            $x = ($total_width - $image['width']) / 2;
            
            // Copy image
            imagecopy($dest, $src, $x, $current_height, 0, 0, $image['width'], $image['height']);
            
            // Update current height
            $current_height += $image['height'];
            
            // Free memory
            imagedestroy($src);
        }
        
        // Save image with appropriate format
        $output_file = $output_path . '.' . $format;
        $result = false;
        
        if ($format === self::FORMAT_WEBP) {
            $result = imagewebp($dest, $output_file, $this->quality_webp);
        } elseif ($format === self::FORMAT_AVIF && function_exists('imageavif')) {
            $result = imageavif($dest, $output_file, $this->quality_avif);
        }
        
        // Free memory
        imagedestroy($dest);
        
        if (!$result) {
            $this->logger->error('Failed to save merged image', array('path' => $output_file));
            return false;
        }
        
        return $output_file;
    }

    /**
     * Merge images using Imagick
     *
     * @param array $image_details Image details
     * @param string $output_path Output path
     * @param string $format Image format
     * @param int $total_width Total width
     * @param int $total_height Total height
     * @return string|false Output path or false on failure
     */
    private function merge_images_imagick($image_details, $output_path, $format, $total_width, $total_height) {
        // Create destination image
        $dest = new \Imagick();
        $dest->newImage($total_width, $total_height, new \ImagickPixel('white'));
        $dest->setImageFormat($format);
        
        // Set quality
        if ($format === self::FORMAT_WEBP) {
            $dest->setImageCompressionQuality($this->quality_webp);
        } elseif ($format === self::FORMAT_AVIF) {
            $dest->setImageCompressionQuality($this->quality_avif);
        }
        
        // Merge images
        $current_height = 0;
        
        foreach ($image_details as $image) {
            try {
                // Load source image
                $src = new \Imagick($image['path']);
                
                // Calculate position (center horizontally)
                $x = ($total_width - $image['width']) / 2;
                
                // Composite image
                $dest->compositeImage($src, \Imagick::COMPOSITE_OVER, $x, $current_height);
                
                // Update current height
                $current_height += $image['height'];
                
                // Free memory
                $src->clear();
                $src->destroy();
            } catch (\Exception $e) {
                $this->logger->warning('Failed to process image with Imagick', array(
                    'path' => $image['path'],
                    'error' => $e->getMessage(),
                ));
            }
        }
        
        // Save image
        $output_file = $output_path . '.' . $format;
        
        try {
            $result = $dest->writeImage($output_file);
            
            // Free memory
            $dest->clear();
            $dest->destroy();
            
            if (!$result) {
                $this->logger->error('Failed to save merged image with Imagick', array('path' => $output_file));
                return false;
            }
            
            return $output_file;
        } catch (\Exception $e) {
            $this->logger->error('Error saving merged image with Imagick', array(
                'path' => $output_file,
                'error' => $e->getMessage(),
            ));
            
            // Free memory
            $dest->clear();
            $dest->destroy();
            
            return false;
        }
    }

    /**
     * Load image with GD
     *
     * @param string $path Image path
     * @param string $mime MIME type
     * @return resource|false GD image resource or false on failure
     */
    private function load_image_gd($path, $mime) {
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($path);
                }
                break;
            case 'image/avif':
                if (function_exists('imagecreatefromavif')) {
                    return imagecreatefromavif($path);
                }
                break;
        }
        
        return false;
    }

    /**
     * Convert image to specified format
     *
     * @param string $input_path Input image path
     * @param string $output_path Output path (without extension)
     * @param string $format Output format (webp or avif)
     * @return string|false Output path or false on failure
     */
    public function convert_image($input_path, $output_path, $format = null) {
        if (!file_exists($input_path)) {
            $this->logger->error('Input image does not exist', array('path' => $input_path));
            return false;
        }
        
        // Use default format if not specified
        if ($format === null) {
            $format = $this->default_format;
        }
        
        // Check if format is supported
        if ($format === self::FORMAT_AVIF && !$this->is_avif_supported()) {
            $this->logger->warning('AVIF format not supported, falling back to WebP');
            $format = self::FORMAT_WEBP;
        }
        
        if ($format === self::FORMAT_WEBP && !$this->is_webp_supported()) {
            $this->logger->error('WebP format not supported');
            return false;
        }
        
        try {
            // Get image details
            $size = getimagesize($input_path);
            
            if (!$size) {
                $this->logger->error('Failed to get image size', array('path' => $input_path));
                return false;
            }
            
            $mime = $size['mime'];
            
            // Use Imagick or GD based on availability
            if (extension_loaded('imagick')) {
                return $this->convert_image_imagick($input_path, $output_path, $format);
            } else {
                return $this->convert_image_gd($input_path, $output_path, $format, $mime);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error converting image', array(
                'input' => $input_path,
                'output' => $output_path,
                'format' => $format,
                'error' => $e->getMessage(),
            ));
            
            return false;
        }
    }

    /**
     * Convert image using GD
     *
     * @param string $input_path Input image path
     * @param string $output_path Output path
     * @param string $format Output format
     * @param string $mime Input image MIME type
     * @return string|false Output path or false on failure
     */
    private function convert_image_gd($input_path, $output_path, $format, $mime) {
        // Load source image
        $src = $this->load_image_gd($input_path, $mime);
        
        if (!$src) {
            $this->logger->error('Failed to load image with GD', array('path' => $input_path));
            return false;
        }
        
        // Get image dimensions
        $width = imagesx($src);
        $height = imagesy($src);
        
        // Create destination image
        $dest = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        if ($mime === 'image/png') {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
            imagefilledrectangle($dest, 0, 0, $width, $height, $transparent);
        }
        
        // Copy image
        imagecopy($dest, $src, 0, 0, 0, 0, $width, $height);
        
        // Free memory
        imagedestroy($src);
        
        // Save image with appropriate format
        $output_file = $output_path . '.' . $format;
        $result = false;
        
        if ($format === self::FORMAT_WEBP) {
            $result = imagewebp($dest, $output_file, $this->quality_webp);
        } elseif ($format === self::FORMAT_AVIF && function_exists('imageavif')) {
            $result = imageavif($dest, $output_file, $this->quality_avif);
        }
        
        // Free memory
        imagedestroy($dest);
        
        if (!$result) {
            $this->logger->error('Failed to save converted image', array('path' => $output_file));
            return false;
        }
        
        return $output_file;
    }

    /**
     * Convert image using Imagick
     *
     * @param string $input_path Input image path
     * @param string $output_path Output path
     * @param string $format Output format
     * @return string|false Output path or false on failure
     */
    private function convert_image_imagick($input_path, $output_path, $format) {
        try {
            // Load source image
            $image = new \Imagick($input_path);
            
            // Set format and quality
            $image->setImageFormat($format);
            
            if ($format === self::FORMAT_WEBP) {
                $image->setImageCompressionQuality($this->quality_webp);
            } elseif ($format === self::FORMAT_AVIF) {
                $image->setImageCompressionQuality($this->quality_avif);
            }
            
            // Save image
            $output_file = $output_path . '.' . $format;
            $result = $image->writeImage($output_file);
            
            // Free memory
            $image->clear();
            $image->destroy();
            
            if (!$result) {
                $this->logger->error('Failed to save converted image with Imagick', array('path' => $output_file));
                return false;
            }
            
            return $output_file;
        } catch (\Exception $e) {
            $this->logger->error('Error converting image with Imagick', array(
                'input' => $input_path,
                'output' => $output_path,
                'format' => $format,
                'error' => $e->getMessage(),
            ));
            
            return false;
        }
    }

    /**
     * Set quality settings
     *
     * @param int $webp WebP quality (0-100)
     * @param int $avif AVIF quality (0-100)
     */
    public function set_quality($webp, $avif) {
        $this->quality_webp = max(0, min(100, intval($webp)));
        $this->quality_avif = max(0, min(100, intval($avif)));
        
        update_option('mms_quality_webp', $this->quality_webp);
        update_option('mms_quality_avif', $this->quality_avif);
    }

    /**
     * Set default format
     *
     * @param string $format Format (webp or avif)
     * @return bool Success
     */
    public function set_default_format($format) {
        if ($format === self::FORMAT_AVIF && !$this->is_avif_supported()) {
            $this->logger->warning('AVIF format not supported, cannot set as default');
            return false;
        }
        
        if ($format === self::FORMAT_WEBP && !$this->is_webp_supported()) {
            $this->logger->warning('WebP format not supported, cannot set as default');
            return false;
        }
        
        if (!in_array($format, array(self::FORMAT_WEBP, self::FORMAT_AVIF))) {
            $this->logger->warning('Invalid format', array('format' => $format));
            return false;
        }
        
        $this->default_format = $format;
        update_option('mms_default_format', $format);
        
        return true;
    }

    /**
     * Get quality settings
     *
     * @return array Quality settings
     */
    public function get_quality() {
        return array(
            'webp' => $this->quality_webp,
            'avif' => $this->quality_avif,
        );
    }

    /**
     * Get default format
     *
     * @return string Default format
     */
    public function get_default_format() {
        return $this->default_format;
    }

    /**
     * Get supported formats
     *
     * @return array Supported formats
     */
    public function get_supported_formats() {
        $formats = array();
        
        if ($this->is_webp_supported()) {
            $formats[] = self::FORMAT_WEBP;
        }
        
        if ($this->is_avif_supported()) {
            $formats[] = self::FORMAT_AVIF;
        }
        
        return $formats;
    }
}
