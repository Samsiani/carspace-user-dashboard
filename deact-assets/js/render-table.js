/**
 * CarSpace Dashboard - Table Rendering Scripts
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 * Last updated: 2025-04-18 23:52:14 by Samsiani
 */

jQuery(document).ready(function($) {
        // Save current view preference to local storage
        function saveViewPreference(view) {
            localStorage.setItem('carspace_view_preference', view);
            
            // Show toast notification
            const viewSavedToast = new bootstrap.Toast(document.getElementById('viewSavedToast'));
            viewSavedToast.show();
        }
        
        // Load view preference from local storage
        function loadViewPreference() {
            const preference = localStorage.getItem('carspace_view_preference');
            if (preference === 'card') {
                $('#table-view').addClass('d-none');
                $('#card-view').removeClass('d-none');
                $('#table-view-btn').removeClass('active').attr('aria-pressed', 'false');
                $('#card-view-btn').addClass('active').attr('aria-pressed', 'true');
            } else {
                $('#card-view').addClass('d-none');
                $('#table-view').removeClass('d-none');
                $('#card-view-btn').removeClass('active').attr('aria-pressed', 'false');
                $('#table-view-btn').addClass('active').attr('aria-pressed', 'true');
            }
        }
        
        // Toggle view
        $('#table-view-btn').on('click', function() {
            $('#card-view').addClass('d-none');
            $('#table-view').removeClass('d-none');
            $('#card-view-btn').removeClass('active').attr('aria-pressed', 'false');
            $(this).addClass('active').attr('aria-pressed', 'true');
            saveViewPreference('table');
        });
        
        $('#card-view-btn').on('click', function() {
            $('#table-view').addClass('d-none');
            $('#card-view').removeClass('d-none');
            $('#table-view-btn').removeClass('active').attr('aria-pressed', 'false');
            $(this).addClass('active').attr('aria-pressed', 'true');
            saveViewPreference('card');
        });
        
        // Load saved view preference on page load
        loadViewPreference();
        
        // Initialize clipboard.js
        new ClipboardJS('.copy-vin').on('success', function(e) {
            // Show feedback
            $(e.trigger).addClass('copy-success');
            
            // Show toast notification
            const vinCode = $(e.trigger).data('clipboard-text');
            $('#vinToastText').text(vinCode);
            const vinToast = new bootstrap.Toast(document.getElementById('vinCopiedToast'));
            vinToast.show();
            
            // Reset copy button after animation completes
            setTimeout(function() {
                $(e.trigger).removeClass('copy-success');
            }, 1000);
            
            e.clearSelection();
        });
        
        // Initialize tooltips
        $('[title]').tooltip();
        
        // Hover effect handled by CSS :hover (works on AJAX-loaded rows too)

        // Initialize date range picker for d/m/Y format
        $('#filter_date_range').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'DD/MM/YYYY'  // Match ACF date format d/m/Y
            }
        });
        
        $('#filter_date_range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            filterTable(); // Apply filter immediately
        });
        
        $('#filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            filterTable(); // Apply filter immediately
        });
        
        // Real-time filtering (only when AJAX pagination is NOT active — server-side filtering handles it otherwise)
        if (!$('#carTable').data('cache-key')) {
            var filterDebounce;
            $('.filter-input, .filter-select').on('input change', function() {
                clearTimeout(filterDebounce);
                filterDebounce = setTimeout(filterTable, 300);
            });
        }
        
        // Save filter functionality
        $('#save-filters').on('click', function() {
            const filterName = prompt(carspaceDashboardL10n.nameYourFilter || 'Name your filter:');
            if (filterName) {
                // Get current filter values
                const filters = {
                    name: filterName,
                    status: $('#filter_status').val(),
                    vin: $('#filter_vin').val(),
                    lot: $('#filter_lot').val(),
                    container: $('#filter_container').val(),
                    dateRange: $('#filter_date_range').val()
                };
                
                // Get existing saved filters
                let savedFilters = JSON.parse(localStorage.getItem('carspace_saved_filters') || '[]');
                
                // Add new filter
                savedFilters.push(filters);
                
                // Save to local storage
                localStorage.setItem('carspace_saved_filters', JSON.stringify(savedFilters));
                
                // Update saved filters dropdown
                updateSavedFiltersDropdown();
                
                // Show saved filters section
                $('#saved-filters-container').removeClass('d-none');
                
                // Show notification
                $('#filterSavedText').text(carspaceDashboardL10n.filterSaved || 'Filter saved as: ' + filterName);
                const filterSavedToast = new bootstrap.Toast(document.getElementById('filterSavedToast'));
                filterSavedToast.show();
            }
        });
        
        // Reset filters button
        $('#reset-filters').on('click', function() {
            // Clear all filter inputs
            $('.filter-input').val('');
            $('.filter-select').val('');
            $('#filter_date_range').val('');
            
            // Reset table view
            $('.car-table tbody tr, .car-card-wrapper').removeClass('d-none');
            $('#noResults').addClass('d-none');
            
            // Update row counts
            updateRowCount();
        });
        
        // Update saved filters dropdown with options from localStorage
        function updateSavedFiltersDropdown() {
            const savedFilters = JSON.parse(localStorage.getItem('carspace_saved_filters') || '[]');
            const $select = $('#saved-filters-select');
            
            // Clear existing options except the first default one
            $select.find('option:not(:first)').remove();
            
            if (savedFilters.length > 0) {
                savedFilters.forEach((filter, index) => {
                    $select.append(`<option value="${index}">${filter.name}</option>`);
                });
                
                // Show saved filters section
                $('#saved-filters-container').removeClass('d-none');
                $('#delete-saved-filter').prop('disabled', true);
            } else {
                // Hide if no saved filters
                $('#saved-filters-container').addClass('d-none');
            }
        }
        
        // Apply saved filter when selected
        $('#saved-filters-select').on('change', function() {
            const selectedIndex = $(this).val();
            if (selectedIndex !== '') {
                const savedFilters = JSON.parse(localStorage.getItem('carspace_saved_filters') || '[]');
                const selectedFilter = savedFilters[selectedIndex];
                
                // Apply filter values
                $('#filter_status').val(selectedFilter.status);
                $('#filter_vin').val(selectedFilter.vin);
                $('#filter_lot').val(selectedFilter.lot);
                $('#filter_container').val(selectedFilter.container);
                $('#filter_date_range').val(selectedFilter.dateRange);
                
                // Apply filters to table
                filterTable();
                
                // Enable delete button
                $('#delete-saved-filter').prop('disabled', false);
            } else {
                // Disable delete button when default option selected
                $('#delete-saved-filter').prop('disabled', true);
            }
        });
        
        // Delete saved filter
        $('#delete-saved-filter').on('click', function() {
            const selectedIndex = $('#saved-filters-select').val();
            if (selectedIndex !== '' && confirm(carspaceDashboardL10n.confirmFilterDelete || 'Are you sure you want to delete this saved filter?')) {
                // Get existing saved filters
                let savedFilters = JSON.parse(localStorage.getItem('carspace_saved_filters') || '[]');
                
                // Remove selected filter
                savedFilters.splice(selectedIndex, 1);
                
                // Save back to local storage
                localStorage.setItem('carspace_saved_filters', JSON.stringify(savedFilters));
                
                // Update dropdown
                updateSavedFiltersDropdown();
                
                // Reset selection
                $('#saved-filters-select').val('');
                
                // Disable delete button
                $(this).prop('disabled', true);
            }
        });
        
        // Initialize saved filters dropdown
        updateSavedFiltersDropdown();
        
        // Add keyboard navigation support
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.modal').modal('hide');
            }
        });
        
        // Focus management for modals
        $('.modal').on('shown.bs.modal', function() {
            $(this).find('.btn-close').focus();
        });
        
        // Filter function for both table and card views
        function filterTable() {
            let statusFilter = $('#filter_status').val().toLowerCase();
            let vinFilter = $('#filter_vin').val().toLowerCase().trim();
            let lotFilter = $('#filter_lot').val().toLowerCase().trim();
            let containerFilter = $('#filter_container').val().toLowerCase().trim();
            let dateFilter = $('#filter_date_range').val();
            
            let visibleCount = 0;
            
            // Parse date range if it exists
            let startDate = null;
            let endDate = null;
            
            if (dateFilter) {
                let dates = dateFilter.split(' - ');
                if (dates.length === 2) {
                    // Parse dates in DD/MM/YYYY format
                    let startParts = dates[0].split('/');
                    let endParts = dates[1].split('/');
                    
                    if (startParts.length === 3 && endParts.length === 3) {
                        // Create YYYY-MM-DD format for comparison
                        startDate = startParts[2] + '-' + startParts[1] + '-' + startParts[0];
                        endDate = endParts[2] + '-' + endParts[1] + '-' + endParts[0];
                    }
                }
            }
            
            // Filter table rows
            $('#carTable tbody tr').each(function() {
                let row = $(this);
                let status = row.data('status') ? row.data('status').toString().toLowerCase() : '';
                let vin = row.data('vin') ? row.data('vin').toString().toLowerCase() : '';
                let lot = row.data('lot') ? row.data('lot').toString().toLowerCase() : '';
                let container = row.data('container') ? row.data('container').toString().toLowerCase() : '';
                let purchaseDate = row.data('purchase-date');
                
                // Check if row matches all filters
                let statusMatch = statusFilter === '' || status === statusFilter;
                let vinMatch = vinFilter === '' || vin.includes(vinFilter);
                let lotMatch = lotFilter === '' || lot.includes(lotFilter);
                let containerMatch = containerFilter === '' || container.includes(containerFilter);
                
                // Date match (only if we have both dates and purchase date)
                let dateMatch = true;
                if (startDate && endDate && purchaseDate) {
                    dateMatch = (purchaseDate >= startDate && purchaseDate <= endDate);
                }
                
                if (statusMatch && vinMatch && lotMatch && containerMatch && dateMatch) {
                    row.removeClass('d-none');
                    visibleCount++;
                } else {
                    row.addClass('d-none');
                }
            });
            
            // Also filter card view
            $('.car-card-wrapper').each(function() {
                let card = $(this);
                let status = card.data('status') ? card.data('status').toString().toLowerCase() : '';
                let vin = card.data('vin') ? card.data('vin').toString().toLowerCase() : '';
                let lot = card.data('lot') ? card.data('lot').toString().toLowerCase() : '';
                let container = card.data('container') ? card.data('container').toString().toLowerCase() : '';
                
                // Check if card matches all filters
                let statusMatch = statusFilter === '' || status === statusFilter;
                let vinMatch = vinFilter === '' || vin.includes(vinFilter);
                let lotMatch = lotFilter === '' || lot.includes(lotFilter);
                let containerMatch = containerFilter === '' || container.includes(containerFilter);
                
                if (statusMatch && vinMatch && lotMatch && containerMatch) {
                    card.removeClass('d-none');
                } else {
                    card.addClass('d-none');
                }
            });
            
            // Show or hide no results message
            if (visibleCount === 0) {
                $('#noResults').removeClass('d-none');
            } else {
                $('#noResults').addClass('d-none');
            }
            
            updateRowCount();
        }
        
        // Update row counts for even/odd styling
        function updateRowCount() {
            let count = 0;
            $('#carTable tbody tr:not(.d-none)').each(function() {
                $(this).removeClass('even odd');
                if (++count % 2 === 0) {
                    $(this).addClass('even');
                } else {
                    $(this).addClass('odd');
                }
            });
        }
        
        // Add table sorting functionality
        $('.car-table th.sortable').click(function() {
            const table = $(this).closest('table');
            const rows = table.find('tbody tr').toArray();
            const column = $(this).data('sort');
            const dataType = $(this).data('type') || 'string';
            const direction = $(this).hasClass('asc') ? -1 : 1;

            // Toggle sort direction
            $(this).toggleClass('asc desc');
            if($(this).hasClass('asc')) {
                $(this).removeClass('desc');
            } else {
                $(this).addClass('desc').removeClass('asc');
            }

            // Remove sort indicators from other columns
            $(this).siblings().removeClass('asc desc');

            // Sort rows
            rows.sort((a, b) => {
                const cellA = $(a).data(column) || '';
                const cellB = $(b).data(column) || '';
                
                if (dataType === 'number') {
                    return direction * (parseFloat(cellA) - parseFloat(cellB));
                } else if (dataType === 'date') {
                    return direction * (cellA > cellB ? 1 : -1);
                } else {
                    return direction * String(cellA).localeCompare(String(cellB));
                }
            });

            // Re-append in new order and update even/odd classes
            $.each(rows, function(index, row) {
                $(row).removeClass('even odd').addClass(index % 2 ? 'even' : 'odd');
                table.children('tbody').append(row);
            });
        });
        
        // Process initial sort based on data attribute
        const defaultSort = $('#carTable').data('default-sort');
        const defaultDirection = $('#carTable').data('default-direction');
        if (defaultSort) {
            const sortColumn = $('.car-table th[data-sort="' + defaultSort + '"]');
            if (sortColumn.length) {
                // Add appropriate class based on direction
                if (defaultDirection === 'desc') {
                    sortColumn.addClass('desc');
                } else {
                    sortColumn.addClass('asc');
                }
                // Trigger click to sort
                sortColumn.click();
            }
        }
        
        // Handle expandable card sections
        $('.expand-btn').on('click', function() {
            const icon = $(this).find('svg');
            if ($(this).attr('aria-expanded') === 'false') {
                icon.css('transform', 'rotate(180deg)');
            } else {
                icon.css('transform', 'rotate(0)');
            }
        });
        
        // Modal handlers are in table-assets.php and dashboard.js (AJAX-based)
    });