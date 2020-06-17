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
	const PROFILE_PICTURE_PARAM_NAME = 'picture';

	/** @var string */
	const RESTORE_PROFILE_PICTURE_PARAM_NAME = 'restore';

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
				<hr />
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
			return sprintf("<span class='wcpc-block'>%s</span>", __("Ops! Looks like you had hit your max amount of uploaded pictures. To unlock more slots, please, buy a <strong>Power Premium Account</strong> from <span class='wcpc-lt'>$999</span> <strong>$998!</strong>"));
		}

		return sprintf("
			<form enctype='multipart/form-data' action='' method='POST'>
				<input type='file' name='%s[]' multiple /><br><br>
				<input type='submit' value='Upload' />
			</form>
		",self::PROFILE_PICTURE_PARAM_NAME );
	}

	public function get_user_id() {
		return get_current_user_id();
	}

	public function get_user_previous_profile_pictures() {
		if ( ! $this->history->has_older_pictures() ) {
			return '';
		}

		$html = sprintf( "
			<form id='wcpc-replace-form' method='POST'>
				<input type='hidden' id='wcpc-replace-image' name='%s' value='' />		
			<div>", self::RESTORE_PROFILE_PICTURE_PARAM_NAME, self::RESTORE_PROFILE_PICTURE_PARAM_NAME );

		/** @var Picture $picture */
		foreach ($this->history->get_older_pictures() as $picture) {
			$html .= sprintf( "<div class='wcpc-inline' onclick='ProfilePicture.changeAvatarPicture(%s);'>%s</div>", $picture->get_id(), $picture->get_avatar( 64 ) );
		}

		$html .= '</div></form>';

		return sprintf( '<hr /><span>You may also switch to a previous profile image</span>%s', $html );
	}

	public function get_user_profile_picture() {
		$current_picture = $this->history->get_current();

		if( ! $current_picture->is_valid() ) {
			return __( 'You don\'t have a profile image yet' );
		}

		return sprintf("
			<span class='wcpc-block'>Current profile picture</span>
			%s
		", $current_picture->get_avatar( 96 ) );
	}

	/**
	 * Function wc_cus_upload_picture
	 *
	 */
	public function upload_profile_picture( $name, $tmp_name ) {

		$wordpress_upload_dir = wp_upload_dir();

		$i = 1; // number of tries when the file with the same name is already exists

		// $profilepicture = $foto;
		$new_file_path = $wordpress_upload_dir['path'] . '/' . $name;
		$new_file_mime = mime_content_type( $tmp_name );

		/*$log = new WC_Logger();

		if( empty( $profilepicture ) )
			$log->add('custom_profile_picture','File is not selected.');

		if( $profilepicture['error'] )
			$log->add('custom_profile_picture',$profilepicture['error']);


		if( $profilepicture['size'] > wp_max_upload_size() )
			$log->add('custom_profile_picture','It is too large than expected.');


		if( !in_array( $new_file_mime, get_allowed_mime_types() ))
			$log->add('custom_profile_picture','WordPress doesn\'t allow this type of uploads.' );*/

		while( file_exists( $new_file_path ) ) {
			$i++;
			$new_file_path = $wordpress_upload_dir['path'] . '/' . $i . '_' . $name;
		}

		// looks like everything is OK
		if( move_uploaded_file( $tmp_name, $new_file_path ) ) {

			$upload_id = wp_insert_attachment( array(
				'guid'           => $new_file_path,
				'post_mime_type' => $new_file_mime,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $name ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			), $new_file_path );

			// wp_generate_attachment_metadata() won't work if you do not include this file
			require_once( ABSPATH.'/wp-admin/includes/image.php' );

			// Generate and save the attachment metas into the database
			wp_update_attachment_metadata( $upload_id, wp_generate_attachment_metadata( $upload_id, $new_file_path ) );
			return $upload_id;
		}
	}

	public function update_profile_picture() {
		$this->load_user_profile_picture_history();

		if( isset( $_FILES[ self::PROFILE_PICTURE_PARAM_NAME ] ) ) {
			echo '<pre>' . var_export($_FILES[ self::PROFILE_PICTURE_PARAM_NAME ], true) . '</pre>';

			for ( $i = 0; $i < count($_FILES[ self::PROFILE_PICTURE_PARAM_NAME ]['name']); $i++) {
				$picture_id = $this->upload_profile_picture($_FILES[ self::PROFILE_PICTURE_PARAM_NAME ]['name'][$i], $_FILES[ self::PROFILE_PICTURE_PARAM_NAME ]['tmp_name'][$i]);
				$this->history->add(new Picture($picture_id));
			}

			$this->history->save();

			$this->load_user_profile_picture_history( true );
		}

		if ( isset ( $_POST[ self::RESTORE_PROFILE_PICTURE_PARAM_NAME ] ) ) {
			$this->history->restore( $_POST[ self::RESTORE_PROFILE_PICTURE_PARAM_NAME ] );
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