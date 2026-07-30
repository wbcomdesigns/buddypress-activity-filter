<?php
/**
 * Settings tab: Default Filters (site-wide + profile).
 *
 * Owns options bp_activity_filter_default + bp_activity_filter_profile_default.
 * Ports the legacy "Default Filters" tab 1:1 into the card-panel shell.
 *
 * @package BuddyPress_Activity_Filter
 * @since   3.2.1
 */

defined( 'ABSPATH' ) || exit;

$bpaf_default_filter         = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_default', '0' );
$bpaf_profile_default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_profile_default', '-1' );
$bpaf_activity_actions       = BP_Activity_Filter_Helper::get_activity_actions();

// Sentinel: tells the sanitizer which option keys this tab owns so saving
// here never wipes the Hidden options. See Playbook 7.1 and
// BP_Activity_Filter_Admin_Panel::tab_rendered_option().
$bpaf_rendered_options = array(
	'bp_activity_filter_default',
	'bp_activity_filter_profile_default',
);
foreach ( $bpaf_rendered_options as $bpaf_rendered_option ) :
	?>
	<input type="hidden" name="bp_activity_filter_rendered_options[]" value="<?php echo esc_attr( $bpaf_rendered_option ); ?>">
<?php endforeach; ?>

<div class="bpaf-card">
	<div class="bpaf-card__head">
		<p class="bpaf-card__title"><?php esc_html_e( 'Default Activity Filters', 'buddypress-activity-filter' ); ?></p>
		<p class="bpaf-card__desc"><?php esc_html_e( 'Choose what each activity stream shows by default. Members can still switch the filter themselves using the activity dropdown.', 'buddypress-activity-filter' ); ?></p>
	</div>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label for="bp_activity_filter_default"><?php esc_html_e( 'Site-wide stream default', 'buddypress-activity-filter' ); ?></label>
				</th>
				<td>
					<select name="bp_activity_filter_default" id="bp_activity_filter_default" class="regular-text">
						<option value="0" <?php selected( $bpaf_default_filter, '0' ); ?>>
							<?php esc_html_e( 'Everything', 'buddypress-activity-filter' ); ?>
						</option>
						<?php foreach ( $bpaf_activity_actions as $bpaf_key => $bpaf_label ) : ?>
							<option value="<?php echo esc_attr( $bpaf_key ); ?>" <?php selected( $bpaf_default_filter, $bpaf_key ); ?>>
								<?php echo esc_html( $bpaf_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Applied to the main site-wide activity stream the first time a visitor loads it.', 'buddypress-activity-filter' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bp_activity_filter_profile_default"><?php esc_html_e( 'Profile stream default', 'buddypress-activity-filter' ); ?></label>
				</th>
				<td>
					<select name="bp_activity_filter_profile_default" id="bp_activity_filter_profile_default" class="regular-text">
						<option value="-1" <?php selected( $bpaf_profile_default_filter, '-1' ); ?>>
							<?php esc_html_e( 'Everything', 'buddypress-activity-filter' ); ?>
						</option>
						<?php foreach ( $bpaf_activity_actions as $bpaf_key => $bpaf_label ) : ?>
							<?php if ( ! in_array( $bpaf_key, array( 'new_member', 'updated_profile' ), true ) ) : ?>
								<option value="<?php echo esc_attr( $bpaf_key ); ?>" <?php selected( $bpaf_profile_default_filter, $bpaf_key ); ?>>
									<?php echo esc_html( $bpaf_label ); ?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Applied to each member profile activity stream. Profile-irrelevant types such as "New Member" are left out of this list.', 'buddypress-activity-filter' ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
</div>
