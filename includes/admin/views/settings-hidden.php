<?php
/**
 * Settings tab: Hidden Activities.
 *
 * Owns option bp_activity_filter_hidden. Checked = hidden, unchecked =
 * visible. Core types (activity_update / activity_comment) are always
 * visible and cannot be hidden.
 *
 * @package BuddyPress_Activity_Filter
 * @since   3.2.1
 */

defined( 'ABSPATH' ) || exit;

$bpaf_hidden_activities = get_option( 'bp_activity_filter_hidden', array() );
if ( ! is_array( $bpaf_hidden_activities ) ) {
	$bpaf_hidden_activities = array();
}
$bpaf_activity_actions = BP_Activity_Filter_Helper::get_activity_actions();

// Core activities that must never be hidden.
$bpaf_core_readonly = array(
	'activity_update'  => __( 'Posted a status update', 'bp-activity-filter' ),
	'activity_comment' => __( 'Replied to a status update', 'bp-activity-filter' ),
);

// Sentinel: this tab owns the hidden-activities option only. Without it,
// unchecking every box would drop the option from POST and the sanitizer
// would keep the stale value. The hidden sentinel keeps the key present
// so "all unchecked" persists as an empty array. See Playbook 7.1.
?>
<input type="hidden" name="bp_activity_filter_rendered_options[]" value="bp_activity_filter_hidden">

<div class="bpaf-card">
	<div class="bpaf-card__head">
		<p class="bpaf-card__title"><?php esc_html_e( 'Hidden Activity Types', 'bp-activity-filter' ); ?></p>
		<p class="bpaf-card__desc"><?php esc_html_e( 'Hide an activity type to remove it from every stream and from the filter dropdown. Core status updates and replies stay visible.', 'bp-activity-filter' ); ?></p>
	</div>
	<div class="bpaf-card__body">
		<?php if ( empty( $bpaf_activity_actions ) ) : ?>
			<div class="bpaf-notice bpaf-notice--warn">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
				<span><?php esc_html_e( 'No activity types are available. Make sure BuddyPress is installed and active.', 'bp-activity-filter' ); ?></span>
			</div>
		<?php else : ?>

			<p class="bpaf-field-group-label"><?php esc_html_e( 'Core activities (always visible)', 'bp-activity-filter' ); ?></p>
			<ul class="bpaf-activity-list">
				<?php foreach ( $bpaf_core_readonly as $bpaf_key => $bpaf_label ) : ?>
					<li class="bpaf-activity-row bpaf-activity-row--locked">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<span class="bpaf-activity-row__label"><?php echo esc_html( $bpaf_label ); ?></span>
						<span class="bpaf-activity-row__status"><?php esc_html_e( 'Always visible', 'bp-activity-filter' ); ?></span>
						<code class="bpaf-activity-row__key"><?php echo esc_html( $bpaf_key ); ?></code>
					</li>
				<?php endforeach; ?>
			</ul>

			<p class="bpaf-field-group-label"><?php esc_html_e( 'Other activity types', 'bp-activity-filter' ); ?></p>
			<p class="description bpaf-field-group-help"><?php esc_html_e( 'Check a box to hide that activity type from your site.', 'bp-activity-filter' ); ?></p>

			<ul class="bpaf-activity-list">
				<?php
				foreach ( $bpaf_activity_actions as $bpaf_key => $bpaf_label ) :
					if ( isset( $bpaf_core_readonly[ $bpaf_key ] ) ) {
						continue;
					}
					$bpaf_is_hidden   = in_array( $bpaf_key, $bpaf_hidden_activities, true );
					$bpaf_checkbox_id = 'bp_hidden_' . sanitize_html_class( $bpaf_key );
					$bpaf_state       = $bpaf_is_hidden ? 'hidden' : 'visible';
					?>
					<li class="bpaf-activity-row bpaf-activity-row--toggle" data-activity-state="<?php echo esc_attr( $bpaf_state ); ?>">
						<label for="<?php echo esc_attr( $bpaf_checkbox_id ); ?>" class="bpaf-activity-row__label-wrap">
							<input type="checkbox"
								id="<?php echo esc_attr( $bpaf_checkbox_id ); ?>"
								class="bpaf-hidden-checkbox"
								name="bp_activity_filter_hidden[]"
								value="<?php echo esc_attr( $bpaf_key ); ?>"
								<?php checked( $bpaf_is_hidden ); ?>>
							<span class="dashicons bpaf-activity-row__icon" aria-hidden="true"></span>
							<span class="bpaf-activity-row__label"><?php echo esc_html( $bpaf_label ); ?></span>
							<span class="bpaf-activity-row__status" aria-hidden="true"></span>
							<code class="bpaf-activity-row__key"><?php echo esc_html( $bpaf_key ); ?></code>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="bpaf-notice bpaf-notice--info">
				<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Visible types appear in streams and the filter dropdown. Hidden types are removed entirely. Core types cannot be hidden.', 'bp-activity-filter' ); ?></span>
			</div>

		<?php endif; ?>
	</div>
</div>
