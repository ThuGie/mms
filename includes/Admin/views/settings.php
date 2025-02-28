<?php
/**
 * Settings view
 * 
 * @package Madara Manga Scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Display settings errors
settings_errors('mms_settings');
?>
<div class="wrap mms-settings">
    <h1><?php _e('Manga Scraper Settings', 'madara-manga-scraper'); ?></h1>
    
    <div class="nav-tab-wrapper">
        <a href="#general-tab" class="nav-tab nav-tab-active"><?php _e('General', 'madara-manga-scraper'); ?></a>
        <a href="#image-tab" class="nav-tab"><?php _e('Image Processing', 'madara-manga-scraper'); ?></a>
        <a href="#schedule-tab" class="nav-tab"><?php _e('Scheduler', 'madara-manga-scraper'); ?></a>
    </div>
    
    <div id="general-tab" class="mms-tab-content mms-tab-active">
        <form method="post" action="">
            <?php wp_nonce_field('mms_settings_form', 'mms_nonce'); ?>
            <input type="hidden" name="mms_settings_action" value="general">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="request_delay"><?php _e('Request Delay', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="request_delay" id="request_delay" class="small-text" min="1" value="<?php echo intval($general_settings['request_delay']); ?>">
                        <p class="description"><?php _e('Delay between requests in seconds. Higher values reduce load on source sites but slow down scraping.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="merge_images"><?php _e('Merge Images', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="merge_images" id="merge_images" value="1" <?php checked($general_settings['merge_images']); ?>>
                            <?php _e('Merge chapter images into a single file', 'madara-manga-scraper'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, all images in a chapter will be merged into a single file for easier reading.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="storage_type"><?php _e('Storage Type', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <select name="storage_type" id="storage_type">
                            <option value="local" <?php selected($general_settings['storage_type'], 'local'); ?>><?php _e('Local Server', 'madara-manga-scraper'); ?></option>
                        </select>
                        <p class="description"><?php _e('Storage type for manga images. Currently only local server storage is supported.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="debug_mode"><?php _e('Debug Mode', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="debug_mode" id="debug_mode" value="1" <?php checked($general_settings['debug_mode']); ?>>
                            <?php _e('Enable debug mode', 'madara-manga-scraper'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, additional debug information will be logged.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="file_logging"><?php _e('File Logging', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="file_logging" id="file_logging" value="1" <?php checked($general_settings['file_logging']); ?>>
                            <?php _e('Enable file logging', 'madara-manga-scraper'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, logs will also be written to files in the wp-content/madara-manga-scraper-logs directory.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save General Settings', 'madara-manga-scraper'); ?>">
            </p>
        </form>
    </div>
    
    <div id="image-tab" class="mms-tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('mms_settings_form', 'mms_nonce'); ?>
            <input type="hidden" name="mms_settings_action" value="image">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="default_format"><?php _e('Default Format', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <select name="default_format" id="default_format">
                            <?php foreach ($image_settings['supported_formats'] as $format) : ?>
                                <option value="<?php echo esc_attr($format); ?>" <?php selected($image_settings['default_format'], $format); ?>><?php echo strtoupper(esc_html($format)); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Default image format for merged chapter images.', 'madara-manga-scraper'); ?>
                            <?php if (!in_array('avif', $image_settings['supported_formats'])) : ?>
                                <br><span class="mms-notice"><?php _e('AVIF format is not supported on this server. PHP 8.1+ is required for AVIF support.', 'madara-manga-scraper'); ?></span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="quality_webp"><?php _e('WebP Quality', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="quality_webp" id="quality_webp" class="small-text" min="1" max="100" value="<?php echo intval($image_settings['quality']['webp']); ?>">
                        <p class="description"><?php _e('Quality for WebP images (1-100). Higher values result in better quality but larger file sizes.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <?php if (in_array('avif', $image_settings['supported_formats'])) : ?>
                    <tr>
                        <th scope="row">
                            <label for="quality_avif"><?php _e('AVIF Quality', 'madara-manga-scraper'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="quality_avif" id="quality_avif" class="small-text" min="1" max="100" value="<?php echo intval($image_settings['quality']['avif']); ?>">
                            <p class="description"><?php _e('Quality for AVIF images (1-100). Higher values result in better quality but larger file sizes.', 'madara-manga-scraper'); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Image Settings', 'madara-manga-scraper'); ?>">
            </p>
        </form>
    </div>
    
    <div id="schedule-tab" class="mms-tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('mms_settings_form', 'mms_nonce'); ?>
            <input type="hidden" name="mms_settings_action" value="schedule">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="check_new_manga"><?php _e('Check for New Manga', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="check_new_manga" id="check_new_manga" value="1" <?php checked($schedule_settings['check_new_manga']); ?>>
                            <?php _e('Automatically check for new manga', 'madara-manga-scraper'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, the scraper will periodically check sources for new manga.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="check_new_chapters"><?php _e('Check for New Chapters', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="check_new_chapters" id="check_new_chapters" value="1" <?php checked($schedule_settings['check_new_chapters']); ?>>
                            <?php _e('Automatically check for new chapters', 'madara-manga-scraper'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, the scraper will periodically check for new chapters of existing manga.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_new_manga"><?php _e('Max New Manga', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="max_new_manga" id="max_new_manga" class="small-text" min="1" value="<?php echo intval($schedule_settings['max_new_manga']); ?>">
                        <p class="description"><?php _e('Maximum number of new manga to add to the queue during each check.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_manga_updates"><?php _e('Max Manga Updates', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="max_manga_updates" id="max_manga_updates" class="small-text" min="1" value="<?php echo intval($schedule_settings['max_manga_updates']); ?>">
                        <p class="description"><?php _e('Maximum number of manga to check for updates during each check.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_new_chapters"><?php _e('Max New Chapters', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="max_new_chapters" id="max_new_chapters" class="small-text" min="1" value="<?php echo intval($schedule_settings['max_new_chapters']); ?>">
                        <p class="description"><?php _e('Maximum number of new chapters to add to the queue for each manga during each check.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="check_interval"><?php _e('Check Interval', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <select name="check_interval" id="check_interval">
                            <?php foreach ($schedules as $key => $display) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($schedule_settings['check_interval'], $key); ?>><?php echo esc_html($display); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('How often to check for updates.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="queue_interval"><?php _e('Queue Processing Interval', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <select name="queue_interval" id="queue_interval">
                            <?php foreach ($schedules as $key => $display) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($schedule_settings['queue_interval'], $key); ?>><?php echo esc_html($display); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('How often to process the queue.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Schedule Settings', 'madara-manga-scraper'); ?>">
                <button type="button" class="button mms-run-now" data-task="check_sources"><?php _e('Run Sources Check Now', 'madara-manga-scraper'); ?></button>
                <button type="button" class="button mms-run-now" data-task="process_queue"><?php _e('Process Queue Now', 'madara-manga-scraper'); ?></button>
            </p>
        </form>
    </div>
</div>

<style>
    /* Settings Styles */
    .mms-tab-content {
        display: none;
        padding: 20px 0;
    }
    
    .mms-tab-active {
        display: block;
    }
    
    .mms-notice {
        color: #e74c3c;
        font-weight: bold;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Tab Navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs and content
        $('.nav-tab').removeClass('nav-tab-active');
        $('.mms-tab-content').removeClass('mms-tab-active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('nav-tab-active');
        $($(this).attr('href')).addClass('mms-tab-active');
        
        // Update URL hash
        window.location.hash = $(this).attr('href');
    });
    
    // Initialize tabs based on URL hash or default to general tab
    var hash = window.location.hash || '#general-tab';
    $('.nav-tab[href="' + hash + '"]').click();
    
    // Run Now Buttons
    $('.mms-run-now').on('click', function() {
        var task = $(this).data('task');
        var taskDisplay = task === 'check_sources' ? 'Sources Check' : 'Queue Processing';
        
        $(this).prop('disabled', true).text('<?php _e('Running...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_run_task_now',
                task: task,
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('.mms-run-now[data-task="' + task + '"]').prop('disabled', false).text('Run ' + taskDisplay + ' Now');
                
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                $('.mms-run-now[data-task="' + task + '"]').prop('disabled', false).text('Run ' + taskDisplay + ' Now');
                alert('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
            }
        });
    });
});
</script>