<?php

class WPML_Media_Without_TM_Notice_Factory implements IWPML_Backend_Action_Loader {

	public function create() {
		global $sitepress;

		return new WPML_Media_Without_TM_Notice( $sitepress->get_wp_api() );
	}


}