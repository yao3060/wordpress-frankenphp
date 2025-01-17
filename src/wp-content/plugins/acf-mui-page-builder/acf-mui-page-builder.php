<?php
/*
 * Plugin Name: ACF MUI Page builder
 * Description: Enable page builder field
 * Version: 0.0.1
 * Requires at least: 6.2
 * Requires PHP: 8.2
 * Author: IT Consultis
 * License: GPLv3
 */

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/MuiPageBuilderConfigManager.php';
require_once __DIR__ . '/MuiPageBuilderMediaLibrary.php';
require_once __DIR__ . '/MuiPageBuilderAutocomplete.php';
require_once __DIR__ . '/MuiPageBuilderFilebird.php';


global $wpdb;
$config_manager = new MuiPageBuilderConfigManager($wpdb);
$mui_media_library = new MuiPageBuilderMediaLibrary($wpdb);
$mui_autocomplete = new MuiPageBuilderAutocomplete($wpdb);
$mui_filebird = new MuiPageBuilderFilebird();


add_action('admin_notices', function () {

    if (!function_exists('acf_register_field_type') || !class_exists(JWTAuth\Setup::class)) {
        // throw new Error('Plugin advanced-custom-fields (ACF) must be installed first.');
        echo '<div class="notice notice-error">
                <p>Plugin <a href="https://www.advancedcustomfields.com/">advanced-custom-fields</a> and <a href="https://wordpress.org/plugins/jwt-auth/">JWT Auth</a> must be installed first.</p>
             </div>';
    }
});

add_action('init', function () {
    if (!function_exists('acf_register_field_type') || !class_exists(JWTAuth\Setup::class)) {
        return;
    }

    require_once __DIR__ . '/MuiPageBuilderSettingsOptionTrait.php';
    require_once __DIR__ . '/AcfFieldMuiPageBuilder.php';
    acf_register_field_type(AcfFieldMuiPageBuilder::class);
    if (is_admin()) {
        require_once __DIR__ . '/MuiPageBuilderSettingsPage.php';
        new MuiPageBuilderSettingsPage();

        require_once __DIR__ . '/MuiPageBuilderRevisions.php';
        new MuiPageBuilderRevisions();

        add_action( 'admin_enqueue_scripts', function(){
            wp_enqueue_style( 'acf-mui-page-builder',
            plugin_dir_url( __FILE__ ) . '/css/acf-mui-page-builder.css' ,
            false,
            '1.0.0' );
        } );

        // disable `page` revisions
        add_filter( 'wp_page_revisions_to_keep',  fn () => 0);

        // show 1 column in `page` editor page
        add_filter('screen_layout_columns', function($columns) {
            $columns['page'] = 1;
            return $columns;
        });
        add_filter('get_user_option_screen_layout_page', fn() => 1 );
    }
});

add_action('rest_api_init', function () use (
    $config_manager,
    $mui_media_library,
    $mui_autocomplete,
    $mui_filebird,
) {
    $config_manager->registerRestRoutes();
    $mui_autocomplete->registerRestRoutes();
    if (function_exists('FileBird\\init')) {
        $mui_filebird->registerRestRoutes();
    } else {
        $mui_media_library->registerRestRoutes();
    }
});

add_action('plugins_loaded', function () use ($config_manager, $mui_media_library) {
    $config_manager->installSchema();
    $mui_media_library->installSchema();
});

register_deactivation_hook(__FILE__, function () use ($config_manager, $mui_media_library) {
});
