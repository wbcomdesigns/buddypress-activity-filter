<?php
/**
 * Admin page shell: page header, sidebar nav, body slot.
 *
 * Receives from BP_Activity_Filter_Admin_Panel::render_page():
 *
 * @var array  $bpaf_tabs           Tab registry keyed by slug.
 * @var string $active              Active tab slug.
 * @var string $page_url            Base URL (admin.php?page=wbcom-activity-filter).
 * @var string $view                View slug (e.g. 'settings-default').
 * @var string $view_path           Absolute path to the partial.
 * @var bool   $in_settings_group   True when the active tab is a Settings tab.
 * @var string $settings_form_group register_setting() group for settings_fields().
 *
 * @package BuddyPress_Activity_Filter
 * @since   3.2.1
 */

defined( 'ABSPATH' ) || exit;

$bpaf_version = defined( 'BP_ACTIVITY_FILTER_VERSION' ) ? BP_ACTIVITY_FILTER_VERSION : '';
?>
<div class="wrap bpaf-admin">

	<header class="bpaf-page-header">
		<div class="bpaf-page-header__title">
			<span class="dashicons dashicons-filter" aria-hidden="true"></span>
			<div>
				<h1><?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?></h1>
				<p class="bpaf-page-header__subtitle"><?php esc_html_e( 'Set default activity filters, hide activity types, and turn custom post types into activity stream entries.', 'bp-activity-filter' ); ?></p>
			</div>
		</div>
		<div class="bpaf-page-header__actions">
			<?php if ( $bpaf_version ) : ?>
				<span class="bpaf-version-pill">v<?php echo esc_html( $bpaf_version ); ?></span>
			<?php endif; ?>
		</div>
	</header>

	<?php
	/*
	 * Without this marker, core's common.js re-parents every .notice to
	 * sit right after the first <h1> it finds, which slots the "Settings
	 * saved" banner between our title and its subtitle instead of below
	 * the whole header.
	 */
	?>
	<hr class="wp-header-end">

	<div class="bpaf-settings-layout">

		<aside class="bpaf-settings-sidebar">
			<div class="bpaf-settings-sidebar-brand">
				<span class="bpaf-settings-brand-icon" aria-hidden="true">
					<span class="dashicons dashicons-filter"></span>
				</span>
				<div class="bpaf-settings-brand-text">
					<p class="bpaf-settings-brand-name"><?php esc_html_e( 'Activity Filter', 'bp-activity-filter' ); ?></p>
					<p class="bpaf-settings-brand-sub"><?php esc_html_e( 'Plugin', 'bp-activity-filter' ); ?></p>
				</div>
			</div>
			<nav class="bpaf-settings-sidebar-nav" aria-label="<?php esc_attr_e( 'Activity Filter navigation', 'bp-activity-filter' ); ?>">
				<?php
				$bpaf_printed_groups = array();
				$bpaf_group_labels   = array(
					'settings'  => esc_html__( 'Settings', 'bp-activity-filter' ),
					'resources' => esc_html__( 'Resources', 'bp-activity-filter' ),
				);
				foreach ( $bpaf_tabs as $bpaf_slug => $bpaf_tab ) {
					$bpaf_group = isset( $bpaf_tab['group'] ) ? $bpaf_tab['group'] : 'settings';
					if ( ! in_array( $bpaf_group, $bpaf_printed_groups, true ) ) {
						if ( ! empty( $bpaf_printed_groups ) ) {
							echo '<div class="bpaf-snav-divider" role="separator"></div>';
						}
						if ( isset( $bpaf_group_labels[ $bpaf_group ] ) ) {
							echo '<p class="bpaf-snav-section-label">' . esc_html( $bpaf_group_labels[ $bpaf_group ] ) . '</p>';
						}
						$bpaf_printed_groups[] = $bpaf_group;
					}
					$bpaf_classes  = 'bpaf-snav-link';
					$bpaf_classes .= $active === $bpaf_slug ? ' bpaf-snav-link--active' : '';
					echo '<a href="' . esc_url( $page_url . '&tab=' . $bpaf_slug ) . '" class="' . esc_attr( $bpaf_classes ) . '">';
					echo '<span class="dashicons ' . esc_attr( $bpaf_tab['icon'] ) . '" aria-hidden="true"></span>';
					echo esc_html( $bpaf_tab['label'] );
					echo '</a>';
				}
				?>

				<div class="bpaf-snav-divider" role="separator"></div>
				<a href="https://docs.wbcomdesigns.com/doc_category/bp-activity-filter/" class="bpaf-snav-link" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-book" aria-hidden="true"></span>
					<?php esc_html_e( 'Documentation', 'bp-activity-filter' ); ?>
					<span class="dashicons dashicons-external bpaf-snav-link__ext" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'bp-activity-filter' ); ?></span>
				</a>
			</nav>
		</aside>

		<div class="bpaf-settings-main">
			<?php settings_errors(); ?>
			<?php if ( $in_settings_group ) : ?>
				<form method="post" action="options.php" id="bpaf-settings-form">
					<?php settings_fields( $settings_form_group ); ?>
					<?php
					if ( file_exists( $view_path ) ) {
						include $view_path;
					}
					?>
					<div class="bpaf-save-bar">
						<?php submit_button( __( 'Save Settings', 'bp-activity-filter' ), 'primary bpaf-btn bpaf-btn-primary', 'submit', false ); ?>
					</div>
				</form>
			<?php else : ?>
				<?php
				if ( file_exists( $view_path ) ) {
					include $view_path;
				}
				?>
			<?php endif; ?>
		</div>

	</div>
</div>
