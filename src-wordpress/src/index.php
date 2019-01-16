<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define('WP_USE_THEMES', true);

/** FOR DEV environment */
if (getenv('DEV_ENV') === "1" && file_exists('/var/www/html/wp-blog-header.php')) {
    require '/var/www/html/wp-blog-header.php';
}
else {
    /** Loads the WordPress Environment and Template */
    require( dirname( __FILE__ ) . '/wp-blog-header.php' );
}
