<?php
/**
 * Logs view
 * 
 * @package Madara Manga Scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap mms-logs">
    <h1><?php _e('Manga Scraper Logs', 'madara-manga-scraper'); ?></h1>
    
    <div class="mms-logs-actions">
        <h2><?php _e('Log Actions', 'madara-manga-scraper'); ?></h2>
        
        <div class="mms-action-buttons">
            <button class="button mms-clear-logs" data-type="logs" data-level=""><?php _e('Clear All Logs', 'madara-manga-scraper'); ?></button>
            <button class="button mms-clear-logs" data-type="logs" data-level="error"><?php _e('Clear Error Logs', 'madara-manga-scraper'); ?></button>
            <button class="button mms-clear-logs" data-type="logs" data-level="warning"><?php _e('Clear Warning Logs', 'madara-manga-scraper'); ?></button>
            <button class="button mms-clear-logs" data-type="errors" data-level=""><?php _e('Clear Error Items', 'madara-manga-scraper'); ?></button>
            <button class="button mms-clear-logs" data-type="files" data-level=""><?php _e('Clear Log Files', 'madara-manga-scraper'); ?></button>
        </div>
        
        <div id="mms-log-action-result" class="hidden">
            <div class="notice notice-success inline">
                <p></p>
            </div>
        </div>
    </div>
    
    <div class="nav-tab-wrapper">
        <a href="#logs-tab" class="nav-tab nav-tab-active"><?php _e('Logs', 'madara-manga-scraper'); ?></a>
        <a href="#errors-tab" class="nav-tab"><?php _e('Error Items', 'madara-manga-scraper'); ?></a>
    </div>
    
    <div id="logs-tab" class="mms-tab-content mms-tab-active">
        <div class="mms-logs-filters">
            <form method="get">
                <input type="hidden" name="page" value="mms-logs">
                
                <select name="level">
                    <option value=""><?php _e('All Levels', 'madara-manga-scraper'); ?></option>
                    <option value="info" <?php selected(isset($_GET['level']) && $_GET['level'] === 'info'); ?>><?php _e('Info', 'madara-manga-scraper'); ?></option>
                    <option value="warning" <?php selected(isset($_GET['level']) && $_GET['level'] === 'warning'); ?>><?php _e('Warning', 'madara-manga-scraper'); ?></option>
                    <option value="error" <?php selected(isset($_GET['level']) && $_GET['level'] === 'error'); ?>><?php _e('Error', 'madara-manga-scraper'); ?></option>
                    <option value="debug" <?php selected(isset($_GET['level']) && $_GET['level'] === 'debug'); ?>><?php _e('Debug', 'madara-manga-scraper'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'madara-manga-scraper'); ?>">
            </form>
        </div>
        
        <div class="mms-logs-list">
            <?php if (empty($logs)) : ?>
                <div class="notice notice-info inline">
                    <p><?php _e('No logs found.', 'madara-manga-scraper'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="column-time"><?php _e('Time', 'madara-manga-scraper'); ?></th>
                            <th scope="col" class="column-level"><?php _e('Level', 'madara-manga-scraper'); ?></th>
                            <th scope="col" class="column-message"><?php _e('Message', 'madara-manga-scraper'); ?></th>
                            <th scope="col" class="column-context"><?php _e('Context', 'madara-manga-scraper'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td class="column-time">
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at)); ?>
                                </td>
                                <td class="column-level">
                                    <span class="mms-log-level mms-log-level-<?php echo esc_attr($log->level); ?>">
                                        <?php echo strtoupper(esc_html($log->level)); ?>
                                    </span>
                                </td>
                                <td class="column-message"><?php echo esc_html($log->message); ?></td>
                                <td class="column-context">
                                    <?php if (!empty($log->context)) : ?>
                                        <button class="button button-small mms-view-context" data-context="<?php echo esc_attr($log->context); ?>">
                                            <?php _e('View Context', 'madara-manga-scraper'); ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="mms-no-context"><?php _e('None', 'madara-manga-scraper'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="errors-tab" class="mms-tab-content">
        <div class="mms-logs-filters">
            <form method="get">
                <input type="hidden" name="page" value="mms-logs">
                <input type="hidden" name="tab" value="errors">
                
                <select name="item_type">
                    <option value=""><?php _e('All Types', 'madara-manga-scraper'); ?></option>
                    <option value="manga" <?php selected(isset($_GET['item_type']) && $_GET['item_type'] === 'manga'); ?>><?php _e('Manga', 'madara-manga-scraper'); ?></option>
                    <option value="chapter" <?php selected(isset($_GET['item_type']) && $_GET['item_type'] === 'chapter'); ?>><?php _e('Chapter', 'madara-manga-scraper'); ?></option>
                    <option value="source" <?php selected(isset($_GET['item_type']) && $_GET['item_type'] === 'source'); ?>><?php _e('Source', 'madara-manga-scraper'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'madara-manga-scraper'); ?>">
            </form>
        </div>
        
        <div class="mms-errors-list">
            <?php if (empty($errors)) : ?>
                <div class="notice notice-info inline">
                    <p><?php _e('No error items found.', 'madara-manga-scraper'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="column-time"><?php _e('Time', 'madara-manga-scraper'); ?></th>
                            <th scope="col" class="column-type"><?php _e('Type', 'madara-manga-scraper'); ?></th>
                            <th scope="col" class="column-item"><?php _e('Item ID', 'madara-manga-scraper'); ?></th>
                            <th scope="col" class="column-message"><?php _e('Error Message', 'madara-manga-scraper'); ?></th>
                            <th scope="col" class="column-trace"><?php _e('Trace', 'madara-manga-scraper'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errors as $error) : ?>
                            <tr>
                                <td class="column-time">
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($error->created_at)); ?>
                                </td>
                                <td class="column-type">
                                    <span class="mms-type mms-type-<?php echo esc_attr($error->item_type); ?>">
                                        <?php echo ucfirst(esc_html($error->item_type)); ?>
                                    </span>
                                </td>
                                <td class="column-item">
                                    <?php echo esc_html($error->item_id); ?>
                                </td>
                                <td class="column-message"><?php echo esc_html($error->error_message); ?></td>
                                <td class="column-trace">
                                    <?php if (!empty($error->error_trace)) : ?>
                                        <button class="button button-small mms-view-trace" data-trace="<?php echo esc_attr($error->error_trace); ?>">
                                            <?php _e('View Trace', 'madara-manga-scraper'); ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="mms-no-trace"><?php _e('None', 'madara-manga-scraper'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Context Modal -->
<div id="mms-context-modal" class="mms-modal">
    <div class="mms-modal-content">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Log Context', 'madara-manga-scraper'); ?></h2>
        
        <div id="mms-context-content">
            <pre></pre>
        </div>
    </div>
</div>

<!-- Trace Modal -->
<div id="mms-trace-modal" class="mms-modal">
    <div class="mms-modal-content">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Error Trace', 'madara-manga-scraper'); ?></h2>
        
        <div id="mms-trace-content">
            <pre></pre>
        </div>
    </div>
</div>

<style>
    /* Log Styles */
    .mms-logs-actions {
        margin-bottom: 20px;
    }
    
    .mms-action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .mms-logs-filters {
        margin: 20px 0;
    }
    
    .mms-logs-filters select {
        margin-right: 10px;
    }
    
    .mms-log-level {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .mms-log-level-info {
        background-color: #e5f5fa;
        color: #0090b2;
    }
    
    .mms-log-level-warning {
        background-color: #fff7e5;
        color: #b26b00;
    }
    
    .mms-log-level-error {
        background-color: #fae5e5;
        color: #b20000;
    }
    
    .mms-log-level-debug {
        background-color: #e5e5e5;
        color: #666;
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
    
    .mms-type-source {
        background-color: #f5e5fa;
        color: #800080;
    }
    
    .mms-no-context,
    .mms-no-trace {
        color: #999;
        font-style: italic;
    }
    
    .column-time {
        width: 150px;
    }
    
    .column-level,
    .column-type {
        width: 100px;
    }
    
    .column-context,
    .column-trace,
    .column-item {
        width: 120px;
    }
    
    /* Tab Styles */
    .mms-tab-content {
        display: none;
        padding: 20px 0;
    }
    
    .mms-tab-active {
        display: block;
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
        width: 60%;
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
    
    #mms-context-content pre,
    #mms-trace-content pre {
        white-space: pre-wrap;
        word-wrap: break-word;
        background-color: #f5f5f5;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 4px;
        overflow: auto;
        max-height: 400px;
    }
    
    .hidden {
        display: none;
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
    
    // Initialize tabs based on URL hash or default to logs tab
    var hash = window.location.hash || '#logs-tab';
    $('.nav-tab[href="' + hash + '"]').click();
    
    // View Log Context
    $('.mms-view-context').on('click', function() {
        var context = $(this).data('context');
        var contextObj = JSON.parse(context);
        
        $('#mms-context-content pre').text(JSON.stringify(contextObj, null, 2));
        $('#mms-context-modal').css('display', 'block');
    });
    
    // View Error Trace
    $('.mms-view-trace').on('click', function() {
        var trace = $(this).data('trace');
        
        $('#mms-trace-content pre').text(trace);
        $('#mms-trace-modal').css('display', 'block');
    });
    
    // Clear Logs
    $('.mms-clear-logs').on('click', function() {
        var type = $(this).data('type');
        var level = $(this).data('level');
        var typeText = type.charAt(0).toUpperCase() + type.slice(1);
        var levelText = level ? level.charAt(0).toUpperCase() + level.slice(1) + ' ' : '';
        
        if (confirm('<?php _e('Are you sure you want to clear', 'madara-manga-scraper'); ?> ' + levelText + typeText + '?')) {
            $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mms_clear_logs',
                    type: type,
                    level: level,
                    nonce: mms_data.nonce
                },
                success: function(response) {
                    $('.mms-clear-logs').prop('disabled', false).each(function() {
                        var btnType = $(this).data('type');
                        var btnLevel = $(this).data('level');
                        var btnTypeText = btnType.charAt(0).toUpperCase() + btnType.slice(1);
                        var btnLevelText = btnLevel ? btnLevel.charAt(0).toUpperCase() + btnLevel.slice(1) + ' ' : '';
                        
                        $(this).text('<?php _e('Clear', 'madara-manga-scraper'); ?> ' + btnLevelText + btnTypeText);
                    });
                    
                    if (response.success) {
                        $('#mms-log-action-result').removeClass('hidden')
                            .find('div').removeClass('notice-error').addClass('notice-success')
                            .find('p').text(response.data.message);
                        
                        // Reload page after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $('#mms-log-action-result').removeClass('hidden')
                            .find('div').removeClass('notice-success').addClass('notice-error')
                            .find('p').text(response.data.message);
                    }
                },
                error: function() {
                    $('.mms-clear-logs').prop('disabled', false).each(function() {
                        var btnType = $(this).data('type');
                        var btnLevel = $(this).data('level');
                        var btnTypeText = btnType.charAt(0).toUpperCase() + btnType.slice(1);
                        var btnLevelText = btnLevel ? btnLevel.charAt(0).toUpperCase() + btnLevel.slice(1) + ' ' : '';
                        
                        $(this).text('<?php _e('Clear', 'madara-manga-scraper'); ?> ' + btnLevelText + btnTypeText);
                    });
                    
                    $('#mms-log-action-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text('<?php _e('An error occurred. Please try again.', 'madara-manga-scraper'); ?>');
                }
            });
        }
    });
    
    // Close modals
    $('.mms-modal-close').on('click', function() {
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