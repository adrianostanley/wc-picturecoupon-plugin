<?php

/**
 * This history is the structure behind all the pictures of a user.
 *
 * This class acts as a way to serve all the picture requests the plugin may need in all views and controls.
 */
class WCPC_History {

	/** The meta_key reference for wp_usermeta table */
	const PROFILE_PICTURES_HISTORY_META_NAME = 'wcpc_history';

	/** @var int */
	private $max_profile_pictures;

	/** @var Array All the pictures of a user */
	private $pictures;

	/** @var int The user ID history owner */
	private $user_id;

	/** @var WP_User */
	private $user;

	private function __construct( $user_id ) {

		$this->pictures = [];
		$this->user_id = $user_id;
		$this->max_profile_pictures = WCPC_Setup::get_instance()->get_max_profile_pictures();
	}

	/**
	 * Adds a picture to the history instance only if the picture is a valid one.
	 *
	 * @param WCPC_Picture $picture
	 * @param bool $full_check performs (or not) a check to avoid adding more pictures than the history limit.
	 */
	public function add( $picture, $full_check = true ) {

		if ( $full_check && $this->is_full() ) {

			return;
		}

		if( $picture->is_valid() ) {

			$this->pictures[] = $picture;
		}
	}

	/**
	 * @return Array returns all profiles pictures for the user.
	 */
	public function get_all() {

		return $this->pictures;
	}

	/**
	 * This method returns the current user profile picture.
	 *
	 * It may also return an invalid picture if the history is empty.
	 *
	 * @return WCPC_Picture
	 */
	public function get_current() {

		if ( $this->is_empty() ) {

			return new WCPC_Picture();
		}

		return end( $this->pictures );
	}

	/**
	 * Builds an array data structure to the user's history which is easily converted to a JSON object.
	 */
	public function get_data() {

		$user = $this->get_user();

		return [

			'count' => count( $this->pictures ),
			'user' => [

				'id' => $user->ID,
				'display_name' => $user->display_name,
				'username' => $user->user_login
			],
			'pictures' => array_map(

				function( $picture ) {

					/** @var WCPC_Picture $picture */
					return $picture->get_data();
				},
				$this->pictures
			)
		];
	}

	/**
	 * @return bool|WP_User the user WordPress instance for this history.
	 */
	public function get_user() {

		if( ! isset( $this->user ) ) {

			$this->user = get_user_by( 'ID', $this->user_id );
		}

		return $this->user;
	}

	/**
	 * Returns all the history pictures except for the current user profile picture.
	 *
	 * @return Array
	 */
	public function get_older_pictures() {

		if ( ! $this->has_older_pictures() ) {

			return [];
		}

		return array_slice( $this->pictures, 0, count( $this->pictures ) - 1);
	}

	/**
	 * @return bool true if the user has an avatar.
	 */
	public function has_profile_picture() {

		return ! $this->is_empty();
	}

	/**
	 * @return bool true if the user history contains older profile pictures.
	 */
	public function has_older_pictures() {

		return count( $this->pictures ) > 1;
	}

	/**
	 * @return bool true if the history has no pictures.
	 */
	public function is_empty() {

		return empty( $this->pictures );
	}

	/**
	 * @return bool true if the history had already hit the max number of profiles pictures a user can submit.
	 */
	public function is_full() {

		return count($this->pictures) >= $this->max_profile_pictures;
	}

	/**
	 * Removes the picture ID from the history.
	 *
	 * @param $older_picture_id
	 */
	public function remove( $older_picture_id ) {

		/**
		 * @var int $key
		 * @var WCPC_Picture $picture
		 */
		foreach( $this->pictures as $key => $picture ) {

			if ( $older_picture_id == $picture->get_id() ) {

				unset( $this->pictures[ $key ] );

				break;
			}
		}
	}

	/**
	 * Moves the picture ID to the end of the history, which makes it the user's current picture.
	 *
	 * @param $older_picture_id
	 */
	public function restore( $older_picture_id ) {

		$older_picture = null;

		/**
		 * @var int $key
		 * @var WCPC_Picture $picture
		 */
		foreach( $this->pictures as $key => $picture ) {

			if ( $older_picture_id == $picture->get_id() ) {

				$older_picture = $picture;

				break;
			}
		}

		if ( isset ( $older_picture ) ) {

			unset( $this->pictures[ $key ] );

			$this->add( $older_picture, false );
		}
	}

	/**
	 * Stores the history in the WordPress database.
	 *
	 * This method must be called to update the user profile pictures.
	 */
	public function save() {

		update_user_meta(

			$this->user_id,
			self::PROFILE_PICTURES_HISTORY_META_NAME,
			array_map( function  ( $picture ) { return $picture->get_id(); }, $this->pictures )
		);
	}

	/**
	 * This method is a way to create a new instance of a History, loading all the profile pictures of a user from the
	 * database.
	 *
	 * If the user ID is not informed, the function gets the current user ID.
	 *
	 * @param int|null $user_id
	 * @return WCPC_History
	 */
	public static function get_user_history( $user_id = null ) {

		$user_id = $user_id ?? get_current_user_id();

		$pictures = get_user_meta( $user_id, self::PROFILE_PICTURES_HISTORY_META_NAME, false );

		$history = new self( $user_id );

		if ( ! empty ( $pictures ) ) {

			foreach ( $pictures[0] as $picture_id ) {

				$history->add( new WCPC_Picture( $picture_id ), false );
			}
		}

		return $history;
	}

	/**
	 * This is an "overload" for get_user_history to be used when the caller has already a WP_User instance loaded.
	 *
	 * A History can have a WP_User stored - although it's not necessary to load its pictures. But whenever the user
	 * information is needed, it can request it to WordPress API. By using get_user_history_by_user and passing a valid
	 * WP_User instance, you may save this request.
	 *
	 * @param WP_User $user
	 * @return WCPC_History
	 */
	public static function get_user_history_by_user( $user ) {

		$history = self::get_user_history( $user->ID );

		$history->user = $user;

		return $history;
	}
}