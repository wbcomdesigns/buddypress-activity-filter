/**
 * BuddyPress Activity Filter Frontend JavaScript - Minimal Fix
 * Only fixes frontend display without touching admin functionality
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

(function($) {
    'use strict';

    /**
     * Simple frontend functionality - no admin interference
     */
    const BPActivityFilterFrontend = {
        
        /**
         * Settings from backend
         */
        settings: {},

        /**
         * Initialize only if not in admin
         */
        init: function() {
            // Skip entirely if in admin area
            if (this.isAdmin()) {
                return;
            }

            // Check if we have settings from backend
            if (typeof bpActivityFilter !== 'undefined') {
                this.settings = bpActivityFilter;
                this.applyDefaultFilter();
            }
        },

        /**
         * Check if we're in admin area
         */
        isAdmin: function() {
            return $('body').hasClass('wp-admin') || 
                   window.location.pathname.indexOf('/wp-admin/') !== -1 ||
                   typeof window.pagenow !== 'undefined';
        },

        /**
         * Apply default filter from backend settings
         */
        applyDefaultFilter: function() {
            const defaultFilter = this.settings.defaultFilter;
            
            // Only apply if we have a default filter set and it's not "Everything"
            if (!defaultFilter || defaultFilter === '0' || defaultFilter === '-1') {
                return;
            }

            // Set the filter dropdown
            this.setFilterDropdown(defaultFilter);
            
            // Set BuddyPress cookie
            this.setBPCookie('bp-activity-filter', defaultFilter);
        },

        /**
         * Set filter dropdown value
         */
        setFilterDropdown: function(value) {
            const $dropdown = $('#activity-filter-by');
            if ($dropdown.length > 0) {
                $dropdown.val(value);
                $dropdown.find('option[value="' + value + '"]').prop('selected', true);
            }
        },

        /**
         * Set BuddyPress cookie
         */
        setBPCookie: function(name, value) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000)); // 30 days
            
            document.cookie = name + '=' + encodeURIComponent(value) + 
                             '; expires=' + expires.toUTCString() + 
                             '; path=/; SameSite=Lax';
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        BPActivityFilterFrontend.init();
    });

    /**
     * Expose for debugging
     */
    window.BPActivityFilterFrontend = BPActivityFilterFrontend;

})(jQuery);