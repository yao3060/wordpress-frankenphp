<?php

class WPML_Display_Gallery_As_Translated_Factory implements IWPML_Frontend_Action_Loader {

	public function create() {
		global $sitepress, $wpdb;

		return new WPML_Display_Gallery_As_Translated( $wpdb, new WPML_Translation_Element_Factory( $sitepress ) );
	}

}