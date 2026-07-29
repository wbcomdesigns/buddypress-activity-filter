<?php
/**
 * Frontend functionality for BuddyPress Activity Filter - FIXED VERSION
 *
 * This version works WITH BuddyPress instead of against it
 *
 * @package BuddyPress_Activity_Filter
 * @since 3.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class - MINIMAL INTERFERENCE APPROACH
 *
 * @since 3.1.0
 */
class BP_Activity_Filter_Frontend {

	/**
	 * Cookie recording the default this browser has already applied.
	 *
	 * Not a preference - it exists purely so we can tell whether the site owner has
	 * changed the default since the member last picked a filter of their own. See
	 * get_member_preference().
	 *
	 * @since 3.2.1
	 * @var string
	 */
	const DEFAULT_STAMP = 'bpaf-applied-default';

	/**
	 * Class instance.
	 *
	 * @since 3.1.0
	 * @var BP_Activity_Filter_Frontend|null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 3.1.0
	 * @return BP_Activity_Filter_Frontend Singleton instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 */
	private function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup frontend hooks - Server-side approach
	 *
	 * @since 3.1.0
	 */
	private function setup_hooks() {
		// Server-side default filter - runs BEFORE activities are queried.
		add_filter( 'bp_after_has_activities_parse_args', array( $this, 'apply_default_filter_server_side' ), 10, 1 );

		// Exclude hidden types from the stream itself. Blocking creation only stops new
		// items, so activities recorded before a type was hidden would still be listed.
		// This must be a WHERE condition, not a filter_query arg: BP_Activity_Activity::get()
		// only honours filter_query when no scope is set, so scoped streams (just-me,
		// friends, groups, mentions, favorites) would ignore it.
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'exclude_hidden_from_query' ), 10, 2 );

		// Also filter AJAX requests.
		add_filter( 'bp_ajax_querystring', array( $this, 'apply_default_filter_ajax' ), 10, 2 );

		// Retire a member's stored filter when the owner changes the default. This has
		// to run in the head, BEFORE BuddyPress reads sessionStorage in initObjects(),
		// or BP has already re-applied the stale filter by the time we could clear it.
		add_action( 'wp_head', array( $this, 'reset_stored_filter_on_default_change' ), 1 );

		// Reflect the applied filter in the dropdown (minimal JS just for UI sync).
		add_action( 'wp_footer', array( $this, 'sync_dropdown_with_default' ), 999 );

		// Remove hidden activities from dropdown (but don't interfere with filtering).
		// Filter the options array, not the built HTML: Nouveau hooks
		// bp_get_activity_show_filters at the same priority and returns the original
		// $filters array, which would discard any edit made to the HTML output.
		add_filter( 'bp_get_activity_show_filters_options', array( $this, 'remove_hidden_from_dropdown' ), 10, 2 );

		// Prevent hidden activities from being created (at source).
		// Use very early priority to catch before other plugins.
		// This is the single enforcement point: BP_Activity_Activity::save() aborts on
		// an empty component/type, so it covers every hidden type regardless of which
		// component records it.
		add_action( 'bp_activity_before_save', array( $this, 'maybe_prevent_activity_save' ), 1 );
	}

	/**
	 * Apply default filter server-side.
	 *
	 * @since 3.1.0
	 * @param array $args Activity query arguments.
	 * @return array Modified arguments.
	 */
	public function apply_default_filter_server_side( $args ) {
		// Skip if already filtered or if specific type is requested.
		if ( ! empty( $args['filter_query'] ) || ! empty( $args['action'] ) || ! empty( $args['type'] ) ) {
			return $args;
		}

		// The member picked a filter on this screen: honour it and never override it.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && $this->member_chose_filter_this_request() ) {
			return $args;
		}

		// The member's own filter wins, unless the owner has changed the default since
		// the member last saw it - then their remembered choice is stale.
		$preference = $this->get_member_preference();
		if ( null !== $preference ) {
			// '' means they picked Everything: apply no filter at all, and do NOT fall
			// through to the admin default.
			if ( '' !== $preference ) {
				$args['action'] = $preference;
			}

			return $args;
		}

		// No preference at all, apply admin default.
		$default_filter = $this->get_default_filter();
		if ( $default_filter && '0' !== $default_filter && '-1' !== $default_filter ) {
			$args['action'] = $default_filter;
		}

		return $args;
	}

	/**
	 * True when this request carries a filter the member actually picked.
	 *
	 * BuddyPress sends the `filter` key on EVERY activity request, so its presence proves
	 * nothing. What distinguishes the two cases is the value, verified against BP's own
	 * AJAX payload:
	 *
	 *   filter=                 member has never touched the dropdown - apply the default
	 *   filter=0  / filter=-1   member picked "- Everything -"        - apply NO filter
	 *   filter=activity_update  member picked a type                  - apply that type
	 *
	 * So the test is "non-empty", not isset() and not ! empty(): empty( '0' ) is true in
	 * PHP, which is exactly how an explicit "show me everything" got mistaken for silence
	 * and had the admin default forced back over it.
	 *
	 * @since 3.2.1
	 * @return bool
	 */
	private function member_chose_filter_this_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only UI filter set by BuddyPress; BP verifies its own nonce.
		if ( ! isset( $_POST['filter'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only UI filter set by BuddyPress; BP verifies its own nonce.
		return '' !== trim( sanitize_text_field( wp_unslash( $_POST['filter'] ) ) );
	}

	/**
	 * What the member has chosen for themselves, if anything.
	 *
	 * Three distinct answers, and they must stay distinct:
	 *
	 *   null - no choice. Fall through to the admin default.
	 *   ''   - they chose "- Everything -". Apply NO filter, and do NOT fall through to
	 *          the admin default.
	 *   type - they chose that activity type. Apply it.
	 *
	 * Collapsing the first two is what made an explicit "Everything" impossible to honour:
	 * BuddyPress uses "0"/"-1" as the literal option value for "- Everything -", so a
	 * member who picked it looked identical to a member who had never touched the
	 * dropdown, and the admin default was quietly forced back on. The control said
	 * Everything while the stream stayed filtered.
	 *
	 * A stale choice (the owner changed the default since this browser last applied one)
	 * counts as no choice, so the new default wins.
	 *
	 * @since 3.2.1
	 * @return string|null Activity type, '' for Everything, or null for no choice.
	 */
	private function get_member_preference() {
		if ( $this->default_changed_since_last_seen() ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI preference set by BuddyPress.
		if ( ! isset( $_COOKIE['bp-activity-filter'] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI preference set by BuddyPress.
		$preference = sanitize_text_field( wp_unslash( $_COOKIE['bp-activity-filter'] ) );

		// BuddyPress's own value for "- Everything -". A deliberate choice, not silence.
		if ( '' === $preference || '0' === $preference || '-1' === $preference ) {
			return '';
		}

		return $preference;
	}

	/**
	 * True when the defaults have changed since this browser last applied them.
	 *
	 * The stamp is written client-side (see reset_stored_filter_on_default_change), so on
	 * a first visit there is no stamp and this reports true - which is correct, since a
	 * fresh visitor has no preference to protect and should get the current default.
	 *
	 * @since 3.2.1
	 * @return bool
	 */
	private function default_changed_since_last_seen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI stamp.
		$stamp = isset( $_COOKIE[ self::DEFAULT_STAMP ] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI stamp.
			? sanitize_text_field( wp_unslash( $_COOKIE[ self::DEFAULT_STAMP ] ) )
			: '';

		return $this->get_defaults_signature() !== $stamp;
	}

	/**
	 * Signature of the whole default configuration, both screens together.
	 *
	 * Deliberately NOT just the default for the current screen. There are two independent
	 * settings (site-wide and profile) but BuddyPress keeps only ONE remembered filter per
	 * member, shared across both streams. Stamping only the current screen's default meant
	 * that when the two settings happened to hold the same value, changing one looked
	 * unchanged to the other, and the member's stale filter survived on that screen.
	 *
	 * Stamping the pair means any change to either default re-asserts both, which is what
	 * "modify the default and it takes effect" has to mean when the memory is shared.
	 *
	 * @since 3.2.1
	 * @return string
	 */
	private function get_defaults_signature() {
		$sitewide = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_default', '0' );
		$profile  = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_profile_default', '-1' );

		return (string) $sitewide . '|' . (string) $profile;
	}

	/**
	 * Exclude hidden activity types from activity stream queries.
	 *
	 * Preventing hidden types from being saved only stops new items. Activities
	 * recorded before an admin hid that type are already in the database and would
	 * still show in the stream, so they have to be excluded at query time too.
	 *
	 * BuddyPress ANDs every condition returned here and applies them after the
	 * scope/filter_query branch, so this covers the directory, scoped streams
	 * (just-me, friends, groups, mentions, favorites), AJAX refreshes, and the
	 * pagination count, which is built from the same WHERE clause.
	 *
	 * The site admin's own Activity screen is left alone so hidden items remain
	 * moderatable in the backend.
	 *
	 * @since 3.1.0
	 * @param array $where_conditions Current WHERE conditions.
	 * @param array $args             Parsed activity query arguments.
	 * @return array WHERE conditions with hidden types excluded.
	 */
	public function exclude_hidden_from_query( $where_conditions, $args = array() ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $where_conditions;
		}

		$hidden_activities = $this->get_hidden_activities();

		if ( empty( $hidden_activities ) ) {
			return $where_conditions;
		}

		$not_in = "'" . implode( "', '", array_map( 'esc_sql', $hidden_activities ) ) . "'";

		$where_conditions['bp_activity_filter_hidden'] = "a.type NOT IN ({$not_in})";

		return $where_conditions;
	}

	/**
	 * Apply filter to AJAX requests.
	 *
	 * @since 3.1.0
	 * @param string $query_string The query string.
	 * @param string $object       The object type.
	 * @return string Modified query string.
	 */
	public function apply_default_filter_ajax( $query_string, $object ) {
		if ( 'activity' !== $object ) {
			return $query_string;
		}

		// Parse existing query string.
		wp_parse_str( $query_string, $args );

		// If action/type already set, don't override.
		if ( ! empty( $args['action'] ) || ! empty( $args['type'] ) ) {
			return $query_string;
		}

		// The member picked a filter on this screen: honour it and never override it.
		if ( $this->member_chose_filter_this_request() ) {
			return $query_string;
		}

		// The member's own filter wins, unless the owner has changed the default since.
		$preference = $this->get_member_preference();
		if ( null !== $preference ) {
			// '' means Everything: no filter, and no falling back to the admin default.
			if ( '' !== $preference ) {
				$args['action'] = $preference;
			}

			return http_build_query( $args );
		}

		// No preference at all, use admin default.
		$default_filter = $this->get_default_filter();
		if ( $default_filter && '0' !== $default_filter && '-1' !== $default_filter ) {
			$args['action'] = $default_filter;
			return http_build_query( $args );
		}

		return $query_string;
	}

	/**
	 * Drop the member's remembered filter when the site owner changes the default.
	 *
	 * A default that a member's session can permanently outrank is not a default. When a
	 * member picks a filter, BuddyPress remembers it in sessionStorage under "bp-activity"
	 * and replays it on every subsequent page load, so the owner's new default was never
	 * shown to anyone who had ever touched the dropdown - the exact "modify the default and
	 * the old one persists" report.
	 *
	 * So we stamp the default we last applied. When the stored stamp no longer matches the
	 * configured default, the owner has changed it since this member last looked: their
	 * remembered filter is stale, and it is dropped so the new default applies. A member's
	 * own choice still survives ordinary reloads, which is the behaviour they expect.
	 *
	 * This MUST run before BuddyPress Nouveau's initObjects() reads sessionStorage (it is
	 * what re-applies the remembered filter and requests the stream). Hence wp_head at
	 * priority 1, not DOMContentLoaded - by then BP has already asked for the stale stream.
	 *
	 * @since 3.2.1
	 */
	public function reset_stored_filter_on_default_change() {
		if ( ! $this->is_stream_with_default() ) {
			return;
		}

		?>
		<script type="text/javascript">
		(function () {
			try {
				var current = <?php echo wp_json_encode( $this->get_defaults_signature() ); ?>;
				var STAMP   = <?php echo wp_json_encode( self::DEFAULT_STAMP ); ?>;
				var stamped = null;

				document.cookie.split( ';' ).forEach( function ( pair ) {
					var bits = pair.split( '=' );
					if ( bits[0].trim() === STAMP ) {
						stamped = decodeURIComponent( bits.slice( 1 ).join( '=' ) );
					}
				} );

				if ( stamped === current ) {
					return; // Default unchanged - the member's own choice stands.
				}

				/*
				 * The owner changed the default since this browser last applied one, so
				 * whatever filter BuddyPress remembered for this member is stale. Drop it
				 * from both places BP keeps it - sessionStorage on Nouveau, the cookie on
				 * Legacy - and record the default we are now applying.
				 */
				var store = JSON.parse( window.sessionStorage.getItem( 'bp-activity' ) || '{}' );
				delete store.filter;
				window.sessionStorage.setItem( 'bp-activity', JSON.stringify( store ) );

				document.cookie = 'bp-activity-filter=; path=/; max-age=0';
				document.cookie = STAMP + '=' + encodeURIComponent( current ) + '; path=/; max-age=31536000; samesite=lax';
			} catch ( e ) {}
		})();
		</script>
		<?php
	}

	/**
	 * True on the two streams this plugin applies a default to.
	 *
	 * @since 3.2.1
	 * @return bool
	 */
	private function is_stream_with_default() {
		if ( function_exists( 'bp_is_activity_directory' ) && bp_is_activity_directory() ) {
			return true;
		}

		return function_exists( 'bp_is_user_activity' ) && bp_is_user_activity();
	}

	/**
	 * Sync dropdown with server-side default (minimal JS just for UI)
	 *
	 * @since 3.1.0
	 */
	public function sync_dropdown_with_default() {
		// Only on activity pages.
		if ( ! $this->is_stream_with_default() ) {
			return;
		}

		// Show whatever the server actually applied: the member's own choice, or the admin
		// default when they have none (or when theirs went stale on a change). A member who
		// chose Everything ('') gets no forced selection - BuddyPress already shows
		// Everything, and overriding it here is what made the control disagree with the
		// stream.
		$preference      = $this->get_member_preference();
		$filter_to_apply = null !== $preference ? $preference : $this->get_default_filter();

		// Nothing to select: leave BuddyPress's own "- Everything -" alone.
		if ( ! $filter_to_apply || '0' === $filter_to_apply || '-1' === $filter_to_apply ) {
			return;
		}

		?>
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			// Wait a moment for BuddyPress to initialize.
			setTimeout(function() {
				var dropdown = document.getElementById('activity-filter-by');
				if (!dropdown) {
					return;
				}

				var filterValue = '<?php echo esc_js( $filter_to_apply ); ?>';

				/*
				 * The member's own filter choice wins over the admin default.
				 *
				 * BuddyPress Nouveau remembers it in sessionStorage under "bp-activity"
				 * and re-applies it to the stream over AJAX. If we overwrote the dropdown
				 * with the admin default here, the control would claim one filter while
				 * the stream below it showed another. Mirror what BuddyPress actually
				 * applied instead.
				 */
				try {
					var bpState = JSON.parse(window.sessionStorage.getItem('bp-activity') || 'null');
					if (bpState && bpState.filter) {
						filterValue = bpState.filter;
					}
				} catch (e) {}

				// BuddyPress renders some options under a combined key, e.g. friendships
				// are listed as "friendship_accepted,friendship_created". Assigning the
				// single type to dropdown.value would not match any option and would
				// leave the dropdown blank, so match on the combined parts as well.
				var selected = Array.prototype.filter.call(dropdown.options, function(option) {
					return option.value === filterValue || option.value.split(',').indexOf(filterValue) !== -1;
				})[0];

				if (!selected) {
					return;
				}

				// Only reflect the value in the dropdown. Do NOT write the
				// bp-activity-filter cookie here.
				//
				// That cookie is the *member's own* choice, and it outranks the admin
				// default. Writing the default into it turned the admin's setting into a
				// permanent per-visitor preference: once a visitor had loaded the page,
				// their cookie pinned the old value and the site owner could never change
				// the default for them again. BuddyPress sets this cookie itself when the
				// member actually picks a filter, which is the only time it should be set.
				dropdown.value = selected.value;
			}, 50);
		});
		</script>
		<?php
	}

	/**
	 * Remove hidden activity types from the filter dropdown options.
	 *
	 * Runs on the options array before the dropdown HTML is built, so both the
	 * Legacy template pack (which renders the HTML) and Nouveau (which re-reads
	 * the raw array) see the same reduced set.
	 *
	 * @since 3.1.0
	 * @param array  $filters Activity filter options, keyed by option value.
	 * @param string $context The current context.
	 * @return array Filter options without the hidden activity types.
	 */
	public function remove_hidden_from_dropdown( $filters, $context = '' ) {
		if ( ! is_array( $filters ) ) {
			return $filters;
		}

		$hidden_activities = $this->get_hidden_activities();

		if ( ! empty( $hidden_activities ) ) {
			// BuddyPress records one "friendship_created" activity for both friendship
			// hooks and collapses the pair into a single "friendship_accepted,friendship_created"
			// option, so hiding one side must account for the other.
			if ( in_array( 'friendship_created', $hidden_activities, true ) ) {
				$hidden_activities[] = 'friendship_accepted';
			}

			foreach ( array_keys( $filters ) as $option_value ) {
				$types = array_map( 'trim', explode( ',', (string) $option_value ) );

				// Drop the option only when every type it would show is hidden.
				if ( ! array_diff( $types, $hidden_activities ) ) {
					unset( $filters[ $option_value ] );
				}
			}
		}

		return $this->ensure_default_is_selectable( $filters, $hidden_activities );
	}

	/**
	 * Make sure the configured default is an option the dropdown can actually show.
	 *
	 * BuddyPress builds a different option list per context. The member list, for
	 * example, has no "Profile Updates" entry - that type only appears site-wide. But
	 * the settings screen happily lets an admin pick it as the profile default, so the
	 * stream was correctly filtered to profile updates while the dropdown had no such
	 * option to select and fell back to "Everything": the control contradicted the
	 * stream it sat above.
	 *
	 * Rather than take the choice away from the admin, add the missing option so the
	 * control can represent the state the server actually applied.
	 *
	 * @since 3.1.0
	 * @param array $filters           Dropdown options, keyed by option value.
	 * @param array $hidden_activities Types the admin has hidden.
	 * @return array Options, including the active default.
	 */
	private function ensure_default_is_selectable( $filters, $hidden_activities ) {
		$default = $this->get_default_filter();

		if ( ! $default || '0' === $default || '-1' === $default ) {
			return $filters;
		}

		// A hidden type must never be offered, even if it is the configured default.
		if ( in_array( $default, $hidden_activities, true ) ) {
			return $filters;
		}

		// Already present, either on its own or inside a combined key such as
		// "friendship_accepted,friendship_created".
		foreach ( array_keys( $filters ) as $option_value ) {
			$types = array_map( 'trim', explode( ',', (string) $option_value ) );
			if ( in_array( $default, $types, true ) ) {
				return $filters;
			}
		}

		/*
		 * Prefer the label BuddyPress itself uses for this type in the site-wide
		 * dropdown, so the option reads the same wherever a member meets it. Fall back
		 * to the plugin's own action list if BuddyPress has no label for it.
		 */
		$label = '';

		if ( function_exists( 'bp_activity_get_actions_for_context' ) ) {
			foreach ( bp_activity_get_actions_for_context( 'activity' ) as $action ) {
				if ( isset( $action['key'], $action['value'] ) && $default === $action['key'] ) {
					$label = $action['value'];
					break;
				}
			}
		}

		if ( '' === $label ) {
			$labels = BP_Activity_Filter_Helper::get_activity_actions();
			$label  = isset( $labels[ $default ] ) ? $labels[ $default ] : '';
		}

		if ( '' !== $label ) {
			$filters[ $default ] = $label;
		}

		return $filters;
	}

	/**
	 * Prevent hidden activities from being saved (at the source).
	 *
	 * @since 3.1.0
	 * @param BP_Activity_Activity $activity The activity object.
	 */
	public function maybe_prevent_activity_save( $activity ) {
		if ( ! isset( $activity->type ) ) {
			return;
		}

		$hidden_activities = $this->get_hidden_activities();

		// If this activity type is hidden, prevent it from being saved.
		if ( ! empty( $hidden_activities ) && in_array( $activity->type, $hidden_activities, true ) ) {
			// Store original type for error message.
			$original_type = $activity->type;

			// Multiple strategies to prevent save.
			// 1. Set type to empty string (BuddyPress checks for empty type).
			$activity->type = '';

			// 2. Also clear component to ensure save fails.
			$activity->component = '';

			// 3. Add an error if the activity object supports it.
			if ( isset( $activity->errors ) && is_wp_error( $activity->errors ) ) {
				$activity->errors->add(
					'bp_activity_type_disabled',
					/* translators: %s: The activity type that has been disabled. */
					sprintf( __( 'Activity type "%s" has been disabled by administrator.', 'bp-activity-filter' ), $original_type )
				);
			}
		}
	}

	/**
	 * Get default filter based on current context
	 *
	 * @since 3.1.0
	 * @return string Default filter value
	 */
	private function get_default_filter() {
		$context = $this->get_filter_context();

		if ( 'profile' === $context ) {
			$default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_profile_default', '-1' );
		} elseif ( 'sitewide' === $context ) {
			$default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_default', '0' );
		} else {
			// No default is configured for this screen (group streams, single activity,
			// anywhere else). Applying the site-wide default here would filter a stream
			// the setting was never meant to touch, and the dropdown on that screen is
			// not synced, so the member would see a filtered stream above a control that
			// says "Everything".
			$default_filter = '';
		}

		/**
		 * Filter the default activity filter value.
		 *
		 * @since 3.1.0
		 *
		 * @param string $default_filter Default filter value.
		 * @param string $context        Filter context (profile, sitewide).
		 */
		return apply_filters( 'bp_activity_filter_default', $default_filter, $context );
	}

	/**
	 * Get current filter context.
	 *
	 * Only two screens have a default configured: the site-wide activity directory and
	 * a member's own activity. Everything else - group activity streams above all -
	 * returns an empty context so no default is forced on it.
	 *
	 * This used to fall through to 'sitewide' for every other screen, which meant that
	 * setting a site-wide default of, say, "Posts" silently emptied every group's
	 * activity stream: the group query was filtered down to blog posts, while the
	 * filter dropdown on that page still read "Everything".
	 *
	 * @since 3.1.0
	 * @return string Context: 'profile', 'sitewide', or '' when no default applies.
	 */
	private function get_filter_context() {
		if ( function_exists( 'bp_is_user_activity' ) && bp_is_user_activity() ) {
			return 'profile';
		}

		if ( function_exists( 'bp_is_activity_directory' ) && bp_is_activity_directory() ) {
			return 'sitewide';
		}

		return '';
	}

	/**
	 * Get hidden activities list
	 *
	 * @since 3.1.0
	 * @return array List of hidden activity types
	 */
	private function get_hidden_activities() {
		// Get the option directly and handle serialization.
		$hidden_activities = get_option( 'bp_activity_filter_hidden', array() );

		// Ensure we have an array (handle serialized data).
		if ( ! is_array( $hidden_activities ) ) {
			$hidden_activities = maybe_unserialize( $hidden_activities );
			if ( ! is_array( $hidden_activities ) ) {
				$hidden_activities = array();
			}
		}

		// Core activities that should never be hidden.
		$core_protected_activities = array(
			'activity_update',
			'activity_comment',
		);

		// Remove any core activities from hidden list (safety protection).
		$hidden_activities = array_diff( $hidden_activities, $core_protected_activities );

		return $hidden_activities;
	}

	/**
	 * Prevent cloning
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing
	 */
	public function __wakeup() {}
}