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
 * Function wc_cus_upload_picture
 *
 */
function wc_cus_upload_picture( $foto ) {

	$wordpress_upload_dir = wp_upload_dir();
	// $wordpress_upload_dir['path'] is the full server path to wp-content/uploads/2017/05, for multisite works good as well
	// $wordpress_upload_dir['url'] the absolute URL to the same folder, actually we do not need it, just to show the link to file
	$i = 1; // number of tries when the file with the same name is already exists

	$profilepicture = $foto;
	$new_file_path = $wordpress_upload_dir['path'] . '/' . $profilepicture['name'];
	$new_file_mime = mime_content_type( $profilepicture['tmp_name'] );

	$log = new WC_Logger();

	if( empty( $profilepicture ) )
		$log->add('custom_profile_picture','File is not selected.');

	if( $profilepicture['error'] )
		$log->add('custom_profile_picture',$profilepicture['error']);


	if( $profilepicture['size'] > wp_max_upload_size() )
		$log->add('custom_profile_picture','It is too large than expected.');


	if( !in_array( $new_file_mime, get_allowed_mime_types() ))
		$log->add('custom_profile_picture','WordPress doesn\'t allow this type of uploads.' );

	while( file_exists( $new_file_path ) ) {
		$i++;
		$new_file_path = $wordpress_upload_dir['path'] . '/' . $i . '_' . $profilepicture['name'];
	}

	// looks like everything is OK
	if( move_uploaded_file( $profilepicture['tmp_name'], $new_file_path ) ) {


		$upload_id = wp_insert_attachment( array(
			'guid'           => $new_file_path,
			'post_mime_type' => $new_file_mime,
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $profilepicture['name'] ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		), $new_file_path );

		// wp_generate_attachment_metadata() won't work if you do not include this file
		require_once( ABSPATH.'/wp-admin/includes/image.php' );

		// Generate and save the attachment metas into the database
		wp_update_attachment_metadata( $upload_id, wp_generate_attachment_metadata( $upload_id, $new_file_path ) );
		return $upload_id;
	}
}


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
		$picture_id = get_user_meta($user->data->ID,'profile_pic');
		if(! empty($picture_id)){
			$avatar = wp_get_attachment_url( $picture_id[0] );
			$avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
		}
	}
	return $avatar;
}





add_action('get_footer', function() {
	// .css
	wp_enqueue_style('wc-picturecoupon', '/wp-content/plugins/wc-picturecoupon-plugin/assets/css/frontend/wc-picturecoupon-frontend.css');
}, 1);