/**
 * BuddyPress Activity Filter - Admin JavaScript (Simplified)
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
         * Initialize hidden activities specific functionality
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
                const $container = $(this).closest('div[style*="display: block"]');
                const $checkbox = $(this).find('input[type="checkbox"]');
                
                if (!$checkbox.is(':checked')) {
                    $container.css('background', '#f0f6fc');
                }
            });
            
            $(document).on('mouseleave', 'label[for^="bp_hidden_"]', function() {
                const $container = $(this).closest('div[style*="display: block"]');
                const $checkbox = $(this).find('input[type="checkbox"]');
                
                if (!$checkbox.is(':checked')) {
                    $container.css('background', '#fafafa');
                }
            });
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
         * Update checkbox visual state (core function for new structure)
         */
        updateCheckboxVisualState: function() {
            const $checkbox = $(this);
            
            // For new simple HTML structure
            if ($checkbox.attr('name') === 'bp_activity_filter_hidden[]') {
                const $container = $checkbox.closest('div[style*="display: block"]');
                
                if ($checkbox.is(':checked')) {
                    // Update to checked styles
                    $container.css({
                        'background': '#e7f3ff',
                        'border': '1px solid #0073aa'
                    });
                } else {
                    // Update to unchecked styles  
                    $container.css({
                        'background': '#fafafa',
                        'border': '1px solid #e1e1e1'
                    });
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
            
            if (!isValid) {
                e.preventDefault();
                BPActivityFilterAdmin.showNotification(
                    'Please correct the errors in the form before saving.',
                    'error'
                );
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
            
        } catch (error) {
            BPActivityFilterAdmin.handleError(error, 'initialization');
        }
    });

    /**
     * Expose object globally for debugging and extensibility
     */
    window.BPActivityFilterAdmin = BPActivityFilterAdmin;

})(jQuery);