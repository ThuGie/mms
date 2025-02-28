<?php
/**
 * Queue view
 * 
 * @package Madara Manga Scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap mms-queue">
    <h1><?php _e('Manga Scraper Queue', 'madara-manga-scraper'); ?></h1>
    
    <div class="mms-queue-stats">
        <div class="mms-queue-stats-box">
            <h2><?php _e('Queue Statistics', 'madara-manga-scraper'); ?></h2>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Status', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Manga', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Chapters', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Total', 'madara-manga-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Pending', 'madara-manga-scraper'); ?></td>
                        <td><?php echo intval($queue_stats['manga']['pending']); ?></td>
                        <td><?php echo intval($queue_stats['chapter']['pending']); ?></td>
                        <td><?php echo intval($queue_stats['pending']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Processing', 'madara-manga-scraper'); ?></td>
                        <td><?php echo intval($queue_stats['manga']['processing']); ?></td>
                        <td><?php echo intval($queue_stats['chapter']['processing']); ?></td>
                        <td><?php echo intval($queue_stats['processing']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Completed', 'madara-manga-scraper'); ?></td>
                        <td><?php echo intval($queue_stats['manga']['completed']); ?></td>
                        <td><?php echo intval($queue_stats['chapter']['completed']); ?></td>
                        <td><?php echo intval($queue_stats['completed']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Failed', 'madara-manga-scraper'); ?></td>
                        <td><?php echo intval($queue_stats['manga']['failed']); ?></td>
                        <td><?php echo intval($queue_stats['chapter']['failed']); ?></td>
                        <td><?php echo intval($queue_stats['failed']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Total', 'madara-manga-scraper'); ?></th>
                        <th><?php echo intval($queue_stats['manga']['total']); ?></th>
                        <th><?php echo intval($queue_stats['chapter']['total']); ?></th>
                        <th><?php echo intval($queue_stats['total']); ?></th>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mms-queue-actions">
            <h2><?php _e('Queue Actions', 'madara-manga-scraper'); ?></h2>
            
            <div class="mms-action-buttons">
                <button class="button mms-clear-queue" data-status="pending" data-type=""><?php _e('Clear Pending Queue', 'madara-manga-scraper'); ?></button>
                <button class="button mms-clear-queue" data-status="failed" data-type=""><?php _e('Clear Failed Queue', 'madara-manga-scraper'); ?></button>
                <button class="button mms-clear-queue" data-status="" data-type=""><?php _e('Clear All Queue', 'madara-manga-scraper'); ?></button>
                <button class="button mms-retry-failed"><?php _e('Retry Failed Items', 'madara-manga-scraper'); ?></button>
                <button class="button mms-reset-processing"><?php _e('Reset Processing Items', 'madara-manga-scraper'); ?></button>
                <button class="button mms-process-queue"><?php _e('Process Queue Now', 'madara-manga-scraper'); ?></button>
            </div>
            
            <div id="mms-queue-action-result" class="hidden">
                <div class="notice notice-success inline">
                    <p></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mms-queue-filters">
        <form method="get">
            <input type="hidden" name="page" value="mms-queue">
            
            <select name="status">
                <option value=""><?php _e('All Statuses', 'madara-manga-scraper'); ?></option>
                <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] === 'pending'); ?>><?php _e('Pending', 'madara-manga-scraper'); ?></option>
                <option value="processing" <?php selected(isset($_GET['status']) && $_GET['status'] === 'processing'); ?>><?php _e('Processing', 'madara-manga-scraper'); ?></option>
                <option value="completed" <?php selected(isset($_GET['status']) && $_GET['status'] === 'completed'); ?>><?php _e('Completed', 'madara-manga-scraper'); ?></option>
                <option value="failed" <?php selected(isset($_GET['status']) && $_GET['status'] === 'failed'); ?>><?php _e('Failed', 'madara-manga-scraper'); ?></option>
            </select>
            
            <select name="type">
                <option value=""><?php _e('All Types', 'madara-manga-scraper'); ?></option>
                <option value="manga" <?php selected(isset($_GET['type']) && $_GET['type'] === 'manga'); ?>><?php _e('Manga', 'madara-manga-scraper'); ?></option>
                <option value="chapter" <?php selected(isset($_GET['type']) && $_GET['type'] === 'chapter'); ?>><?php _e('Chapter', 'madara-manga-scraper'); ?></option>
            </select>
            
            <input type="submit" class="button" value="<?php _e('Filter', 'madara-manga-scraper'); ?>">
        </form>
    </div>
    
    <div class="mms-queue-list">
        <?php if (empty($queue_items)) : ?>
            <div class="notice notice-info inline">
                <p><?php _e('No queue items found.', 'madara-manga-scraper'); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-id"><?php _e('ID', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-type"><?php _e('Type', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-item"><?php _e('Item', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-source"><?php _e('Source', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-priority"><?php _e('Priority', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-attempts"><?php _e('Attempts', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-date"><?php _e('Added', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'madara-manga-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queue_items as $item) : ?>
                        <?php
                        // Get source name
                        $source_name = __('Unknown', 'madara-manga-scraper');
                        if (isset($db) && $db) {
                            $source = $db->get_row('sources', array('id' => $item->source_id));
                            $source_name = $source ? $source->name : $source_name;
                        }
                        
                        // Get item name
                        $item_name = $item->item_id;
                        if ($item->item_type === 'manga' && isset($db) && $db) {
                            $manga = $db->get_row('manga', array('source_manga_id' => $item->item_id, 'source_id' => $item->source_id));
                            if ($manga) {
                                $item_name = $manga->title;
                            }
                        } elseif ($item->item_type === 'chapter' && isset($db) && $db) {
                            global $wpdb;
                            $table_name = $db->get_table_name('chapters');
                            $manga_table = $db->get_table_name('manga');
                            
                            if ($table_name && $manga_table) {
                                $sql = $wpdb->prepare(
                                    "SELECT c.chapter_number, m.title
                                    FROM $table_name c
                                    JOIN $manga_table m ON c.manga_id = m.id
                                    WHERE c.source_chapter_id = %s",
                                    $item->item_id
                                );
                                
                                $chapter = $wpdb->get_row($sql);
                                
                                if ($chapter) {
                                    $item_name = sprintf('%s - Chapter %s', $chapter->title, $chapter->chapter_number);
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td class="column-id"><?php echo intval($item->id); ?></td>
                            <td class="column-type">
                                <?php if ($item->item_type === 'manga') : ?>
                                    <span class="mms-type mms-type-manga"><?php _e('Manga', 'madara-manga-scraper'); ?></span>
                                <?php else : ?>
                                    <span class="mms-type mms-type-chapter"><?php _e('Chapter', 'madara-manga-scraper'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-item"><?php echo esc_html($item_name); ?></td>
                            <td class="column-source"><?php echo esc_html($source_name); ?></td>
                            <td class="column-priority"><?php echo intval($item->priority); ?></td>
                            <td class="column-status">
                                <?php if ($item->status === 'pending') : ?>
                                    <span class="mms-status mms-status-pending"><?php _e('Pending', 'madara-manga-scraper'); ?></span>
                                <?php elseif ($item->status === 'processing') : ?>
                                    <span class="mms-status mms-status-processing"><?php _e('Processing', 'madara-manga-scraper'); ?></span>
                                <?php elseif ($item->status === 'completed') : ?>
                                    <span class="mms-status mms-status-completed"><?php _e('Completed', 'madara-manga-scraper'); ?></span>
                                <?php else : ?>
                                    <span class="mms-status mms-status-failed"><?php _e('Failed', 'madara-manga-scraper'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-attempts">
                                <?php echo intval($item->attempts); ?>/<?php echo intval($item->max_attempts); ?>
                            </td>
                            <td class="column-date">
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at)); ?>
                            </td>
                            <td class="column-actions">
                                <?php if ($item->status === 'pending' || $item->status === 'failed') : ?>
                                    <button class="button mms-scrape-now" data-id="<?php echo esc_attr($item->item_id); ?>" data-type="<?php echo esc_attr($item->item_type); ?>" data-source="<?php echo intval($item->source_id); ?>"><?php _e('Scrape Now', 'madara-manga-scraper'); ?></button>
                                <?php endif; ?>
                                <button class="button mms-remove-queue" data-id="<?php echo intval($item->id); ?>"><?php _e('Remove', 'madara-manga-scraper'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Remove Queue Modal -->
<div id="mms-remove-queue-modal" class="mms-modal">
    <div class="mms-modal-content">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Remove Queue Item', 'madara-manga-scraper'); ?></h2>
        
        <p><?php _e('Are you sure you want to remove this queue item?', 'madara-manga-scraper'); ?></p>
        
        <p class="submit">
            <button type="button" class="button button-primary" id="mms-remove-queue-confirm"><?php _e('Remove', 'madara-manga-scraper'); ?></button>
            <button type="button" class="button mms-modal-cancel"><?php _e('Cancel', 'madara-manga-scraper'); ?></button>
        </p>
        
        <div id="mms-remove-queue-result" class="hidden">
            <div class="notice notice-success inline">
                <p></p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Queue Styles */
    .mms-queue-stats {
        display: flex;
        flex-wrap: wrap;
        margin: 20px 0;
    }
    
    .mms-queue-stats-box {
        flex: 1;
        min-width: 45%;
        padding-right: 20px;
    }
    
    .mms-queue-actions {
        flex: 1;
        min-width: 45%;
    }
    
    .mms-action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .mms-queue-filters {
        margin: 20px 0;
    }
    
    .mms-queue-filters select {
        margin-right: 10px;
    }
    
    .mms-type {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .mms-type-manga {
        background-color: #e5e5fa;
        color: #4b0082;
    }
    
    .mms-type-chapter {
        background-color: #e5fae5;
        color: #006400;
    }
    
    .mms-status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .mms-status-pending {
        background-color: #fff7e5;
        color: #b26b00;
    }
    
    .mms-status-processing {
        background-color: #e5f5fa;
        color: #0090b2;
    }
    
    .mms-status-completed {
        background-color: #e5fae5;
        color: #00b200;
    }
    
    .mms-status-failed {
        background-color: #fae5e5;
        color: #b20000;
    }
    
    .column-id {
        width: 50px;
    }
    
    .column-type, .column-priority, .column-status, .column-attempts {
        width: 90px;
    }
    
    .column-date {
        width: 120px;
    }
    
    .column-actions {
        width: 180px;
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
    
    .hidden {
        display: none;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Clear Queue
    $('.mms-clear-queue').on('click', function() {
        var status = $(this).data('status');
        var type = $(this).data('type');
        var statusText = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'All';
        var typeText = type ? type.charAt(0).toUpperCase() + type.slice(1) : 'All';
        
        if (confirm('<?php _e('Are you sure you want to clear', 'madara-manga-scraper'); ?> ' + statusText + ' ' + typeText + ' <?php _e('queue items?', 'madara-manga-scraper'); ?>')) {
            $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mms_clear_queue',
                    status: status,
                    type: type,
                    nonce: mms_data.nonce
                },
                success: function(response) {
                    $('.mms-clear-queue').prop('disabled', false).each(function() {
                        var btnStatus = $(this).data('status');
                        var btnType = $(this).data('type');
                        var btnStatusText = btnStatus ? btnStatus.charAt(0).toUpperCase() + btnStatus.slice(1) : 'All';
                        var btnTypeText = btnType ? btnType.charAt(0).toUpperCase() + btnType.slice(1) : 'All';
                        
                        $(this).text('<?php _e('Clear', 'madara-manga-scraper'); ?> ' + btnStatusText + ' ' + btnTypeText + ' <?php _e('Queue', 'madara-manga-scraper'); ?>');
                    });
                    
                    if (response.success) {
                        $('#mms-queue-action-result').removeClass('hidden')
                            .find('div').removeClass('notice-error').addClass('notice-success')
                            .find('p').text(response.data.message);
                        
                        // Reload page after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $('#mms-queue-action-result').removeClass('hidden')
                            .find('div').removeClass('notice-success').addClass('notice-error')
                            .find('p').text(response.data.message);
                    }
                },
                error: function() {
                    $('.mms-clear-queue').prop('disabled', false).each(function() {
                        var btnStatus = $(this).data('status');
                        var btnType = $(this).data('type');
                        var btnStatusText = btnStatus ? btnStatus.charAt(0).toUpperCase() + btnStatus.slice(1) : 'All';
                        var btnTypeText = btnType ? btnType.charAt(0).toUpperCase() + btnType.slice(1) : 'All';
                        
                        $(this).text('<?php _e('Clear', 'madara-manga-scraper'); ?> ' + btnStatusText + ' ' + btnTypeText + ' <?php _e('Queue', 'madara-manga-scraper'); ?>');
                    });
                    
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
                }
            });
        }
    });
    
    // Retry Failed Items
    $('.mms-retry-failed').on('click', function() {
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_retry_failed',
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('.mms-retry-failed').prop('disabled', false).text('<?php _e('Retry Failed Items', 'madara-manga-scraper'); ?>');
                
                if (response.success) {
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-error').addClass('notice-success')
                        .find('p').text(response.data.message);
                    
                    // Reload page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text(response.data.message);
                }
            },
            error: function() {
                $('.mms-retry-failed').prop('disabled', false).text('<?php _e('Retry Failed Items', 'madara-manga-scraper'); ?>');
                
                $('#mms-queue-action-result').removeClass('hidden')
                    .find('div').removeClass('notice-success').addClass('notice-error')
                    .find('p').text('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
            }
        });
    });
    
    // Reset Processing Items
    $('.mms-reset-processing').on('click', function() {
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_reset_processing',
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('.mms-reset-processing').prop('disabled', false).text('<?php _e('Reset Processing Items', 'madara-manga-scraper'); ?>');
                
                if (response.success) {
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-error').addClass('notice-success')
                        .find('p').text(response.data.message);
                    
                    // Reload page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text(response.data.message);
                }
            },
            error: function() {
                $('.mms-reset-processing').prop('disabled', false).text('<?php _e('Reset Processing Items', 'madara-manga-scraper'); ?>');
                
                $('#mms-queue-action-result').removeClass('hidden')
                    .find('div').removeClass('notice-success').addClass('notice-error')
                    .find('p').text('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
            }
        });
    });
    
    // Process Queue Now
    $('.mms-process-queue').on('click', function() {
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_process_queue_now',
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('.mms-process-queue').prop('disabled', false).text('<?php _e('Process Queue Now', 'madara-manga-scraper'); ?>');
                
                if (response.success) {
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-error').addClass('notice-success')
                        .find('p').text(response.data.message);
                    
                    // Reload page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text(response.data.message);
                }
            },
            error: function() {
                $('.mms-process-queue').prop('disabled', false).text('<?php _e('Process Queue Now', 'madara-manga-scraper'); ?>');
                
                $('#mms-queue-action-result').removeClass('hidden')
                    .find('div').removeClass('notice-success').addClass('notice-error')
                    .find('p').text('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
            }
        });
    });
    
    // Scrape Now
    $('.mms-scrape-now').on('click', function() {
        var id = $(this).data('id');
        var type = $(this).data('type');
        var source = $(this).data('source');
        
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_scrape_now',
                id: id,
                type: type,
                source_id: source,
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('.mms-scrape-now').prop('disabled', false).text('<?php _e('Scrape Now', 'madara-manga-scraper'); ?>');
                
                if (response.success) {
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-error').addClass('notice-success')
                        .find('p').text(response.data.message);
                    
                    // Reload page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#mms-queue-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text(response.data.message);
                }
            },
            error: function() {
                $('.mms-scrape-now').prop('disabled', false).text('<?php _e('Scrape Now', 'madara-manga-scraper'); ?>');
                
                $('#mms-queue-action-result').removeClass('hidden')
                    .find('div').removeClass('notice-success').addClass('notice-error')
                    .find('p').text('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
            }
        });
    });
    
    // Remove Queue Item
    $('.mms-remove-queue').on('click', function() {
        var id = $(this).data('id');
        
        $('#mms-remove-queue-modal').data('id', id);
        $('#mms-remove-queue-result').addClass('hidden');
        $('#mms-remove-queue-result p').text('');
        
        $('#mms-remove-queue-modal').css('display', 'block');
    });
    
    // Confirm Remove Queue Item
    $('#mms-remove-queue-confirm').on('click', function() {
        var id = $('#mms-remove-queue-modal').data('id');
        
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_remove_queue_item',
                id: id,
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('#mms-remove-queue-confirm').prop('disabled', false).text('<?php _e('Remove', 'madara-manga-scraper'); ?>');
                
                if (response.success) {
                    $('#mms-remove-queue-result').removeClass('hidden')
                        .find('div').removeClass('notice-error').addClass('notice-success')
                        .find('p').text(response.data.message);
                    
                    // Reload page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#mms-remove-queue-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text(response.data.message);
                }
            },
            error: function() {
                $('#mms-remove-queue-confirm').prop('disabled', false).text('<?php _e('Remove', 'madara-manga-scraper'); ?>');
                
                $('#mms-remove-queue-result').removeClass('hidden')
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