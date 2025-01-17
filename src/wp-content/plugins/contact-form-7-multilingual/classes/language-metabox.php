<?php

namespace WPML\CF7;

use WPML\API\Sanitize;

class Language_Metabox implements \IWPML_Backend_Action, \IWPML_DIC_Action {
	/**
	 * Instance of Sitepress.
	 *
	 * @var \SitePress
	 */
	private $sitepress;

	/**
	 * Instance of $wpml_post_translations.
	 *
	 * @var \WPML_Post_Translation
	 */
	private $wpml_post_translations;

	/**
	 * Language_Metabox constructor.
	 *
	 * @param \SitePress             $sitepress              An instance of SitePress class.
	 * @param \WPML_Post_Translation $wpml_post_translations An instance of WPML_Post_Translation class.
	 */
	public function __construct( \SitePress $sitepress, \WPML_Post_Translation $wpml_post_translations ) {
		$this->sitepress              = $sitepress;
		$this->wpml_post_translations = $wpml_post_translations;
	}

	/**
	 * Adds the actions and filters.
	 */
	public function add_hooks() {
		add_action( 'wpcf7_admin_misc_pub_section', [ $this, 'add_language_meta_box' ] );
		add_filter( 'wpml_link_to_translation', [ $this, 'link_to_translation' ], 10, 4 );
		add_filter( 'wpml_admin_language_switcher_items', [ $this, 'admin_language_switcher_items' ] );
		add_filter( 'wpml_enable_language_meta_box', [ $this, 'wpml_enable_language_meta_box_filter' ] );
	}

	/**
	 * @param bool $enable
	 *
	 * @return bool
	 */
	public function wpml_enable_language_meta_box_filter( $enable ) {
		if ( $this->enableScript() ) {
			return true;
		}

		return $enable;
	}

	/**
	 * Add the WPML meta box when editing forms.
	 *
	 * @param int|\WP_Post $post The post ID or an instance of WP_Post.
	 */
	public function add_language_meta_box( $post ) {
		$post = get_post( $post );
		$trid = filter_input( INPUT_GET, 'trid', FILTER_SANITIZE_NUMBER_INT );

		if ( $post ) {
			$isGT6   = version_compare( constant( 'WPCF7_VERSION' ), '6', '>=' );
			$rootTag = $isGT6 ? 'section' : 'div';

			add_filter( 'wpml_post_edit_can_translate', '__return_true' );
			?>
			</div>
		</div>
	</div>
</<?php echo $rootTag; // phpcs:ignore ?>>

<<?php echo $rootTag; // phpcs:ignore ?> class="postbox">
	<h3><?php echo esc_html( __( 'Language', 'sitepress' ) ); ?></h3>
	<div>
		<div>
			<div id="icl_div">
				<div class="inside"><?php $this->sitepress->meta_box( $post ); ?></div>
			<?php
		} elseif ( $trid ) {
			// Used by WPML for connecting new manual translations to their originals.
			echo '<input type="hidden" name="icl_trid" value="' . esc_attr( $trid ) . '" />';
		}
	}

	/**
	 * Filters links to translations in language metabox.
	 *
	 * @param string $link
	 * @param int    $post_id
	 * @param string $lang
	 * @param int    $trid
	 * @return string
	 */
	public function link_to_translation( $link, $post_id, $lang, $trid ) {
		if ( Constants::POST_TYPE === get_post_type( $post_id ) ) {
			$link = $this->get_link_to_translation( $post_id, $lang );
		}

		return $link;
	}

	/**
	 * Filters the top bar admin language switcher links.
	 *
	 * @param array $links
	 * @return array $links
	 */
	public function admin_language_switcher_items( $links ) {
		$is_wpcf7_page = $this->is_wpcf7_page();
		$post_id       = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
		$trid          = filter_input( INPUT_GET, 'trid', FILTER_SANITIZE_NUMBER_INT );

		if ( $is_wpcf7_page && ( $trid || $post_id ) ) {
			// If we are adding a post, get the post_id from the trid and source_lang.
			if ( ! $post_id ) {
				$source_lang = filter_input( INPUT_GET, 'source_lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				$post_id     = $this->wpml_post_translations->get_element_id( $source_lang, $trid );
				unset( $links['all'] );
				// We shouldn't get here, but just in case.
				if ( ! $post_id ) {
					return $links;
				}
			}

			foreach ( $links as $lang => & $link ) {
				if ( 'all' !== $lang && ! $link['current'] ) {
					$link['url'] = $this->get_link_to_translation( $post_id, $lang );
				}
			}
		}

		return $links;
	}

	/**
	 * Check if we are in CF7 admin page.
	 *
	 * @return bool
	 */
	private function is_wpcf7_page() {
		global $pagenow;

		$plugin_page   = $this->get_plugin_page();
		$is_admin_page = $pagenow && ( 'admin.php' === $pagenow );
		$is_wpcf7_page = $plugin_page && in_array( $plugin_page, [ 'wpcf7', 'wpcf7-new' ], true );

		return $is_admin_page && $is_wpcf7_page;
	}

	/**
	 * @return string
	 */
	private function get_plugin_page() {
		global $plugin_page;

		if ( $plugin_page ) {
			return $plugin_page;
		}

		return plugin_basename( (string) Sanitize::stringProp( 'page', $_GET ) );
	}

	/**
	 * Check if script should be enabled.
	 *
	 * @return bool
	 */
	private function enableScript() {
		return $this->is_wpcf7_page() && filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
	}

	/**
	 * Works out the correct link to a translation
	 *
	 * @param int    $post_id The post_id being edited.
	 * @param string $lang    The target language.
	 * @return string
	 */
	private function get_link_to_translation( $post_id, $lang ) {
		$translated_post_id = $this->wpml_post_translations->element_id_in( $post_id, $lang );
		if ( $translated_post_id ) {
			// Rewrite link to edit contact form translation.
			$args = [
				'page'   => 'wpcf7',
				'lang'   => $lang,
				'post'   => $translated_post_id,
				'action' => 'edit',
			];
		} else {
			// Rewrite link to create contact form translation.
			$trid                 = $this->wpml_post_translations->get_element_trid( $post_id );
			$source_language_code = $this->wpml_post_translations->get_element_lang_code( $post_id );

			$args = [
				'page'        => 'wpcf7-new',
				'lang'        => $lang,
				'trid'        => $trid,
				'source_lang' => $source_language_code,
			];
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}
}
