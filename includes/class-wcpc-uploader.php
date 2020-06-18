<?php

/**
 * This is the controller class to serve as the uploader for the user's account details page.
 */
class WCPC_Uploader {

	/** @var string */
	const PROFILE_PICTURE_PARAM_NAME = 'picture';

	/** @var string */
	const REMOVE_PROFILE_PICTURE_PARAM_NAME = 'remove';

	/** @var string */
	const RESTORE_PROFILE_PICTURE_PARAM_NAME = 'restore';

	/** @var WCPC_History */
	private $history;

	/** @var int */
	private $user_id;

	public function __construct() {

		$this->load_history();
	}

	/**
	 * Renders the uploader HTML.
	 *
	 * @return string
	 */
	public function get_html() {

		return sprintf('
			<fieldset id="wcpc-uploader">
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

	/**
	 * Builds the upload form, which may be available only if thu user didn't uploaded the limit defined by the admin in
	 * the plugin settings.
	 *
	 * @return string
	 */
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

	/**
	 * This function works with an internal cache to prevent multiple calls to the WordPress API.
	 *
	 * @return int the current logged user id.
	 */
	public function get_user_id() {

		if( ! isset ( $this->user_id ) ) {

			$this->user_id = get_current_user_id();
		}

		return $this->user_id;
	}

	/**
	 * Builds the HTML for the user to select one of his/her previous uploaded profile pictures.
	 *
	 * This component also offers the options to restore and remove a picture.
	 *
	 * @return string
	 */
	public function get_user_previous_profile_pictures() {

		if ( ! $this->history->has_older_pictures() ) {

			return '';
		}

		$html = sprintf( "
			<form id='wcpc-options-form' method='POST'>
				<input type='hidden' id='wcpc-replace-image' name='%s' value='' />
				<input type='hidden' id='wcpc-remove-image' name='%s' value='' />
				<div>",
				self::RESTORE_PROFILE_PICTURE_PARAM_NAME,
				self::REMOVE_PROFILE_PICTURE_PARAM_NAME
		);

		$html .= '<table>';

		/** @var WCPC_Picture $picture */
		foreach ($this->history->get_older_pictures() as $picture) {

			$html .= sprintf( "
				<tr>
					<td>%s</td>
					<td>%s</td>
					<td>
						<a href='#' onclick='_wcpc.changeAvatarPicture(%s);'>Use this</a>&nbsp;|&nbsp;
						<a href='#' onclick='_wcpc.removeAvatarPicture(%s);'>Remove</a>
					</td>
				</tr>",
				$picture->get_avatar( 48 ),
				$picture->get_file_name(),
				$picture->get_id(),
				$picture->get_id()
			);
		}

		$html .= '</table></div></form>';

		return sprintf( '<hr /><span>You may also switch to a previous profile image</span>%s', $html );
	}

	/**
	 * Builds the HTML to show the user's current profile picture.
	 *
	 * @return string|void
	 */
	public function get_user_profile_picture() {

		$current_picture = $this->history->get_current();

		if( ! $current_picture->is_valid() ) {

			return __( 'You don\'t have a profile image yet' );
		}

		return sprintf("
			<span class='wcpc-block'>Current profile picture</span>
			%s",
			$current_picture->get_avatar( 96 )
		);
	}

	/**
	 * This function calls WCPC_History functions to add, restore or remove pictures.
	 */
	public function update_history() {

		$upload = isset( $_FILES[ self::PROFILE_PICTURE_PARAM_NAME ] );
		$restore = isset ( $_POST[ self::RESTORE_PROFILE_PICTURE_PARAM_NAME ] );
		$remove = isset ( $_POST[ self::REMOVE_PROFILE_PICTURE_PARAM_NAME ] );

		if( $upload ) {

			for ( $i = 0; $i < count($_FILES[ self::PROFILE_PICTURE_PARAM_NAME ]['name']); $i++) {

				$picture_id = $this->upload_profile_picture($_FILES[ self::PROFILE_PICTURE_PARAM_NAME ]['name'][$i], $_FILES[ self::PROFILE_PICTURE_PARAM_NAME ]['tmp_name'][$i]);

				$this->history->add(new WCPC_Picture($picture_id), true );
			}
		}

		if ( $restore ) {

			$this->history->restore( $_POST[ self::RESTORE_PROFILE_PICTURE_PARAM_NAME ] );
		}

		if ( $remove ) {

			$this->history->remove( $_POST[ self::REMOVE_PROFILE_PICTURE_PARAM_NAME ] );
		}

		$this->history->save();

		$this->load_history( true );
	}

	/**
	 * Stores the uploaded file, preventing duplicated names and calling the WordPress proper attachment API methods.
	 *
	 * @param string $name
	 * @param string $tmp_name
	 * @return int|WP_Error
	 */
	public function upload_profile_picture( $name, $tmp_name ) {

		$wp_upload_path = wp_upload_dir()[ 'path' ];

		// Used to prevent duplicated files
		$file_counter = 1;

		$new_file_path = $wp_upload_path . '/' . $name;
		$new_file_mime = mime_content_type( $tmp_name );

		while( file_exists( $new_file_path ) ) {

			$new_file_path = sprintf( '%s/%s_%s', $wp_upload_path, $name, $file_counter++ );
		}

		if( move_uploaded_file( $tmp_name, $new_file_path ) ) {

			$upload_id = wp_insert_attachment( array(

				'guid'           => $new_file_path,
				'post_mime_type' => $new_file_mime,
				'post_title'     => sanitize_title( $name ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			), $new_file_path );

			require_once( ABSPATH . '/wp-admin/includes/image.php' );

			wp_update_attachment_metadata( $upload_id, wp_generate_attachment_metadata( $upload_id, $new_file_path ) );

			return $upload_id;
		}
	}

	/**
	 * Brings the user profile picture history to this controller, in order to manipulate its contents to add, restore
	 * or remove pictures.
	 */
	private function load_history() {

		$this->history = WCPC_History::get_user_history( $this->get_user_id() );
	}
}