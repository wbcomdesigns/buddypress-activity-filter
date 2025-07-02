/**
 * BuddyPress Activity Filter Frontend JavaScript
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

(function($) {
    'use strict';

    /**
     * Frontend object
     */
    const BPActivityFilterFrontend = {
        
        /**
         * Settings from localized script
         */
        settings: {},

        /**
         * Initialize frontend functionality
         */
        init: function() {
            // Check if we have the required object
            if (typeof bpActivityFilter === 'undefined') {
                return;
            }

            this.settings = bpActivityFilter;
            this.bindEvents();
            this.setDefaultFilter();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Activity filter dropdown change
            $(document).on('change', '#activity-filter-by', this.handleFilterChange);
            
            // Component navigation clicks
            $(document).on('click', '.component-navigation li', this.handleNavigationClick);
            
            // Page unload - ensure cookie is set
            $(window).on('beforeunload', this.handlePageUnload);
            
            // AJAX activity requests
            $(document).on('bp_activity_request', this.handleActivityRequest);
            
            // Activity loaded event
            $(document).on('bp_activity_loaded', this.maintainFilterSelection);
        },

        /**
         * Set default filter on page load
         */
        setDefaultFilter: function() {
            if (!this.settings.defaultFilter) {
                return;
            }

            // Set cookie to apply default filter
            this.setCookie('bp_activity_filter_apply', '1', 30 * 60); // 30 minutes
            
            // Set the dropdown selection
            this.selectFilterOption(this.settings.defaultFilter);
        },

        /**
         * Handle filter dropdown change
         */
        handleFilterChange: function() {
            // Remove the apply cookie when user manually changes filter
            BPActivityFilterFrontend.removeCookie('bp_activity_filter_apply');
        },

        /**
         * Handle component navigation clicks
         */
        handleNavigationClick: function() {
            const $navItem = $(this);
            const scope = $navItem.attr('data-bp-scope');
            
            if (!scope) {
                return;
            }

            if (scope !== 'all') {
                // For non-"all" scopes, select "Everything" and remove apply cookie
                BPActivityFilterFrontend.selectFilterOption('0');
                BPActivityFilterFrontend.removeCookie('bp_activity_filter_apply');
            } else {
                // For "all" scope, restore default filter
                BPActivityFilterFrontend.setDefaultFilter();
            }
        },

        /**
         * Handle page unload
         */
        handlePageUnload: function() {
            if ($('#activity-filter-by').length > 0) {
                BPActivityFilterFrontend.setCookie('bp_activity_filter_apply', '1', 30 * 60);
            }
        },

        /**
         * Handle AJAX activity requests
         */
        handleActivityRequest: function() {
            // Ensure default filter is maintained during AJAX requests
            if (BPActivityFilterFrontend.getCookie('bp_activity_filter_apply')) {
                BPActivityFilterFrontend.selectFilterOption(BPActivityFilterFrontend.settings.defaultFilter);
            }
        },

        /**
         * Maintain filter selection after activity loads
         */
        maintainFilterSelection: function() {
            if (BPActivityFilterFrontend.getCookie('bp_activity_filter_apply')) {
                const defaultFilter = BPActivityFilterFrontend.settings.defaultFilter;
                BPActivityFilterFrontend.selectFilterOption(defaultFilter);
            }
        },

        /**
         * Select filter option in dropdown
         */
        selectFilterOption: function(value) {
            const $dropdown = $('#activity-filter-by');
            if ($dropdown.length > 0) {
                $dropdown.find('option').prop('selected', false);
                $dropdown.find('option[value="' + value + '"]').prop('selected', true);
                $dropdown.trigger('change.bp-activity-filter');
            }
        },

        /**
         * Set cookie
         */
        setCookie: function(name, value, seconds) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (seconds * 1000));
            
            document.cookie = name + '=' + encodeURIComponent(value) + 
                             '; expires=' + expires.toUTCString() + 
                             '; path=/; SameSite=Lax';
        },

        /**
         * Get cookie value
         */
        getCookie: function(name) {
            const nameEQ = name + '=';
            const cookies = document.cookie.split(';');
            
            for (let i = 0; i < cookies.length; i++) {
                let cookie = cookies[i];
                while (cookie.charAt(0) === ' ') {
                    cookie = cookie.substring(1, cookie.length);
                }
                if (cookie.indexOf(nameEQ) === 0) {
                    return decodeURIComponent(cookie.substring(nameEQ.length, cookie.length));
                }
            }
            return null;
        },

        /**
         * Remove cookie
         */
        removeCookie: function(name) {
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        BPActivityFilterFrontend.init();
    });

    /**
     * Re-initialize on AJAX content updates
     */
    $(document).on('bp_nouveau_ajax_request', function() {
        // Small delay to ensure content is loaded
        setTimeout(function() {
            BPActivityFilterFrontend.maintainFilterSelection();
        }, 100);
    });

    /**
     * Handle BuddyPress legacy theme AJAX
     */
    if (typeof window.bp !== 'undefined' && window.bp.Activity) {
        // Hook into BuddyPress legacy AJAX events
        $(document).on('bp_ajax_request', function() {
            setTimeout(function() {
                BPActivityFilterFrontend.maintainFilterSelection();
            }, 100);
        });
    }

    /**
     * Expose object globally for debugging
     */
    window.BPActivityFilterFrontend = BPActivityFilterFrontend;

})(jQuery);