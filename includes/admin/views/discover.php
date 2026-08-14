<?php
/**
 * Discover partial: ecosystem cross-promotion (read-only display view).
 *
 * Rendered inside shell.php. Pure presentation - product cards linking out
 * to other free Wbcom Designs tools. No forms, settings, options, or AJAX.
 *
 * @package BuddyPress_Activity_Filter
 * @subpackage BuddyPress_Activity_Filter/includes/admin/views
 * @since   3.2.1
 */

defined( 'ABSPATH' ) || exit;

$bpaf_ecosystem_img = BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/images/ecosystem/';
$bpaf_ecosystem_dir = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'assets/images/ecosystem/';

/*
 * Each product: brand mark (shipped in assets/images/ecosystem/), name, a
 * short admin-specific blurb (worded differently from readme.txt), and the
 * wbcomdesigns.com download URL.
 */
$bpaf_ecosystem = array(
	array(
		'name' => 'BuddyX',
		'logo' => 'buddyx.png',
		'icon' => 'admin-appearance',
		'desc' => __( 'A free, fast community theme for BuddyPress, BuddyBoss and PeepSo with a modern layout and dark mode.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddyx-theme/',
	),
	array(
		'name' => 'BuddyNext',
		'logo' => 'buddynext.svg',
		'icon' => 'groups',
		'desc' => __( 'The full community stack: activity feeds, member spaces, profiles, and private messaging on WordPress.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddynext/',
	),
	array(
		'name' => 'Jetonomy',
		'logo' => 'jetonomy.svg',
		'icon' => 'format-chat',
		'desc' => __( 'Self-moderating forums and Q&A boards that stay fast well beyond 100,000 topics.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/jetonomy/',
	),
	array(
		'name' => 'Mediaverse',
		'logo' => 'mediaverse.svg',
		'icon' => 'format-gallery',
		'desc' => __( 'A photo and video hub with albums, reactions, following, and private chat.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/mediaverse/',
	),
	array(
		'name' => 'Eventonomy',
		'logo' => 'eventonomy.svg',
		'icon' => 'calendar-alt',
		'desc' => __( 'Run community events with RSVPs, calendars, and front-end submissions.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/eventonomy/',
	),
	array(
		'name' => 'WB Gamification',
		'logo' => 'wb-gamification.svg',
		'icon' => 'awards',
		'desc' => __( 'Reward members with points, badges, and leaderboards to keep engagement high.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wordpress-gamification-plugin/',
	),
	array(
		'name' => 'Listora',
		'logo' => 'listora.svg',
		'icon' => 'list-view',
		'desc' => __( 'Searchable directories with ten listing types, ratings, maps, and front-end submissions.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/listora/',
	),
	array(
		'name' => 'WP Career Board',
		'logo' => 'wp-career-board.svg',
		'icon' => 'businessman',
		'desc' => __( 'Add a job board with front-end listings, applications, and employer profiles.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wp-career-board/',
	),
	array(
		'name' => 'Learnomy',
		'logo' => 'learnomy.svg',
		'icon' => 'welcome-learn-more',
		'desc' => __( 'Create, sell, and auto-grade online courses, then hand out certificates automatically.', 'bp-activity-filter' ),
		'url'  => 'https://wbcomdesigns.com/downloads/learnomy/',
	),
);
?>

<div class="bpaf-card">
	<div class="bpaf-card__head">
		<p class="bpaf-card__title"><?php esc_html_e( 'More Free Tools from Wbcom Designs', 'bp-activity-filter' ); ?></p>
		<p class="bpaf-card__desc"><?php esc_html_e( 'Activity Filter keeps your BuddyPress stream focused. These free tools from Wbcom Designs build out the community around that stream: the theme and network itself, forums, media, events, gamification, directories, jobs, and courses.', 'bp-activity-filter' ); ?></p>
	</div>
	<div class="bpaf-card__body">
		<div class="bpaf-discover-grid">
			<?php foreach ( $bpaf_ecosystem as $bpaf_product ) : ?>
				<div class="bpaf-discover-card">
					<span class="bpaf-discover-card__logo" aria-hidden="true">
						<?php if ( file_exists( $bpaf_ecosystem_dir . $bpaf_product['logo'] ) ) : ?>
							<img src="<?php echo esc_url( $bpaf_ecosystem_img . $bpaf_product['logo'] ); ?>" alt="<?php echo esc_attr( $bpaf_product['name'] ); ?>" width="52" height="52" loading="lazy" />
						<?php else : ?>
							<span class="dashicons dashicons-<?php echo esc_attr( isset( $bpaf_product['icon'] ) ? $bpaf_product['icon'] : 'admin-plugins' ); ?>"></span>
						<?php endif; ?>
					</span>
					<h3 class="bpaf-discover-card__title"><?php echo esc_html( $bpaf_product['name'] ); ?></h3>
					<p class="bpaf-discover-card__desc"><?php echo esc_html( $bpaf_product['desc'] ); ?></p>
					<a class="bpaf-btn bpaf-btn-secondary bpaf-discover-card__cta" href="<?php echo esc_url( $bpaf_product['url'] ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Get it free', 'bp-activity-filter' ); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
						<span class="screen-reader-text">
							<?php
							/* translators: %s: product name. */
							echo esc_html( sprintf( __( '%s (opens in a new tab)', 'bp-activity-filter' ), $bpaf_product['name'] ) );
							?>
						</span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
