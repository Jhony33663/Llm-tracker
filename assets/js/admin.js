/**
 * LLM Tracker Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize tooltips
    initTooltips();
    
    // Initialize charts
    initCharts();
    
    // Initialize auto-refresh
    initAutoRefresh();
    
    // Initialize modals
    initModals();
    
    // Initialize filters
    initFilters();
    
    // Initialize settings tabs
    initSettingsTabs();
    
    // Initialize bulk actions
    initBulkActions();
});

/**
 * Initialize tooltips
 */
function initTooltips() {
    jQuery('.llm-tracker-tooltip').each(function() {
        var $this = jQuery(this);
        var tooltip = $this.data('tooltip');
        
        if (tooltip) {
            $this.on('mouseenter', function() {
                var $tooltip = jQuery('<div class="llm-tracker-tooltip-popup">' + tooltip + '</div>');
                jQuery('body').append($tooltip);
                
                var position = $this.offset();
                $tooltip.css({
                    position: 'absolute',
                    top: position.top - $tooltip.outerHeight() - 10,
                    left: position.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                    zIndex: 10000
                });
            }).on('mouseleave', function() {
                jQuery('.llm-tracker-tooltip-popup').remove();
            });
        }
    });
}

/**
 * Initialize charts
 */
function initCharts() {
    // Chart.js is loaded via CDN, check if available
    if (typeof Chart === 'undefined') {
        console.log('Chart.js not loaded, skipping chart initialization');
        return;
    }
    
    // Initialize top bots chart if canvas exists
    var ctx = document.getElementById('topBotsChart');
    if (ctx) {
        // Chart data should be passed from PHP
        // This is handled in the dashboard.php file
    }
    
    // Initialize visits timeline chart if canvas exists
    var timelineCtx = document.getElementById('visitsTimelineChart');
    if (timelineCtx) {
        // Timeline chart data should be passed from PHP
    }
}

/**
 * Initialize auto-refresh functionality
 */
function initAutoRefresh() {
    var $refreshToggle = jQuery('#auto-refresh-toggle');
    var $refreshStatus = jQuery('#refresh-status');
    var refreshInterval;
    
    if ($refreshToggle.length) {
        $refreshToggle.on('change', function() {
            var isEnabled = jQuery(this).is(':checked');
            
            if (isEnabled) {
                startAutoRefresh();
                $refreshStatus.text('Activo').addClass('active');
            } else {
                stopAutoRefresh();
                $refreshStatus.text('Inactivo').removeClass('active');
            }
        });
        
        // Start auto-refresh if enabled by default
        if ($refreshToggle.is(':checked')) {
            startAutoRefresh();
            $refreshStatus.text('Activo').addClass('active');
        }
    }
    
    function startAutoRefresh() {
        refreshInterval = setInterval(function() {
            refreshPage();
        }, 30000); // Refresh every 30 seconds
    }
    
    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    }
    
    function refreshPage() {
        // Show loading indicator
        jQuery('.llm-tracker-dashboard').addClass('loading');
        
        // Reload the page
        window.location.reload();
    }
}

/**
 * Initialize modals
 */
function initModals() {
    // Close modal when clicking outside
    jQuery(document).on('click', function(e) {
        if (jQuery(e.target).hasClass('llm-tracker-modal')) {
            closeModal();
        }
    });
    
    // Close modal with Escape key
    jQuery(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

/**
 * Show visit details modal
 */
function showVisitDetails(visitId) {
    var $modal = jQuery('#visit-details-modal');
    var $content = jQuery('#visit-details-content');
    
    // Show loading state
    $content.html('<div class="loading-spinner"></div> Cargando detalles...');
    $modal.show();
    
    // Fetch visit details via AJAX
    jQuery.post(ajaxurl, {
        action: 'llm_tracker_visit_details',
        visit_id: visitId,
        _ajax_nonce: llm_tracker.nonce
    }, function(response) {
        if (response.success) {
            $content.html(response.data.html);
            initTooltips(); // Re-initialize tooltips for new content
        } else {
            $content.html('<div class="llm-tracker-error">Error al cargar los detalles: ' + response.data.message + '</div>');
        }
    }).fail(function() {
        $content.html('<div class="llm-tracker-error">Error de conexión. Por favor, inténtalo de nuevo.</div>');
    });
}

/**
 * Close modal
 */
function closeVisitDetails() {
    jQuery('#visit-details-modal').hide();
}

/**
 * Initialize filters
 */
function initFilters() {
    var $filterForm = jQuery('.llm-tracker-filters form');
    
    if ($filterForm.length) {
        // Add loading state to filter submission
        $filterForm.on('submit', function() {
            jQuery('.llm-tracker-visits-table').addClass('loading');
        });
        
        // Initialize date pickers if available
        var $dateFrom = jQuery('#date_from');
        var $dateTo = jQuery('#date_to');
        
        if ($dateFrom.length && $dateTo.length) {
            // Set max date to today
            var today = new Date().toISOString().split('T')[0];
            $dateFrom.attr('max', today);
            $dateTo.attr('max', today);
            
            // Ensure date_from is not after date_to
            $dateFrom.on('change', function() {
                $dateTo.attr('min', jQuery(this).val());
            });
            
            $dateTo.on('change', function() {
                $dateFrom.attr('max', jQuery(this).val());
            });
        }
        
        // Initialize bot filter
        var $botFilter = jQuery('#filter_bot');
        if ($botFilter.length) {
            $botFilter.on('change', function() {
                // Auto-submit form when bot filter changes
                $filterForm.submit();
            });
        }
    }
}

/**
 * Initialize settings tabs
 */
function initSettingsTabs() {
    var $tabButtons = jQuery('.tab-button');
    var $tabContents = jQuery('.tab-content');
    
    $tabButtons.on('click', function() {
        var $button = jQuery(this);
        var tabId = $button.data('tab');
        
        // Update active states
        $tabButtons.removeClass('active');
        $button.addClass('active');
        
        $tabContents.removeClass('active');
        jQuery('#' + tabId).addClass('active');
        
        // Save active tab to localStorage
        localStorage.setItem('llm_tracker_active_tab', tabId);
    });
    
    // Restore active tab from localStorage
    var activeTab = localStorage.getItem('llm_tracker_active_tab');
    if (activeTab) {
        jQuery('.tab-button[data-tab="' + activeTab + '"]').click();
    }
}

/**
 * Initialize bulk actions
 */
function initBulkActions() {
    var $bulkActions = jQuery('#bulk-action-selector-top');
    var $doAction = jQuery('#doaction');
    var $checkAll = jQuery('#cb-select-all-1');
    
    if ($bulkActions.length && $doAction.length) {
        $doAction.on('click', function(e) {
            var action = $bulkActions.val();
            var $checked = jQuery('input[name="visit_ids[]"]:checked');
            
            if (!action || action === '-1') {
                e.preventDefault();
                alert('Por favor, selecciona una acción.');
                return;
            }
            
            if ($checked.length === 0) {
                e.preventDefault();
                alert('Por favor, selecciona al menos una visita.');
                return;
            }
            
            // Confirm action
            var confirmMessage = getBulkActionConfirmMessage(action, $checked.length);
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return;
            }
        });
    }
    
    if ($checkAll.length) {
        $checkAll.on('change', function() {
            var isChecked = jQuery(this).prop('checked');
            jQuery('input[name="visit_ids[]"]').prop('checked', isChecked);
            updateBulkActionState();
        });
    }
    
    // Update bulk action state when checkboxes change
    jQuery('input[name="visit_ids[]"]').on('change', updateBulkActionState);
}

/**
 * Update bulk action state
 */
function updateBulkActionState() {
    var $checked = jQuery('input[name="visit_ids[]"]:checked');
    var $doAction = jQuery('#doaction');
    
    if ($checked.length > 0) {
        $doAction.prop('disabled', false);
    } else {
        $doAction.prop('disabled', true);
    }
}

/**
 * Get confirmation message for bulk actions
 */
function getBulkActionConfirmMessage(action, count) {
    var messages = {
        'delete': '¿Estás seguro de que quieres eliminar ' + count + ' visita(s)? Esta acción no se puede deshacer.',
        'export': '¿Exportar ' + count + ' visita(s) a CSV?',
        'mark_bot': '¿Marcar ' + count + ' visita(s) como bot?',
        'mark_human': '¿Marcar ' + count + ' visita(s) como humano?'
    };
    
    return messages[action] || '¿Estás seguro de realizar esta acción en ' + count + ' visita(s)?';
}

/**
 * Export data
 */
function exportVisits(format) {
    format = format || 'csv';
    
    var $exportButton = jQuery('#export-' + format + '-button');
    var originalText = $exportButton.text();
    
    // Show loading state
    $exportButton.html('<span class="loading-spinner"></span> Exportando...').prop('disabled', true);
    
    // Create export URL
    var exportUrl = ajaxurl + '?action=llm_tracker_export&format=' + format + '&_ajax_nonce=' + llm_tracker.nonce;
    
    // Add filter parameters
    var filterParams = jQuery('.llm-tracker-filters form').serialize();
    if (filterParams) {
        exportUrl += '&' + filterParams;
    }
    
    // Trigger download
    window.location.href = exportUrl;
    
    // Reset button after delay
    setTimeout(function() {
        $exportButton.text(originalText).prop('disabled', false);
    }, 2000);
}

/**
 * Clear data
 */
function clearVisits(days) {
    days = days || 30;
    
    if (!confirm('¿Estás seguro de que quieres eliminar todas las visitas más antiguas de ' + days + ' días? Esta acción no se puede deshacer.')) {
        return;
    }
    
    var $clearButton = jQuery('#clear-visits-button');
    var originalText = $clearButton.text();
    
    // Show loading state
    $clearButton.html('<span class="loading-spinner"></span> Eliminando...').prop('disabled', true);
    
    // Send AJAX request
    jQuery.post(ajaxurl, {
        action: 'llm_tracker_clear_visits',
        days: days,
        _ajax_nonce: llm_tracker.nonce
    }, function(response) {
        if (response.success) {
            alert(response.data.message);
            location.reload();
        } else {
            alert('Error: ' + response.data.message);
        }
    }).fail(function() {
        alert('Error de conexión. Por favor, inténtalo de nuevo.');
    }).always(function() {
        $clearButton.text(originalText).prop('disabled', false);
    });
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showNotification('Copiado al portapapeles', 'success');
        }).catch(function() {
            showNotification('Error al copiar', 'error');
        });
    } else {
        // Fallback for older browsers
        var $temp = jQuery('<input>');
        jQuery('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        showNotification('Copiado al portapapeles', 'success');
    }
}

/**
 * Show notification
 */
function showNotification(message, type) {
    type = type || 'info';
    
    var $notification = jQuery('<div class="llm-tracker-notification llm-tracker-' + type + '">' + message + '</div>');
    jQuery('body').append($notification);
    
    // Position notification
    $notification.css({
        position: 'fixed',
        top: '20px',
        right: '20px',
        zIndex: 100000,
        maxWidth: '300px'
    });
    
    // Auto-remove after 3 seconds
    setTimeout(function() {
        $notification.fadeOut(function() {
            $notification.remove();
        });
    }, 3000);
}

// Global functions for inline onclick handlers
window.showVisitDetails = showVisitDetails;
window.closeVisitDetails = closeVisitDetails;
window.exportVisits = exportVisits;
window.clearVisits = clearVisits;
window.copyToClipboard = copyToClipboard;