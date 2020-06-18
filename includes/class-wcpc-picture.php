<?php

/**
 * This is the representation of a picture, which encapsulates the logic provided by WordPress.
 */
class WCPC_Picture {

	/** @var int the image ID stored in wp_posts table */
	private $id;

	/** @var String the image URL used in src="" attribute of an img tag */
	private $source;

	/** @var String */
	private $file_name;

	/** @var String */
	private $file_type;

	public function __construct( $id = 0 ) {

		$this->id = $id;
	}

	/**
	 * @param int $size
	 * @return string an HTML representation for this picture in an avatar model.
	 */
	public function get_avatar( $size = 32 ) {

		return sprintf( "<img alt='avatar' src='%s' class='avatar avatar-%s photo' height='%s' width='%s' />",
			$this->get_source(),
			$size,
			$size,
			$size
		);
	}

	/**
	 * @return string the file name (not the absolute path).
	 */
	public function get_file_name() {

		if ( ! isset( $this->file_name ) ) {

			$this->file_name = basename( get_attached_file( $this->id ) );
		}

		return $this->file_name;
	}

	/**
	 * @return string the file extension.
	 */
	public function get_file_type() {

		if ( ! isset( $this->file_type ) ) {

			$path_info = pathinfo( $this->get_file_name() );

			$this->file_type = $path_info[ 'extension' ];
		}

		return $this->file_type;
	}

	/**
	 * Builds an array data structure to the user's history which is easily converted to a JSON object.
	 */
	public function get_data() {

		return [

			'name' => $this->get_file_name(),
			'public_url' => $this->get_source(),
			'type' => $this->get_file_type()
		];
	}

	/**
	 * @return int the picture ID.
	 */
	public function get_id() {

		return $this->id;
	}

	/**
	 * This method works with a cache to void multiple calls to wp_get_attachment_url.
	 *
	 * @return string the picture URL.
	 */
	public function get_source() {

		if ( ! isset( $this->source ) ) {

			$this->source = wp_get_attachment_url( $this->id );
		}

		return $this->source;
	}

	/**
	 * Returns true when the picture is valid.
	 *
	 * In other words, this method checks if the picture has an ID, which is stored in the WordPress posts table.
	 *
	 * @return bool
	 */
	public function is_valid() {

		return $this->id > 0;
	}
}