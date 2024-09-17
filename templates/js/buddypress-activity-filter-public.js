(function($) {
  'use strict';

  // Ensure jQuery.cookie plugin is used properly and cookie is set when the page is unloaded.
  window.onbeforeunload = function(e) {
    $.cookie("bpaf-default-filter", "1", {
      path: '/'
    });
    $("#activity-filter-by option[value='" + bpaf_js_object.default_filter + "']").prop('selected', true);
  };

  $(document).ready(function() {
    // Set the default filter cookie when the document is ready
    $.cookie("bpaf-default-filter", "1", {
      path: '/'
    });

    // Select the default filter option in the dropdown
    $("#activity-filter-by option[value='" + bpaf_js_object.default_filter + "']").prop('selected', true);

    // Clear the cookie when the activity filter dropdown is clicked
    $('#activity-filter-by').on('click', function() {
      $.removeCookie("bpaf-default-filter", {
        path: '/'
      });
    });
  });

})(jQuery);
