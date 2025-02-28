/**
 * Madara Manga Scraper Admin JavaScript
 * 
 * Handles all AJAX interactions and UI functionality for the admin interface.
 */
(function($) {
    'use strict';

    // Global settings
    const MMS = {
        // Initialize the admin interface
        init: function() {
            this.initTabs();
            this.initModals();
            this.initSourceActions();
            this.initQueueActions();
            this.initMangaActions();
            this.initLogActions();
            this.initSettingsActions();
        },

        // Initialize tab navigation
        initTabs: function() {
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
            
            // Initialize tabs based on URL hash or default to first tab
            var hash = window.location.hash || $('.nav-tab').first().attr('href');
            $('.nav-tab[href="' + hash + '"]').click();
        },

        // Initialize modal dialogs
        initModals: function() {
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
        },

        // Source-related actions
        initSourceActions: function() {
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
                
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: mms_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mms_start_manga_scrape',
                        source_id: id,
                        nonce: mms_data.nonce
                    },
                    success: function(response) {
                        $('#mms-scrape-source-confirm').prop('disabled', false).text('Start Scraping');
                        
                        if (response.success) {
                            $('#mms-scrape-source-result').removeClass('hidden')
                                .find('div').removeClass('notice-error').addClass('notice-success')
                                .find('p').text(response.data.message);
                            
                            // Redirect to queue page after a short delay
                            setTimeout(function() {
                                window.location.href = mms_data.admin_url + '&page=mms-queue';
                            }, 2000);
                        } else {
                            $('#mms-scrape-source-result').removeClass('hidden')
                                .find('div').removeClass('notice-success').addClass('notice-error')
                                .find('p').text(response.data.message);
                        }
                    },
                    error: function() {
                        $('#mms-scrape-source-confirm').prop('disabled', false).text('Start Scraping');
                        
                        $('#mms-scrape-source-result').removeClass('hidden')
                            .find('div').removeClass('notice-success').addClass('notice-error')
                            .find('p').text('An error occurred. Please try again.');
                    }
                });
            });
        },

        // Queue-related actions
        initQueueActions: function() {
            // Clear Queue
            $('.mms-clear-queue').on('click', function() {
                var status = $(this).data('status');
                var type = $(this).data('type');
                var statusText = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'All';
                var typeText = type ? type.charAt(0).toUpperCase() + type.slice(1) : 'All';
                
                if (confirm('Are you sure you want to clear ' + statusText + ' ' + typeText + ' queue items?')) {
                    $(this).prop('disabled', true).text('Processing...');
                    
                    $.ajax({
                        url: mms_data.ajax_url,
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
                                
                                $(this).text('Clear ' + btnStatusText + ' ' + btnTypeText + ' Queue');
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
                                
                                $(this).text('Clear ' + btnStatusText + ' ' + btnTypeText + ' Queue');
                            });
                            
                            $('#mms-queue-action-result').removeClass('hidden')
                                .find('div').removeClass('notice-success').addClass('notice-error')
                                .find('p').text('An error occurred. Please try again.');
                        }
                    });
                }
            });
            
            // Retry Failed Items
            $('.mms-retry-failed').on('click', function() {
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: mms_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mms_retry_failed',
                        nonce: mms_data.nonce
                    },
                    success: function(response) {
                        $('.mms-retry-failed').prop('disabled', false).text('Retry Failed Items');
                        
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
                        $('.mms-retry-failed').prop('disabled', false).text('Retry Failed Items');
                        
                        $('#mms-queue-action-result').removeClass('hidden')
                            .find('div').removeClass('notice-success').addClass('notice-error')
                            .find('p').text('An error occurred. Please try again.');
                    }
                });
            });
            
            // Reset Processing Items
            $('.mms-reset-processing').on('click', function() {
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: mms_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mms_reset_processing',
                        nonce: mms_data.nonce
                    },
                    success: function(response) {
                        $('.mms-reset-processing').prop('disabled', false).text('Reset Processing Items');
                        
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
                        $('.mms-reset-processing').prop('disabled', false).text('Reset Processing Items');
                        
                        $('#mms-queue-action-result').removeClass('hidden')
                            .find('div').removeClass('notice-success').addClass('notice-error')
                            .find('p').text('An error occurred. Please try again.');
                    }
                });
            });
            
            // Process Queue Now
            $('.mms-process-queue').on('click', function() {
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: mms_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mms_process_queue_now',
                        nonce: mms_data.nonce
                    },
                    success: function(response) {
                        $('.mms-process-queue').prop('disabled', false).text('Process Queue Now');
                        
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
                        $('.mms-process-queue').prop('disabled', false).text('Process Queue Now');
                        
                        $('#mms-queue-action-result').removeClass('hidden')
                            .find('div').removeClass('notice-success').addClass('notice-error')
                            .find('p').text('An error occurred. Please try again.');
                    }
                });
            });
            
            // Scrape Now
            $('.mms-scrape-now').on('click', function() {
                var id = $(this).data('id');
                var type = $(this).data('type');
                var source = $(this).data('source');
                
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: mms_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mms_scrape_now',
                        id: id,
                        type: type,
                        source_id: source,
                        nonce: mms_data.nonce
                    },
                    success: function(response) {
                        $('.mms-scrape-now').prop('disabled', false).text('Scrape Now');
                        
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
                        $('.mms-scrape-now').prop('disabled', false).text('Scrape Now');
                        alert('An error occurred. Please try again.');
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
                
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: mms_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mms_remove_queue_item',
                        id: id,
                        nonce: mms_data.nonce
                    },
                    success: function(response) {
                        $('#mms-remove-queue-confirm').prop('disabled', false).text('Remove');
                        
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
                        $('#mms-remove-queue-confirm').prop('disabled', false).text('Remove');
                        
                        $('#mms-remove-queue-result').removeClass('hidden')
                            .find('div').removeClass('notice-success').addClass('notice-error')
                            .find('p').text('An error occurred. Please try again.');
                    }
                });
            });
        },

        // Manga-related actions
        initMangaActions: function() {
            // View Manga Details
            $('.mms-view-manga-details').on('click', function(e) {
                e.preventDefault();
                
                var mangaId = $(this).data('id');
                
                $('#mms-manga-details-content').html('<div class="mms-modal-loading">Loading...</div>');
                $('#mms-manga-details-modal').css('display', 'block');
                
                $.ajax({
                    url: mms_data.ajax_url,
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
                        $('#mms-manga-details-content').html('<div class="notice notice-error inline"><p>An error occurred. Please try again.</p></div>');
                    }
                });
            });
            
            // View Chapters
            $('.mms-view-chapters').on('click', function(e) {
                e.preventDefault();
                
                var mangaId = $(this).data('id');
                
                $('#mms-chapters-content').html('<div class="mms-modal-loading">Loading...</div>');
                $('#mms-chapters-modal').css('display', 'block');
                
                $.ajax({
                    url: mms_data.ajax_url,
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
                        $('#mms-chapters-content').html('<div class="notice notice-error inline"><p>An error occurred. Please try again.</p></div>');
                    }
                });
            });
            
            // Refresh Manga
            $('.mms-refresh-manga').on('click', function() {
                var mangaId = $(this).data('id');
                var sourceId = $(this).data('source');
                
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: mms_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mms_scrape_now',
                        id: mangaId,
                        type: 'manga',
                        source_id: sourceId,
                        nonce: mms_data.nonce
                    },
                    success: function(response) {
                        $('.mms-refresh-manga').prop('disabled', false).text('Refresh');
                        
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
                        $('.mms-refresh-manga').prop('disabled', false).text('Refresh');
                        alert('An error occurred. Please try again.');
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
                
                $(this).prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: mms_data.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mms_remove_manga',
                        manga_id: mangaId,
                        nonce: mms_data.nonce
                    },
                    success: function(response) {
                        $('#mms-remove-manga-confirm').prop('disabled', false).text('Remove');
                        
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
                        $('#mms-remove-manga-confirm').prop('disabled', false).text('Remove');
                        
                        $('#mms-remove-manga-result').removeClass('hidden')
                            .find('div').removeClass('notice-success').addClass('notice-error')
                            .find('p').text('An error occurred. Please try again.');
                    }
                });
            });
        },

        // Log-related actions
        initLogActions: function() {
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
                
                if (confirm('Are you sure you want to clear ' + levelText + typeText + '?')) {
                    $(this).prop('disabled', true).text('Processing...');
                    
                    $.ajax({
                        url: mms_data.ajax_url,
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
                                
                                $(this).text('Clear ' + btnLevelText + btnTypeText);
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
                                
                                $(this).text('Clear ' + btnLevelText + btnTypeText);
                            });
                            
                            $('#mms-log-action-result').removeClass('hidden')
                                .find('div').removeClass('notice-success').addClass('notice-error')
                                .find('p').text('An error occurred. Please try again.');
                        }
                    });
                }
            });
        },

        // Settings-related actions
        initSettingsActions: function() {
            // Run Now Buttons
            $('.mms-run-now').on('click', function() {
                var task = $(this).data('task');
                var taskDisplay = task === 'check_sources' ? 'Sources Check' : 'Queue Processing';
                
                $(this).prop('disabled', true).text('Running...');
                
                $.ajax({
                    url: mms_data.ajax_url,
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
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MMS.init();
    });

})(jQuery);