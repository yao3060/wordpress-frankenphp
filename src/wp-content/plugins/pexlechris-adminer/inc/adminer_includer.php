<?php
	
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * adminer_object can be overridden, in WP action pexlechris_adminer_before_adminer_loads.
 * If a developer want to make his/her own changes (adding plugins, extensions or customizations),
 * it is strongly recommended to include_once the class Pexlechris_Adminer and extend it and
 * make adminer_object function return his/her new class.
 *
 * It is strongly recommended, because Pexlechris_Adminer class contains WordPress/Adminer integration (auto login with WordPress credentials)
 *
 * If a developer want to add just JS and/or CSS in head, he/she can just use the action pexlechris_adminer_head.
 * See plugin's FAQs, for more.
 *
 * @since 2.1.0
 *
 * @link https://www.adminer.org/en/plugins/#use Documentation URL.
 * @link https://www.adminer.org/en/plugins/ Adminer' plugins Documentation URL.
 * @link https://www.adminer.org/en/extension/ Adminer' extensions Documentation URL.
 */
if ( !function_exists('adminer_object') ) {

	function adminer_object() {

        include_once PEXLECHRIS_ADMINER_DIR . '/inc/class-pexlechris-adminer.php';

		return new Pexlechris_Adminer();

	}

}

// include original Adminer
include 'adminer.php';