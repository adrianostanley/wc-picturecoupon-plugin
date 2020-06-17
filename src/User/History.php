<?php

namespace PictureCoupon\User;

use PictureCoupon\Integration\Setup;

/**
 * This history is the structure behind all the pictures of a user.
 *
 * This class acts as a way to serve all the picture requests the plugin may need in all views and controls.
 *
 * @package PictureCoupon\User
 */
class History {

	/** The meta_key reference for wp_usermeta table */
	const PROFILE_PICTURES_HISTORY_META_NAME = 'wcpc_history';

	private $max_profile_pictures;

	/** @var Array All the pictures of a user */
	private $pictures;

	/** @var int The user ID history owner */
	private $user_id;

	private function __construct( $user_id ) {
		$this->pictures = [];
		$this->user_id = $user_id;
		$this->max_profile_pictures = Setup::get_instance()->get_max_profile_pictures();
	}

	/**
	 * Adds a picture to the history instance only if the picture is a valid one.
	 *
	 * @param Picture $picture
	 */
	public function add( $picture ) {
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
	 * @return Picture
	 */
	public function get_current() {
		if ( $this->is_empty() ) {
			return new Picture();
		}

		return end( $this->pictures );
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

	public function restore( $older_picture_id ) {
		$older_picture = null;

		/**
		 * @var int $key
		 * @var Picture $picture
		 */
		foreach( $this->pictures as $key => $picture ) {
			if ( $older_picture_id == $picture->get_id() ) {
				$older_picture = $picture;
				break;
			}
		}

		if ( isset ( $older_picture ) ) {
			unset( $this->pictures[ $key ] );
			$this->add( $older_picture );
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
	 * @param int $user_id
	 * @return History
	 */
	public static function get_user_history( $user_id ) {
		$pictures = get_user_meta( $user_id, self::PROFILE_PICTURES_HISTORY_META_NAME, false );

		$history = new History( $user_id );

		if ( ! empty ( $pictures ) ) {
			foreach ($pictures[0] as $picture_id) {
				$history->add(new Picture($picture_id));
			}
		}

		return $history;
	}
}