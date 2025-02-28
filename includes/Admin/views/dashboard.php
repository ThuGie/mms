<?php
/**
 * Dashboard view
 * 
 * @package Madara Manga Scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// No class definitions in views
?>
<div class="wrap mms-dashboard">
    <h1><?php _e('Madara Manga Scraper', 'madara-manga-scraper'); ?></h1>
    
    <div class="mms-dashboard-header">
        <div class="mms-welcome-panel">
            <h2><?php _e('Welcome to Madara Manga Scraper', 'madara-manga-scraper'); ?></h2>
            <p class="about-description"><?php _e('A comprehensive tool for scraping and importing manga from Madara-based websites.', 'madara-manga-scraper'); ?></p>
            
            <div class="mms-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=mms-sources'); ?>" class="button button-primary"><?php _e('Manage Sources', 'madara-manga-scraper'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=mms-queue'); ?>" class="button"><?php _e('View Queue', 'madara-manga-scraper'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=mms-manga'); ?>" class="button"><?php _e('Manage Manga', 'madara-manga-scraper'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=mms-settings'); ?>" class="button"><?php _e('Settings', 'madara-manga-scraper'); ?></a>
            </div>
        </div>
    </div>
    
    <div class="mms-dashboard-stats">
        <div class="mms-stat-box">
            <h3><?php _e('Sources', 'madara-manga-scraper'); ?></h3>
            <div class="mms-stat-value"><?php echo intval($stats['sources']); ?></div>
            <a href="<?php echo admin_url('admin.php?page=mms-sources'); ?>" class="mms-stat-link"><?php _e('Manage Sources', 'madara-manga-scraper'); ?></a>
        </div>
        
        <div class="mms-stat-box">
            <h3><?php _e('Manga', 'madara-manga-scraper'); ?></h3>
            <div class="mms-stat-value"><?php echo intval($stats['manga']); ?></div>
            <a href="<?php echo admin_url('admin.php?page=mms-manga'); ?>" class="mms-stat-link"><?php _e('Manage Manga', 'madara-manga-scraper'); ?></a>
        </div>
        
        <div class="mms-stat-box">
            <h3><?php _e('Chapters', 'madara-manga-scraper'); ?></h3>
            <div class="mms-stat-value"><?php echo intval($stats['chapters']); ?></div>
            <a href="<?php echo admin_url('admin.php?page=mms-manga'); ?>" class="mms-stat-link"><?php _e('View Chapters', 'madara-manga-scraper'); ?></a>
        </div>
        
        <div class="mms-stat-box">
            <h3><?php _e('Queue', 'madara-manga-scraper'); ?></h3>
            <div class="mms-stat-value"><?php echo intval($stats['queue']['total']); ?></div>
            <a href="<?php echo admin_url('admin.php?page=mms-queue'); ?>" class="mms-stat-link"><?php _e('Manage Queue', 'madara-manga-scraper'); ?></a>
        </div>
    </div>
    
    <div class="mms-dashboard-content">
        <div class="mms-dashboard-column">
            <div class="mms-dashboard-box">
                <h3><?php _e('Queue Status', 'madara-manga-scraper'); ?></h3>
                
                <table class="mms-dashboard-table">
                    <tr>
                        <th><?php _e('Status', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Manga', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Chapters', 'madara-manga-scraper'); ?></th>
                        <th><?php _e('Total', 'madara-manga-scraper'); ?></th>
                    </tr>
                    <tr>
                        <td><?php _e('Pending', 'madara-manga-scraper'); ?></td>
                        <td><?php echo intval($stats['queue']['manga']['pending']); ?></td>
                        <td><?php echo intval($stats['queue']['chapter']['pending']); ?></td>
                        <td><?php echo intval($stats['queue']['pending']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Processing', 'madara-manga-scraper'); ?></td>
                        <td><?php echo intval($stats['queue']['manga']['processing']); ?></td>
                        <td><?php echo intval($stats['queue']['chapter']['processing']); ?></td>
                        <td><?php echo intval($stats['queue']['processing']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Completed', 'madara-manga-scraper'); ?></td>
                        <td><?php echo intval($stats['queue']['manga']['completed']); ?></td>
                        <td><?php echo intval($stats['queue']['chapter']['completed']); ?></td>
                        <td><?php echo intval($stats['queue']['completed']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Failed', 'madara-manga-scraper'); ?></td>
                        <td><?php echo intval($stats['queue']['manga']['failed']); ?></td>
                        <td><?php echo intval($stats['queue']['chapter']['failed']); ?></td>
                        <td><?php echo intval($stats['queue']['failed']); ?></td>
                    </tr>
                </table>
                
                <a href="<?php echo admin_url('admin.php?page=mms-queue'); ?>" class="button"><?php _e('View Queue', 'madara-manga-scraper'); ?></a>
            </div>
            
            <div class="mms-dashboard-box">
                <h3><?php _e('Recent Logs', 'madara-manga-scraper'); ?></h3>
                
                <?php if (empty($recent_logs)) : ?>
                    <p><?php _e('No logs found.', 'madara-manga-scraper'); ?></p>
                <?php else : ?>
                    <table class="mms-dashboard-table">
                        <tr>
                            <th><?php _e('Time', 'madara-manga-scraper'); ?></th>
                            <th><?php _e('Level', 'madara-manga-scraper'); ?></th>
                            <th><?php _e('Message', 'madara-manga-scraper'); ?></th>
                        </tr>
                        <?php foreach ($recent_logs as $log) : ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at)); ?></td>
                                <td><span class="mms-log-level mms-log-level-<?php echo esc_attr($log->level); ?>"><?php echo strtoupper(esc_html($log->level)); ?></span></td>
                                <td><?php echo esc_html($log->message); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <a href="<?php echo admin_url('admin.php?page=mms-logs'); ?>" class="button"><?php _e('View All Logs', 'madara-manga-scraper'); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mms-dashboard-column">
            <div class="mms-dashboard-box">
                <h3><?php _e('Recent Manga', 'madara-manga-scraper'); ?></h3>
                
                <?php if (empty($recent_manga)) : ?>
                    <p><?php _e('No manga found.', 'madara-manga-scraper'); ?></p>
                <?php else : ?>
                    <table class="mms-dashboard-table">
                        <tr>
                            <th><?php _e('Title', 'madara-manga-scraper'); ?></th>
                            <th><?php _e('Chapters', 'madara-manga-scraper'); ?></th>
                            <th><?php _e('Status', 'madara-manga-scraper'); ?></th>
                        </tr>
                        <?php foreach ($recent_manga as $manga) : ?>
                            <?php
                            // Get chapter count using the passed database object directly
                            $chapter_count = $db->count('chapters', array('manga_id' => $manga->id));
                            
                            // Get post status
                            $post_status = $manga->wp_post_id ? get_post_status($manga->wp_post_id) : 'none';
                            ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($manga->title); ?>
                                    <?php if ($manga->wp_post_id) : ?>
                                        <a href="<?php echo get_permalink($manga->wp_post_id); ?>" target="_blank"><span class="dashicons dashicons-external"></span></a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo intval($chapter_count); ?></td>
                                <td>
                                    <?php if ($manga->wp_post_id) : ?>
                                        <span class="mms-status mms-status-published"><?php _e('Published', 'madara-manga-scraper'); ?></span>
                                    <?php else : ?>
                                        <span class="mms-status mms-status-pending"><?php _e('Pending', 'madara-manga-scraper'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <a href="<?php echo admin_url('admin.php?page=mms-manga'); ?>" class="button"><?php _e('View All Manga', 'madara-manga-scraper'); ?></a>
                <?php endif; ?>
            </div>
            
            <div class="mms-dashboard-box">
                <h3><?php _e('Recent Chapters', 'madara-manga-scraper'); ?></h3>
                
                <?php if (empty($recent_chapters)) : ?>
                    <p><?php _e('No chapters found.', 'madara-manga-scraper'); ?></p>
                <?php else : ?>
                    <table class="mms-dashboard-table">
                        <tr>
                            <th><?php _e('Manga', 'madara-manga-scraper'); ?></th>
                            <th><?php _e('Chapter', 'madara-manga-scraper'); ?></th>
                            <th><?php _e('Status', 'madara-manga-scraper'); ?></th>
                        </tr>
                        <?php foreach ($recent_chapters as $chapter) : ?>
                            <?php
                            // Get manga title using the passed database object directly
                            $manga = $db->get_row('manga', array('id' => $chapter->manga_id));
                            $manga_title = $manga ? $manga->title : __('Unknown', 'madara-manga-scraper');
                            
                            // Get chapter status
                            $status = $chapter->processed ? __('Processed', 'madara-manga-scraper') : ($chapter->downloaded ? __('Downloaded', 'madara-manga-scraper') : __('Pending', 'madara-manga-scraper'));
                            ?>
                            <tr>
                                <td><?php echo esc_html($manga_title); ?></td>
                                <td><?php echo esc_html($chapter->chapter_number); ?></td>
                                <td>
                                    <?php if ($chapter->processed) : ?>
                                        <span class="mms-status mms-status-processed"><?php echo $status; ?></span>
                                    <?php elseif ($chapter->downloaded) : ?>
                                        <span class="mms-status mms-status-downloaded"><?php echo $status; ?></span>
                                    <?php else : ?>
                                        <span class="mms-status mms-status-pending"><?php echo $status; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <a href="<?php echo admin_url('admin.php?page=mms-manga'); ?>" class="button"><?php _e('View All Chapters', 'madara-manga-scraper'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* Dashboard Styles */
    .mms-dashboard-header {
        margin-bottom: 20px;
    }
    
    .mms-welcome-panel {
        padding: 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
    }
    
    .mms-quick-actions {
        margin-top: 15px;
    }
    
    .mms-quick-actions .button {
        margin-right: 10px;
    }
    
    .mms-dashboard-stats {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px 20px;
    }
    
    .mms-stat-box {
        flex: 1;
        min-width: 150px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        padding: 15px;
        margin: 0 10px 20px;
        text-align: center;
    }
    
    .mms-stat-value {
        font-size: 32px;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .mms-stat-link {
        display: block;
        margin-top: 10px;
    }
    
    .mms-dashboard-content {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .mms-dashboard-column {
        flex: 1;
        min-width: 45%;
        padding: 0 10px;
    }
    
    .mms-dashboard-box {
        background-color: #fff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .mms-dashboard-box h3 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .mms-dashboard-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    .mms-dashboard-table th, .mms-dashboard-table td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #eee;
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
    
    .mms-status-processed {
        background-color: #e5f5fa;
        color: #0090b2;
    }
    
    .mms-status-downloaded {
        background-color: #e5e5fa;
        color: #4b0082;
    }
    
    /* Responsive */
    @media screen and (max-width: 782px) {
        .mms-dashboard-stats, .mms-dashboard-content {
            flex-direction: column;
        }
        
        .mms-stat-box, .mms-dashboard-column {
            min-width: calc(100% - 20px);
        }
    }
</style>