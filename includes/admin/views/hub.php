<?php
/**
 * WB Plugins hub — landing dashboard at ?page=wbcomplugins.
 *
 * Lists every Wbcom plugin that has registered a submenu under the
 * shared wbcomplugins parent. Peer plugins appear automatically once
 * their admin page is in the WordPress menu table. Legacy wbcom-wrapper
 * boilerplate helper pages are filtered out so they do not read as
 * duplicate plugin cards. See references/wbcom-wrapper-migration.md
 * Part 15.
 *
 * @package BuddyPress_Activity_Filter
 * @since   3.2.1
 */

defined( 'ABSPATH' ) || exit;

$bpaf_submenu_entries = isset( $GLOBALS['submenu']['wbcomplugins'] ) && is_array( $GLOBALS['submenu']['wbcomplugins'] )
	? $GLOBALS['submenu']['wbcomplugins']
	: array();

// Slugs of the legacy wbcom-wrapper boilerplate helper pages. Filterable
// so Pro modules or future wrapper slugs can be added.
$bpaf_wrapper_helper_slugs = apply_filters(
	'wbcom_hub_wrapper_helper_slugs',
	array(
		'wbcom-plugins-page',
		'wbcom-themes-page',
		'wbcom-support-page',
		'wbcom-license-page',
	)
);

$bpaf_plugins = array();
foreach ( $bpaf_submenu_entries as $bpaf_entry ) {
	$bpaf_slug = isset( $bpaf_entry[2] ) ? (string) $bpaf_entry[2] : '';
	if ( '' === $bpaf_slug || 'wbcomplugins' === $bpaf_slug ) {
		continue;
	}
	if ( in_array( $bpaf_slug, $bpaf_wrapper_helper_slugs, true ) ) {
		continue;
	}
	$bpaf_plugins[] = array(
		'slug'       => $bpaf_slug,
		'menu_title' => isset( $bpaf_entry[0] ) ? wp_strip_all_tags( (string) $bpaf_entry[0] ) : $bpaf_slug,
		'page_title' => isset( $bpaf_entry[3] ) ? wp_strip_all_tags( (string) $bpaf_entry[3] ) : '',
		'url'        => admin_url( 'admin.php?page=' . rawurlencode( $bpaf_slug ) ),
	);
}

$bpaf_plugin_count = count( $bpaf_plugins );
?>
<div class="wrap bpaf-admin">
	<header class="bpaf-page-header">
		<div class="bpaf-page-header__title">
			<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
			<div>
				<h1>WB Plugins</h1>
				<p class="bpaf-page-header__subtitle">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: active Wbcom plugin count */
							_n(
								'%d Wbcom plugin active on this site.',
								'%d Wbcom plugins active on this site.',
								$bpaf_plugin_count,
								'bp-activity-filter'
							),
							$bpaf_plugin_count
						)
					);
					?>
				</p>
			</div>
		</div>
	</header>

	<?php if ( 0 === $bpaf_plugin_count ) : ?>
		<div class="bpaf-empty-state">
			<span class="bpaf-empty-state__icon" aria-hidden="true">
				<span class="dashicons dashicons-lightbulb"></span>
			</span>
			<p class="bpaf-empty-state__title"><?php esc_html_e( 'No Wbcom plugins attached to this hub yet', 'bp-activity-filter' ); ?></p>
			<p class="bpaf-empty-state__desc">
				<?php esc_html_e( 'Activate one or more Wbcom plugins and they will appear here automatically.', 'bp-activity-filter' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="bpaf-hub-grid">
			<?php foreach ( $bpaf_plugins as $bpaf_p ) : ?>
				<a href="<?php echo esc_url( $bpaf_p['url'] ); ?>" class="bpaf-hub-card">
					<span class="bpaf-hub-card__icon" aria-hidden="true">
						<span class="dashicons dashicons-admin-plugins"></span>
					</span>
					<span class="bpaf-hub-card__title"><?php echo esc_html( $bpaf_p['menu_title'] ); ?></span>
					<?php if ( ! empty( $bpaf_p['page_title'] ) && $bpaf_p['page_title'] !== $bpaf_p['menu_title'] ) : ?>
						<span class="bpaf-hub-card__subtitle"><?php echo esc_html( $bpaf_p['page_title'] ); ?></span>
					<?php endif; ?>
					<span class="bpaf-hub-card__cta">
						<?php esc_html_e( 'Open settings', 'bp-activity-filter' ); ?>
						<span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="bpaf-card bpaf-card--spaced">
		<div class="bpaf-card__head">
			<p class="bpaf-card__title"><?php esc_html_e( 'About WB Plugins', 'bp-activity-filter' ); ?></p>
		</div>
		<div class="bpaf-card__body">
			<p class="bpaf-card__body-lead">
				<?php esc_html_e( 'This hub is the single entry point for every Wbcom Designs plugin installed on your site. Each plugin lives on its own page under this menu and keeps its own settings and data.', 'bp-activity-filter' ); ?>
			</p>
			<p>
				<a href="https://wbcomdesigns.com/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Visit wbcomdesigns.com for more plugins and themes', 'bp-activity-filter' ); ?>
				</a>
			</p>
		</div>
	</div>
</div>
