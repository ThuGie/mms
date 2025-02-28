<?php
/**
 * Manga view
 * 
 * @package Madara Manga Scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap mms-manga">
    <h1><?php _e('Manage Manga', 'madara-manga-scraper'); ?></h1>
    
    <div class="mms-manga-filters">
        <form method="get">
            <input type="hidden" name="page" value="mms-manga">
            
            <select name="source">
                <option value=""><?php _e('All Sources', 'madara-manga-scraper'); ?></option>
                <?php foreach ($sources as $src) : ?>
                    <option value="<?php echo intval($src->id); ?>" <?php selected(isset($_GET['source']) && $_GET['source'] == $src->id); ?>><?php echo esc_html($src->name); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="status">
                <option value=""><?php _e('All Statuses', 'madara-manga-scraper'); ?></option>
                <option value="published" <?php selected(isset($_GET['status']) && $_GET['status'] === 'published'); ?>><?php _e('Published', 'madara-manga-scraper'); ?></option>
                <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] === 'pending'); ?>><?php _e('Pending', 'madara-manga-scraper'); ?></option>
            </select>
            
            <input type="text" name="search" placeholder="<?php _e('Search by title...', 'madara-manga-scraper'); ?>" value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">
            
            <input type="submit" class="button" value="<?php _e('Filter', 'madara-manga-scraper'); ?>">
        </form>
    </div>
    
    <div class="mms-manga-list">
        <?php if (empty($manga_list)) : ?>
            <div class="notice notice-info inline">
                <p><?php _e('No manga found.', 'madara-manga-scraper'); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-id"><?php _e('ID', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-cover"><?php _e('Cover', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-title"><?php _e('Title', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-source"><?php _e('Source', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-chapters"><?php _e('Chapters', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'madara-manga-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manga_list as $manga) : ?>
                        <?php
                        // Get source
                        $source_name = __('Unknown', 'madara-manga-scraper');
                        $chapter_count = 0;
                        $downloaded_chapters = 0;
                        
                        if (isset($db) && $db) {
                            $source = $db->get_row('sources', array('id' => $manga->source_id));
                            $source_name = $source ? $source->name : $source_name;
                            
                            // Get chapter count
                            $chapter_count = $db->count('chapters', array('manga_id' => $manga->id));
                            $downloaded_chapters = $db->count('chapters', array('manga_id' => $manga->id, 'downloaded' => 1));
                        }
                        
                        // Get last chapter
                        $last_chapter = $db->get_results(
                            'chapters',
                            array('manga_id' => $manga->id),
                            'chapter_number',
                            'DESC',
                            1
                        );
                        $last_chapter_number = !empty($last_chapter) ? $last_chapter[0]->chapter_number : '';
                        ?>
                        <tr>
                            <td class="column-id"><?php echo intval($manga->id); ?></td>
                            <td class="column-cover">
                                <?php if (!empty($manga->cover_url)) : ?>
                                    <img src="<?php echo esc_url($manga->cover_url); ?>" alt="<?php echo esc_attr($manga->title); ?>" style="max-width: 60px; max-height: 80px;">
                                <?php endif; ?>
                            </td>
                            <td class="column-title">
                                <strong><?php echo esc_html($manga->title); ?></strong>
                                <?php if ($manga->wp_post_id) : ?>
                                    <a href="<?php echo get_permalink($manga->wp_post_id); ?>" target="_blank"><span class="dashicons dashicons-external"></span></a>
                                <?php endif; ?>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="#" class="mms-view-manga-details" data-id="<?php echo intval($manga->id); ?>"><?php _e('Details', 'madara-manga-scraper'); ?></a> |
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo admin_url('post.php?post=' . $manga->wp_post_id . '&action=edit'); ?>" target="_blank"><?php _e('Edit Post', 'madara-manga-scraper'); ?></a> |
                                    </span>
                                    <span class="chapters">
                                        <a href="#" class="mms-view-chapters" data-id="<?php echo intval($manga->id); ?>"><?php _e('View Chapters', 'madara-manga-scraper'); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-source"><?php echo esc_html($source_name); ?></td>
                            <td class="column-chapters">
                                <?php echo $downloaded_chapters; ?>/<?php echo $chapter_count; ?>
                                <?php if (!empty($last_chapter_number)) : ?>
                                    <div class="last-chapter">
                                        <?php echo sprintf(__('Last: Ch.%s', 'madara-manga-scraper'), esc_html($last_chapter_number)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <?php if ($manga->wp_post_id) : ?>
                                    <span class="mms-status mms-status-published"><?php _e('Published', 'madara-manga-scraper'); ?></span>
                                <?php else : ?>
                                    <span class="mms-status mms-status-pending"><?php _e('Pending', 'madara-manga-scraper'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button class="button mms-refresh-manga" data-id="<?php echo esc_attr($manga->source_manga_id); ?>" data-source="<?php echo intval($manga->source_id); ?>"><?php _e('Refresh', 'madara-manga-scraper'); ?></button>
                                <button class="button mms-remove-manga" data-id="<?php echo intval($manga->id); ?>"><?php _e('Remove', 'madara-manga-scraper'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- View Manga Details Modal -->
<div id="mms-manga-details-modal" class="mms-modal">
    <div class="mms-modal-content mms-large-modal">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Manga Details', 'madara-manga-scraper'); ?></h2>
        
        <div id="mms-manga-details-content" class="mms-manga-details-content">
            <div class="mms-modal-loading"><?php _e('Loading...', 'madara-manga-scraper'); ?></div>
        </div>
    </div>
</div>

<!-- View Chapters Modal -->
<div id="mms-chapters-modal" class="mms-modal">
    <div class="mms-modal-content mms-large-modal">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Manga Chapters', 'madara-manga-scraper'); ?></h2>
        
        <div id="mms-chapters-content" class="mms-chapters-content">
            <div class="mms-modal-loading"><?php _e('Loading...', 'madara-manga-scraper'); ?></div>
        </div>
    </div>
</div>

<!-- Remove Manga Modal -->
<div id="mms-remove-manga-modal" class="mms-modal">
    <div class="mms-modal-content">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Remove Manga', 'madara-manga-scraper'); ?></h2>
        
        <p><?php _e('Are you sure you want to remove this manga? This will delete all associated data including chapters.', 'madara-manga-scraper'); ?></p>
        <p><?php _e('Note: This will not delete the WordPress post if it exists.', 'madara-manga-scraper'); ?></p>
        
        <p class="submit">
            <button type="button" class="button button-primary" id="mms-remove-manga-confirm"><?php _e('Remove', 'madara-manga-scraper'); ?></button>
            <button type="button" class="button mms-modal-cancel"><?php _e('Cancel', 'madara-manga-scraper'); ?></button>
        </p>
        
        <div id="mms-remove-manga-result" class="hidden">
            <div class="notice notice-success inline">
                <p></p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Manga Styles */
    .mms-manga-filters {
        margin: 20px 0;
    }
    
    .mms-manga-filters select,
    .mms-manga-filters input[type="text"] {
        margin-right: 10px;
    }
    
    .column-id {
        width: 50px;
    }
    
    .column-cover {
        width: 80px;
    }
    
    .column-source, .column-chapters, .column-status {
        width: 120px;
    }
    
    .column-actions {
        width: 180px;
    }
    
    .column-actions .button {
        margin-right: 5px;
    }
    
    .last-chapter {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    
    .mms-status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .mms-status-published {
        background-color: #e5fae5;
        color: #00b200;
    }
    
    .mms-status-pending {
        background-color: #fff7e5;
        color: #b26b00;
    }
    
    /* Modal Styles */
    .mms-modal {
        display: none;
        position: fixed;
        z-index: 100000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }
    
    .mms-modal-content {
        position: relative;
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 50%;
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
    }
    
    .mms-large-modal {
        width: 80%;
        max-width: 1200px;
    }
    
    .mms-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .mms-modal-close:hover,
    .mms-modal-close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    
    .mms-modal-loading {
        text-align: center;
        padding: 20px;
    }
    
    .mms-manga-details-content {
        display: flex;
        flex-wrap: wrap;
    }
    
    .mms-manga-details-cover {
        flex: 0 0 200px;
        margin-right: 20px;
    }
    
    .mms-manga-details-info {
        flex: 1;
    }
    
    .mms-manga-details-info table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .mms-manga-details-info th,
    .mms-manga-details-info td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #eee;
        vertical-align: top;
    }
    
    .mms-manga-details-info th {
        width: 120px;
    }
    
    .mms-manga-details-description {
        margin-top: 20px;
    }
    
    .mms-chapters-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .mms-chapters-table th,
    .mms-chapters-table td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .hidden {
        display: none;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // View Manga Details
    $('.mms-view-manga-details').on('click', function(e) {
        e.preventDefault();
        
        var mangaId = $(this).data('id');
        
        $('#mms-manga-details-content').html('<div class="mms-modal-loading"><?php _e('Loading...', 'madara-manga-scraper'); ?></div>');
        $('#mms-manga-details-modal').css('display', 'block');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_get_manga_details',
                manga_id: mangaId,
                nonce: mms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#mms-manga-details-content').html(response.data.html);
                } else {
                    $('#mms-manga-details-content').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $('#mms-manga-details-content').html('<div class="notice notice-error inline"><p><?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?></p></div>');
            }
        });
    });
    
    // View Chapters
    $('.mms-view-chapters').on('click', function(e) {
        e.preventDefault();
        
        var mangaId = $(this).data('id');
        
        $('#mms-chapters-content').html('<div class="mms-modal-loading"><?php _e('Loading...', 'madara-manga-scraper'); ?></div>');
        $('#mms-chapters-modal').css('display', 'block');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_get_manga_chapters',
                manga_id: mangaId,
                nonce: mms_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#mms-chapters-content').html(response.data.html);
                } else {
                    $('#mms-chapters-content').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $('#mms-chapters-content').html('<div class="notice notice-error inline"><p><?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?></p></div>');
            }
        });
    });
    
    // Refresh Manga
    $('.mms-refresh-manga').on('click', function() {
        var mangaId = $(this).data('id');
        var sourceId = $(this).data('source');
        
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_scrape_now',
                id: mangaId,
                type: 'manga',
                source_id: sourceId,
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('.mms-refresh-manga').prop('disabled', false).text('<?php _e('Refresh', 'madara-manga-scraper'); ?>');
                
                if (response.success) {
                    alert(response.data.message);
                    
                    // Reload page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                $('.mms-refresh-manga').prop('disabled', false).text('<?php _e('Refresh', 'madara-manga-scraper'); ?>');
                alert('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
            }
        });
    });
    
    // Remove Manga
    $('.mms-remove-manga').on('click', function() {
        var mangaId = $(this).data('id');
        
        $('#mms-remove-manga-modal').data('id', mangaId);
        $('#mms-remove-manga-result').addClass('hidden');
        $('#mms-remove-manga-result p').text('');
        
        $('#mms-remove-manga-modal').css('display', 'block');
    });
    
    // Confirm Remove Manga
    $('#mms-remove-manga-confirm').on('click', function() {
        var mangaId = $('#mms-remove-manga-modal').data('id');
        
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_remove_manga',
                manga_id: mangaId,
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('#mms-remove-manga-confirm').prop('disabled', false).text('<?php _e('Remove', 'madara-manga-scraper'); ?>');
                
                if (response.success) {
                    $('#mms-remove-manga-result').removeClass('hidden')
                        .find('div').removeClass('notice-error').addClass('notice-success')
                        .find('p').text(response.data.message);
                    
                    // Reload page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#mms-remove-manga-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text(response.data.message);
                }
            },
            error: function() {
                $('#mms-remove-manga-confirm').prop('disabled', false).text('<?php _e('Remove', 'madara-manga-scraper'); ?>');
                
                $('#mms-remove-manga-result').removeClass('hidden')
                    .find('div').removeClass('notice-success').addClass('notice-error')
                    .find('p').text('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
            }
        });
    });
    
    // Close modals
    $('.mms-modal-close, .mms-modal-cancel').on('click', function() {
        $('.mms-modal').css('display', 'none');
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('mms-modal')) {
            $('.mms-modal').css('display', 'none');
        }
    });
});
</script>