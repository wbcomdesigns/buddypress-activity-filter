<?php
/**
 * Improved Wbcom Designs Unified Menu System with Streamlined Premium Tab
 *
 * Enhanced version with better error handling, menu management, and dashboard functionality.
 * Includes the streamlined premium plugins display without images.
 *
 * @package BuddyPress_Activity_Filter
 * @subpackage Admin
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wbcom Designs Menu Manager - Improved Version
 *
 * Handles the creation and management of the unified Wbcom Designs admin menu
 * with better error handling and menu management.
 *
 * @since 4.0.0
 */
class Wbcom_Designs_Menu {

	/**
	 * Class instance.
	 *
	 * @since 4.0.0
	 * @var Wbcom_Designs_Menu|null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Main menu slug.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	private $menu_slug = 'wbcom-designs';

	/**
	 * Menu icon (SVG base64).
	 *
	 * @since 4.0.0
	 * @var string
	 */
	private $menu_icon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDJMMTMuMDkgOC4yNkwyMCA5TDE0IDEyTDE1IDIwTDEwIDE3TDUgMjBMNiAxMkwwIDlMNi45MSA4LjI2TDEwIDJaIiBmaWxsPSIjYTdhYWFkIi8+Cjwvc3ZnPgo=';

	/**
	 * Menu position.
	 *
	 * @since 4.0.0
	 * @var int
	 */
	private $menu_position = 58.5;

	/**
	 * Registered submenus.
	 *
	 * @since 4.0.0
	 * @var array
	 */
	private $submenus = array();

	/**
	 * Plugin priority order for submenu positioning.
	 *
	 * @since 4.0.0
	 * @var array
	 */
	private $plugin_priority = array(
		'dashboard'           => 0,
		'activity-filter'     => 10,
		'hashtags'           => 20,
		'polls'              => 30,
		'quotes'             => 40,
		'status-reactions'   => 50,
		'sticky-post'        => 60,
		'wp-stories'         => 70,
		'moderation'         => 80,
		'checkins'           => 90,
		'settings'           => 100,
	);

	/**
	 * Menu creation status.
	 *
	 * @since 4.0.0
	 * @var bool
	 */
	private $menu_created = false;

	/**
	 * Get singleton instance.
	 *
	 * @since 4.0.0
	 * @return Wbcom_Designs_Menu
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
	 * @since 4.0.0
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'create_main_menu' ), 1 );
		add_action( 'admin_menu', array( $this, 'add_dashboard_submenu' ), 5 );
		add_action( 'admin_menu', array( $this, 'sort_submenus' ), 999 );
		add_action( 'admin_head', array( $this, 'add_menu_styles' ) );
	}

	/**
	 * Create the main Wbcom Designs menu.
	 *
	 * @since 4.0.0
	 * @return string|false Menu hook or false on failure.
	 */
	public function create_main_menu() {
		// Prevent duplicate menu creation
		if ( $this->menu_created || $this->main_menu_exists() ) {
			return false;
		}

		$menu_hook = add_menu_page(
			esc_html__( 'Wbcom Designs', 'bp-activity-filter' ),
			esc_html__( 'Wbcom Designs', 'bp-activity-filter' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'dashboard_page' ),
			$this->menu_icon,
			$this->menu_position
		);

		if ( $menu_hook ) {
			$this->menu_created = true;
			
			// Add help tab for main menu
			add_action( "load-{$menu_hook}", array( $this, 'add_dashboard_help_tab' ) );
		}

		return $menu_hook;
	}

	/**
	 * Add dashboard submenu as the first item.
	 *
	 * @since 4.0.0
	 */
	public function add_dashboard_submenu() {
		if ( ! $this->main_menu_exists() ) {
			return;
		}

		$this->add_submenu(
			'dashboard',
			esc_html__( 'Dashboard', 'bp-activity-filter' ),
			esc_html__( 'Dashboard', 'bp-activity-filter' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'dashboard_page' )
		);
	}

	/**
	 * Check if main menu already exists.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	private function main_menu_exists() {
		global $menu;
		
		if ( ! is_array( $menu ) ) {
			return false;
		}

		foreach ( $menu as $menu_item ) {
			if ( isset( $menu_item[2] ) && $this->menu_slug === $menu_item[2] ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Add a submenu to the Wbcom Designs menu.
	 *
	 * @since 4.0.0
	 *
	 * @param string   $plugin_key   Unique plugin identifier.
	 * @param string   $page_title   Page title.
	 * @param string   $menu_title   Menu title.
	 * @param string   $capability   Required capability.
	 * @param string   $menu_slug    Menu slug.
	 * @param callable $function     Callback function.
	 * @param int      $position     Optional. Menu position override.
	 * @return string|false Menu hook suffix or false on failure.
	 */
	public function add_submenu( $plugin_key, $page_title, $menu_title, $capability, $menu_slug, $function, $position = null ) {
		// Ensure main menu exists
		if ( ! $this->main_menu_exists() ) {
			$this->create_main_menu();
		}

		// Determine position based on priority
		$priority = isset( $this->plugin_priority[ $plugin_key ] ) 
			? $this->plugin_priority[ $plugin_key ] 
			: 999;

		if ( null !== $position ) {
			$priority = $position;
		}

		// Store submenu info
		$this->submenus[ $plugin_key ] = array(
			'page_title' => $page_title,
			'menu_title' => $menu_title,
			'capability' => $capability,
			'menu_slug'  => $menu_slug,
			'function'   => $function,
			'priority'   => $priority,
		);

		// Add submenu page
		$hook_suffix = add_submenu_page(
			$this->menu_slug,
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$function
		);

		return $hook_suffix;
	}

	/**
	 * Sort submenus based on priority.
	 *
	 * @since 4.0.0
	 */
	public function sort_submenus() {
		global $submenu;

		if ( ! isset( $submenu[ $this->menu_slug ] ) || empty( $this->submenus ) ) {
			return;
		}

		// Create array for sorting
		$sorted_submenus = array();
		
		foreach ( $submenu[ $this->menu_slug ] as $index => $submenu_item ) {
			$menu_slug = $submenu_item[2];
			$priority = 999; // Default priority

			// Find priority from our stored submenus
			foreach ( $this->submenus as $plugin_key => $submenu_data ) {
				if ( $submenu_data['menu_slug'] === $menu_slug ) {
					$priority = $submenu_data['priority'];
					break;
				}
			}

			$sorted_submenus[ $priority . '_' . $index ] = $submenu_item;
		}

		// Sort by priority
		ksort( $sorted_submenus );

		// Reassign sorted submenus
		$submenu[ $this->menu_slug ] = array_values( $sorted_submenus );
	}

	/**
	 * Dashboard page content.
	 *
	 * @since 4.0.0
	 */
	public function dashboard_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'overview';
		?>
		<div class="wrap wbcom-dashboard">
			<h1>
				<span class="wbcom-logo">
					<img src="data:image/svg+xml;base64,<?php echo base64_encode( $this->get_wbcom_logo_svg() ); ?>" alt="Wbcom Designs" width="32" height="32">
				</span>
				<?php esc_html_e( 'Wbcom Designs', 'bp-activity-filter' ); ?>
				<span class="wbcom-version">v<?php echo esc_html( $this->get_dashboard_version() ); ?></span>
			</h1>
			
			<?php $this->render_admin_notices(); ?>
			
			<div class="wbcom-dashboard-content">
				<div class="wbcom-dashboard-main">
					<?php $this->render_dashboard_tabs( $active_tab ); ?>
				</div>

				<div class="wbcom-dashboard-sidebar">
					<?php $this->render_sidebar_widgets(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render admin notices for dashboard.
	 *
	 * @since 4.0.0
	 */
	private function render_admin_notices() {
		$wbcom_plugins = $this->get_wbcom_plugins();
		$active_count = count( array_filter( $wbcom_plugins, function( $p ) { return $p['active']; } ) );
		
		if ( $active_count === 0 ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Welcome to Wbcom Designs!', 'bp-activity-filter' ); ?></strong>
					<?php esc_html_e( 'No Wbcom plugins are currently active. Activate plugins to see them here.', 'bp-activity-filter' ); ?>
				</p>
			</div>
			<?php
		} elseif ( $active_count === 1 ) {
			?>
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Great start!', 'bp-activity-filter' ); ?></strong>
					<?php esc_html_e( 'You have 1 Wbcom plugin active. Explore our other plugins to enhance your site further.', 'bp-activity-filter' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Render dashboard tabs with themes included.
	 *
	 * @since 4.0.0
	 * @param string $active_tab Current active tab.
	 */
	private function render_dashboard_tabs( $active_tab ) {
		$tabs = array(
			'overview' => array(
				'title' => esc_html__( 'Overview', 'bp-activity-filter' ),
				'icon'  => 'dashicons-dashboard',
			),
			'plugins' => array(
				'title' => esc_html__( 'Installed Plugins', 'bp-activity-filter' ),
				'icon'  => 'dashicons-admin-plugins',
			),
			'premium' => array(
				'title' => esc_html__( 'Premium Plugins', 'bp-activity-filter' ),
				'icon'  => 'dashicons-star-filled',
			),
			'themes' => array(
				'title' => esc_html__( 'Premium Themes', 'bp-activity-filter' ),
				'icon'  => 'dashicons-admin-appearance',
			),
			'news' => array(
				'title' => esc_html__( 'News & Updates', 'bp-activity-filter' ),
				'icon'  => 'dashicons-rss',
			),
		);
		?>
		<div class="wbcom-dashboard-tabs">
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab_data ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-designs&tab=' . $tab_key ) ); ?>" 
					   class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						<span class="dashicons <?php echo esc_attr( $tab_data['icon'] ); ?>"></span>
						<?php echo esc_html( $tab_data['title'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="tab-content">
				<?php
				switch ( $active_tab ) {
					case 'plugins':
						$this->render_plugins_tab();
						break;
					case 'premium':
						$this->render_premium_tab();
						break;
					case 'themes':
						$this->render_themes_tab();
						break;
					case 'news':
						$this->render_news_tab();
						break;
					case 'overview':
					default:
						$this->render_overview_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render overview tab.
	 *
	 * @since 4.0.0
	 */
	private function render_overview_tab() {
		$wbcom_plugins = $this->get_wbcom_plugins();
		$stats = $this->get_dashboard_stats();
		?>
		<div class="wbcom-welcome-panel">
			<div class="wbcom-welcome-panel-content">
				<h2><?php esc_html_e( 'Welcome to Wbcom Designs Dashboard', 'bp-activity-filter' ); ?></h2>
				<p class="about-description">
					<?php esc_html_e( 'Your central hub for managing all Wbcom Designs plugins. We create premium WordPress and BuddyPress solutions to enhance your community experience.', 'bp-activity-filter' ); ?>
				</p>
				
				<!-- Quick Stats -->
				<div class="wbcom-stats-overview">
					<div class="stat-box">
						<div class="stat-number"><?php echo esc_html( $stats['total_plugins'] ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Total Plugins', 'bp-activity-filter' ); ?></div>
					</div>
					<div class="stat-box">
						<div class="stat-number"><?php echo esc_html( $stats['active_plugins'] ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Active Plugins', 'bp-activity-filter' ); ?></div>
					</div>
					<div class="stat-box">
						<div class="stat-number"><?php echo esc_html( $stats['bp_version'] ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'BuddyPress Version', 'bp-activity-filter' ); ?></div>
					</div>
					<div class="stat-box">
						<div class="stat-number"><?php echo esc_html( $stats['wp_version'] ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'WordPress Version', 'bp-activity-filter' ); ?></div>
					</div>
				</div>

				<div class="wbcom-welcome-panel-column-container">
					<div class="wbcom-welcome-panel-column">
						<h3><?php esc_html_e( 'Quick Actions', 'bp-activity-filter' ); ?></h3>
						<ul class="wbcom-action-list">
							<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-designs&tab=plugins' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Manage Plugins', 'bp-activity-filter' ); ?></a></li>
							<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-designs&tab=premium' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Browse Premium', 'bp-activity-filter' ); ?></a></li>
							<li><a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary"><?php esc_html_e( 'Get Support', 'bp-activity-filter' ); ?></a></li>
						</ul>
					</div>
					<div class="wbcom-welcome-panel-column">
						<h3><?php esc_html_e( 'Recent Activity', 'bp-activity-filter' ); ?></h3>
						<?php if ( ! empty( $wbcom_plugins ) ) : ?>
							<ul class="wbcom-recent-activity">
								<?php foreach ( array_slice( $wbcom_plugins, 0, 3 ) as $plugin ) : ?>
									<li>
										<span class="status-indicator <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>"></span>
										<strong><?php echo esc_html( $plugin['name'] ); ?></strong>
										<span class="plugin-status"><?php echo $plugin['active'] ? esc_html__( 'Active', 'bp-activity-filter' ) : esc_html__( 'Inactive', 'bp-activity-filter' ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php esc_html_e( 'No recent plugin activity.', 'bp-activity-filter' ); ?></p>
						<?php endif; ?>
					</div>
					<div class="wbcom-welcome-panel-column wbcom-welcome-panel-last">
						<h3><?php esc_html_e( 'System Status', 'bp-activity-filter' ); ?></h3>
						<ul class="wbcom-system-status">
							<li>
								<span class="status-indicator <?php echo version_compare( get_bloginfo( 'version' ), '5.0', '>=' ) ? 'active' : 'inactive'; ?>"></span>
								<?php esc_html_e( 'WordPress Version', 'bp-activity-filter' ); ?>
							</li>
							<li>
								<span class="status-indicator <?php echo function_exists( 'buddypress' ) ? 'active' : 'inactive'; ?>"></span>
								<?php esc_html_e( 'BuddyPress Active', 'bp-activity-filter' ); ?>
							</li>
							<li>
								<span class="status-indicator <?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? 'inactive' : 'active'; ?>"></span>
								<?php esc_html_e( 'Production Mode', 'bp-activity-filter' ); ?>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render installed plugins tab.
	 *
	 * @since 4.0.0
	 */
	private function render_plugins_tab() {
		$wbcom_plugins = $this->get_wbcom_plugins();
		?>
		<div class="wbcom-plugins-header">
			<h2><?php esc_html_e( 'Installed Wbcom Plugins', 'bp-activity-filter' ); ?></h2>
			<div class="wbcom-plugins-filters">
				<button type="button" class="button filter-btn active" data-filter="all"><?php esc_html_e( 'All', 'bp-activity-filter' ); ?></button>
				<button type="button" class="button filter-btn" data-filter="active"><?php esc_html_e( 'Active', 'bp-activity-filter' ); ?></button>
				<button type="button" class="button filter-btn" data-filter="inactive"><?php esc_html_e( 'Inactive', 'bp-activity-filter' ); ?></button>
			</div>
		</div>

		<div class="wbcom-plugins-grid">
			<?php if ( empty( $wbcom_plugins ) ) : ?>
				<div class="wbcom-no-plugins">
					<div class="no-plugins-icon">
						<span class="dashicons dashicons-admin-plugins"></span>
					</div>
					<h3><?php esc_html_e( 'No Wbcom Plugins Found', 'bp-activity-filter' ); ?></h3>
					<p><?php esc_html_e( 'Looks like you haven\'t installed any Wbcom Designs plugins yet.', 'bp-activity-filter' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-designs&tab=premium' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Browse Premium Plugins', 'bp-activity-filter' ); ?>
					</a>
				</div>
			<?php else : ?>
				<?php foreach ( $wbcom_plugins as $plugin ) : ?>
					<div class="wbcom-plugin-card plugin-status-<?php echo $plugin['active'] ? 'active' : 'inactive'; ?>" data-status="<?php echo $plugin['active'] ? 'active' : 'inactive'; ?>">
						<div class="plugin-card-top">
							<div class="plugin-card-header">
								<h3><?php echo esc_html( $plugin['name'] ); ?></h3>
								<div class="plugin-status-badge <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>">
									<?php echo $plugin['active'] ? esc_html__( 'Active', 'bp-activity-filter' ) : esc_html__( 'Inactive', 'bp-activity-filter' ); ?>
								</div>
							</div>
							<p class="plugin-description"><?php echo esc_html( wp_trim_words( $plugin['description'], 20 ) ); ?></p>
							<?php if ( ! empty( $plugin['version'] ) ) : ?>
								<div class="plugin-version">
									<span class="version-label"><?php esc_html_e( 'Version:', 'bp-activity-filter' ); ?></span>
									<span class="version-number"><?php echo esc_html( $plugin['version'] ); ?></span>
								</div>
							<?php endif; ?>
						</div>
						<div class="plugin-card-bottom">
							<div class="plugin-actions">
								<?php if ( ! empty( $plugin['settings_url'] ) ) : ?>
									<a href="<?php echo esc_url( $plugin['settings_url'] ); ?>" class="button button-primary">
										<span class="dashicons dashicons-admin-generic"></span>
										<?php esc_html_e( 'Settings', 'bp-activity-filter' ); ?>
									</a>
								<?php endif; ?>
								<?php if ( ! $plugin['active'] ) : ?>
									<a href="<?php echo wp_nonce_url( 'plugins.php?action=activate&plugin=' . $plugin['file'], 'activate-plugin_' . $plugin['file'] ); ?>" class="button button-secondary">
										<span class="dashicons dashicons-yes"></span>
										<?php esc_html_e( 'Activate', 'bp-activity-filter' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render premium plugins tab without images.
	 *
	 * @since 4.0.0
	 */
	private function render_premium_tab() {
		$premium_plugins = $this->get_premium_plugins();
		?>
		<div class="wbcom-premium-section">
			<div class="wbcom-premium-header">
				<h2><?php esc_html_e( 'Premium BuddyPress Plugins', 'bp-activity-filter' ); ?></h2>
				<p><?php esc_html_e( 'Enhance your community with these powerful premium plugins designed specifically for BuddyPress.', 'bp-activity-filter' ); ?></p>
			</div>
			
			<div class="premium-plugins-list">
				<?php foreach ( $premium_plugins as $plugin ) : ?>
					<div class="premium-plugin-item">
						<div class="plugin-header">
							<h3><?php echo esc_html( $plugin['name'] ); ?></h3>
							<?php if ( ! empty( $plugin['price'] ) ) : ?>
								<div class="plugin-price">
									<span class="price-amount"><?php echo esc_html( $plugin['price'] ); ?></span>
								</div>
							<?php endif; ?>
						</div>
						<div class="plugin-content">
							<p class="plugin-description"><?php echo esc_html( $plugin['description'] ); ?></p>
							<?php if ( ! empty( $plugin['features'] ) ) : ?>
								<ul class="plugin-features">
									<?php foreach ( array_slice( $plugin['features'], 0, 4 ) as $feature ) : ?>
										<li><span class="dashicons dashicons-yes"></span> <?php echo esc_html( $feature ); ?></li>
									<?php endforeach; ?>
									<?php if ( count( $plugin['features'] ) > 4 ) : ?>
										<li class="more-features">+ <?php echo count( $plugin['features'] ) - 4; ?> more features</li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>
						</div>
						<div class="plugin-actions">
							<a href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" rel="noopener" class="button button-primary">
								<?php esc_html_e( 'View Plugin', 'bp-activity-filter' ); ?>
								<span class="dashicons dashicons-external"></span>
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="premium-footer">
				<p class="center-text">
					<a href="https://wbcomdesigns.com/downloads/" target="_blank" rel="noopener" class="button button-secondary button-large">
						<?php esc_html_e( 'Browse All Premium Plugins', 'bp-activity-filter' ); ?>
						<span class="dashicons dashicons-external"></span>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render themes tab.
	 *
	 * @since 4.0.0
	 */
	private function render_themes_tab() {
		$premium_themes = $this->get_premium_themes();
		?>
		<div class="wbcom-themes-section">
			<div class="wbcom-themes-header">
				<h2><?php esc_html_e( 'Premium BuddyPress Themes', 'bp-activity-filter' ); ?></h2>
				<p><?php esc_html_e( 'Professional WordPress themes designed specifically for BuddyPress communities with modern designs and advanced features.', 'bp-activity-filter' ); ?></p>
			</div>
			
			<div class="premium-themes-list">
				<?php foreach ( $premium_themes as $theme ) : ?>
					<div class="premium-theme-item">
						<div class="theme-header">
							<h3><?php echo esc_html( $theme['name'] ); ?></h3>
							<?php if ( ! empty( $theme['price'] ) ) : ?>
								<div class="theme-price">
									<span class="price-amount"><?php echo esc_html( $theme['price'] ); ?></span>
								</div>
							<?php endif; ?>
						</div>
						<div class="theme-content">
							<p class="theme-description"><?php echo esc_html( $theme['description'] ); ?></p>
							<?php if ( ! empty( $theme['features'] ) ) : ?>
								<ul class="theme-features">
									<?php foreach ( array_slice( $theme['features'], 0, 4 ) as $feature ) : ?>
										<li><span class="dashicons dashicons-yes"></span> <?php echo esc_html( $feature ); ?></li>
									<?php endforeach; ?>
									<?php if ( count( $theme['features'] ) > 4 ) : ?>
										<li class="more-features">+ <?php echo count( $theme['features'] ) - 4; ?> more features</li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>
						</div>
						<div class="theme-actions">
							<a href="<?php echo esc_url( $theme['url'] ); ?>" target="_blank" rel="noopener" class="button button-primary">
								<?php esc_html_e( 'View Theme', 'bp-activity-filter' ); ?>
								<span class="dashicons dashicons-external"></span>
							</a>
							<?php if ( ! empty( $theme['demo_url'] ) ) : ?>
								<a href="<?php echo esc_url( $theme['demo_url'] ); ?>" target="_blank" rel="noopener" class="button button-secondary">
									<?php esc_html_e( 'Live Demo', 'bp-activity-filter' ); ?>
									<span class="dashicons dashicons-external"></span>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="themes-footer">
				<p class="center-text">
					<a href="https://wbcomdesigns.com/themes/" target="_blank" rel="noopener" class="button button-secondary button-large">
						<?php esc_html_e( 'Browse All Premium Themes', 'bp-activity-filter' ); ?>
						<span class="dashicons dashicons-external"></span>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render news tab with better error handling.
	 *
	 * @since 4.0.0
	 */
	private function render_news_tab() {
		?>
		<div class="wbcom-news-section">
			<div class="wbcom-news-header">
				<h2><?php esc_html_e( 'Latest News from Wbcom Designs', 'bp-activity-filter' ); ?></h2>
				<p><?php esc_html_e( 'Stay updated with the latest plugin releases, updates, and WordPress community news.', 'bp-activity-filter' ); ?></p>
			</div>
			
			<div id="wbcom-news-feed" class="wbcom-news-feed">
				<div class="news-loading">
					<span class="spinner is-active"></span>
					<p><?php esc_html_e( 'Loading latest news...', 'bp-activity-filter' ); ?></p>
				</div>
			</div>

			<div class="news-footer" style="display: none;">
				<p class="center-text">
					<a href="https://wbcomdesigns.com/blog/" target="_blank" rel="noopener" class="button button-secondary">
						<?php esc_html_e( 'Visit Our Blog', 'bp-activity-filter' ); ?>
						<span class="dashicons dashicons-external"></span>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render sidebar widgets.
	 *
	 * @since 4.0.0
	 */
	private function render_sidebar_widgets() {
		?>
		<div class="wbcom-sidebar-widget">
			<h3><?php esc_html_e( 'Quick Stats', 'bp-activity-filter' ); ?></h3>
			<?php
			$stats = $this->get_dashboard_stats();
			?>
			<ul class="wbcom-stats-list">
				<li>
					<strong><?php echo esc_html( $stats['total_plugins'] ); ?></strong>
					<span><?php esc_html_e( 'Plugins Installed', 'bp-activity-filter' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( $stats['active_plugins'] ); ?></strong>
					<span><?php esc_html_e( 'Plugins Active', 'bp-activity-filter' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( $stats['wp_version'] ); ?></strong>
					<span><?php esc_html_e( 'WordPress Version', 'bp-activity-filter' ); ?></span>
				</li>
			</ul>
		</div>

		<div class="wbcom-sidebar-widget">
			<h3><?php esc_html_e( 'Need Help?', 'bp-activity-filter' ); ?></h3>
			<p><?php esc_html_e( 'Get expert support for all Wbcom Designs plugins and WordPress development.', 'bp-activity-filter' ); ?></p>
			<div class="widget-actions">
				<a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary button-large">
					<span class="dashicons dashicons-sos"></span>
					<?php esc_html_e( 'Get Support', 'bp-activity-filter' ); ?>
				</a>
				<a href="https://docs.wbcomdesigns.com/" target="_blank" class="button button-link">
					<?php esc_html_e( 'Documentation', 'bp-activity-filter' ); ?>
				</a>
			</div>
		</div>

		<div class="wbcom-sidebar-widget">
			<h3><?php esc_html_e( 'Community', 'bp-activity-filter' ); ?></h3>
			<p><?php esc_html_e( 'Join our community and stay connected with updates and discussions.', 'bp-activity-filter' ); ?></p>
			<div class="widget-actions">
				<a href="https://wordpress.org/support/plugin/bp-activity-filter/reviews/#new-post" target="_blank" class="button button-secondary">
					<span class="dashicons dashicons-star-filled"></span>
					<?php esc_html_e( 'Leave Review', 'bp-activity-filter' ); ?>
				</a>
				<div class="social-links">
					<a href="https://twitter.com/wbcomdesigns" target="_blank" title="<?php esc_attr_e( 'Follow on Twitter', 'bp-activity-filter' ); ?>">
						<span class="dashicons dashicons-twitter"></span>
					</a>
					<a href="https://www.facebook.com/wbcomdesigns/" target="_blank" title="<?php esc_attr_e( 'Like on Facebook', 'bp-activity-filter' ); ?>">
						<span class="dashicons dashicons-facebook"></span>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get dashboard statistics.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	private function get_dashboard_stats() {
		$wbcom_plugins = $this->get_wbcom_plugins();
		
		return array(
			'total_plugins'  => count( $wbcom_plugins ),
			'active_plugins' => count( array_filter( $wbcom_plugins, function( $p ) { return $p['active']; } ) ),
			'wp_version'     => get_bloginfo( 'version' ),
			'bp_version'     => function_exists( 'buddypress' ) ? buddypress()->version : esc_html__( 'Not Active', 'bp-activity-filter' ),
		);
	}

	/**
	 * Get list of installed Wbcom plugins.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	private function get_wbcom_plugins() {
		$all_plugins = get_plugins();
		$wbcom_plugins = array();

		// Known Wbcom plugins with their identifiers
		$known_plugins = array(
			'bp-activity-filter/buddypress-activity-filter.php' => array(
				'name' => 'BuddyPress Activity Filter',
				'settings_url' => admin_url( 'admin.php?page=wbcom-activity-filter' ),
			),
			// Add other known Wbcom plugins here
		);

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			// Check if it's a Wbcom plugin
			if ( strpos( $plugin_data['Author'], 'Wbcom' ) !== false || 
				 strpos( $plugin_data['AuthorURI'], 'wbcomdesigns.com' ) !== false ||
				 isset( $known_plugins[ $plugin_file ] ) ) {
				
				$plugin_info = isset( $known_plugins[ $plugin_file ] ) ? $known_plugins[ $plugin_file ] : array();
				
				$wbcom_plugins[] = array(
					'file'         => $plugin_file,
					'name'         => isset( $plugin_info['name'] ) ? $plugin_info['name'] : $plugin_data['Name'],
					'description'  => $plugin_data['Description'],
					'version'      => $plugin_data['Version'],
					'active'       => is_plugin_active( $plugin_file ),
					'settings_url' => isset( $plugin_info['settings_url'] ) ? $plugin_info['settings_url'] : '',
				);
			}
		}

		return $wbcom_plugins;
	}

	/**
	 * Get list of premium plugins without images.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	private function get_premium_plugins() {
		return array(
			array(
				'name'        => 'BuddyPress Hashtags',
				'description' => 'Add Instagram-style hashtag functionality to BuddyPress activities with trending tags, search, and comprehensive analytics.',
				'price'       => '$49',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-hashtags/',
				'features'    => array(
					'Instagram-style hashtag functionality',
					'Trending hashtags widget and analytics',
					'Advanced hashtag search and filtering',
					'Custom hashtag colors and styling',
					'Hashtag notifications and mentions',
					'Comprehensive analytics dashboard'
				),
			),
			array(
				'name'        => 'BuddyPress Polls',
				'description' => 'Create engaging polls and surveys within your BuddyPress community with real-time results and advanced analytics.',
				'price'       => '$59',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-polls/',
				'features'    => array(
					'Multiple poll types (single/multiple choice)',
					'Real-time voting results with charts',
					'Poll scheduling and expiration dates',
					'Voting restrictions and permissions',
					'Anonymous voting options',
					'Export results to CSV/PDF'
				),
			),
			array(
				'name'        => 'BuddyPress Quotes',
				'description' => 'Share inspirational quotes with beautiful background templates, custom typography, and social sharing integration.',
				'price'       => '$39',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-quotes/',
				'features'    => array(
					'100+ beautiful background templates',
					'Custom typography and font options',
					'Quote categories and tagging system',
					'Social media sharing integration',
					'Quote of the day widget',
					'User-submitted quotes moderation'
				),
			),
			array(
				'name'        => 'BuddyPress Status & Reactions',
				'description' => 'Advanced member status system with emoji reactions, mood tracking, and comprehensive engagement analytics.',
				'price'       => '$69',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-status-reactions/',
				'features'    => array(
					'Custom member status indicators',
					'Emoji reactions system (like, love, laugh)',
					'Advanced mood tracking and analytics',
					'Status change notifications',
					'Custom status messages and icons',
					'Detailed engagement statistics'
				),
			),
			array(
				'name'        => 'BuddyPress Sticky Post',
				'description' => 'Pin important activities and announcements to the top of activity streams with advanced scheduling features.',
				'price'       => '$29',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-sticky-post/',
				'features'    => array(
					'Pin activities to top of streams',
					'Advanced scheduling and expiration',
					'Group-specific sticky posts',
					'Priority levels and ordering',
					'Bulk sticky post management',
					'Analytics and engagement tracking'
				),
			),
			array(
				'name'        => 'WP Stories',
				'description' => 'Add Instagram-like stories feature to WordPress with automatic expiration, rich media support, and viewer analytics.',
				'price'       => '$79',
				'url'         => 'https://wbcomdesigns.com/downloads/wp-stories/',
				'features'    => array(
					'Instagram-style stories functionality',
					'24-hour automatic expiration',
					'Image and video story support',
					'Story highlights and archives',
					'Viewer analytics and insights',
					'Mobile-optimized interface'
				),
			),
		);
	}

	/**
	 * Get list of premium themes.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	private function get_premium_themes() {
		return array(
			array(
				'name'        => 'Reign Theme',
				'description' => 'Modern BuddyPress community theme with advanced customization options, multiple layouts, and integrated social features.',
				'price'       => '$99',
				'url'         => 'https://wbcomdesigns.com/downloads/reign-buddypress-theme/',
				'demo_url'    => 'https://reign-theme.com/',
				'features'    => array(
					'Drag & drop page builder integration',
					'Multiple header and layout options',
					'Advanced BuddyPress styling',
					'WooCommerce compatibility',
					'Mobile-responsive design',
					'SEO optimized structure'
				),
			),
			array(
				'name'        => 'BuddyX Theme',
				'description' => 'Clean and modern BuddyPress theme perfect for communities, with focus on user experience and performance.',
				'price'       => '$79',
				'url'         => 'https://wbcomdesigns.com/downloads/buddyx-theme/',
				'demo_url'    => 'https://buddyx.com/',
				'features'    => array(
					'Gutenberg block editor support',
					'Multiple community layouts',
					'Advanced member directory',
					'Event management integration',
					'Learning management system support',
					'Performance optimized'
				),
			),
			array(
				'name'        => 'SocialPress Theme',
				'description' => 'Social networking theme with advanced community features, real-time notifications, and modern design.',
				'price'       => '$89',
				'url'         => 'https://wbcomdesigns.com/downloads/socialpress-theme/',
				'demo_url'    => 'https://socialpress-demo.com/',
				'features'    => array(
					'Real-time notifications system',
					'Advanced messaging integration',
					'Social media style interface',
					'Custom profile layouts',
					'Activity stream customization',
					'Dark mode support'
				),
			),
		);
	}

	/**
	 * Get Wbcom logo SVG.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	private function get_wbcom_logo_svg() {
		return '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M16 4L20.944 13.216L32 14.4L22.4 19.2L24 32L16 27.2L8 32L9.6 19.2L0 14.4L11.056 13.216L16 4Z" fill="#0073aa"/>
		</svg>';
	}

	/**
	 * Get dashboard version.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	private function get_dashboard_version() {
		return defined( 'BP_ACTIVITY_FILTER_VERSION' ) ? BP_ACTIVITY_FILTER_VERSION : '1.0.0';
	}

	/**
	 * Add help tab to dashboard.
	 *
	 * @since 4.0.0
	 */
	public function add_dashboard_help_tab() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'wbcom_dashboard_help',
				'title'   => esc_html__( 'Dashboard Help', 'bp-activity-filter' ),
				'content' => $this->get_dashboard_help_content(),
			)
		);

		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'bp-activity-filter' ) . '</strong></p>' .
			'<p><a href="https://wbcomdesigns.com/" target="_blank">' . esc_html__( 'Wbcom Designs', 'bp-activity-filter' ) . '</a></p>' .
			'<p><a href="https://wbcomdesigns.com/support/" target="_blank">' . esc_html__( 'Support', 'bp-activity-filter' ) . '</a></p>' .
			'<p><a href="https://docs.wbcomdesigns.com/" target="_blank">' . esc_html__( 'Documentation', 'bp-activity-filter' ) . '</a></p>'
		);
	}

	/**
	 * Get dashboard help content.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	private function get_dashboard_help_content() {
		return '<h3>' . esc_html__( 'Dashboard Overview', 'bp-activity-filter' ) . '</h3>' .
			'<p>' . esc_html__( 'This dashboard provides a central location to manage all your Wbcom Designs plugins.', 'bp-activity-filter' ) . '</p>' .
			'<h3>' . esc_html__( 'Managing Plugins', 'bp-activity-filter' ) . '</h3>' .
			'<p>' . esc_html__( 'Use the Installed Plugins tab to view, activate, and configure your Wbcom plugins.', 'bp-activity-filter' ) . '</p>' .
			'<h3>' . esc_html__( 'Getting Support', 'bp-activity-filter' ) . '</h3>' .
			'<p>' . esc_html__( 'Visit our support center for documentation, tutorials, and expert assistance.', 'bp-activity-filter' ) . '</p>';
	}

	/**
	 * Add custom styles for the dashboard and menu.
	 *
	 * @since 4.0.0
	 */
	public function add_menu_styles() {
		?>
		<style>
		/* Wbcom Dashboard Styles */
		.wbcom-dashboard {
			max-width: 1200px;
		}
		
		.wbcom-dashboard h1 {
			display: flex;
			align-items: center;
			gap: 12px;
			margin-bottom: 20px;
		}
		
		.wbcom-logo img {
			border-radius: 4px;
		}
		
		.wbcom-version {
			font-size: 14px;
			font-weight: normal;
			color: #666;
			background: #f0f0f1;
			padding: 2px 8px;
			border-radius: 12px;
		}
		
		.wbcom-dashboard-content {
			display: flex;
			gap: 20px;
			margin-top: 20px;
		}
		
		.wbcom-dashboard-main {
			flex: 1;
		}
		
		.wbcom-dashboard-sidebar {
			width: 300px;
		}
		
		/* Welcome Panel */
		.wbcom-welcome-panel {
			background: #fff;
			border: 1px solid #c3c4c7;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
			padding: 20px;
			margin-bottom: 20px;
			border-radius: 4px;
		}
		
		.wbcom-welcome-panel h2 {
			margin: 0 0 10px 0;
			color: #0073aa;
		}
		
		.about-description {
			font-size: 16px;
			margin-bottom: 20px;
			color: #646970;
		}
		
		/* Stats Overview */
		.wbcom-stats-overview {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 15px;
			margin: 20px 0;
			padding: 20px 0;
			border-top: 1px solid #f0f0f1;
			border-bottom: 1px solid #f0f0f1;
		}
		
		.stat-box {
			text-align: center;
			padding: 15px;
			background: #f8f9fa;
			border-radius: 8px;
			border: 1px solid #e2e4e7;
		}
		
		.stat-number {
			font-size: 32px;
			font-weight: bold;
			color: #0073aa;
			line-height: 1;
		}
		
		.stat-label {
			font-size: 13px;
			color: #646970;
			margin-top: 5px;
		}
		
		/* Welcome Panel Columns */
		.wbcom-welcome-panel-column-container {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin-top: 20px;
		}
		
		.wbcom-welcome-panel-column h3 {
			margin: 0 0 15px 0;
			color: #1d2327;
		}
		
		.wbcom-action-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}
		
		.wbcom-action-list li {
			margin-bottom: 10px;
		}
		
		.wbcom-recent-activity,
		.wbcom-system-status {
			list-style: none;
			padding: 0;
			margin: 0;
		}
		
		.wbcom-recent-activity li,
		.wbcom-system-status li {
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 8px 0;
			border-bottom: 1px solid #f0f0f1;
		}
		
		.wbcom-recent-activity li:last-child,
		.wbcom-system-status li:last-child {
			border-bottom: none;
		}
		
		.status-indicator {
			width: 8px;
			height: 8px;
			border-radius: 50%;
			flex-shrink: 0;
		}
		
		.status-indicator.active {
			background-color: #00a32a;
		}
		
		.status-indicator.inactive {
			background-color: #dba617;
		}
		
		.plugin-status {
			margin-left: auto;
			font-size: 12px;
			color: #646970;
		}
		
		/* Tab Navigation */
		.wbcom-dashboard-tabs {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
		}
		
		.nav-tab-wrapper {
			border-bottom: 1px solid #c3c4c7;
			margin: 0;
			padding: 0;
			background: #f8f9fa;
			border-radius: 4px 4px 0 0;
		}
		
		.nav-tab {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			border: none;
			border-bottom: 2px solid transparent;
			background: transparent;
			margin: 0;
			padding: 12px 16px;
			text-decoration: none;
			color: #646970;
			font-weight: 500;
		}
		
		.nav-tab:hover {
			background: #fff;
			color: #0073aa;
		}
		
		.nav-tab-active {
			background: #fff;
			color: #0073aa;
			border-bottom-color: #0073aa;
		}
		
		.tab-content {
			padding: 20px;
		}
		
		/* Premium Plugins List - No Images */
		.premium-plugins-list,
		.premium-themes-list {
			display: grid;
			grid-template-columns: 1fr;
			gap: 20px;
			margin-top: 20px;
		}
		
		.premium-plugin-item,
		.premium-theme-item {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 6px;
			padding: 20px;
			transition: all 0.2s ease;
			position: relative;
		}
		
		.premium-plugin-item:hover,
		.premium-theme-item:hover {
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			transform: translateY(-2px);
			border-color: #0073aa;
		}
		
		.plugin-header,
		.theme-header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			margin-bottom: 15px;
			padding-bottom: 15px;
			border-bottom: 1px solid #f0f0f1;
		}
		
		.plugin-header h3,
		.theme-header h3 {
			margin: 0;
			font-size: 20px;
			font-weight: 600;
			color: #0073aa;
			line-height: 1.3;
			flex: 1;
		}
		
		.plugin-price,
		.theme-price {
			flex-shrink: 0;
			margin-left: 15px;
		}
		
		.price-amount {
			font-size: 24px;
			font-weight: 700;
			color: #0073aa;
			background: #e7f3ff;
			padding: 8px 16px;
			border-radius: 20px;
			border: 2px solid #0073aa;
			display: inline-block;
			min-width: 80px;
			text-align: center;
		}
		
		.plugin-content,
		.theme-content {
			margin-bottom: 20px;
		}
		
		.plugin-description,
		.theme-description {
			font-size: 14px;
			color: #646970;
			line-height: 1.6;
			margin: 0 0 15px 0;
		}
		
		.plugin-features,
		.theme-features {
			list-style: none;
			padding: 0;
			margin: 0;
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 8px;
		}
		
		.plugin-features li,
		.theme-features li {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: 13px;
			color: #23282d;
			padding: 4px 0;
		}
		
		.plugin-features .dashicons,
		.theme-features .dashicons {
			color: #00a32a;
			font-size: 16px;
			flex-shrink: 0;
		}
		
		.more-features {
			font-style: italic;
			color: #8c8f94 !important;
		}
		
		.more-features .dashicons {
			display: none;
		}
		
		.plugin-actions,
		.theme-actions {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
			padding-top: 15px;
			border-top: 1px solid #f0f0f1;
		}
		
		.plugin-actions .button,
		.theme-actions .button {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			text-decoration: none;
			font-weight: 500;
		}
		
		.plugin-actions .button-primary,
		.theme-actions .button-primary {
			background: #0073aa;
			border-color: #0073aa;
			color: #fff;
		}
		
		.plugin-actions .button-primary:hover,
		.theme-actions .button-primary:hover {
			background: #005a87;
			border-color: #005a87;
			transform: translateY(-1px);
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		
		.plugin-actions .button-secondary,
		.theme-actions .button-secondary {
			background: #fff;
			border-color: #c3c4c7;
			color: #646970;
		}
		
		.plugin-actions .button-secondary:hover,
		.theme-actions .button-secondary:hover {
			background: #f6f7f7;
			border-color: #8c8f94;
			color: #23282d;
		}
		
		/* Footer sections */
		.premium-footer,
		.themes-footer,
		.news-footer {
			margin-top: 30px;
			padding-top: 20px;
			border-top: 1px solid #f0f0f1;
		}
		
		.center-text {
			text-align: center;
			margin: 0;
		}
		
		.button-large {
			padding: 12px 24px;
			font-size: 16px;
			font-weight: 600;
		}
		
		/* Plugins Grid */
		.wbcom-plugins-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
		}
		
		.wbcom-plugins-filters {
			display: flex;
			gap: 5px;
		}
		
		.filter-btn {
			padding: 6px 12px;
			border: 1px solid #c3c4c7;
			background: #f0f0f1;
			color: #646970;
			cursor: pointer;
		}
		
		.filter-btn.active,
		.filter-btn:hover {
			background: #0073aa;
			color: #fff;
			border-color: #0073aa;
		}
		
		.wbcom-plugins-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
			gap: 20px;
		}
		
		.wbcom-no-plugins {
			grid-column: 1 / -1;
			text-align: center;
			padding: 60px 20px;
			background: #f8f9fa;
			border: 2px dashed #c3c4c7;
			border-radius: 8px;
		}
		
		.no-plugins-icon .dashicons {
			font-size: 64px;
			color: #c3c4c7;
		}
		
		.wbcom-plugin-card {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			overflow: hidden;
			transition: all 0.2s ease;
		}
		
		.wbcom-plugin-card:hover {
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			transform: translateY(-2px);
		}
		
		.wbcom-plugin-card.plugin-status-active {
			border-left: 4px solid #00a32a;
		}
		
		.wbcom-plugin-card.plugin-status-inactive {
			border-left: 4px solid #dba617;
		}
		
		.plugin-card-top {
			padding: 20px;
		}
		
		.plugin-card-header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			margin-bottom: 12px;
		}
		
		.plugin-card-header h3 {
			margin: 0;
			font-size: 16px;
			line-height: 1.3;
		}
		
		.plugin-status-badge {
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			padding: 3px 8px;
			border-radius: 12px;
			letter-spacing: 0.5px;
		}
		
		.plugin-status-badge.active {
			background: #d1e7dd;
			color: #0f5132;
		}
		
		.plugin-status-badge.inactive {
			background: #fff3cd;
			color: #664d03;
		}
		
		.plugin-description {
			color: #646970;
			font-size: 14px;
			line-height: 1.5;
			margin: 0 0 12px 0;
		}
		
		.plugin-version {
			font-size: 12px;
			color: #8c8f94;
		}
		
		.version-label {
			font-weight: 500;
		}
		
		.plugin-card-bottom {
			padding: 15px 20px;
			background: #f8f9fa;
			border-top: 1px solid #f0f0f1;
		}
		
		.plugin-actions {
			display: flex;
			gap: 8px;
		}
		
		.plugin-actions .button {
			display: inline-flex;
			align-items: center;
			gap: 5px;
			font-size: 13px;
		}
		
		/* News Section */
		.wbcom-news-section {
			max-width: 800px;
		}
		
		.wbcom-news-header {
			margin-bottom: 30px;
		}
		
		.wbcom-news-header h2 {
			margin: 0 0 10px 0;
		}
		
		.news-loading {
			text-align: center;
			padding: 40px;
			color: #646970;
		}
		
		.news-loading .spinner {
			float: none;
			margin: 0 auto 15px;
		}
		
		.news-item {
			padding: 20px 0;
			border-bottom: 1px solid #f0f0f1;
		}
		
		.news-item:last-child {
			border-bottom: none;
		}
		
		.news-item h4 {
			margin: 0 0 10px 0;
			font-size: 16px;
		}
		
		.news-item h4 a {
			text-decoration: none;
			color: #0073aa;
		}
		
		.news-item h4 a:hover {
			text-decoration: underline;
		}
		
		.news-item p {
			margin: 0 0 8px 0;
			color: #646970;
			line-height: 1.5;
			font-size: 14px;
		}
		
		.news-item small {
			color: #8c8f94;
			font-size: 12px;
		}
		
		/* Sidebar Widgets */
		.wbcom-sidebar-widget {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			padding: 20px;
			margin-bottom: 20px;
		}
		
		.wbcom-sidebar-widget h3 {
			margin: 0 0 15px 0;
			color: #1d2327;
			font-size: 16px;
		}
		
		.wbcom-stats-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}
		
		.wbcom-stats-list li {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 10px 0;
			border-bottom: 1px solid #f0f0f1;
		}
		
		.wbcom-stats-list li:last-child {
			border-bottom: none;
		}
		
		.wbcom-stats-list strong {
			color: #0073aa;
			font-size: 18px;
		}
		
		.wbcom-stats-list span {
			color: #646970;
			font-size: 13px;
		}
		
		.widget-actions {
			margin-top: 15px;
		}
		
		.widget-actions .button {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			margin-bottom: 8px;
		}
		
		.widget-actions .button-link {
			display: block;
			text-align: center;
			margin-top: 8px;
			text-decoration: none;
		}
		
		.social-links {
			display: flex;
			gap: 8px;
			margin-top: 10px;
		}
		
		.social-links a {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 32px;
			height: 32px;
			background: #f0f0f1;
			border-radius: 4px;
			color: #646970;
			text-decoration: none;
			transition: all 0.2s ease;
		}
		
		.social-links a:hover {
			background: #0073aa;
			color: #fff;
		}
		
		/* Responsive Design */
		@media (max-width: 1200px) {
			.wbcom-dashboard-content {
				flex-direction: column;
			}
			
			.wbcom-dashboard-sidebar {
				width: 100%;
			}
			
			.plugin-features,
			.theme-features {
				grid-template-columns: 1fr;
			}
			
			.plugin-header,
			.theme-header {
				flex-direction: column;
				align-items: flex-start;
				gap: 10px;
			}
			
			.plugin-price,
			.theme-price {
				margin-left: 0;
				align-self: flex-end;
			}
		}
		
		@media (max-width: 768px) {
			.wbcom-welcome-panel-column-container {
				grid-template-columns: 1fr;
			}
			
			.wbcom-stats-overview {
				grid-template-columns: repeat(2, 1fr);
			}
			
			.wbcom-plugins-grid {
				grid-template-columns: 1fr;
			}
			
			.wbcom-plugins-header {
				flex-direction: column;
				gap: 15px;
				align-items: flex-start;
			}
			
			.premium-plugin-item,
			.premium-theme-item {
				padding: 15px;
			}
			
			.plugin-header h3,
			.theme-header h3 {
				font-size: 18px;
			}
			
			.price-amount {
				font-size: 20px;
				padding: 6px 12px;
			}
			
			.plugin-actions,
			.theme-actions {
				flex-direction: column;
			}
			
			.plugin-actions .button,
			.theme-actions .button {
				width: 100%;
				justify-content: center;
			}
		}
		
		@media (max-width: 480px) {
			.wbcom-stats-overview {
				grid-template-columns: 1fr;
			}
			
			.nav-tab {
				padding: 8px 12px;
				font-size: 14px;
			}
			
			.nav-tab .dashicons {
				display: none;
			}
		}
		
		/* Loading States & Animations */
		.loading-placeholder {
			background: linear-gradient(90deg, #f0f0f1 25%, #e0e0e1 50%, #f0f0f1 75%);
			background-size: 200% 100%;
			animation: loading 1.5s infinite;
		}
		
		@keyframes loading {
			0% { background-position: 200% 0; }
			100% { background-position: -200% 0; }
		}
		
		.wbcom-spin {
			animation: spin 1s linear infinite;
		}
		
		@keyframes spin {
			from { transform: rotate(0deg); }
			to { transform: rotate(360deg); }
		}
		
		.animate-in {
			animation: fadeInUp 0.6s ease-out;
		}
		
		@keyframes fadeInUp {
			from {
				opacity: 0;
				transform: translateY(20px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
		
		/* Accessibility Improvements */
		.wbcom-dashboard *:focus {
			outline: 2px solid #0073aa;
			outline-offset: 2px;
		}
		
		.sr-only {
			position: absolute;
			width: 1px;
			height: 1px;
			padding: 0;
			margin: -1px;
			overflow: hidden;
			clip: rect(0,0,0,0);
			white-space: nowrap;
			border: 0;
		}
		
		/* High Contrast Mode Support */
		@media (prefers-contrast: high) {
			.wbcom-plugin-card {
				border-width: 2px;
			}
			
			.status-indicator {
				border: 1px solid;
			}
			
			.nav-tab-active {
				border-bottom-width: 3px;
			}
			
			.price-amount {
				border-width: 3px;
			}
		}
		
		/* Reduced Motion Support */
		@media (prefers-reduced-motion: reduce) {
			.wbcom-plugin-card,
			.premium-plugin-item,
			.premium-theme-item {
				transition: none;
			}
			
			.loading-placeholder,
			.wbcom-spin,
			.animate-in {
				animation: none;
			}
			
			.premium-plugin-item:hover,
			.premium-theme-item:hover {
				transform: none;
			}
		}
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			// Basic fallback functionality if admin.js fails to load
			if (typeof WbcomDashboard === 'undefined') {
				console.log('Loading basic dashboard fallback...');
				
				// Basic plugin filter functionality
				$('.filter-btn').on('click', function() {
					var filter = $(this).data('filter');
					$('.filter-btn').removeClass('active');
					$(this).addClass('active');
					
					if (filter === 'all') {
						$('.wbcom-plugin-card').show();
					} else {
						$('.wbcom-plugin-card').hide();
						$('.wbcom-plugin-card[data-status="' + filter + '"]').show();
					}
				});
				
				// Basic news feed loading
				if ($('#wbcom-news-feed').length > 0) {
					$.ajax({
						url: 'https://wbcomdesigns.com/wp-json/wp/v2/posts',
						data: { per_page: 5 },
						timeout: 10000,
						success: function(posts) {
							var newsHtml = '';
							if (posts && posts.length > 0) {
								posts.forEach(function(post) {
									var excerpt = post.excerpt.rendered.replace(/<[^>]*>/g, '');
									var date = new Date(post.date).toLocaleDateString();
									newsHtml += '<div class="news-item">';
									newsHtml += '<h4><a href="' + post.link + '" target="_blank">' + post.title.rendered + '</a></h4>';
									newsHtml += '<p>' + excerpt + '</p>';
									newsHtml += '<small>' + date + '</small>';
									newsHtml += '</div>';
								});
								$('#wbcom-news-feed').html(newsHtml);
								$('.news-footer').show();
							} else {
								$('#wbcom-news-feed').html('<div class="news-empty"><h3>No News Available</h3><p>Unable to load recent news at this time.</p></div>');
							}
						},
						error: function() {
							$('#wbcom-news-feed').html('<div class="news-error"><span class="dashicons dashicons-warning"></span><h3>Unable to Load News</h3><p>Please check your internet connection and try again later.</p></div>');
						}
					});
				}
			}
		});
		</script>
		<?php
	}

	/**
	 * Get main menu slug.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public function get_menu_slug() {
		return $this->menu_slug;
	}

	/**
	 * Remove a submenu by plugin key.
	 *
	 * @since 4.0.0
	 * @param string $plugin_key Plugin identifier.
	 */
	public function remove_submenu( $plugin_key ) {
		if ( isset( $this->submenus[ $plugin_key ] ) ) {
			$menu_slug = $this->submenus[ $plugin_key ]['menu_slug'];
			remove_submenu_page( $this->menu_slug, $menu_slug );
			unset( $this->submenus[ $plugin_key ] );
		}
	}

	/**
	 * Check if menu system is working properly.
	 *
	 * @since 4.0.0
	 * @return array Status information.
	 */
	public function get_menu_status() {
		return array(
			'menu_created'    => $this->menu_created,
			'menu_exists'     => $this->main_menu_exists(),
			'submenu_count'   => count( $this->submenus ),
			'dashboard_version' => $this->get_dashboard_version(),
		);
	}

	/**
	 * Get registered submenus.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public function get_submenus() {
		return $this->submenus;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 4.0.0
	 */
	public function __clone() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Cloning instances of this class is forbidden.', 'bp-activity-filter' ),
			'4.0.0'
		);
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since 4.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Unserializing instances of this class is forbidden.', 'bp-activity-filter' ),
			'4.0.0'
		);
	}
}