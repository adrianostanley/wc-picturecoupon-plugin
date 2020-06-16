<?php

namespace PictureCoupon\Integration;

class Setup extends \WC_Integration {

	public function __construct() {
		// global $woocommerce;

		$this->id                 = 'my-plugin-integration';
		$this->method_title       = __( 'WooCommerce Picture Coupon Plugin' );
		$this->method_description = __( 'Allows customers to upload multiple profile pictures and have better discount codes on products' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		// $this->custom_name          = $this->get_option( 'custom_name' );

		// Actions.
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize integration settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'max_profile_images' => array(
				'title'             => __( 'Max profile images' ),
				'type'              => 'text',
				'description'       => __( 'Restricts the maximum number of profile images a user can have' ),
				'desc_tip'          => true,
				'default'           => ''
			),
		);
	}
}