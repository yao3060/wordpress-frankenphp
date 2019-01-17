<?php

/**
 * Class WPML_Block_Editor_Helper
 */
class WPML_Block_Editor_Helper {

	/**
	 * Check if Block Editor is active.
	 * Must only be used after plugins_loaded action is fired.
	 *
	 * @return bool
	 */
	public static function is_active() {
		// Gutenberg plugin is installed and activated.
		$gutenberg = ! ( false === has_filter( 'replace_editor', 'gutenberg_init' ) );

		// Block editor since 5.0.
		$block_editor = version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' );

		if ( ! $gutenberg && ! $block_editor ) {
			return false;
		}

		if ( self::is_classic_editor_plugin_active() ) {
			return get_option( 'classic-editor-replace' ) === 'no-replace';
		}

		return true;
	}

	/**
	 * Check if it is admin page to edit any type of post with Block Editor.
	 * Must be used not earlier than plugins_loaded action fired.
	 *
	 * @return bool
	 */
	public static function is_edit_post() {
		return get_current_screen() && 'post' === get_current_screen()->base && self::is_active();
	}

	/**
	 * Check if Classic Editor plugin is active.
	 *
	 * @return bool
	 */
	public static function is_classic_editor_plugin_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
			return true;
		}

		return false;
	}
}
