<?php

namespace PictureCoupon\Controls;

use PictureCoupon\User\History;
use PictureCoupon\User\Picture;

/**
 * This is the controller class to serve as the uploader for the user's account details page.
 *
 * @package PictureCoupon\Controls
 */
class Uploader {

	/** @var string */
	const PROFILE_PICTURE_PARAM_NAME = 'profile_pic';

	/** @var History */
	private $history;

	public function __construct() {
		$this->load_user_profile_picture_history();
	}

	public function get_html() {
		return sprintf('
			<fieldset>
				<legend>%s</legend>
				%s
				%s
				%s
			</fieldset>',
			__( 'Change your profile picture' ),
			$this->get_user_profile_picture(),
			$this->get_user_previous_profile_pictures(),
			$this->get_upload_form()
		);
	}

	public function get_upload_form() {
		if ( $this->history->is_full() ) {
			return sprintf("<span class='wcpc-block'>%s</span>", __("Ops! Looks like you had hit your max amount of uploaded pictures. To unlock more pictures, please, buy a <strong>Power Premium Account</strong> from <span class='wcpc-lt'>$999</span> <strong>$998!</strong>"));
		}

		return "
			<form enctype='multipart/form-data' action='' method='POST'>
				<input type='hidden' name='MAX_FILE_SIZE' value='250000' />
				<input name='{self::PROFILE_PICTURE_PARAM_NAME}' type='file' size='25' /><br><br>
				<input type='submit' value='Upload' />
			</form>
		";
	}

	public function get_user_id() {
		return get_current_user_id();
	}

	public function get_user_previous_profile_pictures() {
		if ( ! $this->history->has_older_pictures() ) {
			return '';
		}

		$html = '<select>';

		/** @var Picture $picture */
		foreach ($this->history->get_older_pictures() as $picture) {
			$html .= sprintf('<option style="background-image:url(\'%s\');">%s</option>', $picture->get_source(), $picture->get_id() );
		}

		$html .= '</select>';

		return sprintf( '<span>You may also switch to a previous profile image</span>%s', $html );
	}

	public function get_user_profile_picture() {
		$current_picture = $this->history->get_current();

		if( ! $current_picture->is_valid() ) {
			return __( 'You don\'t have a profile image yet' );
		}

		return "
			<span class='wcpc-block'>Current profile picture</span>
			<img class='wcpc-avatar' src='{$this->history->get_current()->get_source()}' />
		";
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