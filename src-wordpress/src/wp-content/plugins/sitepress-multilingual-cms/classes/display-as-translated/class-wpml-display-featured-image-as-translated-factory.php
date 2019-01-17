<?php

class WPML_Display_Featured_Image_As_Translated_Factory implements IWPML_Frontend_Action_Loader, IWPML_AJAX_Action_Loader {

	public function create() {
		global $sitepress;
		return new WPML_Display_Featured_Image_As_Translated( new WPML_Translation_Element_Factory( $sitepress ) );
	}
}