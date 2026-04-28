/**
 * Carspace Dashboard JavaScript
 *
 * Handles all interactive functionality on the dashboard
 * 
 * @package Carspace_Dashboard
 * @version 3.3.0
 */

"use strict";

(function($) {
    // Initialize when document is ready
    $(document).ready(function() {
        
        // Initialize modules
        CarspaceDashboard.init();
    });
    
    /**
     * Carspace Dashboard main object
     */
    const CarspaceDashboard = {
        // Track if notifications have been checked
        notificationsLoaded: false,
        
        // Initialize all dashboard functionality
        init: function() {
            this.setupNonceRefresh();
            this.setupNotificationHandlers();
            this.setupModalHandlers();
            this.setupTableSorting();
            this.setupResponsiveHandlers();
            
            // Notification polling handled by live-dashboard-notifications.js
        },
        
        /**
         * Set up periodic nonce refresh to maintain security
         */
        setupNonceRefresh: function() {
            // Refresh nonce every 10 minutes
            this._nonceInterval = setInterval(function() {
                $.ajax({
                    url: carspace_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'refresh_carspace_nonce',
                        _: Date.now() // avoid caching
                    },
                    success: function(response) {
                        if (response.success && response.data.nonce) {
                            carspace_ajax.nonce = response.data.nonce;
                        } else {
                        }
                    },
                    error: function(xhr, status, error) {
                    }
                });
            }, 10 * 60 * 1000); // every 10 minutes

            // Clean up on page unload
            $(window).on('beforeunload', function() {
                clearInterval(CarspaceDashboard._nonceInterval);
            });
        },
        
        /**
         * Set up notification related event handlers
         */
        setupNotificationHandlers: function() {
            // Mark single notification as read
            $(document).on('click', '.mark-notification-read', function(e) {
                e.preventDefault();
                const button = $(this);
                const notificationId = button.data('notification-id');
                
                if (!notificationId) {
                    return;
                }
                
                CarspaceDashboard.markNotificationAsRead(notificationId, button);
            });
            
            // Mark all notifications as read
            $(document).on('click', '#mark-all-read', function(e) {
                e.preventDefault();
                CarspaceDashboard.markAllNotificationsAsRead();
            });
            
            // Delete notification
            $(document).on('click', '.delete-notification', function(e) {
                e.preventDefault();
                const button = $(this);
                const notificationId = button.data('notification-id');
                
                if (!notificationId) {
                    return;
                }
                
                if (confirm(carspace_notification_data.i18n_confirm_delete || 'Are you sure you want to delete this notification?')) {
                    CarspaceDashboard.deleteNotification(notificationId, button);
                }
            });
            
            // Notification polling handled by live-dashboard-notifications.js
        },
        
        /**
         * Mark a notification as read
         * 
         * @param {number} notificationId The notification ID
         * @param {object} button jQuery button element that was clicked
         */
        markNotificationAsRead: function(notificationId, button) {
            $.ajax({
                url: carspace_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mark_notification_as_read',
                    nonce: carspace_ajax.nonce,
                    notification_id: notificationId,
                    _: Date.now()
                },
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('.notification-card')
                              .addClass('read')
                              .find('.mark-read-btn')
                              .fadeOut();
                    } else {
                        button.html('Error').addClass('btn-danger');
                    }
                },
                error: function(xhr, status, error) {
                    button.html('Error').addClass('btn-danger');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        },
        
        /**
         * Delete a notification
         * 
         * @param {number} notificationId The notification ID
         * @param {object} button jQuery button element that was clicked
         */
        deleteNotification: function(notificationId, button) {
            $.ajax({
                url: carspace_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_notification',
                    nonce: carspace_ajax.nonce,
                    notification_id: notificationId,
                    _: Date.now()
                },
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('.notification-card').fadeOut(400, function() {
                            $(this).remove();
                            
                            // If no more notifications, show empty message
                            if ($('.notification-card').length === 0) {
                                $('.notification-list').html('<p class="text-center mt-4">' + 
                                    (carspace_notification_data.i18n_no_notifications || 'No notifications found.') + '</p>');
                            }
                        });
                    } else {
                        button.html('<i class="fas fa-exclamation-circle"></i>').addClass('btn-danger');
                        setTimeout(function() {
                            button.html('<i class="fas fa-trash-alt"></i>').removeClass('btn-danger');
                        }, 2000);
                    }
                },
                error: function(xhr, status, error) {
                    button.html('<i class="fas fa-exclamation-circle"></i>').addClass('btn-danger');
                    setTimeout(function() {
                        button.html('<i class="fas fa-trash-alt"></i>').removeClass('btn-danger');
                    }, 2000);
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        },
        
        /**
         * Mark all notifications as read
         */
        markAllNotificationsAsRead: function() {
            const button = $('#mark-all-read');
            
            $.ajax({
                url: carspace_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mark_all_notifications_as_read',
                    nonce: carspace_ajax.nonce,
                    _: Date.now()
                },
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        $('.notification-card').addClass('read');
                        $('.mark-notification-read').closest('.mark-read-btn').fadeOut();
                        
                        button.html('✓ All Read').addClass('btn-success');
                        setTimeout(function() {
                            button.html('Mark All as Read').removeClass('btn-success');
                        }, 2000);
                        
                        // Update the notification badge if present
                        $('.notification-badge').text('0').addClass('d-none');
                        
                        // Store updated count
                        localStorage.setItem('lastNotificationCount', '0');
                    } else {
                        button.html('Error').addClass('btn-danger');
                        setTimeout(function() {
                            button.html('Mark All as Read').removeClass('btn-danger');
                        }, 2000);
                    }
                },
                error: function(xhr, status, error) {
                    button.html('Error').addClass('btn-danger');
                    setTimeout(function() {
                        button.html('Mark All as Read').removeClass('btn-danger');
                    }, 2000);
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        },
        
        /**
         * Set up modal handlers for car data display
         */
        setupModalHandlers: function() {
            // Skip if car table is present — table-assets.php handles modals there
            if ($('#carTable').length) {
                return;
            }

            // Image gallery popup
            $(document).on('click', '.view-images', function() {
                const productId = $(this).data('product-id');
                CarspaceDashboard.loadCarImagesPopup(productId);
            });

            // Shipping info popup
            $(document).on('click', '.shipping-info', function() {
                const productId = $(this).data('product-id');
                CarspaceDashboard.loadShippingInfoPopup(productId);
            });

            // Clear modals when hidden
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('.modal-body').html('<p class="text-center">Loading...</p>');
            });
        },
        
        /**
         * Load car images for gallery popup
         * 
         * @param {number} productId Product ID
         */
        loadCarImagesPopup: function(productId) {
            const modalBody = $('#image-gallery-body');
            modalBody.html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Loading images...</p></div>');
            
            $.ajax({
                url: carspace_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_car_images_popup',
                    product_id: productId,
                    nonce: carspace_ajax.nonce,
                    _: Date.now()
                },
                success: function(response) {
                    if (response.success) {
                        modalBody.html(response.data.html);
                        
                        // Initialize GLightbox again after loading images
                        if (typeof GLightbox === 'function') {
                            const lightbox = GLightbox({
                                selector: '.glightbox',
                                touchNavigation: true,
                                loop: true,
                                openEffect: 'zoom',
                                closeEffect: 'fade',
                                zoomable: true,
                                draggable: true
                            });
                        }
                    } else {
                        modalBody.html('<div class="alert alert-danger">' + (response.data.message || 'Error loading images.') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    modalBody.html('<div class="alert alert-danger">Error: ' + error + '</div>');
                }
            });
        },
        
        /**
         * Load shipping information popup
         * 
         * @param {number} productId Product ID
         */
        loadShippingInfoPopup: function(productId) {
            const modalBody = $('#shipping-info-body');
            modalBody.html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Loading shipping info...</p></div>');
            
            $.ajax({
                url: carspace_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_shipping_info_popup',
                    product_id: productId,
                    nonce: carspace_ajax.nonce,
                    _: Date.now()
                },
                success: function(response) {
                    if (response.success) {
                        modalBody.html(response.data.html);
                    } else {
                        modalBody.html('<div class="alert alert-danger">' + (response.data.message || 'Error loading shipping info.') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    modalBody.html('<div class="alert alert-danger">Error: ' + error + '</div>');
                }
            });
        },
        
        /**
         * Set up sortable table functionality
         */
        setupTableSorting: function() {
            // Only initialize if we have tables with sortable columns
            if ($('.car-table th.sortable').length === 0) {
                return;
            }
            
            // Handle sortable column clicks
            $(document).on('click', 'th.sortable', function() {
                const table = $(this).closest('table');
                const index = $(this).index();
                const isAsc = $(this).hasClass('asc');
                
                // Remove sorting classes from all headers
                table.find('th.sortable').removeClass('asc desc');
                
                // Set new sorting class
                $(this).addClass(isAsc ? 'desc' : 'asc');
                
                // Get data type (default to string)
                const dataType = $(this).data('type') || 'string';
                
                // Sort the table
                CarspaceDashboard.sortTable(table, index, !isAsc, dataType);
            });
            
            // Set default sort if specified
            const defaultSortColumn = $('.car-table').data('default-sort');
            if (defaultSortColumn) {
                const direction = $('.car-table').data('default-direction') === 'desc' ? false : true;
                const column = $('.car-table th[data-sort="' + defaultSortColumn + '"]');
                
                if (column.length) {
                    column.addClass(direction ? 'asc' : 'desc');
                    CarspaceDashboard.sortTable(column.closest('table'), column.index(), direction, column.data('type') || 'string');
                }
            }
        },
        
        /**
         * Sort a table by column index
         * 
         * @param {object} table jQuery table object
         * @param {number} columnIndex Column index to sort by
         * @param {boolean} ascending Sort direction (true for ascending)
         * @param {string} dataType Data type ('string', 'number', 'date', etc.)
         */
        sortTable: function(table, columnIndex, ascending, dataType) {
            const rows = table.find('tbody tr').get();
            
            rows.sort(function(a, b) {
                const aValue = $(a).find('td').eq(columnIndex).text().trim();
                const bValue = $(b).find('td').eq(columnIndex).text().trim();
                
                let result;
                
                // Sort based on data type
                switch (dataType) {
                    case 'number':
                        const aNum = parseFloat(aValue.replace(/[^0-9.-]+/g, ''));
                        const bNum = parseFloat(bValue.replace(/[^0-9.-]+/g, ''));
                        result = isNaN(aNum) || isNaN(bNum) ? aValue.localeCompare(bValue) : aNum - bNum;
                        break;
                    case 'date':
                        const aDate = aValue ? new Date(aValue) : new Date(0);
                        const bDate = bValue ? new Date(bValue) : new Date(0);
                        result = aDate - bDate;
                        break;
                    default: // string
                        result = aValue.localeCompare(bValue);
                }
                
                return ascending ? result : -result;
            });
            
            // Reorder the table
            $.each(rows, function(index, row) {
                table.find('tbody').append(row);
            });
            
            // Update row striping
            table.find('tbody tr').removeClass('odd even').each(function(index) {
                $(this).addClass(index % 2 === 0 ? 'even' : 'odd');
            });
        },
        
        /**
         * Set up responsive handlers
         */
        setupResponsiveHandlers: function() {
            // Handle table scrolling on mobile
            if (window.innerWidth < 768) {
                $('.car-table-wrapper').each(function() {
                    const wrapper = $(this);
                    if (wrapper.find('table').width() > wrapper.width()) {
                        wrapper.addClass('has-scroll').append('<div class="swipe-hint">← Swipe to see more →</div>');
                    }
                });
            }
            
            // Toggle mobile menu
            $(document).on('click', '.mobile-menu-toggle', function(e) {
                e.preventDefault();
                $('.woocommerce-MyAccount-navigation').toggleClass('active');
                $(this).toggleClass('active');
            });
        }
    };
})(jQuery);