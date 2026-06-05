<?php
/**
 * Settings tab: Custom Post Types.
 *
 * Owns option bp_activity_filter_cpt_settings. Enables automatic activity
 * generation for public custom post types when they are published, with
 * an optional custom label and a global "hide from site-wide stream" flag.
 *
 * @package BuddyPress_Activity_Filter
 * @since   3.2.1
 */

defined( 'ABSPATH' ) || exit;

$bpaf_cpt_settings = get_option( 'bp_activity_filter_cpt_settings', array() );
if ( ! is_array( $bpaf_cpt_settings ) ) {
	$bpaf_cpt_settings = array();
}
$bpaf_post_types = BP_Activity_Filter_Helper::get_eligible_post_types();

// Sentinel: this tab owns the CPT settings option only. See Playbook 7.1.
?>
<input type="hidden" name="bp_activity_filter_rendered_options[]" value="bp_activity_filter_cpt_settings">

<div class="bpaf-card">
	<div class="bpaf-card__head">
		<p class="bpaf-card__title"><?php esc_html_e( 'Custom Post Type Activities', 'bp-activity-filter' ); ?></p>
		<p class="bpaf-card__desc"><?php esc_html_e( 'Turn on a post type to create an activity entry whenever one of its posts is published. Only public custom post types with an admin screen are listed.', 'bp-activity-filter' ); ?></p>
	</div>
	<div class="bpaf-card__body">
		<?php if ( empty( $bpaf_post_types ) ) : ?>
			<div class="bpaf-notice bpaf-notice--info">
				<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
				<span>
					<strong><?php esc_html_e( 'No eligible custom post types found.', 'bp-activity-filter' ); ?></strong>
					<?php esc_html_e( 'A post type must be public, have an admin screen, and support a title to appear here. Built-in posts and pages have their own activity systems and are not listed.', 'bp-activity-filter' ); ?>
				</span>
			</div>
		<?php else : ?>
			<?php foreach ( $bpaf_post_types as $bpaf_post_type => $bpaf_post_type_obj ) : ?>
				<?php
				$bpaf_enabled     = isset( $bpaf_cpt_settings[ $bpaf_post_type ]['enabled'] ) ? $bpaf_cpt_settings[ $bpaf_post_type ]['enabled'] : false;
				$bpaf_label       = isset( $bpaf_cpt_settings[ $bpaf_post_type ]['label'] ) ? $bpaf_cpt_settings[ $bpaf_post_type ]['label'] : '';
				$bpaf_post_count  = wp_count_posts( $bpaf_post_type );
				$bpaf_total_posts = isset( $bpaf_post_count->publish ) ? (int) $bpaf_post_count->publish : 0;
				?>
				<div class="bpaf-cpt-item">
					<label class="bpaf-cpt-item__toggle">
						<input type="checkbox"
							name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $bpaf_post_type ); ?>][enabled]"
							value="1" <?php checked( $bpaf_enabled ); ?>>
						<span class="bpaf-cpt-item__name"><?php echo esc_html( $bpaf_post_type_obj->label ); ?></span>
						<span class="bpaf-cpt-item__meta">
							<?php
							printf(
								'(%1$s) &mdash; ',
								esc_html( $bpaf_post_type )
							);
							printf(
								/* translators: %s: number of published posts. */
								esc_html( _n( '%s post', '%s posts', $bpaf_total_posts, 'bp-activity-filter' ) ),
								esc_html( number_format_i18n( $bpaf_total_posts ) )
							);
							?>
						</span>
					</label>

					<?php if ( ! empty( $bpaf_post_type_obj->description ) ) : ?>
						<p class="description bpaf-cpt-item__desc"><?php echo esc_html( $bpaf_post_type_obj->description ); ?></p>
					<?php endif; ?>

					<div class="bpaf-cpt-item__label-field">
						<label for="cpt_<?php echo esc_attr( $bpaf_post_type ); ?>_label">
							<?php esc_html_e( 'Custom activity label (optional)', 'bp-activity-filter' ); ?>
						</label>
						<input type="text"
							id="cpt_<?php echo esc_attr( $bpaf_post_type ); ?>_label"
							name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $bpaf_post_type ); ?>][label]"
							value="<?php echo esc_attr( $bpaf_label ); ?>"
							class="regular-text"
							placeholder="<?php echo esc_attr( strtolower( $bpaf_post_type_obj->labels->singular_name ) ); ?>">
						<p class="description">
							<?php esc_html_e( 'Leave empty to use the post type name. Shows in entries like "John published a new [label]: Post Title".', 'bp-activity-filter' ); ?>
						</p>
					</div>
				</div>
			<?php endforeach; ?>

			<div class="bpaf-cpt-global">
				<p class="bpaf-field-group-label"><?php esc_html_e( 'Global Settings', 'bp-activity-filter' ); ?></p>
				<label class="bpaf-cpt-global__toggle">
					<input type="checkbox"
						name="bp_activity_filter_cpt_settings[_global][hide_sitewide]"
						value="1"
						<?php checked( isset( $bpaf_cpt_settings['_global']['hide_sitewide'] ) ? $bpaf_cpt_settings['_global']['hide_sitewide'] : false ); ?>>
					<span>
						<?php esc_html_e( 'Hide custom post type activities from the site-wide stream', 'bp-activity-filter' ); ?>
						<span class="description">
							<?php esc_html_e( 'When on, these activities appear only on the author profile stream, keeping the main feed less cluttered while still giving authors credit.', 'bp-activity-filter' ); ?>
						</span>
					</span>
				</label>
			</div>

			<div class="bpaf-notice bpaf-notice--info">
				<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Only posts published after turning a type on will create activities. Existing posts are left untouched.', 'bp-activity-filter' ); ?></span>
			</div>
		<?php endif; ?>
	</div>
</div>
