<?php
/**
 * Plugin Name: Database Manager - WP Adminer
 * Description: Manage the database from your WordPress Dashboard using Adminer.
 * Version: 3.1.1
 * Stable tag: 3.1.1
 * Adminer version: 4.8.4
 * Author: Pexle Chris
 * Author URI: https://www.pexlechris.dev
 * Contributors: pexlechris
 * Domain Path: /languages
 * Requires at least: 4.7.0
 * Tested up to: 6.7.1
 * Requires PHP: 5.6
 * Tested up to PHP: 8.2
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) die;

/**
 * PEXLECHRIS_ADMINER_DIR constant
 *
 * @since 2.1.0
 */
define('PEXLECHRIS_ADMINER_DIR', __DIR__);

/**
 * PEXLECHRIS_ADMINER_MU_PLUGIN_DATA constant
 *
 * @since 3.0.0
 */
define('PEXLECHRIS_ADMINER_MU_PLUGIN_DATA', [
	'file'          => 'pexlechris_adminer_avoid_conflicts_with_other_plugins.php',
	'version'       => '3.0.3',
	'option_name'   => 'pexlechris_adminer_mu_plugin_version',
]);

require_once WP_PLUGIN_DIR . '/pexlechris-adminer/pluggable-functions.php';


add_filter('plugin_action_links_pexlechris-adminer/pexlechris-adminer.php', 'pexlechris_adminer_add_open_wp_adminer_link_in_plugin_action_links', 15, 2);
function pexlechris_adminer_add_open_wp_adminer_link_in_plugin_action_links($links)
{
    $url = esc_url(site_url() . '/' . PEXLECHRIS_ADMINER_SLUG);
    $anchor = '<a href="' . $url . '" target="_blank">' . __('Open WP Adminer', 'pexlechris-adminer') . '</a>';
    $new = [$anchor];

    return array_merge($new, $links);
}




/**
 * @since 2.2.0
 * @since 3.0.0 MU Plugin version controlled by option pexlechris_adminer_mu_plugin_version
 */
add_action( 'admin_init', 'pexlechris_adminer_copy_adminer_mu_plugin', 1 );
function pexlechris_adminer_copy_adminer_mu_plugin() {

	extract(PEXLECHRIS_ADMINER_MU_PLUGIN_DATA);

    $option = get_option( $option_name, null );

    if( $option === null) {
		// continue to updating mu plugin
	}elseif( empty($option) ){ // 0 or empty string
		return;
    }elseif( version_compare( $version, $option ) > 0 ){
		// continue to updating mu plugin
	}else{
		return;
	}

    $from = PEXLECHRIS_ADMINER_DIR . '/' . $file . '.txt';
	$to = WPMU_PLUGIN_DIR . '/' . $file;

	if( file_exists($to) ){
        unlink($to);
    }

    wp_mkdir_p(WPMU_PLUGIN_DIR);
    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
    $WP_Filesystem_Direct = new WP_Filesystem_Direct([]);
    $WP_Filesystem_Direct->copy($from, $to);

	update_option($option_name, $version);

}

/**
 * @since 2.2.0
 */
register_deactivation_hook(__FILE__, 'pexlechris_adminer_delete_adminer_mu_plugin');
function pexlechris_adminer_delete_adminer_mu_plugin()
{
	extract(PEXLECHRIS_ADMINER_MU_PLUGIN_DATA);

	$mu_plugin = WPMU_PLUGIN_DIR . '/' . $file;
	if (file_exists($mu_plugin)) {
		unlink($mu_plugin);
	}
	delete_option($option_name);
}

add_action( 'plugins_loaded', 'pexlechris_maybe_set_wp_admin_constant', 1 );
function pexlechris_maybe_set_wp_admin_constant()
{
	if( !pexlechris_is_current_url_the_wp_adminer_url() ) return;
	if( !have_current_user_access_to_pexlechris_adminer() ) return;
	if( defined('WP_ADMIN') ) return;

	define('WP_ADMIN', true); // adminer is an admin tool, so must be considered as admin interface
}



add_action( 'plugins_loaded', 'pexlechris_adminer_load_plugin_textdomain', 1 );
function pexlechris_adminer_load_plugin_textdomain() {
	load_plugin_textdomain(
		'pexlechris-adminer',
		false,
		'pexlechris-adminer/languages'
	);
}


//INIT
function determine_if_pexlechris_adminer_will_be_included()
{
	if( have_current_user_access_to_pexlechris_adminer() ){
		/**
		 * @hooked pexlechris_adminer_before_adminer_loads, priority: 10
		 * @hooked pexlechris_adminer_disable_display_errors_before_adminer_loads, priority: 100
		 */
		do_action('pexlechris_adminer_before_adminer_loads');
		include 'inc/adminer_includer.php';
		exit;
	}else{
		do_action('pexlechris_adminer_current_user_has_not_access');
	}
}



if( pexlechris_is_current_url_the_wp_adminer_url() )
{
    add_action('plugins_loaded', 'determine_if_pexlechris_adminer_will_be_included', 2);
}



//POSITION 1
add_action('admin_bar_menu', 'pexlechris_adminer_register_in_wp_admin_bar' , 50);
function pexlechris_adminer_register_in_wp_admin_bar($wp_admin_bar) {

	if( have_current_user_access_to_pexlechris_adminer() ){
		$args = array(
			'id' => 'wp_adminer',
			'title' => esc_html__('WP Adminer', 'pexlechris-adminer'),
			'href' => esc_url(site_url() . '/' . PEXLECHRIS_ADMINER_SLUG),
			"meta" => array(
				"target" => "_blank"
			)
		);
		$wp_admin_bar->add_node($args);
	}

}

//POSITION 2
add_action('admin_menu', 'register_pexlechris_adminer_as_tool');
function register_pexlechris_adminer_as_tool(){
	add_submenu_page(
		'tools.php',
		esc_html__('WP Adminer', 'pexlechris-adminer'),
		esc_html__('WP Adminer', 'pexlechris-adminer'),
		implode(',', pexlechris_adminer_access_capabilities()),
		PEXLECHRIS_ADMINER_SLUG,
		'pexlechris_adminer_tools_page_content',
		3
	);
}


//IN TOOLS
if( !function_exists('pexlechris_adminer_tools_page_content') ){
	function pexlechris_adminer_tools_page_content(){
		?>
		<br>
		<a href="<?php echo esc_url( site_url() . '/' . PEXLECHRIS_ADMINER_SLUG )?>" class="button-primary pexlechris-adminer-tools-page-button" target="_blank">
			<?php esc_html_e('Open Adminer in a new tab', 'pexlechris-adminer');?>
        </a>
		<?php
	}
}


add_action('pexlechris_adminer_before_adminer_loads', 'pexlechris_adminer_before_adminer_loads');
function pexlechris_adminer_before_adminer_loads()
{
	if( !defined('PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB') || true === PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB ){
		if( !isset($_GET['db']) && isset($_GET['username']) && '' == $_GET['username'] ){
			// show wordpress database
			wp_redirect( $_SERVER["REQUEST_URI"] . '&db=' . DB_NAME);
			exit;
		}elseif( isset($_GET['db']) && DB_NAME != $_GET['db'] ){
			// if try to show another of wordpress database, wp_die
			wp_die(
				esc_html__("You haven't access to any database other than the site's database. In order to enable access, you need to add the following line code in the wp-config.php file", 'pexlechris-adminer') .
				"<pre>define('PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB', false);</pre>"
			);
		}
	}
}


add_action('pexlechris_adminer_before_adminer_loads', 'pexlechris_adminer_disable_display_errors_before_adminer_loads', 100);
function pexlechris_adminer_disable_display_errors_before_adminer_loads()
{
	/**
	 * @since 2.1.0 firstly set
	 * @since 2.1.1 move after action pexlechris_adminer_before_adminer_loads
     * @since 2.2.0 moved to a wp action with priority 100
     */
	ini_set('display_errors', 0);

	/**
	 * @since 3.0.0
	 */
	add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
}
