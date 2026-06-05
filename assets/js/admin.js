/**
 * BuddyPress Activity Filter — Admin JS.
 *
 * Card-panel admin behaviour. Screen-scoped: only runs on the plugin's
 * settings page (the script is enqueued only there). Toggles the visual
 * state of the Hidden Activities rows as the user checks/unchecks them.
 *
 * Skill Part 6 rules 10 + 12: no browser alert()/confirm() anywhere.
 *
 * @since 3.2.1
 */
( function ( $ ) {
	'use strict';

	var i18n = ( window.bpActivityFilterAdmin && window.bpActivityFilterAdmin.i18n ) || {};

	/**
	 * Sync a hidden-activity row's visual state with its checkbox.
	 *
	 * Checked  = hidden  (danger styling, "Hidden" status).
	 * Unchecked = visible (success styling, "Visible" status).
	 *
	 * @param {HTMLElement} checkbox The toggled checkbox element.
	 */
	function syncRowState( checkbox ) {
		var $checkbox = $( checkbox );
		var $row      = $checkbox.closest( '.bpaf-activity-row--toggle' );
		if ( ! $row.length ) {
			return;
		}

		var isHidden = $checkbox.is( ':checked' );
		var $icon    = $row.find( '.bpaf-activity-row__icon' );
		var $status  = $row.find( '.bpaf-activity-row__status' );

		$row.attr( 'data-activity-state', isHidden ? 'hidden' : 'visible' );

		$icon
			.removeClass( 'dashicons-visibility dashicons-hidden' )
			.addClass( isHidden ? 'dashicons-hidden' : 'dashicons-visibility' );

		$status.text( isHidden ? ( i18n.hidden || 'Hidden' ) : ( i18n.visible || 'Visible' ) );
	}

	$( function () {
		// Initialise each toggle row + keep it in sync on change.
		$( '.bpaf-hidden-checkbox' ).each( function () {
			syncRowState( this );
		} );

		$( document ).on( 'change', '.bpaf-hidden-checkbox', function () {
			syncRowState( this );
		} );
	} );

} )( jQuery );
