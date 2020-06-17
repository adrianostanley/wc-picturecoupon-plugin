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
 */
class WCPC_Loader {

	/** @var WCPC_Loader the only possible instance of this plugin */
	protected static $instance;

	/**
	 * Initializes the loader.
	 */
	private function __construct() {

		$this->load_classes();
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Registers all the Picture Coupon actions in a row.
	 */
	public function add_actions() {

		$this->add_action_add_metaboxes();
		$this->add_action_plugins_loaded();
		$this->add_action_edit_account_form();
		$this->add_action_edit_user_profile();
		$this->add_action_get_footer();
		$this->add_action_woocommerce_checkout_create_order();
	}

	/**
	 * Registers all the Picture Coupon filters in a row.
	 */
	public function add_filters() {

		$this->add_filter_get_avatar();
	}

	/**
	 * Registers the action to include a metabox in the order details so admins can see which profile picture the user
	 * was using during the order.
	 */
	public function add_action_add_metaboxes() {

		add_action( 'add_meta_boxes', function() {
			add_meta_box( 'user_profile_picture_at_order', __( 'User\'s Profile Picture' ), function() {
				global $post;

				$order = new WC_Order($post->ID);

				$picture_id = $order->get_meta( 'user_profile_picture' );

				if ( ! isset ( $picture_id ) ) {
					echo __( 'The user had no profile picture set at the checkout.' );
				} else {
					$picture = new WCPC_Picture( $picture_id );

					echo $picture->get_avatar( 128 );
				}
			}, 'shop_order', 'side', 'core'
			);
		});
	}

	/**
	 * Registers the action to add an uploader instance to the user account details page and allow him/her to upload
	 * multiple profile pictures.
	 */
	public function add_action_edit_account_form() {

		add_action( 'woocommerce_before_edit_account_form', function( $atts, $content = NULL) {

			$picture = new WCPC_Uploader();
			$picture->update_profile_picture();
			echo $picture->get_html();
		});
	}

	/**
	 * Registers the action to allow admins to see all available customer profile images on the customer's user profile
	 * page
	 */
	public function add_action_edit_user_profile() {

		add_action( 'edit_user_profile', function( $user ) {

			/** @var WP_User $user */
			$history = WCPC_History::get_user_history( $user->ID );

			$profile_pictures = $history->get_all();

			$html = '';

			/** @var WCPC_Picture $picture */
			foreach ( $profile_pictures as $picture ) {
				$html .= $picture->get_avatar( 96 );
			}

			echo sprintf("<h2>%s</h2>%s",
				__("Profile pictures"),
				$html
			);
		} );
	}

	/**
	 * Registers the action to enqueue the assets.
	 */
	public function add_action_get_footer() {

		add_action('get_footer', function() {

			wp_enqueue_script( 'wc-picturecoupon', '/wp-content/plugins/wc-picturecoupon-plugin/assets/js/frontend/wc-picturecoupon-frontend.js' );

			wp_enqueue_style( 'wc-picturecoupon', '/wp-content/plugins/wc-picturecoupon-plugin/assets/css/frontend/wc-picturecoupon-frontend.css' );
		}, 1);
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
	 * Registers the action that stores the current user's profile picture in the order meta data.
	 */
	public function add_action_woocommerce_checkout_create_order() {

		add_action( 'woocommerce_checkout_create_order' , function( $order, $data ) {

			/** @var WC_Order $order */
			$history = WCPC_History::get_user_history( $order->get_user_id() );

			$current_profile_picture = $history->get_current();

			if ( $current_profile_picture->is_valid() ) {
				$order->update_meta_data( 'user_profile_picture', $current_profile_picture->get_id() );
			}
		}, 20, 2);
	}

	/**
	 * Registers the filter used by WordPress to replace the default avatars by Gravatar for the selected avatar from the
	 * user's history.
	 */
	public function add_filter_get_avatar() {

		add_filter( 'get_avatar' , function ( $avatar, $id_or_email, $size, $default, $alt ) {
			$user = false;
			if ( is_numeric( $id_or_email ) ) {
				$id = (int) $id_or_email;
				$user = get_user_by( 'id' , $id );
			} elseif ( is_object( $id_or_email ) ) {
				if ( ! empty( $id_or_email->user_id ) ) {
					$id = (int) $id_or_email->user_id;
					$user = get_user_by( 'id' , $id );
				}
			} else {
				$user = get_user_by( 'email', $id_or_email );
			}

			if ( $user && is_object( $user ) ) {
				$history = WCPC_History::get_user_history($user->ID);

				if ( $history->has_profile_picture() ) {
					$avatar = $history->get_current()->get_avatar( $size );
				}
			}
			return $avatar;
		} , 1 , 5 );
	}

	/**
	 * Loads all the classes used by the plugin.
	 *
	 * For the sake of simplicity for this plugin demonstration, all the classes will be loaded. In a running production
	 * environment, the classes should be loaded on demand, only when they're really needed.
	 */
	public function load_classes() {
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wcpc-history.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-wcpc-picture.php' );
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

if ( ! WCPC_Loader::is_woocommerce_active() ) {
	// If WooCommerce is not active, Picture Coupon plugin must do nothing
	return;
}

WCPC_Loader::instance();