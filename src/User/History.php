<?php

namespace PictureCoupon\User;

class History {

	/** @var string */
	const PROFILE_PICTURES_HISTORY_META_NAME = 'profile_hist';

	/** @var Array */
	private $pictures;

	/** @var int */
	private $user_id;

	private function __construct( $user_id ) {
		$this->pictures = [];
		$this->user_id = $user_id;
	}

	/**
	 * @param Picture $picture
	 */
	public function add( $picture ) {
		if( $picture->is_valid() ) {
			$this->pictures[] = $picture;
		}
	}

	/**
	 * @return Picture|null
	 */
	public function get_current() {
		if ( $this->is_empty() ) {
			return new Picture();
		}

		return end( $this->pictures );
	}

	public function get_html() {
		if ( $this->is_empty() ) {
			return '';
		}

		$html = '';

		/** @var Picture $picture */
		foreach ($this->pictures as $picture) {
			$html .= $picture->get_avatar_html();
		}

		return $html;
	}

	public function is_empty() {
		return empty( $this->pictures );
	}

	public function save() {
		update_user_meta(
			$this->user_id,
			self::PROFILE_PICTURES_HISTORY_META_NAME,
			array_map( function  ( $picture ) { return $picture->get_id(); }, $this->pictures )
		);
	}

	public static function get_user_history( $user_id ) {
		$pictures = get_user_meta( $user_id, self::PROFILE_PICTURES_HISTORY_META_NAME, false );

		$history = new History( $user_id );

		foreach( $pictures[ 0 ] as $picture_id ) {
			$history->add(new Picture( $picture_id ));
		}

		return $history;
	}

}