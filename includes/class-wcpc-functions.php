<?php

/**
 * Concentrates all the core functions of Picture Coupon plugin.
 */
class WCPC_Functions {

	/** The order meta key to store the user's current profile picture at the checkout */
	const USER_PROFILE_PICTURE_ORDER_META_KEY = 'wcpc_userpp';

	/**
	 * Adds content to any WooCommerce store page like cart and checkout to let users know they will have better
	 * discounts if they set up a profile picture.
	 */
	public function add_profile_picture_widget() {

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
	}

	/**
	 * Includes a metabox in the order details so admins can see which profile picture the user was using during the
	 * order.
	 */
	public function add_meta_boxes() {

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
	}

	/**
	 * Adds an uploader instance to the user account details page and allow him/her to upload multiple profile pictures.
	 */
	public function add_uploader() {

		$picture = new WCPC_Uploader();

		$picture->update_history();

		echo $picture->get_html();
	}

	/**
	 * Allows admins to see all available customer profile images on the customer's user profile page.
	 *
	 * @param WP_User $user
	 */
	public function edit_user_profile( $user ) {

		$history = WCPC_History::get_user_history( $user->ID );

		$profile_pictures = $history->get_all();

		$html = '';

		/** @var WCPC_Picture $picture */
		foreach ( $profile_pictures as $picture ) {

			$html .= sprintf( '
					<span style="display: block; float: left; text-align: center; width: 7%%;">%s<br />
						<a href="%s" target="_blank">Edit</a>
					</span>
				',
				$picture->get_avatar( 96 ),
				get_edit_post_link( $picture->get_id() ),
				$picture->get_file_name()
			);
		}

		$html .= '<div style="clear:both;"></div>';

		echo sprintf("<h2>%s</h2>%s",
			__("Profile pictures"),
			$html
		);
	}

	/**
	 * Enqueues both CSS and JavaScript file.
	 */
	public function enqueue_assets() {

		wp_enqueue_script( 'wc-picturecoupon', '/wp-content/plugins/wc-picturecoupon-plugin/assets/js/frontend/wc-picturecoupon-frontend.js' );

		wp_enqueue_style( 'wc-picturecoupon', '/wp-content/plugins/wc-picturecoupon-plugin/assets/css/frontend/wc-picturecoupon-frontend.css' );
	}

	/**
	 * This filter will replace user's profile picture only if the user has a history. Otherwise, the default WordPress
	 * avatars (from Gravatar) will be used.
	 *
	 * @param $avatar
	 * @param $user_ref
	 * @param $size
	 * @return string
	 */
	public function get_avatar( $avatar, $user_ref, $size ) {

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
	}

	/**
	 * Stores the current user's profile picture in the order meta data.
	 *
	 * @param WC_Order $order
	 */
	public function store_picture_at_order( $order ) {

		$history = WCPC_History::get_user_history( $order->get_user_id() );

		$current_profile_picture = $history->get_current();

		if ( $current_profile_picture->is_valid() ) {

			$order->update_meta_data( self::USER_PROFILE_PICTURE_ORDER_META_KEY, $current_profile_picture->get_id() );
		}
	}
}

