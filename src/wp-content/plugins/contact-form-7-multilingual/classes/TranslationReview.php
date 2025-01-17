<?php

namespace WPML\CF7;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslationReview implements \IWPML_Frontend_Action {

	/**
	 * @return void
	 */
	public function add_hooks() {
		Hooks::onAction( 'wpml_tm_handle_translation_review', 10, 2 )
			->then( spreadArgs( [ $this, 'handleTranslationReview' ] ) );
	}

	/**
	 * @param int             $jobId
	 * @param object|\WP_Post $post
	 */
	public function handleTranslationReview( $jobId, $post ) {
		if ( Constants::POST_TYPE === Obj::prop( 'post_type', $post ) ) {
			Hooks::onFilter( 'template_include' )
				->then( $this->previewFormTranslation( $post ) );
		}
	}

	/**
	 * @param \WP_Post $post
	 *
	 * @return \Closure( void ) : null
	 */
	public function previewFormTranslation( $post ) {
		return function() use ( $post ) {
			get_header();
			echo do_shortcode( '[contact-form-7 id="' . $post->ID . '"]' );
			get_footer();
			return null;
		};
	}
}
