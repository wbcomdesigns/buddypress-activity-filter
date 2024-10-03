(function ($) {
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
      var selectedFilter = userFilter || bpaf_js_object.default_filter;
      if (selectedFilter) {
        $("#activity-filter-by option[value='" + selectedFilter + "']").prop('selected', true);
      }
    }

  }

  // Function to handle filter changes dynamically and update the activity stream
  function handleFilterChange() {
    // Update the cookie with the newly selected filter
    $.cookie("bpaf-default-filter", "1", {
      path: '/'
    });

    // Select the default filter option in the dropdown
    $("#activity-filter-by option[value='" + bpaf_js_object.default_filter + "']").prop('selected', true);

    // Clear the cookie when the activity filter dropdown is clicked
    $('#activity-filter-by').on('click', function () {
      $.removeCookie("bpaf-default-filter", {
        path: '/'
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