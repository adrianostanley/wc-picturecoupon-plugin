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

	/** The order meta key to store the user's current profile picture at the checkout */
	const USER_PROFILE_PICTURE_ORDER_META_KEY = 'wcpc_userpp';

	/** @var WCPC_Loader the only possible instance of this plugin */
	private static $instance;

	/** @var WCPC_Rest_API_Controller */
	private $rest_api;

	/**
	 * Initializes the loader.
	 */
	private function __construct() {

		$this->load_classes();
		$this->add_actions();
		$this->add_filters();

		$this->rest_api = new WCPC_Rest_API_Controller();
	}

	/**
	 * Registers all the Picture Coupon actions in a row.
	 */
	public function add_actions() {

		$this->add_action_plugins_loaded();

		if( is_admin() ) {

			$this->add_action_add_metaboxes();
			$this->add_action_edit_user_profile();
		}

		$this->add_action_edit_account_form();
		$this->add_action_get_footer();
		$this->add_action_rest_api_init();
		$this->add_action_woocommerce_checkout_create_order();
		$this->add_action_woocommerce_before_cart();
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

				$picture_id = $order->get_meta( self::USER_PROFILE_PICTURE_ORDER_META_KEY );

				if ( empty( $picture_id ) ) {

					echo __( 'The user had no profile picture set at the checkout.' );
				} else {

					$picture = new WCPC_Picture( $picture_id );

					echo $picture->get_avatar( 128 );
				}
			}, 'shop_order', 'side', 'core'	);
		});
	}

	/**
	 * Registers the action to add an uploader instance to the user account details page and allow him/her to upload
	 * multiple profile pictures.
	 */
	public function add_action_edit_account_form() {

		add_action( 'woocommerce_before_edit_account_form', function( $atts, $content = NULL) {

			$picture = new WCPC_Uploader();
			$picture->update_history();
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
	 * Registers the action of creating a REST API endpoint for showing profile pictures data.
	 */
	public function add_action_rest_api_init() {

		add_action( 'rest_api_init', function () {

			$this->rest_api->register_routes();
		} );
	}

	/**
	 * Register the action of adding content to the cart and checkout to let users know they will have better discounts
	 * if they set up a profile picture.
	 */
	public function add_action_woocommerce_before_cart() {

		$profile_picture_widget = function () {

			$history = WCPC_History::get_user_history();

			$edit_account_url = esc_url( wc_get_account_endpoint_url( 'edit-account' ) );

			echo '
				<div class="wcpc-clear"></div>
				<div id="wcpc-checkout-widget">
				<h3>' . __( 'Your profile picture may give you a great discount!' ) . '</h3>
			';

			if( $history->has_profile_picture() ) {

				echo sprintf( '
					<div>%s</div>
					<div>
						This is your current profile picture. If it has an article of clothing that is the same type as what you are buying, then you will receive an additional discount.
						<a href="%s">Click here to switch to another picture</a>.
					</div>',
					$history->get_current()->get_avatar( 128 ),
					$edit_account_url
				);
			} else {

				echo sprintf( '<p>%s <a href="%s">Click here to set up your profile picture</a>.</p>',
					__( 'Did you know that if you have a profile picture with an article of clothing that is the same type as what you are buying (ex: pants, shirt, hat) then you will receive an additional discount?' ),
					$edit_account_url
				);
			}

			echo '</div><div class="wcpc-clear"></div>';
		};

		add_action( 'woocommerce_before_cart', $profile_picture_widget );
		add_action( 'woocommerce_checkout_before_customer_details', $profile_picture_widget );
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

				$order->update_meta_data( self::USER_PROFILE_PICTURE_ORDER_META_KEY, $current_profile_picture->get_id() );
			}
		}, 20, 2);
	}

	/**
	 * Registers the filter used by WordPress to replace the default avatars by Gravatar for the selected avatar from
	 * the user's history.
	 *
	 * This filter will replace user's profile picture only if the user has a history. Otherwise, the default WordPress
	 * avatars (from Gravatar) will be used.
	 */
	public function add_filter_get_avatar() {

		add_filter( 'get_avatar' , function ( $avatar, $user_ref, $size ) {

			$history = null;

			if ( is_numeric( $user_ref ) ) {

				$history = WCPC_History::get_user_history( (int) $user_ref);
			} elseif ( is_string( $user_ref ) ) {

				$user = get_user_by( 'email', $user_ref );

				$history = WCPC_History::get_user_history( $user->ID );
			} elseif ( property_exists( $user_ref, 'user_id' ) ) {

				$history = WCPC_History::get_user_history( $user_ref->user_id );
			} elseif ( property_exists( $user_ref, 'ID' ) ) {

				$history = WCPC_History::get_user_history( $user_ref->ID );
			}

			if ( isset($history) && $history->has_profile_picture() ) {

				$avatar = $history->get_current()->get_avatar( $size );
			}

			return $avatar;
		}, 1 , 5 );
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

