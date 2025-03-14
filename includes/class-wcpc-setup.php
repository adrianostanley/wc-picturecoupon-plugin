<?php

/**
 * WCPC_Setup is a singleton class that does the integration with WooCommerce and keeps the plugins settings to be used
 * by other classes.
 */
class WCPC_Setup extends \WC_Integration {

	const MAX_PROFILES_IMAGE_SETTING_DEFAULT = 10;

	/** @var WCPC_Setup */
	private static $instance;

	public function __construct() {

		if ( ! isset( self::$instance ) ) {

			$this->id = 'my-plugin-integration';
			$this->method_title = __('WooCommerce Picture Coupon Plugin');
			$this->method_description = __('Allows customers to upload multiple profile pictures and have better discount codes on products');

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			add_action('woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options'));

			self::$instance = $this;
		}
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
				'default'           => self::MAX_PROFILES_IMAGE_SETTING_DEFAULT
			),
		);
	}

	/**
	 * Returns the max numbers of profile pictures a user can upload.
	 *
	 * @return int
	 */
	public function get_max_profile_pictures() {

		$max_profile_images = $this->settings["max_profile_images"];

		return ! empty ( $max_profile_images ) && is_numeric( $max_profile_images ) && intval( $max_profile_images ) > 0 ? intval( $max_profile_images ) : self::MAX_PROFILES_IMAGE_SETTING_DEFAULT;
	}

	/**
	 * @return WCPC_Setup the only WCPC_Setup instance.
	 */
	public static function get_instance() {

		return self::$instance;
	}
}