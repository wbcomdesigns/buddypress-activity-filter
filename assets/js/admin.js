/**
 * BuddyPress Activity Filter - Admin JavaScript (Plugin-Specific Only)
 * 
 * Contains only functionality specific to the Activity Filter plugin settings.
 * General dashboard functionality is handled by the shared Wbcom integration.
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
            this.showLoadedState();
        },

        /**
         * Bind admin events
         */
        bindEvents: function() {
            // Hidden activities select/deselect all
            $(document).on('click', '#select-all-hidden', this.selectAllHidden);
            $(document).on('click', '#deselect-all-hidden', this.deselectAllHidden);
            
            // CPT enable/disable toggles
            $(document).on('change', '.cpt-enable-checkbox', this.toggleCPTSettings);
            
            // Checkbox state changes
            $(document).on('change', '.bp-activity-checkbox', this.updateCheckboxState);
            
            // Form validation
            $(document).on('submit', '.bp-activity-filter-admin form', this.validateForm);
            
            // Tab navigation with keyboard support
            $('.nav-tab').on('keydown', this.handleTabKeydown);
        },

        /**
         * Select all hidden activities
         */
        selectAllHidden: function(e) {
            e.preventDefault();
            $('#bp-hidden-activities-fieldset input[type="checkbox"]').prop('checked', true).trigger('change');
        },

        /**
         * Deselect all hidden activities
         */
        deselectAllHidden: function(e) {
            e.preventDefault();
            $('#bp-hidden-activities-fieldset input[type="checkbox"]').prop('checked', false).trigger('change');
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
         * Update checkbox visual state
         */
        updateCheckboxState: function() {
            const $checkbox = $(this);
            const $label = $checkbox.closest('.bp-activity-checkbox-label');
            
            if ($checkbox.is(':checked')) {
                $label.addClass('checked');
            } else {
                $label.removeClass('checked');
            }
        },

        /**
         * Initialize checkbox states on page load
         */
        initCheckboxStates: function() {
            $('.bp-activity-checkbox').each(function() {
                BPActivityFilterAdmin.updateCheckboxState.call(this);
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
         * Utility: Debounce function calls
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
        },

        /**
         * Get current tab from URL
         */
        getCurrentTab: function() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tab') || 'default';
        },

        /**
         * Smooth scroll to element
         */
        scrollToElement: function($element, offset = 100) {
            if ($element.length && !this.prefersReducedMotion()) {
                $('html, body').animate({
                    scrollTop: $element.offset().top - offset
                }, 500);
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