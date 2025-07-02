<?php
/**
 * Wbcom Designs Unified Menu System
 *
 * Creates a centralized admin menu for all Wbcom Designs plugins.
 * This approach provides better organization and user experience.
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
 * Wbcom Designs Menu Manager
 *
 * Handles the creation and management of the unified Wbcom Designs admin menu.
 * This class uses a singleton pattern to ensure only one menu system exists.
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
	 * Menu icon (dashicon or base64).
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
	private $menu_position = 58.5; // Between Settings (80) and Tools (75)

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
		'dashboard'           => 0,  // Wbcom Designs Dashboard (overview)
		'activity-filter'     => 10, // BuddyPress Activity Filter
		'hashtags'           => 20, // BuddyPress Hashtags
		'polls'              => 30, // BuddyPress Polls
		'quotes'             => 40, // BuddyPress Quotes
		'status-reactions'   => 50, // BuddyPress Status & Reactions
		'sticky-post'        => 60, // BuddyPress Sticky Post
		'wp-stories'         => 70, // WP Stories
		'moderation'         => 80, // BuddyPress Moderation
		'checkins'           => 90, // BuddyPress Check-ins
		'settings'           => 100, // Global Wbcom Settings
	);

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
		// Create main menu immediately if we're already in admin_menu hook
		if ( 'admin_menu' === current_action() ) {
			$this->create_main_menu();
		} else {
			// Create main menu first with highest priority
			add_action( 'admin_menu', array( $this, 'create_main_menu' ), 1 );
		}
		
		// Add dashboard submenu with lower priority  
		add_action( 'admin_menu', array( $this, 'add_dashboard_submenu' ), 5 );
		// Add menu styles
		add_action( 'admin_head', array( $this, 'add_menu_styles' ) );
	}

	/**
	 * Create the main Wbcom Designs menu.
	 *
	 * @since 4.0.0
	 */
	public function create_main_menu() {
		// Check if main menu already exists.
		if ( $this->main_menu_exists() ) {
			return;
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
	}

	/**
	 * Add dashboard submenu as the first item.
	 *
	 * @since 4.0.0
	 */
	public function add_dashboard_submenu() {
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
		// Ensure main menu exists before adding submenu
		if ( ! $this->main_menu_exists() ) {
			$this->create_main_menu();
		}

		// Determine position based on priority.
		$priority = isset( $this->plugin_priority[ $plugin_key ] ) 
			? $this->plugin_priority[ $plugin_key ] 
			: 999;

		if ( null !== $position ) {
			$priority = $position;
		}

		// Store submenu info.
		$this->submenus[ $plugin_key ] = array(
			'page_title' => $page_title,
			'menu_title' => $menu_title,
			'capability' => $capability,
			'menu_slug'  => $menu_slug,
			'function'   => $function,
			'priority'   => $priority,
		);

		// Add submenu page.
		$hook_suffix = add_submenu_page(
			$this->menu_slug,
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$function,
			$priority
		);

		// Sort submenus after adding.
		add_action( 'admin_menu', array( $this, 'sort_submenus' ), 999 );

		return $hook_suffix;
	}

	/**
	 * Sort submenus based on priority.
	 *
	 * @since 4.0.0
	 */
	public function sort_submenus() {
		global $submenu;

		if ( ! isset( $submenu[ $this->menu_slug ] ) ) {
			return;
		}

		// Create array for sorting.
		$sorted_submenus = array();
		
		foreach ( $submenu[ $this->menu_slug ] as $index => $submenu_item ) {
			$menu_slug = $submenu_item[2];
			$priority = 999; // Default priority.

			// Find priority from our stored submenus.
			foreach ( $this->submenus as $plugin_key => $submenu_data ) {
				if ( $submenu_data['menu_slug'] === $menu_slug ) {
					$priority = $submenu_data['priority'];
					break;
				}
			}

			$sorted_submenus[ $priority . '_' . $index ] = $submenu_item;
		}

		// Sort by priority.
		ksort( $sorted_submenus );

		// Reassign sorted submenus.
		$submenu[ $this->menu_slug ] = array_values( $sorted_submenus );
	}

	/**
	 * Dashboard page content.
	 *
	 * @since 4.0.0
	 */
	public function dashboard_page() {
		?>
		<div class="wrap wbcom-dashboard">
			<h1><?php esc_html_e( 'Wbcom Designs', 'bp-activity-filter' ); ?></h1>
			
			<div class="wbcom-dashboard-content">
				<div class="wbcom-dashboard-main">
					<div class="wbcom-welcome-panel">
						<div class="wbcom-welcome-panel-content">
							<h2><?php esc_html_e( 'Welcome to Wbcom Designs', 'bp-activity-filter' ); ?></h2>
							<p class="about-description">
								<?php esc_html_e( 'Thank you for choosing Wbcom Designs plugins! We create premium WordPress and BuddyPress plugins to enhance your community experience.', 'bp-activity-filter' ); ?>
							</p>
							
							<div class="wbcom-welcome-panel-column-container">
								<div class="wbcom-welcome-panel-column">
									<h3><?php esc_html_e( 'Get Started', 'bp-activity-filter' ); ?></h3>
									<ul>
										<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-designs&tab=plugins' ) ); ?>"><?php esc_html_e( 'View Installed Plugins', 'bp-activity-filter' ); ?></a></li>
										<li><a href="https://wbcomdesigns.com/downloads/" target="_blank"><?php esc_html_e( 'Browse All Plugins', 'bp-activity-filter' ); ?></a></li>
										<li><a href="https://docs.wbcomdesigns.com/" target="_blank"><?php esc_html_e( 'Read Documentation', 'bp-activity-filter' ); ?></a></li>
									</ul>
								</div>
								<div class="wbcom-welcome-panel-column">
									<h3><?php esc_html_e( 'Support', 'bp-activity-filter' ); ?></h3>
									<ul>
										<li><a href="https://wbcomdesigns.com/support/" target="_blank"><?php esc_html_e( 'Get Support', 'bp-activity-filter' ); ?></a></li>
										<li><a href="https://wbcomdesigns.com/contact/" target="_blank"><?php esc_html_e( 'Contact Us', 'bp-activity-filter' ); ?></a></li>
										<li><a href="https://wordpress.org/support/plugin/bp-activity-filter/" target="_blank"><?php esc_html_e( 'Community Forums', 'bp-activity-filter' ); ?></a></li>
									</ul>
								</div>
								<div class="wbcom-welcome-panel-column wbcom-welcome-panel-last">
									<h3><?php esc_html_e( 'Stay Connected', 'bp-activity-filter' ); ?></h3>
									<ul>
										<li><a href="https://wbcomdesigns.com/blog/" target="_blank"><?php esc_html_e( 'Blog & Updates', 'bp-activity-filter' ); ?></a></li>
										<li><a href="https://twitter.com/wbcomdesigns" target="_blank"><?php esc_html_e( 'Follow on Twitter', 'bp-activity-filter' ); ?></a></li>
										<li><a href="https://www.facebook.com/wbcomdesigns/" target="_blank"><?php esc_html_e( 'Like on Facebook', 'bp-activity-filter' ); ?></a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>

					<?php $this->render_dashboard_tabs(); ?>
				</div>

				<div class="wbcom-dashboard-sidebar">
					<?php $this->render_sidebar_widgets(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render dashboard tabs.
	 *
	 * @since 4.0.0
	 */
	private function render_dashboard_tabs() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'plugins';
		?>
		<div class="wbcom-dashboard-tabs">
			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-designs&tab=plugins' ) ); ?>" 
				   class="nav-tab <?php echo 'plugins' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Installed Plugins', 'bp-activity-filter' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-designs&tab=news' ) ); ?>" 
				   class="nav-tab <?php echo 'news' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'News & Updates', 'bp-activity-filter' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-designs&tab=premium' ) ); ?>" 
				   class="nav-tab <?php echo 'premium' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Premium Plugins', 'bp-activity-filter' ); ?>
				</a>
			</nav>

			<div class="tab-content">
				<?php
				switch ( $active_tab ) {
					case 'news':
						$this->render_news_tab();
						break;
					case 'premium':
						$this->render_premium_tab();
						break;
					case 'plugins':
					default:
						$this->render_plugins_tab();
						break;
				}
				?>
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
		<div class="wbcom-plugins-grid">
			<?php if ( empty( $wbcom_plugins ) ) : ?>
				<p><?php esc_html_e( 'No Wbcom Designs plugins are currently installed.', 'bp-activity-filter' ); ?></p>
			<?php else : ?>
				<?php foreach ( $wbcom_plugins as $plugin ) : ?>
					<div class="wbcom-plugin-card <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>">
						<div class="plugin-card-top">
							<h3><?php echo esc_html( $plugin['name'] ); ?></h3>
							<p><?php echo esc_html( $plugin['description'] ); ?></p>
						</div>
						<div class="plugin-card-bottom">
							<div class="plugin-status">
								<span class="status-label <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>">
									<?php echo $plugin['active'] ? esc_html__( 'Active', 'bp-activity-filter' ) : esc_html__( 'Inactive', 'bp-activity-filter' ); ?>
								</span>
								<?php if ( ! empty( $plugin['version'] ) ) : ?>
									<span class="version">v<?php echo esc_html( $plugin['version'] ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $plugin['settings_url'] ) ) : ?>
								<a href="<?php echo esc_url( $plugin['settings_url'] ); ?>" class="button button-primary">
									<?php esc_html_e( 'Settings', 'bp-activity-filter' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render news tab.
	 *
	 * @since 4.0.0
	 */
	private function render_news_tab() {
		?>
		<div class="wbcom-news-section">
			<h3><?php esc_html_e( 'Latest News from Wbcom Designs', 'bp-activity-filter' ); ?></h3>
			<div id="wbcom-news-feed">
				<p><?php esc_html_e( 'Loading latest news...', 'bp-activity-filter' ); ?></p>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			// Load news feed via AJAX
			$.get('https://wbcomdesigns.com/wp-json/wp/v2/posts?per_page=5', function(posts) {
				var newsHtml = '';
				if (posts && posts.length > 0) {
					posts.forEach(function(post) {
						newsHtml += '<div class="news-item">';
						newsHtml += '<h4><a href="' + post.link + '" target="_blank">' + post.title.rendered + '</a></h4>';
						newsHtml += '<p>' + post.excerpt.rendered + '</p>';
						newsHtml += '<small>' + new Date(post.date).toLocaleDateString() + '</small>';
						newsHtml += '</div>';
					});
				} else {
					newsHtml = '<p><?php esc_html_e( 'No news available at the moment.', 'bp-activity-filter' ); ?></p>';
				}
				$('#wbcom-news-feed').html(newsHtml);
			}).fail(function() {
				$('#wbcom-news-feed').html('<p><?php esc_html_e( 'Unable to load news. Please visit our website for the latest updates.', 'bp-activity-filter' ); ?></p>');
			});
		});
		</script>
		<?php
	}

	/**
	 * Render premium plugins tab.
	 *
	 * @since 4.0.0
	 */
	private function render_premium_tab() {
		$premium_plugins = $this->get_premium_plugins();
		?>
		<div class="wbcom-premium-plugins">
			<p><?php esc_html_e( 'Enhance your community with these premium plugins:', 'bp-activity-filter' ); ?></p>
			<div class="premium-plugins-grid">
				<?php foreach ( $premium_plugins as $plugin ) : ?>
					<div class="premium-plugin-card">
						<h4><?php echo esc_html( $plugin['name'] ); ?></h4>
						<p><?php echo esc_html( $plugin['description'] ); ?></p>
						<div class="plugin-price">
							<?php if ( ! empty( $plugin['price'] ) ) : ?>
								<span class="price"><?php echo esc_html( $plugin['price'] ); ?></span>
							<?php endif; ?>
						</div>
						<a href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" class="button button-primary">
							<?php esc_html_e( 'Learn More', 'bp-activity-filter' ); ?>
						</a>
					</div>
				<?php endforeach; ?>
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
			<ul class="wbcom-stats-list">
				<li>
					<strong><?php echo count( $this->get_wbcom_plugins() ); ?></strong>
					<span><?php esc_html_e( 'Plugins Installed', 'bp-activity-filter' ); ?></span>
				</li>
				<li>
					<strong><?php echo count( array_filter( $this->get_wbcom_plugins(), function( $p ) { return $p['active']; } ) ); ?></strong>
					<span><?php esc_html_e( 'Plugins Active', 'bp-activity-filter' ); ?></span>
				</li>
			</ul>
		</div>

		<div class="wbcom-sidebar-widget">
			<h3><?php esc_html_e( 'Need Help?', 'bp-activity-filter' ); ?></h3>
			<p><?php esc_html_e( 'Get support for all Wbcom Designs plugins.', 'bp-activity-filter' ); ?></p>
			<a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary">
				<?php esc_html_e( 'Get Support', 'bp-activity-filter' ); ?>
			</a>
		</div>

		<div class="wbcom-sidebar-widget">
			<h3><?php esc_html_e( 'Rate Our Plugins', 'bp-activity-filter' ); ?></h3>
			<p><?php esc_html_e( 'Help others discover our plugins by leaving a review.', 'bp-activity-filter' ); ?></p>
			<a href="https://wordpress.org/support/plugin/bp-activity-filter/reviews/#new-post" target="_blank" class="button button-secondary">
				<?php esc_html_e( 'Leave Review', 'bp-activity-filter' ); ?>
			</a>
		</div>
		<?php
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

		// Known Wbcom plugins with their identifiers.
		$known_plugins = array(
			'bp-activity-filter/buddypress-activity-filter.php' => array(
				'name' => 'BuddyPress Activity Filter',
				'settings_url' => admin_url( 'admin.php?page=wbcom-activity-filter' ),
			),
			// Add other Wbcom plugins here as they're updated to use the unified menu.
		);

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			// Check if it's a Wbcom plugin.
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
	 * Get list of premium plugins.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	private function get_premium_plugins() {
		return array(
			array(
				'name'        => 'BuddyPress Hashtags',
				'description' => 'Add hashtag functionality to BuddyPress activities.',
				'price'       => '$29',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-hashtags/',
			),
			array(
				'name'        => 'BuddyPress Polls',
				'description' => 'Create and participate in polls within your community.',
				'price'       => '$39',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-polls/',
			),
			array(
				'name'        => 'BuddyPress Quotes',
				'description' => 'Share beautiful quotes with custom backgrounds.',
				'price'       => '$29',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-quotes/',
			),
			array(
				'name'        => 'BuddyPress Status & Reactions',
				'description' => 'Custom member statuses and emoji reactions.',
				'price'       => '$49',
				'url'         => 'https://wbcomdesigns.com/downloads/buddypress-status/',
			),
			array(
				'name'        => 'WP Stories',
				'description' => 'Instagram-like stories feature for WordPress.',
				'price'       => '$59',
				'url'         => 'https://wbcomdesigns.com/downloads/wp-stories/',
			),
		);
	}

	/**
	 * Add custom styles for the menu.
	 *
	 * @since 4.0.0
	 */
	public function add_menu_styles() {
		?>
		<style>
		.wbcom-dashboard {
			max-width: 1200px;
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
		
		.wbcom-welcome-panel {
			background: #fff;
			border: 1px solid #c3c4c7;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
			padding: 20px;
			margin-bottom: 20px;
		}
		
		.wbcom-welcome-panel-column-container {
			display: flex;
			gap: 20px;
			margin-top: 20px;
		}
		
		.wbcom-welcome-panel-column {
			flex: 1;
		}
		
		.wbcom-plugins-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
			gap: 20px;
			margin-top: 20px;
		}
		
		.wbcom-plugin-card {
			background: #fff;
			border: 1px solid #c3c4c7;
			padding: 20px;
			border-radius: 4px;
		}
		
		.wbcom-plugin-card.active {
			border-left: 4px solid #00a32a;
		}
		
		.wbcom-plugin-card.inactive {
			border-left: 4px solid #dba617;
		}
		
		.plugin-card-bottom {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-top: 15px;
			padding-top: 15px;
			border-top: 1px solid #f0f0f1;
		}
		
		.status-label.active {
			color: #00a32a;
		}
		
		.status-label.inactive {
			color: #dba617;
		}
		
		.wbcom-sidebar-widget {
			background: #fff;
			border: 1px solid #c3c4c7;
			padding: 15px;
			margin-bottom: 20px;
		}
		
		.wbcom-stats-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}
		
		.wbcom-stats-list li {
			display: flex;
			justify-content: space-between;
			padding: 10px 0;
			border-bottom: 1px solid #f0f0f1;
		}
		
		.wbcom-stats-list li:last-child {
			border-bottom: none;
		}
		
		.premium-plugins-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
			gap: 20px;
			margin-top: 20px;
		}
		
		.premium-plugin-card {
			background: #fff;
			border: 1px solid #c3c4c7;
			padding: 20px;
			text-align: center;
		}
		
		.plugin-price .price {
			font-size: 24px;
			font-weight: bold;
			color: #135e96;
		}
		
		.news-item {
			padding: 15px 0;
			border-bottom: 1px solid #f0f0f1;
		}
		
		.news-item:last-child {
			border-bottom: none;
		}
		
		.news-item h4 {
			margin: 0 0 10px 0;
		}
		
		.news-item p {
			margin: 0 0 5px 0;
		}
		
		@media (max-width: 768px) {
			.wbcom-dashboard-content {
				flex-direction: column;
			}
			
			.wbcom-dashboard-sidebar {
				width: 100%;
			}
			
			.wbcom-welcome-panel-column-container {
				flex-direction: column;
			}
		}
		</style>
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