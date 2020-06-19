<?php

/**
 * Class WCPC_Rest_API_Controller
 */
class WCPC_Rest_API_Controller {

	/**
	 * Register both Picture Coupon REST API endpoints.
	 */
	public function register_routes() {

		$namespace = 'picture-coupon/v1';

		register_rest_route( $namespace, "/profile-pictures/all", array (

			'methods' => 'GET',
			'callback' => array( $this, 'list_all_users_histories' ),
			'permission_callback' => array( $this, 'is_allowed' )
		));

		register_rest_route( $namespace, "/profile-pictures/(?P<user_id>\d+)", array (

			'methods' => 'GET',
			'callback' => array( $this, 'get_user_history' ),
			'args' => array(

				'user_id' => array(

					'validate_callback' => function( $param ) {

						return is_numeric( $param ) && intval( $param ) > 0;
					}
				),
			),
			'permission_callback' => array( $this, 'is_allowed' )
		));
	}

	/**
	 * Returns a WCPC_History prepared data to be used in a REST API request for a single user.
	 *
	 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 *
	 * @param WP_REST_Request $request
	 * @return string
	 */
	function get_user_history( $request ) {

		$user = get_user_by( 'ID', $request [ 'user_id' ] );

		if ( ! $user ) {

			return new WP_Error( 'invalid user', sprintf( 'User with id %s was not found', $request[ 'user_id' ] ) );
		}

		$history = WCPC_History::get_user_history_by_user( $user );

		if ( $history->is_empty() ) {

			return [];
		}

		return $history->get_data();
	}

	/**
	 * @return bool true if the user is authenticated and has permissions to access the REST API endpoints.
	 */
	function is_allowed() {

		return true; // current_user_can( 'edit_others_posts' );
	}

	/**
	 * Returns a list of WCPC_History prepared data to be used in a REST API request for all users.
	 *
	 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 *
	 * @return array
	 */
	function list_all_users_histories() {

		$users = get_users();

		$histories = [];

		foreach( $users as $user ) {

			$histories[] = WCPC_History::get_user_history_by_user( $user )->get_data();
		}

		return $histories;
	}
}