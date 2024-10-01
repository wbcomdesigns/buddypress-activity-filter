(function($) {
  'use strict';

  // Function to detect if we're on the "just-me" tab in the profile activity
  function isJustMeTab() {
    return $('body').hasClass('bp-user') && $('body').hasClass('my-activity') && bpaf_js_object.current_action === 'just-me';
  }

  // Function to detect if we're on the sitewide activity page
  function isSitewideActivity() {
    return $('body').hasClass('directory') && $('body').hasClass('activity');
  }

  // Function to set the default filter only for the "just-me" tab or sitewide activity
  function setDefaultFilter() {
    if (isJustMeTab() || isSitewideActivity()) {
      // Check if the filter is set via cookie
      var userFilter = $.cookie("bpaf-default-filter");

      // If no cookie is found, use the default filter from the backend (bpaf_js_object.default_filter)
      var selectedFilter = userFilter || bpaf_js_object.default_filter;

      // Set the default filter in the dropdown if a valid filter is found
      if (selectedFilter) {
        var defaultFilterOption = $("#activity-filter-by option[value='" + selectedFilter + "']");
        if (defaultFilterOption.length) {
          defaultFilterOption.prop('selected', true);
        }
      }
    }
    // For all other tabs, skip applying the default filter
  }

  // Function to handle filter changes dynamically and update the activity stream
  function handleFilterChange() {
    $('#activity-filter-by').on('change', function() {
      var selectedFilter = $(this).val();

      // Update the cookie with the newly selected filter
      $.cookie("bpaf-default-filter", selectedFilter, { path: '/' });

      // Prepare AJAX data for updating the activity stream
      var ajaxData = {
        action: 'activity_get_activities',
        cookie: bp_get_cookies(),
        scope: selectedFilter
      };

      // Update the activity stream
      $.post(bp_ajax_url, ajaxData, function(response) {
        $('#activity-stream').html(response.contents);
        $(document).trigger('bp-ajax-activity-loaded');
      });
    });
  }

  $(document).ready(function() {
    // Set the default filter when the page is ready (only for just-me or sitewide)
    setDefaultFilter();

    // Handle filter dropdown change
    handleFilterChange();
  });

})(jQuery);
