(function( $ ) {
  'use strict';

    window.onbeforeunload = function(e) {
		jQuery.cookie("bpaf-default-filter", "1", { path: '/' });
		jQuery( '#activity-filter-by' ).val( bpaf_js_object.default_filter );
	};
});

window.wp = window.wp || {};
window.bp = window.bp || {};
jQuery(document).ready(function($){
	window.onbeforeunload = function(e) {
		jQuery.cookie("bpaf-default-filter", "1", { path: '/' });
	};
	jQuery.cookie("bpaf-default-filter", "1", { path: '/' });
	jQuery( '#activity-filter-by' ).val( bpaf_js_object.default_filter );
 	jQuery( '#activity-filter-by' ).on( 'click', function(){
 		jQuery.cookie("bpaf-default-filter", null, { path: '/' } );
	});
});	