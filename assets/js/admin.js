/**
 * BuddyPress Activity Filter Admin JavaScript (Consolidated)
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

(function($) {
    'use strict';

    /**
     * Activity Filter Admin object
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
     * Wbcom Designs Dashboard object (for dashboard pages)
     */
    const WbcomDashboard = {
        
        /**
         * Settings from localized script
         */
        settings: typeof wbcomDashboard !== 'undefined' ? wbcomDashboard : {},

        /**
         * Initialize dashboard functionality
         */
        init: function() {
            // Only initialize on dashboard pages
            if (!$('.wbcom-dashboard').length) {
                return;
            }

            this.bindEvents();
            this.initPluginFilters();
            this.loadNewsFeed();
            this.initAccessibility();
            this.initAnimations();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Plugin filter buttons
            $(document).on('click', '.filter-btn', this.handlePluginFilter);
            
            // Tab navigation with keyboard support
            $(document).on('keydown', '.nav-tab', this.handleTabKeyboard);
            
            // Plugin action buttons
            $(document).on('click', '.plugin-action-btn', this.handlePluginAction);
            
            // Smooth scrolling for anchor links
            $(document).on('click', 'a[href^="#"]', this.handleSmoothScroll);
            
            // News feed refresh
            $(document).on('click', '.refresh-news', this.refreshNewsFeed);
            
            // Plugin card interactions
            $(document).on('mouseenter', '.wbcom-plugin-card', this.handleCardHover);
            $(document).on('mouseleave', '.wbcom-plugin-card', this.handleCardLeave);
            
            // Search functionality
            $(document).on('input', '.plugin-search', this.handlePluginSearch);
            
            // Global keyboard shortcuts
            $(document).on('keydown', this.handleGlobalKeydown);
        },

        /**
         * Initialize plugin filters
         */
        initPluginFilters: function() {
            const $filterBtns = $('.filter-btn');
            const $pluginCards = $('.wbcom-plugin-card');
            
            if ($filterBtns.length === 0 || $pluginCards.length === 0) {
                return;
            }

            // Set initial counts
            this.updateFilterCounts();
            
            // Handle URL parameters for initial filter
            const urlParams = new URLSearchParams(window.location.search);
            const filterParam = urlParams.get('filter');
            
            if (filterParam && $filterBtns.filter('[data-filter="' + filterParam + '"]').length > 0) {
                this.applyFilter(filterParam);
            }
        },

        /**
         * Handle plugin filter button clicks
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
            
            // Update URL without reload
            const url = new URL(window.location);
            if (filter === 'all') {
                url.searchParams.delete('filter');
            } else {
                url.searchParams.set('filter', filter);
            }
            window.history.replaceState({}, '', url);
            
            // Announce to screen readers
            WbcomDashboard.announceToScreenReader('Showing ' + (filter === 'all' ? 'all plugins' : filter + ' plugins'));
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
            
            // Update counts
            setTimeout(() => {
                this.updateFilterCounts();
            }, 250);
        },

        /**
         * Update filter button counts
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
                    count = $cards.filter('[data-status="' + filter + '"]:visible').length;
                }
                
                // Update button text with count
                const baseText = $btn.text().replace(/ \(\d+\)/, '');
                $btn.text(baseText + ' (' + count + ')');
            });
        },

        /**
         * Handle plugin search
         */
        handlePluginSearch: function() {
            const searchTerm = $(this).val().toLowerCase();
            const $cards = $('.wbcom-plugin-card');
            
            if (searchTerm === '') {
                $cards.show();
                return;
            }
            
            $cards.each(function() {
                const $card = $(this);
                const pluginName = $card.find('h3').text().toLowerCase();
                const pluginDesc = $card.find('.plugin-description').text().toLowerCase();
                
                if (pluginName.includes(searchTerm) || pluginDesc.includes(searchTerm)) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
        },

        /**
         * Handle tab keyboard navigation
         */
        handleTabKeyboard: function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                window.location.href = $(this).attr('href');
            }
        },

        /**
         * Handle global keyboard shortcuts
         */
        handleGlobalKeydown: function(e) {
            // Only on dashboard pages
            if (!$('.wbcom-dashboard').length) {
                return;
            }

            // Escape key - reset filters
            if (e.key === 'Escape') {
                $('.filter-btn[data-filter="all"]').trigger('click');
            }
            
            // Alt + number keys for quick tab switching
            if (e.altKey && e.key >= '1' && e.key <= '4') {
                e.preventDefault();
                const tabIndex = parseInt(e.key) - 1;
                const $tabs = $('.nav-tab');
                if ($tabs.eq(tabIndex).length) {
                    $tabs.eq(tabIndex)[0].click();
                }
            }
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
         * Render news feed content
         */
        renderNewsFeed: function(posts) {
            const $newsFeed = $('#wbcom-news-feed');
            let newsHtml = '';
            
            if (posts && posts.length > 0) {
                posts.forEach(function(post) {
                    const excerpt = $('<div>').html(post.excerpt.rendered).text().trim();
                    const date = new Date(post.date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    
                    let featuredImage = '';
                    if (post._embedded && post._embedded['wp:featuredmedia'] && post._embedded['wp:featuredmedia'][0]) {
                        const imageData = post._embedded['wp:featuredmedia'][0];
                        const imageUrl = imageData.media_details && imageData.media_details.sizes && imageData.media_details.sizes.thumbnail 
                            ? imageData.media_details.sizes.thumbnail.source_url 
                            : imageData.source_url;
                        featuredImage = '<div class="news-image"><img src="' + imageUrl + '" alt="' + WbcomDashboard.escapeHtml(post.title.rendered) + '" loading="lazy"></div>';
                    }
                    
                    newsHtml += '<article class="news-item">';
                    newsHtml += featuredImage;
                    newsHtml += '<div class="news-content">';
                    newsHtml += '<h4><a href="' + post.link + '" target="_blank" rel="noopener">' + WbcomDashboard.escapeHtml(post.title.rendered) + '</a></h4>';
                    newsHtml += '<p>' + WbcomDashboard.escapeHtml(excerpt) + '</p>';
                    newsHtml += '<div class="news-meta">';
                    newsHtml += '<time datetime="' + post.date + '">' + date + '</time>';
                    if (post._embedded && post._embedded.author && post._embedded.author[0]) {
                        newsHtml += '<span class="author">by ' + WbcomDashboard.escapeHtml(post._embedded.author[0].name) + '</span>';
                    }
                    newsHtml += '</div>';
                    newsHtml += '</div>';
                    newsHtml += '</article>';
                });
                
                // Add refresh button
                newsHtml += '<div class="news-actions">';
                newsHtml += '<button type="button" class="button button-secondary refresh-news">';
                newsHtml += '<span class="dashicons dashicons-update"></span> Refresh News';
                newsHtml += '</button>';
                newsHtml += '</div>';
            } else {
                newsHtml = '<div class="news-empty">';
                newsHtml += '<p>No news available at the moment.</p>';
                newsHtml += '<a href="https://wbcomdesigns.com/blog/" target="_blank" class="button button-primary">Visit Our Blog</a>';
                newsHtml += '</div>';
            }
            
            $newsFeed.html(newsHtml);
        },

        /**
         * Render news feed error
         */
        renderNewsFeedError: function() {
            const $newsFeed = $('#wbcom-news-feed');
            const errorHtml = '<div class="news-error">' +
                '<p><span class="dashicons dashicons-warning"></span> Unable to load news feed.</p>' +
                '<p>Please check your internet connection or <a href="https://wbcomdesigns.com/blog/" target="_blank">visit our blog</a> directly.</p>' +
                '<button type="button" class="button button-secondary refresh-news">Try Again</button>' +
                '</div>';
            
            $newsFeed.html(errorHtml);
        },

        /**
         * Refresh news feed
         */
        refreshNewsFeed: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $feed = $('#wbcom-news-feed');
            
            // Show loading state
            $btn.prop('disabled', true);
            $btn.find('.dashicons').addClass('wbcom-spin');
            
            $feed.html('<div class="news-loading">' +
                '<span class="spinner is-active"></span>' +
                '<p>Loading latest news...</p>' +
                '</div>');
            
            // Reload news feed
            setTimeout(() => {
                WbcomDashboard.loadNewsFeed();
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('wbcom-spin');
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
                }, 500, 'swing');
            }
        },

        /**
         * Handle plugin card hover
         */
        handleCardHover: function() {
            $(this).addClass('hovered');
        },

        /**
         * Handle plugin card leave
         */
        handleCardLeave: function() {
            $(this).removeClass('hovered');
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA labels to interactive elements
            $('.filter-btn').attr('role', 'button').attr('aria-pressed', 'false');
            $('.wbcom-plugin-card').attr('role', 'article');
            
            // Update ARIA states
            $(document).on('click', '.filter-btn', function() {
                $('.filter-btn').attr('aria-pressed', 'false');
                $(this).attr('aria-pressed', 'true');
            });
            
            // Add live region for announcements
            if ($('#wbcom-live-region').length === 0) {
                $('body').append('<div id="wbcom-live-region" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
            }
        },

        /**
         * Initialize animations
         */
        initAnimations: function() {
            // Check for reduced motion preference
            if (this.prefersReducedMotion()) {
                return;
            }
            
            // Fade in cards on page load
            $('.wbcom-plugin-card').each(function(index) {
                $(this).css('opacity', '0').delay(index * 50).animate({ opacity: 1 }, 300);
            });
            
            // Add intersection observer for scroll animations
            if ('IntersectionObserver' in window) {
                this.initScrollAnimations();
            }
        },

        /**
         * Initialize scroll-based animations
         */
        initScrollAnimations: function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        $(entry.target).addClass('animate-in');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            // Observe elements for animation
            $('.wbcom-sidebar-widget, .premium-plugin-card').each(function() {
                observer.observe(this);
            });
        },

        /**
         * Announce to screen readers
         */
        announceToScreenReader: function(message) {
            $('#wbcom-live-region').text(message);
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            const noticeClass = 'notice-' + type;
            const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>');
            
            $('.wbcom-dashboard h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
            
            // Handle manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(() => $notice.remove());
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
         * Utility function to debounce function calls
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
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
     * Hidden Activities functionality
     */
    const HiddenActivities = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Select/Deselect all buttons for hidden activities
            $('#select-all-hidden').on('click', this.selectAllHidden);
            $('#deselect-all-hidden').on('click', this.deselectAllHidden);
        },

        selectAllHidden: function(e) {
            e.preventDefault();
            $('#bp-hidden-activities-fieldset input[type="checkbox"]').prop('checked', true);
            BPActivityFilterAdmin.validateDefaultFilters();
        },

        deselectAllHidden: function(e) {
            e.preventDefault();
            $('#bp-hidden-activities-fieldset input[type="checkbox"]').prop('checked', false);
            BPActivityFilterAdmin.validateDefaultFilters();
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Always initialize main admin functionality
        BPActivityFilterAdmin.init();
        
        // Initialize dashboard functionality if on dashboard page
        WbcomDashboard.init();
        
        // Initialize hidden activities functionality
        HiddenActivities.init();
        
        // Handle AJAX form submissions if needed
        if (typeof bpActivityFilterAdmin !== 'undefined') {
            // AJAX functionality can be added here
        }
        
        // Handle window resize events for dashboard
        $(window).on('resize', WbcomDashboard.debounce(function() {
            if ($('.wbcom-dashboard').length) {
                WbcomDashboard.updateFilterCounts();
            }
        }, 250));
        
        // Expose objects globally for debugging
        window.BPActivityFilterAdmin = BPActivityFilterAdmin;
        window.WbcomDashboard = WbcomDashboard;
    });

})(jQuery);