<?php
/**
 * Defining class for Filter dropdown option for public setting.
 *
 * @package BuddyPress_Activity_Filter
 */

if ( ! class_exists( 'WbCom_BP_Activity_Filter_Public_Setting' ) ) {

	/**
	 * Defining class for Filter dropdown option for public setting.
	 *
	 * @package BuddyPress_Activity_Filter
	 */
	class WbCom_BP_Activity_Filter_Public_Setting {

		/**
		 * Constructor
		 */
		public function __construct() {

			/**
			 * Showing selected filters in dropdown
			 */
			add_filter( 'bp_get_activity_show_filters', array( $this, 'getting_all_filters_function' ), 11, 3 );

			/* Clearing cookie for correct result */
			$past = time() - 3600;

			if ( isset( $_COOKIE['bp-activity-filter'] ) ) {
				setcookie( 'bp-activity-filter', ' ', $past, '/' );
			}

		}

		/**
		 * Populating dropdown with selected filter on front-end
		 *
		 * @param string $output Output.
		 * @param array  $filters Filters.
		 * @param array  $context  Context.
		 * @return string
		 */
		public function getting_all_filters_function( $output, $filters, $context ) {
			// Build the options output.
			$output = '';

			$filters_db = bp_get_option( 'bp-hidden-filters-name' );

			// Ensure $filters_db is always an array
			if ( ! is_array( $filters_db ) ) {
				$filters_db = array();
			}

			if ( ! empty( $filters_db ) ) {
				foreach ( $filters as $key => $value ) {
					if ( in_array( $key, $filters_db, true ) ) {
						unset( $filters[ $key ] );
					}
				}
			}

			if ( ! empty( $filters ) ) {
				$defult_activity_stream = bp_get_option( 'bp-default-filter-name' );
				foreach ( $filters as $value => $filter ) {
					if ( $value == $defult_activity_stream ) {
						$output .= '<option value="' . esc_attr( $value ) . '" selected=selected>' . esc_html( $filter ) . '</option>' . "\n";
					} else {
						$output .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $filter ) . '</option>' . "\n";
					}
				}
			}

			$bp_template_option = bp_get_option( '_bp_theme_package_id' );
			if ( class_exists( 'BBP_BuddyPress_Activity' ) ) {
				remove_action( 'bp_activity_filter_options', array( bbpress()->extend->buddypress->activity, 'activity_filter_options' ), 10 );
				add_action( 'bp_activity_filter_options', array( $this, 'bp_activity_filter_options' ), 10 );
			}
			if ( 'nouveau' === $bp_template_option ) {
				if ( class_exists( 'Youzify' ) ) {
					return $output;
				} else {
					return array(
						'filters' => $filters,
						'context' => $context,
					);
				}
			} else {
				return $output;
			}
		}

		/**
		 * Fires inside the select input for activity filter by options.
		 *
		 * @return void
		 */
		public function bp_activity_filter_options() {
			$filters_db = bp_get_option( 'bp-hidden-filters-name' );

			// Ensure $filters_db is always an array
			if ( ! is_array( $filters_db ) ) {
				$filters_db = array();
			}

			if ( ! in_array( 'bbp_topic_create', $filters_db, true ) ) {
				?>
				<option value="bbp_topic_create"><?php esc_html_e( 'Topics', 'bp-activity-filter' ); ?></option>
				<?php
			}
			if ( ! in_array( 'bbp_reply_create', $filters_db, true ) ) {
				?>
				<option value="bbp_reply_create"><?php esc_html_e( 'Replies', 'bp-activity-filter' ); ?></option>
				<?php
			}
		}

	}

}

if ( class_exists( 'WbCom_BP_Activity_Filter_Public_Setting' ) ) {

	$filter_obj = new WbCom_BP_Activity_Filter_Public_Setting();

}
