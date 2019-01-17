<?php

class WPML_Upgrade_Media_Settings_To_Core implements IWPML_Upgrade_Command {

	const OLD_MEDIA_OPTION = '_wpml_media';

	const GLOBAL_DISPLAY_FEATURED_IMAGE_TRANSLATED = 'display_featured_image_as_translated';

	/** @var SitePress */
	private $sitepress;

	/** @var WPML_Upgrade_Schema */
	private $upgrade_db;

	public function __construct( array $args ) {
		$this->sitepress    = $args[0];
		$this->upgrade_db = $args[1];
	}

	/**
	 * @return bool
	 */
	public function run_admin() {
		$this->update_global_options();
		$this->update_settings_per_post();
		return true;
	}

	/**
	 * @return bool
	 */
	public function run_ajax() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function run_frontend() {
		return false;
	}

	/**
	 * @return array
	 */
	public function get_results() {
		return array();
	}

	private function update_global_options() {
		$media_setting = get_option( self::OLD_MEDIA_OPTION );
		if ( is_array( $media_setting ) ) {
			$this->sitepress->set_setting( self::GLOBAL_DISPLAY_FEATURED_IMAGE_TRANSLATED, $media_setting['new_content_settings']['duplicate_featured'], true );
		} else {
			$this->sitepress->set_setting( self::GLOBAL_DISPLAY_FEATURED_IMAGE_TRANSLATED, true, true );
		}
	}

	private function update_settings_per_post() {
		$wpdb = $this->upgrade_db->get_wpdb();

		$wpdb->query(
			"INSERT INTO {$wpdb->prefix}postmeta ( post_id, meta_key, meta_value )
			  SELECT post_id, '" . WPML_Admin_Post_Actions::DISPLAY_FEATURED_IMAGE_AS_TRANSLATED_META_KEY . "', meta_value
			  FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wpml_media_featured'" );
	}
}