<?php

class WPML_Display_Gallery_As_Translated implements IWPML_Action {

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var WPML_Translation_Element_Factory
	 */
	private $wpml_translation_element_factory;
	/**
	 * @var int
	 */
	private $source_post_id;

	/**
	 * WPML_Display_Gallery_As_Translated constructor.
	 *
	 * @param WPML_Translation_Element_Factory $wpml_translation_element_factory
	 */
	public function __construct( wpdb $wpdb, WPML_Translation_Element_Factory $wpml_translation_element_factory ) {
		$this->wpdb = $wpdb;
		$this->wpml_translation_element_factory = $wpml_translation_element_factory;
	}

	public function add_hooks() {
		add_filter( 'do_shortcode_tag', array( $this, 'filter_gallery' ), 10, 3 );
	}

	public function filter_gallery( $output, $tag, $attr ) {

		if ( 'gallery' === $tag && empty( $output ) && empty( $attr['ids'] ) ) {

			$post_id = get_the_ID();
			if ( $post_id ) {

				$post_translation = $this->wpml_translation_element_factory->create( $post_id, 'post' );
				$source_element   = $post_translation->get_source_element();

				if ( null !== $source_element ) {
					$this->source_post_id = $source_element->get_id();

					add_filter( 'shortcode_atts_gallery', array( $this, 'set_attachments_from_source_post' ) );
					$output = gallery_shortcode( $attr );
					remove_filter( 'shortcode_atts_gallery', array( $this, 'set_attachments_from_source_post' ) );
				}

			}


		}

		return $output;
	}

	/**
	 * @param array $out
	 *
	 * @return array mixed
	 */
	public function set_attachments_from_source_post( $out ) {
		$out['include'] = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT ID FROM {$this->wpdb->posts} WHERE post_type='attachment' AND post_parent=%d", $this->source_post_id ) );

		return $out;
	}

}