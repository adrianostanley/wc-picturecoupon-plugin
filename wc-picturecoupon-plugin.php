<?php
/**
 * Plugin Name: WooCommerce Picture Coupon Plugin
 * Description: Allows customers to upload multiple profile pictures and have better discount codes on products.
 * Author: Adriano Castro
 * Version: 0.1
 * WC requires at least: 3.0.9
 * WC tested up to: 4.1.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   PictureCoupon
 * @author    ACastro
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * The plugin loader class.
 *
 * This class is responsible for loading classes, adding all actions, filters, mexaboxes and other components to
 * WooCommerce pages and functionalities.
 */
class WCPC_Loader {

	/** @var WCPC_Loader the only possible instance of this plugin */
	private static $instance;

	/** @var WCPC_Functions */
	private $functions;

	/** @var WCPC_Rest_API_Controller */
	private $rest_api;

	/**
	 * Initializes the loader.
	 */
	private function __construct() {

		$this->load_classes();

		$this->functions = new WCPC_Functions();
		$this->rest_api  = new WCPC_Rest_API_Controller();

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Registers all the Picture Coupon actions in a row.
	 */
	public function add_actions() {

		$this->add_action_plugins_loaded();

		if( is_admin() ) {

			add_action( 'add_meta_boxes', array( $this->functions, 'add_meta_boxes' ) );
			add_action( 'edit_user_profile', array( $this->functions, 'edit_user_profile' ) );
		}

		add_action( 'get_footer', array( $this->functions, 'enqueue_assets' ), 1 );

		add_action( 'woocommerce_before_cart', array( $this->functions, 'add_profile_picture_widget' ) );
		add_action( 'woocommerce_before_edit_account_form', array( $this->functions, 'add_uploader' ) );
		add_action( 'woocommerce_checkout_before_customer_details', array( $this->functions, 'add_profile_picture_widget' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this->functions, 'store_picture_at_order' ), 20, 2 );

		add_action( 'rest_api_init', array( $this->rest_api, 'register_routes' ) );
	}

	/**
	 * Registers all the Picture Coupon filters in a row.
	 */
	public function add_filters() {

		add_filter( 'get_avatar', array( $this->functions, 'get_avatar' ), 1, 5 );
	}

	/**
	 * Registers the integration between Picture Coupon plugin and WooCommerce.
	 */
	public function add_action_plugins_loaded() {

		add_action( 'plugins_loaded', function() {

			// Register the integration.
			add_filter( 'woocommerce_integrations', function( $integrations ) {
				require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wcpc-setup.php' );

				$integrations[] = WCPC_Setup::class;

				return $integrations;
			} );

			// Set the plugin slug
			define( 'PICTURE_COUPON', 'wc-settings' );

			// Setting action for plugin
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {

				$links[] = '<a href="'. menu_page_url( PICTURE_COUPON, false ) .'&tab=integration">Settings</a>';

				return $links;
			});
		});
	}

	/**
	 * Loads all the classes used by the plugin.
	 *
	 * For the sake of simplicity for this plugin demonstration, all the classes will be loaded. In a running production
	 * environment, the classes should be loaded on demand, only when they're really needed.
	 */
	public function load_classes() {
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wcpc-functions.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wcpc-history.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wcpc-picture.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wcpc-restapi.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wcpc-uploader.php' );
	}

	/**
	 * Checks if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins, false ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}

	/**
	 * Returns the only WCPC_Loader instance.
	 *
	 * Singleton: Ensures only one instance is loaded at one time.
	 *
	 * @return WCPC_Loader
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

if ( WCPC_Loader::is_woocommerce_active() ) {
	// If WooCommerce is active, Picture Coupon plugin must be activated
	WCPC_Loader::instance();
}

