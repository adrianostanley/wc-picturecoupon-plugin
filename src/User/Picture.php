<?php

namespace PictureCoupon\User;

class Picture {

	/** @var string */
	const PROFILE_PICTURE_META_NAME = 'profile_pic';

	/** @var int */
	private $id;

	public function __construct( $id = 0 ) {
		$this->id = $id;
	}

	public function get_avatar_html() {
		if ( ! $this->is_valid() ) {
			return __( 'You don\'t have a profile image yet' );
		}

		$avatar = wp_get_attachment_url( $this->id );

		return "<img src='{$avatar}' />";
	}

	public function get_id() {
		return $this->id;
	}

	public function is_valid() {
		return $this->id > 0;
	}

	public function save_as_current_profile_picture() {
		update_user_meta( $this->get_user_id(), self::PROFILE_PICTURE_META_NAME, $this->id );
	}

	public static function get_user_current_profile_picture( $user_id ) {
		$picture_id = get_user_meta( $user_id, self::PROFILE_PICTURE_META_NAME, true );

		return new Picture( $picture_id );
	}
}
