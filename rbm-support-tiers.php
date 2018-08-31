<?php
/**
 * Plugin Name: RBM Support Tiers
 * Plugin URI: https://github.com/realbig/rbm-support-tiers
 * Description: Functionality for RBM Support Tiers
 * Version: 1.0.0
 * Text Domain: rbm-support-tiers
 * Author: Eric Defore
 * Author URI: https://realbigmarketing.com/
 * Contributors: d4mation
 * GitHub Plugin URI: https://github.com/realbig/rbm-support-tiers
 * GitHub Branch: develop
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EDD_SLUG', 'plans' );

if ( ! class_exists( 'RBM_Support_Tiers' ) ) {

	/**
	 * Main RBM_Support_Tiers class
	 *
	 * @since	  1.0.0
	 */
	class RBM_Support_Tiers {
		
		/**
		 * @var			RBM_Support_Tiers $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			RBM_Support_Tiers $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true RBM_Support_Tiers
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %s or higher to be installed!', 'Outdated Dependency Error', 'rbm-support-tiers' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>WordPress</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
			add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'sitemap_exclude' ), 10, 2 );
		
			add_action( 'wp_head', array( $this, 'noindex_meta' ) );

			add_filter( 'redirect_canonical', array( $this, 'prevent_canonical_redirect' ), 10, 2 );
			
			add_filter( 'post_type_labels_download', array( $this, 'relabel_downloads' ) );
			
			add_filter( 'register_post_type_args', array( $this, 'alter_post_type_args' ), 10, 2 );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'RBM_Support_Tiers_VER' ) ) {
				// Plugin version
				define( 'RBM_Support_Tiers_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'RBM_Support_Tiers_DIR' ) ) {
				// Plugin path
				define( 'RBM_Support_Tiers_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'RBM_Support_Tiers_URL' ) ) {
				// Plugin URL
				define( 'RBM_Support_Tiers_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'RBM_Support_Tiers_FILE' ) ) {
				// Plugin File
				define( 'RBM_Support_Tiers_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = RBM_Support_Tiers_DIR . '/languages/';
			$lang_dir = apply_filters( 'RBM_Support_Tiers_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'rbm-support-tiers' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'rbm-support-tiers', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/rbm-support-tiers/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/rbm-support-tiers/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'rbm-support-tiers', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/rbm-support-tiers/languages/ folder
				load_textdomain( 'rbm-support-tiers', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'rbm-support-tiers', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'rbm-support-tiers',
				RBM_Support_Tiers_URL . 'assets/css/style.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Support_Tiers_VER
			);
			
			wp_register_script(
				'rbm-support-tiers',
				RBM_Support_Tiers_URL . 'assets/js/script.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Support_Tiers_VER,
				true
			);
			
			wp_localize_script( 
				'rbm-support-tiers',
				'rbmsupporttiers',
				apply_filters( 'RBM_Support_Tiers_localize_script', array() )
			);
			
			wp_register_style(
				'rbm-support-tiers-admin',
				RBM_Support_Tiers_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Support_Tiers_VER
			);
			
			wp_register_script(
				'rbm-support-tiers-admin',
				RBM_Support_Tiers_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Support_Tiers_VER,
				true
			);
			
			wp_localize_script( 
				'rbm-support-tiers-admin',
				'rbmsupporttiers',
				apply_filters( 'RBM_Support_Tiers_localize_admin_script', array() )
			);
			
		}
		
		/**
		 * Force Yoast SEO to not include this CPT in the Sitemap
		 * It will still appear to be enabled on the Settings Page, but it will not show in the actual Sitemap
		 * 
		 * @param		boolean $exclude   To exclude or not
		 * @param		string  $post_type Post Type
		 *                                 
		 * @access		public
		 * @since		1.0.0
		 * @return		boolean To exclude or not
		 */
		public function sitemap_exclude( $exclude = false, $post_type ) {

			if ( $post_type == 'download' ) {
				return true;
			}

			return false;

		}
		
		/**
		 * Add noindex <meta> to the Head to prevent Crawlers from Indexing these Pages
		 * 
		 * @access		public
		 * @since		1.0.0
		 * @return		void
		 */
		public function noindex_meta() {

			if ( get_post_type() == 'download' ) : ?>

				<meta name="robots" content="noindex, nofollow">
				<meta name="googlebot" content="noindex">

			<?php endif;

		}

		/**
		 * Prevent Redirecting to Downloads based on matching URL Slugs
		 * 
		 * @param		string $redirect_url  The redirect URL. Returning False prevents the Redirect
		 * @param		string $requested_url The requested URL
		 *                                             
		 * @access		public
		 * @since		1.0.0
		 * @return		string The redirect URL
		 */
		public function prevent_canonical_redirect( $redirect_url, $requested_url ) {

			$post_id = url_to_postid( $redirect_url );

			if ( get_post_type( $post_id ) == 'download' ) {
				return false;
			}

			return $redirect_url;

		}
		
		/**
		 * Relabel the Downloads Post Type
		 * 
		 * @param		object $labels Post Type Labels casted to an Object for some reason
		 *              
		 * @access		public
		 * @since		1.0.0
		 * @return		object Post Type Labels
		 */
		public function relabel_downloads( $labels ) {
			
			$labels->name = __( 'Plans', 'rbm-support-tiers' );
			$labels->all_items = __( 'All Plans', 'rbm-support-tiers' );
			$labels->singular_name = __( 'Plan', 'rbm-support-tiers' );
			$labels->add_new = __( 'Add Plan', 'rbm-support-tiers' );
			$labels->add_new_item = __( 'Add Plan', 'rbm-support-tiers' );
			$labels->edit_item = __( 'Edit Plan', 'rbm-support-tiers' );
			$labels->new_item = __( 'New Plan', 'rbm-support-tiers' );
			$labels->view_item = __( 'View Plan', 'rbm-support-tiers' );
			$labels->search_items = __( 'Search Plans', 'rbm-support-tiers' );
			$labels->not_found = __( 'No Plans found', 'rbm-support-tiers' );
			$labels->not_found_in_trash = __( 'No Plans found in trash', 'rbm-support-tiers' );
			$labels->parent_item_colon = __( 'Parent Plan:', 'rbm-support-tiers' );
			$labels->menu_name = __( 'Plans', 'rbm-support-tiers' );
			$labels->name_admin_bar = __( 'Plan', 'rbm-support-tiers' );
			$labels->archives = __( 'Our Plans', 'rbm-support-tiers' );
			
			return $labels;
			
		}
		
		/**
		 * Switch Menu Icon for Posts Post Type
		 * 
		 * @param		array  $args      Post Type Args
		 * @param		string $post_type Post Type Key
		 *                                     
		 * @access		public
		 * @since		1.0.0
		 * @return		array  Post Type Args
		 */
		public function alter_post_type_args( $args, $post_type ) {
    
			if ( $post_type == 'download' ) {

				$args['menu_icon'] = 'dashicons-networking';
				$args['exclude_from_search'] = true;

			}

			return $args;

		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true RBM_Support_Tiers
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \RBM_Support_Tiers The one true RBM_Support_Tiers
 */
add_action( 'plugins_loaded', 'RBM_Support_Tiers_load' );
function RBM_Support_Tiers_load() {

	require_once __DIR__ . '/core/rbm-support-tiers-functions.php';
	RBMSUPPORTTIERS();

}