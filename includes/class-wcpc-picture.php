<?php

/**
 * This is the representation of a picture, which encapsulates the logic provided by WordPress.
 */
class WCPC_Picture {

	/** @var int the image ID stored in wp_posts table */
	private $id;

	/** @var String the image URL used in src="" attribute of an img tag */
	private $source;

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