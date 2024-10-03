(function ($) {
  'use strict';
  // Ensure jQuery.cookie plugin is used properly and cookie is set when the page is unloaded.
  window.onbeforeunload = function (e) {
    $.cookie("bpaf-default-filter", "1", {
      path: '/'
    });
    $("#activity-filter-by option[value='" + bpaf_js_object.default_filter + "']").prop('selected', true);
  };
  $(document).ready(function () {
    // Set the default filter cookie when the document is ready
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

    
    /**
     * Handles the click event on component navigation list items.
     * Sets the 'scope' variable to the value of the 'data-bp-scope' attribute of the clicked list item.
     * If the 'scope' is not equal to 'all', it performs an action (not specified in the given code snippet).
     *
     * @listens click Event on '.component-navigation li' elements
     */
    $('.component-navigation li').on('click', function () {
      /**
       * The scope of the clicked list item.
       *
       * @type {string}
       */
      let scope = $(this).attr('data-bp-scope');
    
      // Perform an action when the scope is not 'all'
      if ('all' !== scope) {
        $("#activity-filter-by option[value='" + 0 + "']").prop('selected', true);
        $.removeCookie("bpaf-default-filter", {
          path: '/'
        });
      }else{
        $("#activity-filter-by option[value='" + bpaf_js_object.default_filter + "']").prop('selected', true);
      }
    });

  });
})(jQuery);