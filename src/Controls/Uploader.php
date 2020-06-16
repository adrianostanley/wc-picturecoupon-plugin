<?php

namespace PictureCoupon\Controls;

use PictureCoupon\User\History;
use PictureCoupon\User\Picture;

class Uploader {

	/** @var string */
	const PROFILE_PICTURE_PARAM_NAME = 'profile_pic';

	/** @var History */
	private $history;

	public function __construct() {
		$this->load_user_profile_picture_history();
	}

	public function get_upload_form() {
		return sprintf('
			<fieldset>
				<legend>%s</legend>
				%s
				<form enctype="multipart/form-data" action="" method="POST">
					<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
					<input name="%s" type="file" size="25" /><br><br>
					<input type="submit" value="Upload" />
				</form>
			</fieldset>',
			__( 'Change your profile picture' ),
			$this->get_user_profile_picture(),
			self::PROFILE_PICTURE_PARAM_NAME
		);
	}

	public function get_user_id() {
		return get_current_user_id();
	}

	public function get_user_profile_picture() {
		return $this->history->get_current()->get_avatar_html();
	}

	public function update_profile_picture() {
		$this->load_user_profile_picture_history();

		if( isset( $_FILES[self::PROFILE_PICTURE_PARAM_NAME] ) ) {
			$picture_id = wc_cus_upload_picture( $_FILES[self::PROFILE_PICTURE_PARAM_NAME] );

			$this->history->add(new Picture( $picture_id ));
			$this->history->save();

			$this->load_user_profile_picture_history( true );
		}
	}

	private function load_user_profile_picture_history($reload = false) {
		if ( $reload || ! isset( $this->history ) ) {
			$this->history = History::get_user_history( $this->get_user_id() );
		}
	}
}