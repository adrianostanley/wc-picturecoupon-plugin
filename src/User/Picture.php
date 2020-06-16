<?php

namespace PictureCoupon\User;

class Picture {

	/** @var string */
	const PROFILE_PICTURES_HISTORY_META_NAME = 'profile_hist';

	/** @var string */
	const PROFILE_PICTURE_PARAM_NAME = 'profile_pic';

	/** @var string */
	const PROFILE_PICTURE_META_NAME = 'profile_pic';

	/** @var Array */
	private $profile_picture_id;

	public function getUploadForm() {
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
			$this->getUserProfilePicture(),
			self::PROFILE_PICTURE_PARAM_NAME
		);
	}

	public function getUserId() {
		return get_current_user_id();
	}

	public function getUserProfilePicture() {
		$this->load_user_profile_picture();

		if( ! empty( $this->profile_picture_id ) ){
			$avatar = wp_get_attachment_url( $this->profile_picture_id[0] );

			return "<img src='{$avatar}' />";
		}
		return __( 'You don\'t have a profile image yet' );
	}

	public function updateProfilePicture() {
		$this->load_user_profile_picture();

		if( isset( $_FILES[self::PROFILE_PICTURE_PARAM_NAME] ) ) {
			$picture_id = wc_cus_upload_picture( $_FILES[self::PROFILE_PICTURE_PARAM_NAME] );

			$this->archive_current_profile_picture();

			// wc_cus_save_profile_pic($picture_id, $this->getUserId());
			update_user_meta( $this->getUserId(), self::PROFILE_PICTURE_META_NAME, $picture_id );

			$this->load_user_profile_picture( true );
		}



		var_dump(get_user_meta( $this->getUserId(), self::PROFILE_PICTURES_HISTORY_META_NAME ));

	}

	private function archive_current_profile_picture() {
		if ( empty($history = get_user_meta( $this->getUserId(), self::PROFILE_PICTURES_HISTORY_META_NAME ) ) ) {
			$history = [];
		}

		$history[] = $this->profile_picture_id;

		update_user_meta( $this->getUserId(), self::PROFILE_PICTURES_HISTORY_META_NAME, $history );
	}

	private function load_user_profile_picture($reload = false) {
		if ( ! $reload && ! isset( $this->profile_picture_id ) ) {
			$this->profile_picture_id = get_user_meta($this->getUserId(), self::PROFILE_PICTURE_META_NAME);
		}
	}
}
