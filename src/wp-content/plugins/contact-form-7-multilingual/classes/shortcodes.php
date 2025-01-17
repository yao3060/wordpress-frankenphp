<?php

namespace WPML\CF7;

use WPML\FP\Obj;

class Shortcodes implements \IWPML_Frontend_Action {

	/**
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'shortcode_atts_wpcf7', [ $this, 'translate_shortcode_form_id' ] );
	}

	/**
	 * Find the right form and return it in the current language.
	 *
	 * @param array $atts Shortcode attributes to be filtered.
	 *
	 * @return array
	 */
	public function translate_shortcode_form_id( $atts ) {
		$form         = null;
		$formIdOrHash = Obj::prop('id', $atts );;
		$formTitle    = Obj::prop('title', $atts );

		if ( $formIdOrHash && function_exists( 'wpcf7_get_contact_form_by_hash' ) ) {
			$form = wpcf7_get_contact_form_by_hash( trim( $formIdOrHash ) );
		}

		if ( ! $form && $formIdOrHash ) {
			$form = wpcf7_contact_form( (int) $formIdOrHash );
		}

		if ( ! $form && $formTitle ) {
			$form = wpcf7_get_contact_form_by_title( trim( $atts['title'] ) );
		}

		if ( $form ) {
			$atts['id']    = apply_filters( 'wpml_object_id', $form->id(), Constants::POST_TYPE, true );
			$atts['title'] = ''; // Reset the title attribute only if we managed to set the id attribute.
		}

		return $atts;
	}
}
