/**
 * BuddyPress Activity Filter Admin JavaScript
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

(function($) {
    'use strict';

    /**
     * Admin object
     */
    const BPActivityFilterAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initCPTSettings();
            this.handleFormValidation();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // CPT checkbox changes
            $(document).on('change', '.cpt-enable-checkbox', this.handleCPTToggle);
            
            // Form submission
            $(document).on('submit', 'form', this.handleFormSubmit);
            
            // Tab navigation with keyboard
            $('.nav-tab').on('keydown', this.handleTabKeydown);
            
            // Settings validation
            $('#bp_activity_filter_default, #bp_activity_filter_profile_default').on('change', this.validateDefaultFilters);
            
            // CPT label input changes for preview
            $(document).on('input', '.cpt-label-input', this.updateActivityPreview);
            
            // Global CPT settings help
            $(document).on('click', '.cpt-help-toggle', this.toggleHelp);
        },

        /**
         * Handle CPT toggle
         */
        handleCPTToggle: function() {
            const $checkbox = $(this);
            const $container = $checkbox.closest('.cpt-setting-item');
            const $labelInput = $container.find('.cpt-label-input');
            const $settings = $container.find('.cpt-settings');
            
            if ($checkbox.is(':checked')) {
                $labelInput.prop('disabled', false);
                $container.removeClass('disabled');
                $settings.slideDown(200);
                
                // Focus on label input for better UX
                setTimeout(function() {
                    $labelInput.focus();
                }, 250);
                
                // Update preview when label changes
                $labelInput.on('input', BPActivityFilterAdmin.updateActivityPreview);
                BPActivityFilterAdmin.updateActivityPreview.call($labelInput[0]);
            } else {
                $labelInput.prop('disabled', true);
                $container.addClass('disabled');
                $settings.slideUp(200);
                $labelInput.off('input', BPActivityFilterAdmin.updateActivityPreview);
            }
        },

        /**
         * Update activity preview text
         */
        updateActivityPreview: function() {
            const $input = $(this);
            const $container = $input.closest('.cpt-setting-item');
            const $preview = $container.find('.activity-preview-text');
            const placeholder = $input.attr('placeholder') || 'post';
            const customLabel = $input.val().trim() || placeholder;
            
            const previewText = BPActivityFilterAdmin.getPreviewText(customLabel);
            $preview.html(previewText);
        },

        /**
         * Get activity preview text
         */
        getPreviewText: function(label) {
            const authorName = '<strong>John Doe</strong>';
            const postType = '<em>' + BPActivityFilterAdmin.escapeHtml(label) + '</em>';
            const postTitle = '<a href="#">Sample Post Title</a>';
            
            return authorName + ' published a new ' + postType + ': ' + postTitle;
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
         * Initialize CPT settings
         */
        initCPTSettings: function() {
            $('.cpt-enable-checkbox').each(function() {
                $(this).trigger('change');
            });
            
            // Initialize previews for enabled CPTs
            $('.cpt-setting-item').each(function() {
                const $container = $(this);
                const $checkbox = $container.find('.cpt-enable-checkbox');
                const $labelInput = $container.find('.cpt-label-input');
                
                if ($checkbox.is(':checked')) {
                    BPActivityFilterAdmin.updateActivityPreview.call($labelInput[0]);
                }
            });
        },

        /**
         * Toggle help text
         */
        toggleHelp: function(e) {
            e.preventDefault();
            const $helpText = $(this).next('.help-text');
            $helpText.slideToggle(200);
            
            const $icon = $(this).find('.dashicons');
            $icon.toggleClass('dashicons-arrow-down dashicons-arrow-up');
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');
            const originalText = $submitButton.val();
            
            // Show loading state
            $submitButton.val('Saving...');
            $submitButton.prop('disabled', true);
            $form.addClass('loading');
            
            // Reset after a delay (form will submit normally)
            setTimeout(function() {
                $submitButton.val(originalText);
                $submitButton.prop('disabled', false);
                $form.removeClass('loading');
            }, 2000);
        },

        /**
         * Handle tab keyboard navigation
         */
        handleTabKeydown: function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this)[0].click();
            }
        },

        /**
         * Validate default filters
         */
        validateDefaultFilters: function() {
            const $hiddenCheckboxes = $('input[name="bp_activity_filter_hidden[]"]:checked');
            const $defaultSelect = $('#bp_activity_filter_default');
            const $profileDefaultSelect = $('#bp_activity_filter_profile_default');
            
            if ($hiddenCheckboxes.length === 0) {
                return; // No validation needed if nothing is hidden
            }
            
            const hiddenValues = [];
            $hiddenCheckboxes.each(function() {
                hiddenValues.push($(this).val());
            });
            
            // Check if selected default filter is hidden
            const defaultValue = $defaultSelect.val();
            const profileDefaultValue = $profileDefaultSelect.val();
            
            if (hiddenValues.indexOf(defaultValue) !== -1) {
                BPActivityFilterAdmin.showWarning('The selected site-wide default filter is currently hidden.');
            }
            
            if (hiddenValues.indexOf(profileDefaultValue) !== -1) {
                BPActivityFilterAdmin.showWarning('The selected profile default filter is currently hidden.');
            }
        },

        /**
         * Handle form validation
         */
        handleFormValidation: function() {
            // Validate on hidden activities change
            $(document).on('change', 'input[name="bp_activity_filter_hidden[]"]', function() {
                setTimeout(BPActivityFilterAdmin.validateDefaultFilters, 100);
            });
        },

        /**
         * Show warning message
         */
        showWarning: function(message) {
            // Remove existing warnings
            $('.bp-activity-filter-warning').remove();
            
            // Create warning notice
            const $warning = $('<div class="notice notice-warning bp-activity-filter-warning"><p>' + message + '</p></div>');
            
            // Insert after the form
            $('form').after($warning);
            
            // Remove warning after 5 seconds
            setTimeout(function() {
                $warning.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            const $success = $('<div class="notice notice-success"><p>' + message + '</p></div>');
            $('h1').after($success);
            
            setTimeout(function() {
                $success.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Show error message
         */
        showError: function(message) {
            const $error = $('<div class="notice notice-error"><p>' + message + '</p></div>');
            $('h1').after($error);
            
            setTimeout(function() {
                $error.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        BPActivityFilterAdmin.init();
        
        // Handle AJAX form submissions if needed
        if (typeof bpActivityFilterAdmin !== 'undefined') {
            // AJAX functionality can be added here
        }
    });

})(jQuery);