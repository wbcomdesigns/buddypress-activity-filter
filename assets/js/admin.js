/**
 * BuddyPress Activity Filter - Admin JavaScript (Corrected Logic)
 * 
 * @package BuddyPress_Activity_Filter
 * @version 4.0.0
 */

(function($) {
    'use strict';

    /**
     * Activity Filter Admin functionality
     */
    const BPActivityFilterAdmin = {
        
        /**
         * Settings from localized script
         */
        settings: typeof bpActivityFilterAdmin !== 'undefined' ? bpActivityFilterAdmin : {},

        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initCheckboxStates();
            this.initCPTToggles();
            this.initHiddenActivities();
            this.showLoadedState();
        },

        /**
         * Bind admin events
         */
        bindEvents: function() {
            // CPT enable/disable toggles
            $(document).on('change', '.cpt-enable-checkbox', this.toggleCPTSettings);
            
            // Checkbox state changes (legacy support)
            $(document).on('change', '.bp-activity-checkbox', this.updateCheckboxState);
            
            // Form validation
            $(document).on('submit', '.bp-activity-filter-admin form', this.validateForm);
            
            // Tab navigation with keyboard support
            $('.nav-tab').on('keydown', this.handleTabKeydown);
        },

        /**
         * Initialize hidden activities specific functionality with CORRECTED logic
         */
        initHiddenActivities: function() {
            // Update visual states when checkboxes change
            $(document).on('change', 'input[name="bp_activity_filter_hidden[]"]', function() {
                BPActivityFilterAdmin.updateCheckboxVisualState.call(this);
            });
            
            // Initialize visual states on page load
            $('input[name="bp_activity_filter_hidden[]"]').each(function() {
                BPActivityFilterAdmin.updateCheckboxVisualState.call(this);
            });
            
            // Add hover effects for labels
            $(document).on('mouseenter', 'label[for^="bp_hidden_"]', function() {
                const $container = $(this).closest('div[data-activity-state]');
                
                // Add subtle hover effect regardless of state
                $container.css('box-shadow', '0 2px 8px rgba(0,0,0,0.15)');
            });
            
            $(document).on('mouseleave', 'label[for^="bp_hidden_"]', function() {
                const $container = $(this).closest('div[data-activity-state]');
                
                // Remove hover effect
                $container.css('box-shadow', 'none');
            });
            
            // Add keyboard accessibility
            $(document).on('keydown', 'label[for^="bp_hidden_"]', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const $checkbox = $(this).find('input[type="checkbox"]');
                    $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
                }
            });
            
            // Make labels focusable
            $('label[for^="bp_hidden_"]').attr('tabindex', '0');
        },

        /**
         * Toggle CPT settings visibility
         */
        toggleCPTSettings: function() {
            const $checkbox = $(this);
            const $item = $checkbox.closest('.cpt-setting-item');
            const $settings = $item.find('.cpt-settings');
            const $inputs = $settings.find('input, select, textarea');
            
            if ($checkbox.is(':checked')) {
                $item.removeClass('disabled');
                $settings.slideDown(200);
                $inputs.prop('disabled', false);
            } else {
                $item.addClass('disabled');
                $settings.slideUp(200);
                $inputs.prop('disabled', true);
            }
        },

        /**
         * Update checkbox visual state (legacy support)
         */
        updateCheckboxState: function() {
            BPActivityFilterAdmin.updateCheckboxVisualState.call(this);
        },

        /**
         * Update checkbox visual state with CORRECTED LOGIC
         * Checked = Hidden (Red), Unchecked = Visible (Green)
         */
        updateCheckboxVisualState: function() {
            const $checkbox = $(this);
            
            // For new simple HTML structure with corrected logic
            if ($checkbox.attr('name') === 'bp_activity_filter_hidden[]') {
                const $container = $checkbox.closest('div[data-activity-state]');
                const $icon = $container.find('.dashicons');
                const $statusText = $container.find('span[style*="font-weight: 600"]');
                const $mainText = $container.find('span[style*="flex: 1"]');
                
                if ($checkbox.is(':checked')) {
                    // CHECKED = HIDDEN (Red styling)
                    $container.css({
                        'background': '#ffeaea',
                        'border': '1px solid #f44336'
                    }).attr('data-activity-state', 'hidden');
                    
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden')
                         .css('color', '#f44336');
                    
                    $statusText.text('Hidden').css('color', '#f44336');
                    $mainText.css('color', '#c62828');
                    
                } else {
                    // UNCHECKED = VISIBLE (Green styling)
                    $container.css({
                        'background': '#e8f5e8',
                        'border': '1px solid #4caf50'
                    }).attr('data-activity-state', 'visible');
                    
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility')
                         .css('color', '#4caf50');
                    
                    $statusText.text('Visible').css('color', '#4caf50');
                    $mainText.css('color', '#2e7d32');
                }
            } else {
                // For legacy structure (if still exists)
                const $label = $checkbox.closest('.bp-activity-checkbox-label');
                
                if ($checkbox.is(':checked')) {
                    $label.addClass('checked');
                } else {
                    $label.removeClass('checked');
                }
            }
        },

        /**
         * Initialize checkbox states on page load
         */
        initCheckboxStates: function() {
            $('.bp-activity-checkbox').each(function() {
                BPActivityFilterAdmin.updateCheckboxVisualState.call(this);
            });
            
            // Also initialize new structure checkboxes
            $('input[name="bp_activity_filter_hidden[]"]').each(function() {
                BPActivityFilterAdmin.updateCheckboxVisualState.call(this);
            });
        },

        /**
         * Initialize CPT toggles on page load
         */
        initCPTToggles: function() {
            $('.cpt-enable-checkbox').each(function() {
                BPActivityFilterAdmin.toggleCPTSettings.call(this);
            });
        },

        /**
         * Handle tab keyboard navigation
         */
        handleTabKeydown: function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).trigger('click');
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                e.preventDefault();
                const $tabs = $('.nav-tab');
                const currentIndex = $tabs.index(this);
                let newIndex;
                
                if (e.key === 'ArrowLeft') {
                    newIndex = currentIndex === 0 ? $tabs.length - 1 : currentIndex - 1;
                } else {
                    newIndex = currentIndex === $tabs.length - 1 ? 0 : currentIndex + 1;
                }
                
                $tabs.eq(newIndex).focus().trigger('click');
            }
        },

        /**
         * Validate form before submission
         */
        validateForm: function(e) {
            const $form = $(this);
            let isValid = true;
            
            // Clear previous error states
            $form.find('.error').removeClass('error');
            
            // Validate CPT labels (if any custom validation needed)
            $form.find('.cpt-label-input').each(function() {
                const $input = $(this);
                const value = $input.val().trim();
                
                // Add custom validation logic here if needed
                if (value.length > 50) {
                    $input.addClass('error');
                    isValid = false;
                }
            });
            
            // Check if too many activities are hidden
            const totalActivities = $('input[name="bp_activity_filter_hidden[]"]').length;
            const hiddenActivities = $('input[name="bp_activity_filter_hidden[]"]:checked').length;
            const visibleActivities = totalActivities - hiddenActivities;
            const coreActivities = 2; // activity_update and activity_comment
            
            if (visibleActivities + coreActivities < 3) {
                isValid = false;
                BPActivityFilterAdmin.showNotification(
                    'Warning: You have hidden most activity types. Consider keeping some activities visible for better user engagement.',
                    'warning'
                );
            }
            
            if (!isValid) {
                e.preventDefault();
                if (hiddenActivities === totalActivities) {
                    BPActivityFilterAdmin.showNotification(
                        'You cannot hide all activity types. At least some activities must remain visible.',
                        'error'
                    );
                }
            }
            
            return isValid;
        },

        /**
         * Show loaded state
         */
        showLoadedState: function() {
            $('.bp-activity-filter-admin').addClass('loaded');
        },

        /**
         * Show notification message
         */
        showNotification: function(message, type = 'info', duration = 5000) {
            const noticeClass = 'notice-' + type;
            const $notice = $(
                '<div class="notice ' + noticeClass + ' is-dismissible">' +
                '<p>' + this.escapeHtml(message) + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>'
            );
            
            $('.bp-activity-filter-admin h1').after($notice);
            
            // Auto-dismiss
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, duration);
            
            // Manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Escape HTML for safe display
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Handle errors gracefully
         */
        handleError: function(error, context) {
            console.error('[BP Activity Filter] Error in ' + context + ':', error);
            
            if (this.settings.debug) {
                this.showNotification(
                    'An error occurred in ' + context + '. Check console for details.',
                    'error'
                );
            }
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA live region for announcements
            if ($('#bp-live-region').length === 0) {
                $('body').append('<div id="bp-live-region" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
            }
            
            // Improve keyboard navigation
            $('.cpt-setting-item').attr('tabindex', '0');
            
            // Add focus indicators
            $('input, select, button, .nav-tab, [tabindex]').on('focus', function() {
                $(this).addClass('focus-visible');
            }).on('blur', function() {
                $(this).removeClass('focus-visible');
            });
        },

        /**
         * Announce to screen readers
         */
        announceToScreenReader: function(message) {
            $('#bp-live-region').text(message);
        },

        /**
         * Get activity statistics
         */
        getActivityStats: function() {
            const totalActivities = $('input[name="bp_activity_filter_hidden[]"]').length;
            const hiddenActivities = $('input[name="bp_activity_filter_hidden[]"]:checked').length;
            const visibleActivities = totalActivities - hiddenActivities;
            const coreActivities = 2; // Always visible
            
            return {
                total: totalActivities,
                hidden: hiddenActivities,
                visible: visibleActivities,
                core: coreActivities,
                totalVisible: visibleActivities + coreActivities
            };
        },

        /**
         * Show activity statistics
         */
        showActivityStats: function() {
            const stats = this.getActivityStats();
            const message = `Activity Status: ${stats.hidden} hidden, ${stats.totalVisible} visible (${stats.core} core + ${stats.visible} other)`;
            
            console.log(message);
            this.announceToScreenReader(message);
        },

        /**
         * Add helpful tooltips
         */
        addTooltips: function() {
            // Add tooltips to activity items
            $('label[for^="bp_hidden_"]').each(function() {
                const $label = $(this);
                const $checkbox = $label.find('input[type="checkbox"]');
                const activityName = $label.find('span[style*="flex: 1"]').text();
                
                const tooltipText = $checkbox.is(':checked') ? 
                    `${activityName} is currently HIDDEN from your site` :
                    `${activityName} is currently VISIBLE on your site`;
                
                $label.attr('title', tooltipText);
            });
        },

        /**
         * Utility functions
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        /**
         * Check if user prefers reduced motion
         */
        prefersReducedMotion: function() {
            return window.matchMedia && 
                   window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        try {
            // Initialize main functionality
            BPActivityFilterAdmin.init();
            
            // Initialize accessibility features
            BPActivityFilterAdmin.initAccessibility();
            
            // Add tooltips if hidden activities exist
            if ($('input[name="bp_activity_filter_hidden[]"]').length > 0) {
                BPActivityFilterAdmin.addTooltips();
                
                // Update tooltips when checkboxes change
                $(document).on('change', 'input[name="bp_activity_filter_hidden[]"]', function() {
                    BPActivityFilterAdmin.addTooltips();
                    BPActivityFilterAdmin.showActivityStats();
                });
                
                // Show initial stats
                BPActivityFilterAdmin.showActivityStats();
            }
            
        } catch (error) {
            BPActivityFilterAdmin.handleError(error, 'initialization');
        }
    });

    /**
     * Handle page navigation and state preservation
     */
    $(window).on('beforeunload', function() {
        // Could implement auto-save functionality here if needed
        const stats = BPActivityFilterAdmin.getActivityStats();
        if (stats.hidden > stats.visible) {
            // Optional: Show warning if hiding too many activities
            // return 'You have hidden most activity types. Are you sure you want to leave?';
        }
    });

    /**
     * Expose object globally for debugging and extensibility
     */
    window.BPActivityFilterAdmin = BPActivityFilterAdmin;

})(jQuery);