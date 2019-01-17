<?php

class WPML_Media_Without_TM_Notice implements IWPML_Action {

	const NOTICE_ID = 'turn-off-media';
	const NOTICE_GROUP = 'media-without-tm';

	/**
	 * @var WPML_WP_API
	 */
	private $wpml_wp_api;

	/**
	 * WPML_Media_Without_TM_Notice constructor.
	 *
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( WPML_WP_API $wpml_wp_api ) {
		$this->wpml_wp_api = $wpml_wp_api;
	}

	public function add_hooks() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {

		$admin_notices = $this->wpml_wp_api->get_admin_notices();

		if ( $this->can_deactivate_wpml_media() &&
		     $this->wpml_media_is_installed( $this->wpml_wp_api ) &&
		     ! $this->wpml_tm_is_installed()
		) {

			$plugin_file                  = $this->get_wpml_media_plugin_id();
			$deactivation_link_action     = 'deactivate-plugin_' . $plugin_file;
			$deactivation_link_params     = array(
				'action' => urlencode( 'deactivate' ),
				'plugin' => urlencode( $plugin_file ),
			);
			$deactivation_link_url        = add_query_arg( $deactivation_link_params, admin_url( 'plugins.php' ) );
			$deactivation_link_url_nonced = wp_nonce_url( $deactivation_link_url, $deactivation_link_action );
			$deactivation_link            = '<a href="' . $deactivation_link_url_nonced . '">' . esc_html__( 'Deactivate WPML Media', 'sitepress' ) . '</a>';


			$text = '<p><strong>' . esc_html__( 'WPML Media does not need to be active for your site!', 'sitepress' ) . '</strong></p>';
			$text .= '<p>';
			$text .= esc_html__( 'WPML Media is no longer intended to be used without WPML Translation Management. As of WPML 4.0, attachments no longer need to be duplicated in order to show up in all languages. It is strongly recommended to deactivate WPML Media.', 'sitepress' );
			$text .= '</p>';
			$text .= '<br/>' . $deactivation_link;

			$notice = new WPML_Notice( self::NOTICE_ID, $text, self::NOTICE_GROUP );
			$notice->set_dismissible( true );
			$notice->set_css_class_types( 'notice-error' );
			$admin_notices->add_notice( $notice );
		} else {
			$admin_notices->remove_notice( self::NOTICE_GROUP, self::NOTICE_ID );
		}

	}

	private function wpml_media_is_installed() {
		return $this->wpml_wp_api->constant( 'WPML_MEDIA_VERSION' ) &&
		       version_compare( $this->wpml_wp_api->constant( 'WPML_MEDIA_VERSION' ), '2.3.0', '>=' );
	}

	private function wpml_tm_is_installed() {
		return $this->wpml_wp_api->constant( 'WPML_TM_VERSION' ) &&
		       version_compare( $this->wpml_wp_api->constant( 'WPML_TM_VERSION' ), '2.6.0', '>=' );
	}

	private function can_deactivate_wpml_media() {
		return current_user_can( 'deactivate_plugin', $this->get_wpml_media_plugin_id() );
	}

	private function get_wpml_media_plugin_id() {
		return basename( $this->wpml_wp_api->constant( 'WPML_MEDIA_PATH' ) ) . '/plugin.php';
	}
}