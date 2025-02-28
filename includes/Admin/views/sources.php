<?php
/**
 * Sources view
 * 
 * @package Madara Manga Scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Display settings errors
settings_errors('mms_sources');
?>
<div class="wrap mms-sources">
    <h1><?php _e('Manga Sources', 'madara-manga-scraper'); ?></h1>
    
    <div class="mms-add-source-panel">
        <h2><?php _e('Add New Source', 'madara-manga-scraper'); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('mms_source_form', 'mms_nonce'); ?>
            <input type="hidden" name="mms_source_action" value="add">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="source_name"><?php _e('Source Name', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="source_name" id="source_name" class="regular-text" required>
                        <p class="description"><?php _e('Enter a name for this source.', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="source_url"><?php _e('Source URL', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <input type="url" name="source_url" id="source_url" class="regular-text" required>
                        <p class="description"><?php _e('Enter the URL of the Madara-based manga site (e.g., https://example.com).', 'madara-manga-scraper'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Add Source', 'madara-manga-scraper'); ?>">
            </p>
        </form>
    </div>
    
    <div class="mms-sources-list">
        <h2><?php _e('Manage Sources', 'madara-manga-scraper'); ?></h2>
        
        <?php if (empty($sources)) : ?>
            <div class="notice notice-info inline">
                <p><?php _e('No sources found. Add your first source above.', 'madara-manga-scraper'); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-id"><?php _e('ID', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-name"><?php _e('Name', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-url"><?php _e('URL', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-last-checked"><?php _e('Last Checked', 'madara-manga-scraper'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'madara-manga-scraper'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $source) : ?>
                        <tr>
                            <td class="column-id"><?php echo intval($source->id); ?></td>
                            <td class="column-name">
                                <strong><?php echo esc_html($source->name); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="#" class="mms-edit-source" data-id="<?php echo intval($source->id); ?>" data-name="<?php echo esc_attr($source->name); ?>" data-status="<?php echo intval($source->status); ?>"><?php _e('Edit', 'madara-manga-scraper'); ?></a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="mms-delete-source" data-id="<?php echo intval($source->id); ?>" data-name="<?php echo esc_attr($source->name); ?>"><?php _e('Delete', 'madara-manga-scraper'); ?></a> |
                                    </span>
                                    <span class="scrape">
                                        <a href="#" class="mms-scrape-source" data-id="<?php echo intval($source->id); ?>" data-name="<?php echo esc_attr($source->name); ?>"><?php _e('Scrape Manga', 'madara-manga-scraper'); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-url">
                                <a href="<?php echo esc_url($source->url); ?>" target="_blank">
                                    <?php echo esc_html($source->url); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td class="column-status">
                                <?php if ($source->status) : ?>
                                    <span class="mms-status mms-status-active"><?php _e('Active', 'madara-manga-scraper'); ?></span>
                                <?php else : ?>
                                    <span class="mms-status mms-status-inactive"><?php _e('Inactive', 'madara-manga-scraper'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-last-checked">
                                <?php if ($source->last_checked) : ?>
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($source->last_checked)); ?>
                                <?php else : ?>
                                    <?php _e('Never', 'madara-manga-scraper'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button class="button mms-scrape-source" data-id="<?php echo intval($source->id); ?>" data-name="<?php echo esc_attr($source->name); ?>"><?php _e('Scrape', 'madara-manga-scraper'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Source Modal -->
<div id="mms-edit-source-modal" class="mms-modal">
    <div class="mms-modal-content">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Edit Source', 'madara-manga-scraper'); ?></h2>
        
        <form method="post" action="" id="mms-edit-source-form">
            <?php wp_nonce_field('mms_source_form', 'mms_nonce'); ?>
            <input type="hidden" name="mms_source_action" value="edit">
            <input type="hidden" name="source_id" id="edit_source_id" value="">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="edit_source_name"><?php _e('Source Name', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="source_name" id="edit_source_name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="edit_source_status"><?php _e('Status', 'madara-manga-scraper'); ?></label>
                    </th>
                    <td>
                        <select name="source_status" id="edit_source_status">
                            <option value="1"><?php _e('Active', 'madara-manga-scraper'); ?></option>
                            <option value="0"><?php _e('Inactive', 'madara-manga-scraper'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="edit_submit" class="button button-primary" value="<?php _e('Update Source', 'madara-manga-scraper'); ?>">
            </p>
        </form>
    </div>
</div>

<!-- Delete Source Modal -->
<div id="mms-delete-source-modal" class="mms-modal">
    <div class="mms-modal-content">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Delete Source', 'madara-manga-scraper'); ?></h2>
        
        <p><?php _e('Are you sure you want to delete this source? This action cannot be undone.', 'madara-manga-scraper'); ?></p>
        <p id="mms-delete-source-name"></p>
        
        <form method="post" action="" id="mms-delete-source-form">
            <?php wp_nonce_field('mms_source_form', 'mms_nonce'); ?>
            <input type="hidden" name="mms_source_action" value="delete">
            <input type="hidden" name="source_id" id="delete_source_id" value="">
            
            <p class="submit">
                <input type="submit" name="submit" class="button button-primary" value="<?php _e('Delete Source', 'madara-manga-scraper'); ?>">
                <button type="button" class="button mms-modal-cancel"><?php _e('Cancel', 'madara-manga-scraper'); ?></button>
            </p>
        </form>
    </div>
</div>

<!-- Scrape Source Modal -->
<div id="mms-scrape-source-modal" class="mms-modal">
    <div class="mms-modal-content">
        <span class="mms-modal-close">&times;</span>
        <h2><?php _e('Scrape Source', 'madara-manga-scraper'); ?></h2>
        
        <p><?php _e('This will add all manga from the selected source to the queue for scraping. Continue?', 'madara-manga-scraper'); ?></p>
        <p id="mms-scrape-source-name"></p>
        
        <p class="submit">
            <button type="button" class="button button-primary" id="mms-scrape-source-confirm"><?php _e('Start Scraping', 'madara-manga-scraper'); ?></button>
            <button type="button" class="button mms-modal-cancel"><?php _e('Cancel', 'madara-manga-scraper'); ?></button>
        </p>
        
        <div id="mms-scrape-source-result" class="hidden">
            <div class="notice notice-success inline">
                <p></p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Source Styles */
    .mms-add-source-panel {
        background-color: #fff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .mms-add-source-panel h2 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .mms-sources-list {
        margin-top: 30px;
    }
    
    .mms-status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
    }
    
    .mms-status-active {
        background-color: #e5fae5;
        color: #00b200;
    }
    
    .mms-status-inactive {
        background-color: #fae5e5;
        color: #b20000;
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
    
    #mms-delete-source-name,
    #mms-scrape-source-name {
        font-weight: bold;
        margin-bottom: 20px;
    }
    
    .hidden {
        display: none;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Edit Source
    $('.mms-edit-source').on('click', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var name = $(this).data('name');
        var status = $(this).data('status');
        
        $('#edit_source_id').val(id);
        $('#edit_source_name').val(name);
        $('#edit_source_status').val(status);
        
        $('#mms-edit-source-modal').css('display', 'block');
    });
    
    // Delete Source
    $('.mms-delete-source').on('click', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#delete_source_id').val(id);
        $('#mms-delete-source-name').text(name);
        
        $('#mms-delete-source-modal').css('display', 'block');
    });
    
    // Scrape Source
    $('.mms-scrape-source').on('click', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#mms-scrape-source-name').text(name);
        $('#mms-scrape-source-modal').data('id', id);
        $('#mms-scrape-source-result').addClass('hidden');
        $('#mms-scrape-source-result p').text('');
        
        $('#mms-scrape-source-modal').css('display', 'block');
    });
    
    // Start Scraping
    $('#mms-scrape-source-confirm').on('click', function() {
        var id = $('#mms-scrape-source-modal').data('id');
        
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'madara-manga-scraper'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mms_start_manga_scrape',
                source_id: id,
                nonce: mms_data.nonce
            },
            success: function(response) {
                $('#mms-scrape-source-confirm').prop('disabled', false).text('<?php _e('Start Scraping', 'madara-manga-scraper'); ?>');
                
                if (response.success) {
                    $('#mms-scrape-source-result').removeClass('hidden').find('p').text(response.data.message);
                } else {
                    $('#mms-scrape-source-result').removeClass('hidden')
                        .find('div').removeClass('notice-success').addClass('notice-error')
                        .find('p').text(response.data.message);
                }
            },
            error: function() {
                $('#mms-scrape-source-confirm').prop('disabled', false).text('<?php _e('Start Scraping', 'madara-manga-scraper'); ?>');
                
                $('#mms-scrape-source-result').removeClass('hidden')
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