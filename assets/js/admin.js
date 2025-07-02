/**
 * BuddyPress Activity Filter Admin JavaScript - Production Ready
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

(function($) {
    'use strict';

    /**
     * Enhanced Activity Filter Admin object
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
            this.initTabs();
            this.initCPTSettings();
            this.initHiddenActivities();
            this.handleFormValidation();
            this.initAccessibility();
            this.showLoadedState();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // CPT checkbox changes
            $(document).on('change', '.cpt-enable-checkbox', this.handleCPTToggle);
            
            // Form submission with enhanced feedback
            $(document).on('submit', 'form', this.handleFormSubmit);
            
            // Tab navigation with keyboard support
            $('.nav-tab').on('keydown', this.handleTabKeydown);
            $('.nav-tab').on('click', this.handleTabClick);
            
            // Settings validation
            $('#bp_activity_filter_default, #bp_activity_filter_profile_default').on('change', this.validateDefaultFilters);
            
            // CPT label input changes for preview
            $(document).on('input', '.cpt-label-input', this.updateActivityPreview);
            
            // Hidden activities bulk actions
            $('#select-all-hidden').on('click', this.selectAllHidden);
            $('#deselect-all-hidden').on('click', this.deselectAllHidden);
            
            // Hidden activities checkbox changes
            $(document).on('change', '.bp-activity-checkbox', this.handleHiddenActivityChange);
            
            // Prevent form submission on Enter in text inputs (except submit buttons)
            $('input[type="text"]').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(this).blur();
                }
            });

            // Auto-save draft functionality (optional)
            if (this.settings.autosave) {
                this.initAutosave();
            }
        },

        /**
         * Initialize tab functionality
         */
        initTabs: function() {
            // Set ARIA attributes
            $('.nav-tab').each(function() {
                const tabId = $(this).data('tab');
                $(this).attr('aria-controls', 'tab-content-' + tabId);
            });

            // Handle direct tab links
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab) {
                $('.nav-tab[data-tab="' + activeTab + '"]').addClass('nav-tab-active');
            }
        },

        /**
         * Handle tab clicks with smooth transitions
         */
        handleTabClick: function(e) {
            e.preventDefault();
            
            const $tab = $(this);
            const tabId = $tab.data('tab');
            
            // Update tab states
            $('.nav-tab').removeClass('nav-tab-active').attr('aria-selected', 'false');
            $tab.addClass('nav-tab-active').attr('aria-selected', 'true');
            
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
            
            // Navigate to the tab
            window.location.href = $tab.attr('href');
        },

        /**
         * Handle CPT toggle with enhanced UX
         */
        handleCPTToggle: function() {
            const $checkbox = $(this);
            const $container = $checkbox.closest('.cpt-setting-item');
            const $labelInput = $container.find('.cpt-label-input');
            const $settings = $container.find('.cpt-settings');
            
            if ($checkbox.is(':checked')) {
                $labelInput.prop('disabled', false);
                $container.removeClass('disabled').addClass('enabled');
                
                // Smooth slide down animation
                $settings.slideDown({
                    duration: 300,
                    easing: 'easeOutCubic',
                    complete: function() {
                        // Focus on label input for better UX
                        $labelInput.focus();
                        // Update preview
                        BPActivityFilterAdmin.updateActivityPreview.call($labelInput[0]);
                    }
                });
                
                // Bind preview updates
                $labelInput.on('input', BPActivityFilterAdmin.updateActivityPreview);
                
            } else {
                $labelInput.prop('disabled', true);
                $container.removeClass('enabled').addClass('disabled');
                
                // Smooth slide up animation
                $settings.slideUp({
                    duration: 300,
                    easing: 'easeInCubic'
                });
                
                // Unbind preview updates
                $labelInput.off('input', BPActivityFilterAdmin.updateActivityPreview);
            }
            
            // Announce change to screen readers
            BPActivityFilterAdmin.announceToScreenReader(
                $checkbox.is(':checked') 
                    ? BPActivityFilterAdmin.settings.strings.cptEnabled 
                    : BPActivityFilterAdmin.settings.strings.cptDisabled
            );
        },

        /**
         * Update activity preview text with enhanced formatting
         */
        updateActivityPreview: function() {
            const $input = $(this);
            const $container = $input.closest('.cpt-setting-item');
            const $preview = $container.find('.activity-preview-text');
            const placeholder = $input.attr('placeholder') || 'post';
            const customLabel = $input.val().trim() || placeholder;
            
            // Create preview with proper escaping
            const previewText = BPActivityFilterAdmin.getPreviewText(customLabel);
            
            // Animate the preview update
            $preview.fadeOut(150, function() {
                $preview.html(previewText).fadeIn(150);
            });
        },

        /**
         * Get activity preview text with proper formatting
         */
        getPreviewText: function(label) {
            const authorName = '<strong>John Doe</strong>';
            const postType = '<em>' + BPActivityFilterAdmin.escapeHtml(label) + '</em>';
            const postTitle = '<a href="#" onclick="return false;">Sample Post Title</a>';
            
            return authorName + ' published a new ' + postType + ': ' + postTitle;
        },

        /**
         * Initialize CPT settings with enhanced functionality
         */
        initCPTSettings: function() {
            // Initialize all CPT toggles
            $('.cpt-enable-checkbox').each(function() {
                BPActivityFilterAdmin.handleCPTToggle.call(this);
            });
            
            // Add character counter to label inputs
            $('.cpt-label-input').each(function() {
                BPActivityFilterAdmin.addCharacterCounter($(this));
            });
        },

        /**
         * Add character counter to input fields
         */
        addCharacterCounter: function($input) {
            const maxLength = 50; // Reasonable limit for activity labels
            const $counter = $('<div class="character-counter"></div>');
            
            $input.after($counter);
            
            $input.on('input', function() {
                const length = $(this).val().length;
                const remaining = maxLength - length;
                
                $counter.text(remaining + ' characters remaining');
                
                if (remaining < 10) {
                    $counter.addClass('warning');
                } else {
                    $counter.removeClass('warning');
                }
                
                if (remaining < 0) {
                    $counter.addClass('error');
                    $(this).addClass('error');
                } else {
                    $counter.removeClass('error');
                    $(this).removeClass('error');
                }
            });
            
            // Initialize counter
            $input.trigger('input');
        },

        /**
         * Initialize hidden activities functionality
         */
        initHiddenActivities: function() {
            // Update checkbox label states
            $('.bp-activity-checkbox').each(function() {
                BPActivityFilterAdmin.updateCheckboxLabelState($(this));
            });
            
            // Add search functionality
            BPActivityFilterAdmin.addHiddenActivitiesSearch();
        },

        /**
         * Add search functionality to hidden activities
         */
        addHiddenActivitiesSearch: function() {
            const $fieldset = $('#bp-hidden-activities-fieldset');
            if ($fieldset.length === 0) return;
            
            const $searchBox = $(
                '<div class="hidden-activities-search">' +
                '<label for="hidden-activities-search-input">' + 
                (BPActivityFilterAdmin.settings.strings.searchActivities || 'Search activities:') + 
                '</label>' +
                '<input type="text" id="hidden-activities-search-input" class="regular-text" placeholder="' + 
                (BPActivityFilterAdmin.settings.strings.searchPlaceholder || 'Type to search...') + '">' +
                '</div>'
            );
            
            $fieldset.before($searchBox);
            
            const $searchInput = $searchBox.find('input');
            $searchInput.on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.bp-activity-checkbox-label').each(function() {
                    const $label = $(this);
                    const labelText = $label.find('.checkbox-label-text').text().toLowerCase();
                    const keyText = $label.find('.activity-key').text().toLowerCase();
                    
                    if (labelText.includes(searchTerm) || keyText.includes(searchTerm)) {
                        $label.show();
                    } else {
                        $label.hide();
                    }
                });
            });
        },

        /**
         * Handle hidden activity checkbox changes
         */
        handleHiddenActivityChange: function() {
            const $checkbox = $(this);
            BPActivityFilterAdmin.updateCheckboxLabelState($checkbox);
            BPActivityFilterAdmin.validateDefaultFilters();
            
            // Update bulk action button states
            BPActivityFilterAdmin.updateBulkActionStates();
        },

        /**
         * Update checkbox label visual state
         */
        updateCheckboxLabelState: function($checkbox) {
            const $label = $checkbox.closest('.bp-activity-checkbox-label');
            
            if ($checkbox.is(':checked')) {
                $label.addClass('checked');
            } else {
                $label.removeClass('checked');
            }
        },

        /**
         * Update bulk action button states
         */
        updateBulkActionStates: function() {
            const $checkboxes = $('.bp-activity-checkbox');
            const totalVisible = $checkboxes.filter(':visible').length;
            const checkedVisible = $checkboxes.filter(':visible:checked').length;
            
            const $selectAll = $('#select-all-hidden');
            const $deselectAll = $('#deselect-all-hidden');
            
            // Update button states
            $selectAll.prop('disabled', checkedVisible === totalVisible);
            $deselectAll.prop('disabled', checkedVisible === 0);
            
            // Update button text to show counts
            $selectAll.html(
                '<span class="dashicons dashicons-yes"></span> ' +
                BPActivityFilterAdmin.settings.strings.selectAll + ' (' + (totalVisible - checkedVisible) + ')'
            );
            $deselectAll.html(
                '<span class="dashicons dashicons-no"></span> ' +
                BPActivityFilterAdmin.settings.strings.deselectAll + ' (' + checkedVisible + ')'
            );
        },

        /**
         * Select all hidden activities
         */
        selectAllHidden: function(e) {
            e.preventDefault();
            
            $('.bp-activity-checkbox:visible').prop('checked', true).each(function() {
                BPActivityFilterAdmin.updateCheckboxLabelState($(this));
            });
            
            BPActivityFilterAdmin.validateDefaultFilters();
            BPActivityFilterAdmin.updateBulkActionStates();
            
            // Announce to screen readers
            BPActivityFilterAdmin.announceToScreenReader(
                BPActivityFilterAdmin.settings.strings.allSelected || 'All visible activities selected'
            );
        },

        /**
         * Deselect all hidden activities
         */
        deselectAllHidden: function(e) {
            e.preventDefault();
            
            $('.bp-activity-checkbox:visible').prop('checked', false).each(function() {
                BPActivityFilterAdmin.updateCheckboxLabelState($(this));
            });
            
            BPActivityFilterAdmin.validateDefaultFilters();
            BPActivityFilterAdmin.updateBulkActionStates();
            
            // Announce to screen readers
            BPActivityFilterAdmin.announceToScreenReader(
                BPActivityFilterAdmin.settings.strings.allDeselected || 'All visible activities deselected'
            );
        },

        /**
         * Handle form submission with enhanced feedback
         */
        handleFormSubmit: function(e) {
            const $form = $(this);
            const $submitButton = $form.find('#bp-activity-filter-submit');
            const $spinner = $form.find('#bp-activity-filter-spinner');
            const originalText = $submitButton.val();
            
            // Validate form before submission
            if (!BPActivityFilterAdmin.validateForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $submitButton.val(BPActivityFilterAdmin.settings.strings.saving || 'Saving...');
            $submitButton.prop('disabled', true);
            $spinner.addClass('is-active');
            $form.addClass('loading');
            
            // Add visual feedback
            BPActivityFilterAdmin.showNotice('info', BPActivityFilterAdmin.settings.strings.saving || 'Saving settings...');
            
            // Reset after form submission (page will reload)
            setTimeout(function() {
                $submitButton.val(originalText);
                $submitButton.prop('disabled', false);
                $spinner.removeClass('is-active');
                $form.removeClass('loading');
            }, 2000);
        },

        /**
         * Validate form before submission
         */
        validateForm: function($form) {
            let isValid = true;
            const errors = [];
            
            // Validate CPT labels don't exceed reasonable length
            $('.cpt-label-input:enabled').each(function() {
                const $input = $(this);
                if ($input.val().length > 50) {
                    errors.push('Custom post type labels should be 50 characters or less.');
                    $input.addClass('error');
                    isValid = false;
                } else {
                    $input.removeClass('error');
                }
            });
            
            // Show validation errors
            if (!isValid) {
                errors.forEach(function(error) {
                    BPActivityFilterAdmin.showNotice('error', error);
                });
            }
            
            return isValid;
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
         * Validate default filters against hidden activities
         */
        validateDefaultFilters: function() {
            const $hiddenCheckboxes = $('.bp-activity-checkbox:checked');
            const $defaultSelect = $('#bp_activity_filter_default');
            const $profileDefaultSelect = $('#bp_activity_filter_profile_default');
            
            if ($hiddenCheckboxes.length === 0) {
                BPActivityFilterAdmin.clearValidationWarnings();
                return;
            }
            
            const hiddenValues = [];
            $hiddenCheckboxes.each(function() {
                hiddenValues.push($(this).val());
            });
            
            // Check if selected default filter is hidden
            const defaultValue = $defaultSelect.val();
            const profileDefaultValue = $profileDefaultSelect.val();
            
            let warnings = [];
            
            if (hiddenValues.indexOf(defaultValue) !== -1) {
                warnings.push('The selected site-wide default filter is currently hidden and will not be effective.');
                $defaultSelect.addClass('warning');
            } else {
                $defaultSelect.removeClass('warning');
            }
            
            if (hiddenValues.indexOf(profileDefaultValue) !== -1) {
                warnings.push('The selected profile default filter is currently hidden and will not be effective.');
                $profileDefaultSelect.addClass('warning');
            } else {
                $profileDefaultSelect.removeClass('warning');
            }
            
            // Show or clear warnings
            if (warnings.length > 0) {
                warnings.forEach(function(warning) {
                    BPActivityFilterAdmin.showValidationWarning(warning);
                });
            } else {
                BPActivityFilterAdmin.clearValidationWarnings();
            }
        },

        /**
         * Show validation warning
         */
        showValidationWarning: function(message) {
            // Remove existing warnings of this type first
            $('.bp-activity-filter-validation-warning').remove();
            
            const $warning = $(
                '<div class="notice notice-warning bp-activity-filter-validation-warning inline">' +
                '<p><strong>Warning:</strong> ' + BPActivityFilterAdmin.escapeHtml(message) + '</p>' +
                '</div>'
            );
            
            // Insert after the form table
            $('.form-table').after($warning);
            
            // Auto-remove warning after 10 seconds
            setTimeout(function() {
                $warning.fadeOut(function() {
                    $(this).remove();
                });
            }, 10000);
        },

        /**
         * Clear validation warnings
         */
        clearValidationWarnings: function() {
            $('.bp-activity-filter-validation-warning').fadeOut(function() {
                $(this).remove();
            });
            $('.warning').removeClass('warning');
        },

        /**
         * Handle form validation on input changes
         */
        handleFormValidation: function() {
            // Validate on hidden activities change
            $(document).on('change', '.bp-activity-checkbox', function() {
                setTimeout(BPActivityFilterAdmin.validateDefaultFilters, 100);
            });
            
            // Validate on default filter changes
            $('#bp_activity_filter_default, #bp_activity_filter_profile_default').on('change', function() {
                setTimeout(BPActivityFilterAdmin.validateDefaultFilters, 100);
            });
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA live region for announcements
            if ($('#bp-activity-filter-live-region').length === 0) {
                $('body').append('<div id="bp-activity-filter-live-region" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
            }
            
            // Add proper ARIA labels and descriptions
            $('input[type="checkbox"]').each(function() {
                const $checkbox = $(this);
                const $label = $checkbox.closest('label');
                if ($label.length > 0) {
                    const labelText = $label.find('.checkbox-label-text').text();
                    $checkbox.attr('aria-label', labelText);
                }
            });
            
            // Add keyboard navigation help
            $('.nav-tab-wrapper').attr('role', 'tablist');
            $('.nav-tab').attr('role', 'tab');
            
            // Add focus indicators for better visibility
            $('input, select, button, .nav-tab').on('focus', function() {
                $(this).addClass('focus-visible');
            }).on('blur', function() {
                $(this).removeClass('focus-visible');
            });
        },

        /**
         * Show loaded state to prevent flash of unstyled content
         */
        showLoadedState: function() {
            $('.bp-activity-filter-admin').addClass('loaded');
        },

        /**
         * Initialize auto-save functionality (optional)
         */
        initAutosave: function() {
            let autosaveTimeout;
            const autosaveDelay = 3000; // 3 seconds
            
            $('input, select, textarea').on('change input', function() {
                clearTimeout(autosaveTimeout);
                autosaveTimeout = setTimeout(function() {
                    BPActivityFilterAdmin.performAutosave();
                }, autosaveDelay);
            });
        },

        /**
         * Perform auto-save
         */
        performAutosave: function() {
            const $form = $('form');
            const formData = $form.serialize();
            
            $.ajax({
                url: BPActivityFilterAdmin.settings.ajaxUrl,
                type: 'POST',
                data: formData + '&action=bp_activity_filter_autosave&nonce=' + BPActivityFilterAdmin.settings.nonce,
                success: function(response) {
                    if (response.success) {
                        BPActivityFilterAdmin.showNotice('success', 'Settings auto-saved', 2000);
                    }
                },
                error: function() {
                    // Silently fail for auto-save
                }
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message, duration) {
            duration = duration || 5000;
            
            // Remove existing notices of the same type
            $('.bp-activity-filter-notice').remove();
            
            const noticeClass = 'notice-' + type;
            const $notice = $(
                '<div class="notice ' + noticeClass + ' bp-activity-filter-notice is-dismissible">' +
                '<p>' + BPActivityFilterAdmin.escapeHtml(message) + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>'
            );
            
            $('.bp-activity-filter-admin h1').after($notice);
            
            // Auto-dismiss after duration
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, duration);
            
            // Handle manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Announce to screen readers
         */
        announceToScreenReader: function(message) {
            $('#bp-activity-filter-live-region').text(message);
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
         * Utility function to debounce function calls
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
         * Handle responsive breakpoints
         */
        handleResponsiveBreakpoints: function() {
            const $window = $(window);
            const breakpoints = {
                mobile: 768,
                tablet: 1024
            };
            
            function updateLayout() {
                const windowWidth = $window.width();
                
                if (windowWidth <= breakpoints.mobile) {
                    $('body').addClass('bp-admin-mobile').removeClass('bp-admin-tablet bp-admin-desktop');
                } else if (windowWidth <= breakpoints.tablet) {
                    $('body').addClass('bp-admin-tablet').removeClass('bp-admin-mobile bp-admin-desktop');
                } else {
                    $('body').addClass('bp-admin-desktop').removeClass('bp-admin-mobile bp-admin-tablet');
                }
            }
            
            // Initial layout update
            updateLayout();
            
            // Update on resize (debounced)
            $window.on('resize', BPActivityFilterAdmin.debounce(updateLayout, 250));
        },

        /**
         * Enhanced error handling
         */
        handleError: function(error, context) {
            console.error('[BP Activity Filter] Error in ' + context + ':', error);
            
            // Show user-friendly error message
            BPActivityFilterAdmin.showNotice(
                'error', 
                'An error occurred. Please refresh the page and try again. If the problem persists, contact support.',
                10000
            );
            
            // Log error details for debugging
            if (BPActivityFilterAdmin.settings.debug) {
                console.log('Error details:', {
                    context: context,
                    error: error,
                    timestamp: new Date().toISOString(),
                    userAgent: navigator.userAgent,
                    url: window.location.href
                });
            }
        },

        /**
         * Performance monitoring
         */
        initPerformanceMonitoring: function() {
            if (!BPActivityFilterAdmin.settings.debug) {
                return;
            }
            
            // Monitor script load time
            const startTime = performance.now();
            
            $(document).ready(function() {
                const endTime = performance.now();
                console.log('[BP Activity Filter] Admin script loaded in ' + (endTime - startTime) + ' milliseconds');
            });
            
            // Monitor form submission time
            $('form').on('submit', function() {
                const submitStartTime = performance.now();
                
                $(window).on('beforeunload', function() {
                    const submitEndTime = performance.now();
                    console.log('[BP Activity Filter] Form submission took ' + (submitEndTime - submitStartTime) + ' milliseconds');
                });
            });
        }
    };

    /**
     * Enhanced Dashboard functionality (if on dashboard page)
     */
    const WbcomDashboard = {
        
        /**
         * Initialize dashboard
         */
        init: function() {
            if (!$('.wbcom-dashboard').length) {
                return;
            }
            
            this.bindEvents();
            this.initPluginFilters();
            this.loadNewsFeed();
            this.initAnimations();
        },

        /**
         * Bind dashboard events
         */
        bindEvents: function() {
            // Plugin filter buttons
            $(document).on('click', '.filter-btn', this.handlePluginFilter);
            
            // News feed refresh
            $(document).on('click', '.refresh-news', this.refreshNewsFeed);
            
            // Smooth scrolling for anchor links
            $(document).on('click', 'a[href^="#"]', this.handleSmoothScroll);
        },

        /**
         * Handle plugin filter
         */
        handlePluginFilter: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const filter = $btn.data('filter');
            
            // Update active state
            $('.filter-btn').removeClass('active');
            $btn.addClass('active');
            
            // Apply filter
            WbcomDashboard.applyFilter(filter);
        },

        /**
         * Apply plugin filter
         */
        applyFilter: function(filter) {
            const $cards = $('.wbcom-plugin-card');
            
            if (filter === 'all') {
                $cards.fadeIn(200);
            } else {
                $cards.each(function() {
                    const $card = $(this);
                    const cardStatus = $card.data('status');
                    
                    if (cardStatus === filter) {
                        $card.fadeIn(200);
                    } else {
                        $card.fadeOut(200);
                    }
                });
            }
        },

        /**
         * Initialize plugin filters
         */
        initPluginFilters: function() {
            // Set initial filter states
            this.updateFilterCounts();
        },

        /**
         * Update filter counts
         */
        updateFilterCounts: function() {
            const $filterBtns = $('.filter-btn');
            const $cards = $('.wbcom-plugin-card');
            
            $filterBtns.each(function() {
                const $btn = $(this);
                const filter = $btn.data('filter');
                let count;
                
                if (filter === 'all') {
                    count = $cards.length;
                } else {
                    count = $cards.filter('[data-status="' + filter + '"]').length;
                }
                
                const baseText = $btn.text().replace(/ \(\d+\)/, '');
                $btn.text(baseText + ' (' + count + ')');
            });
        },

        /**
         * Load news feed
         */
        loadNewsFeed: function() {
            const $newsFeed = $('#wbcom-news-feed');
            
            if ($newsFeed.length === 0) {
                return;
            }
            
            $.ajax({
                url: 'https://wbcomdesigns.com/wp-json/wp/v2/posts',
                data: { 
                    per_page: 5,
                    _embed: true
                },
                timeout: 10000,
                success: function(posts) {
                    WbcomDashboard.renderNewsFeed(posts);
                },
                error: function() {
                    WbcomDashboard.renderNewsFeedError();
                }
            });
        },

        /**
         * Render news feed
         */
        renderNewsFeed: function(posts) {
            const $newsFeed = $('#wbcom-news-feed');
            let newsHtml = '';
            
            if (posts && posts.length > 0) {
                posts.forEach(function(post) {
                    const excerpt = $('<div>').html(post.excerpt.rendered).text().trim();
                    const date = new Date(post.date).toLocaleDateString();
                    
                    newsHtml += '<div class="news-item">';
                    newsHtml += '<h4><a href="' + post.link + '" target="_blank">' + WbcomDashboard.escapeHtml(post.title.rendered) + '</a></h4>';
                    newsHtml += '<p>' + WbcomDashboard.escapeHtml(excerpt) + '</p>';
                    newsHtml += '<small>' + date + '</small>';
                    newsHtml += '</div>';
                });
            } else {
                newsHtml = '<p>No news available.</p>';
            }
            
            $newsFeed.html(newsHtml);
        },

        /**
         * Render news feed error
         */
        renderNewsFeedError: function() {
            const $newsFeed = $('#wbcom-news-feed');
            $newsFeed.html('<p>Unable to load news feed.</p>');
        },

        /**
         * Refresh news feed
         */
        refreshNewsFeed: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            $btn.prop('disabled', true);
            
            setTimeout(function() {
                WbcomDashboard.loadNewsFeed();
                $btn.prop('disabled', false);
            }, 1000);
        },

        /**
         * Handle smooth scrolling
         */
        handleSmoothScroll: function(e) {
            const href = $(this).attr('href');
            
            if (!href || !href.startsWith('#')) {
                return;
            }
            
            const $target = $(href);
            
            if ($target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $target.offset().top - 100
                }, 500);
            }
        },

        /**
         * Initialize animations
         */
        initAnimations: function() {
            if (BPActivityFilterAdmin.prefersReducedMotion()) {
                return;
            }
            
            // Fade in cards on load
            $('.wbcom-plugin-card').each(function(index) {
                $(this).css('opacity', '0').delay(index * 50).animate({ opacity: 1 }, 300);
            });
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        try {
            // Initialize main admin functionality
            BPActivityFilterAdmin.init();
            
            // Initialize dashboard if present
            WbcomDashboard.init();
            
            // Initialize performance monitoring
            BPActivityFilterAdmin.initPerformanceMonitoring();
            
            // Handle responsive breakpoints
            BPActivityFilterAdmin.handleResponsiveBreakpoints();
            
        } catch (error) {
            BPActivityFilterAdmin.handleError(error, 'initialization');
        }
    });

    /**
     * Handle window events
     */
    $(window).on('resize', BPActivityFilterAdmin.debounce(function() {
        try {
            BPActivityFilterAdmin.handleResponsiveBreakpoints();
        } catch (error) {
            BPActivityFilterAdmin.handleError(error, 'window resize');
        }
    }, 250));

    /**
     * Expose objects globally for debugging and extensibility
     */
    window.BPActivityFilterAdmin = BPActivityFilterAdmin;
    window.WbcomDashboard = WbcomDashboard;

})(jQuery);