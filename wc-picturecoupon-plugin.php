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

require_once( plugin_dir_path( __FILE__ ) . '/vendor/autoload.php' );

add_action( 'plugins_loaded', function() {
	// Checks if WooCommerce is installed.
	if ( class_exists( 'WC_Integration' ) ) {
		// Register the integration.
		add_filter( 'woocommerce_integrations', function( $integrations ) {
			$integrations[] = \PictureCoupon\Integration\Setup::class;
			return $integrations;
		} );
	}

	// Set the plugin slug
	define( 'PICTURE_COUPON', 'wc-settings' );

	// Setting action for plugin
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
		$links[] = '<a href="'. menu_page_url( PICTURE_COUPON, false ) .'&tab=integration">Settings</a>';
		return $links;
	} );
} );












// =========================================================================
/**
 * Function wc_cus_cpp_form
 *
 */
add_action( 'woocommerce_before_edit_account_form', function( $atts, $content= NULL) {
	$picture = new \PictureCoupon\Controls\Uploader();
	$picture->update_profile_picture();
	echo $picture->get_html();
});




// =========================================================================
/**
 * Function wc_cus_change_avatar
 *
 */
add_filter( 'get_avatar' , 'wc_cus_change_avatar' , 1 , 5 );
function wc_cus_change_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
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
		$history = \PictureCoupon\User\History::get_user_history($user->ID);

		if ( $history->has_profile_picture() ) {
			$avatar = $history->get_current()->get_avatar( $size );
		}
	}
	return $avatar;
}





add_action('get_footer', function() {
	wp_enqueue_script( 'wc-picturecoupon', '/wp-content/plugins/wc-picturecoupon-plugin/assets/js/frontend/wc-picturecoupon-frontend.js' );

	wp_enqueue_style( 'wc-picturecoupon', '/wp-content/plugins/wc-picturecoupon-plugin/assets/css/frontend/wc-picturecoupon-frontend.css' );
}, 1);




add_action( 'edit_user_profile', function( $user ) {
	/** @var WP_User $user */
	$history = \PictureCoupon\User\History::get_user_history( $user->ID );

	$profile_pictures = $history->get_all();

	$html = '';

	/** @var \PictureCoupon\User\Picture $picture */
	foreach ( $profile_pictures as $picture ) {
		$html .= $picture->get_avatar( 96 );
	}

	echo sprintf("<h2>%s</h2>%s",
		__("Profile pictures"),
		$html
	);
} );




add_action( 'woocommerce_checkout_create_order' , function( $order, $data ) {
	/** @var WC_Order $order */
	$history = \PictureCoupon\User\History::get_user_history( $order->get_user_id() );

	$current_profile_picture = $history->get_current();

	if ( $current_profile_picture->is_valid() ) {
		$order->update_meta_data( 'user_profile_picture', $current_profile_picture->get_id() );
	}
}, 20, 2);



add_action( 'add_meta_boxes', function () {
	add_meta_box( 'user_profile_picture_at_order', __( 'User\'s Profile Picture' ), function() {
			global $post;

			$order = new WC_Order($post->ID);

			$picture_id = $order->get_meta( 'user_profile_picture' );

			if ( ! isset ( $picture_id ) ) {
				echo __( 'The user had no profile picture set at the checkout.' );
			} else {
				$picture = new \PictureCoupon\User\Picture( $picture_id );

				echo "<img src={$picture->get_source()} />";
			}
		}, 'shop_order', 'side', 'core'
	);
});