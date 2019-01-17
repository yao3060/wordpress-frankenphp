<?php

class WPML_Display_Featured_Image_As_Translated implements IWPML_Action {

	/**
	 * @var WPML_Translation_Element_Factory
	 */
	private $translation_element_factory;

	public function __construct( WPML_Translation_Element_Factory $translation_element_factory ) {
		$this->translation_element_factory = $translation_element_factory;
	}

	public function add_hooks() {
		add_filter( 'get_post_metadata', array( $this, 'localize_image_id' ), 10, 3 );
		add_action( 'wpml_pro_translation_completed', array( $this, 'initialize_for_new_translation' ), 10, 1 );
	}

	public function localize_image_id( $value, $object_id, $meta_key ) {

		if ( '_thumbnail_id' === $meta_key && get_post_meta( $object_id, WPML_Admin_Post_Actions::DISPLAY_FEATURED_IMAGE_AS_TRANSLATED_META_KEY, true ) ) {

			remove_filter( 'get_post_metadata', array( $this, 'localize_image_id' ), 10, 3 );
			$meta_value = get_post_meta( $object_id, '_thumbnail_id', true );
			if ( empty( $meta_value ) ) {
				$post_element   = $this->translation_element_factory->create( $object_id, 'post' );
				$source_element = $post_element->get_source_element();
				if ( null !== $source_element ) {
					$value = get_post_meta( $source_element->get_id(), '_thumbnail_id', true );
				}

			}
			add_filter( 'get_post_metadata', array( $this, 'localize_image_id' ), 10, 3 );

		}

		return $value;
	}

	public function initialize_for_new_translation( $post_id ){
		if( '' === get_post_meta( $post_id, WPML_Admin_Post_Actions::DISPLAY_FEATURED_IMAGE_AS_TRANSLATED_META_KEY, true ) ){
			$post_element   = $this->translation_element_factory->create( $post_id, 'post' );
			$source_element = $post_element->get_source_element();
			if ( null !== $source_element ) {
				$value = get_post_meta( $source_element->get_id(), WPML_Admin_Post_Actions::DISPLAY_FEATURED_IMAGE_AS_TRANSLATED_META_KEY, true );
				update_post_meta( $post_id, WPML_Admin_Post_Actions::DISPLAY_FEATURED_IMAGE_AS_TRANSLATED_META_KEY, $value );
			}
		}
	}

}