<?php

namespace WPML\CF7;

use WPML\FP\Obj;

class Locale implements \IWPML_Frontend_Action {

	/**
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'get_post_metadata', [ $this, 'getFormLocale' ], 10, 3 );
	}

	/**
	 * Set the form locale to the current language when displaying on frontend.
	 *
	 * @param mixed  $value
	 * @param int    $post_id
	 * @param string $meta_key
	 *
	 * @return mixed
	 */
	public function getFormLocale( $value, $post_id, $meta_key ) {
		if ( '_locale' === $meta_key
			&& Constants::POST_TYPE === get_post_type( $post_id ) ) {
			return Obj::propOr(
				$value,
				'locale',
				apply_filters( 'wpml_post_language_details', [], $post_id )
			);
		}
		return $value;
	}
}
