<?php
/**
 * FAQ tab: rendered inside shell.php (non-settings group, no save bar).
 *
 * @package BuddyPress_Activity_Filter
 * @since   3.2.1
 */

defined( 'ABSPATH' ) || exit;

$bpaf_faqs = array(
	array(
		'q' => __( 'What is BuddyPress Activity Filter?', 'buddypress-activity-filter' ),
		'a' => __( 'It gives you control over the BuddyPress activity stream. You can hide activity types you do not want members to see, and choose which filter is selected by default.', 'buddypress-activity-filter' ),
	),
	array(
		'q' => __( 'How do I set a default filter for all activity streams?', 'buddypress-activity-filter' ),
		'a' => __( 'Open the Default Filters tab and pick a default from the site-wide dropdown. It applies automatically the first time someone loads the activity stream.', 'buddypress-activity-filter' ),
	),
	array(
		'q' => __( 'Can I set a different default filter for profiles?', 'buddypress-activity-filter' ),
		'a' => __( 'Yes. The Default Filters tab has a separate profile dropdown so member profile streams can default to something different from the site-wide stream.', 'buddypress-activity-filter' ),
	),
	array(
		'q' => __( 'How do I hide specific activity types?', 'buddypress-activity-filter' ),
		'a' => __( 'Use the Hidden Activities tab and check the types you want to hide. Hidden types are removed from streams and the filter dropdown. Core status updates and replies cannot be hidden.', 'buddypress-activity-filter' ),
	),
	array(
		'q' => __( 'Does hiding a type delete those activities?', 'buddypress-activity-filter' ),
		'a' => __( 'No. Hiding excludes the type from the activity query, so those entries stop appearing immediately and come back if you unhide the type. They stay visible on the WordPress Activity screen so you can still moderate them.', 'buddypress-activity-filter' ),
	),
	array(
		'q' => __( 'Why are some activities missing from the filter?', 'buddypress-activity-filter' ),
		'a' => __( 'Check the Hidden Activities tab to confirm the type is not hidden. Some system activity types may also be unavailable if another plugin has disabled them.', 'buddypress-activity-filter' ),
	),
	array(
		'q' => __( 'Is this plugin compatible with BuddyBoss Platform?', 'buddypress-activity-filter' ),
		'a' => __( 'No. This plugin is built for BuddyPress. BuddyBoss Platform has its own activity filtering, so this plugin is not needed and is not fully compatible with it.', 'buddypress-activity-filter' ),
	),
	array(
		'q' => __( 'How do filters affect activity stream performance?', 'buddypress-activity-filter' ),
		'a' => __( 'Filtering happens at the database query level using BuddyPress core mechanisms, so it stays efficient even on large activity streams.', 'buddypress-activity-filter' ),
	),
);
?>

<div class="bpaf-card">
	<div class="bpaf-card__head">
		<p class="bpaf-card__title"><?php esc_html_e( 'Frequently Asked Questions', 'buddypress-activity-filter' ); ?></p>
	</div>
	<div class="bpaf-card__body">
		<div class="bpaf-faq-list">
			<?php foreach ( $bpaf_faqs as $bpaf_faq ) : ?>
				<div class="bpaf-faq-item">
					<h3 class="bpaf-faq-item__q"><?php echo esc_html( $bpaf_faq['q'] ); ?></h3>
					<p class="bpaf-faq-item__a"><?php echo esc_html( $bpaf_faq['a'] ); ?></p>
				</div>
			<?php endforeach; ?>

			<div class="bpaf-faq-item">
				<h3 class="bpaf-faq-item__q"><?php esc_html_e( 'Where can I get support or report issues?', 'buddypress-activity-filter' ); ?></h3>
				<p class="bpaf-faq-item__a">
					<?php
					printf(
						/* translators: %1$s: opening link tag for support, %2$s: closing link tag, %3$s: opening link tag for docs, %4$s: closing link tag. */
						esc_html__( 'Visit our %1$ssupport forum%2$s or read the %3$splugin documentation%4$s.', 'buddypress-activity-filter' ),
						'<a href="https://wbcomdesigns.com/support/" target="_blank" rel="noopener noreferrer">',
						'</a>',
						'<a href="https://docs.wbcomdesigns.com/doc_category/bp-activity-filter/" target="_blank" rel="noopener noreferrer">',
						'</a>'
					);
					?>
				</p>
			</div>
		</div>
	</div>
</div>
