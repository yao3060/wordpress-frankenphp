<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

 // a helper function to lookup "env_FILE", "env", then fallback
if (!function_exists('getenv_docker')) {
	// https://github.com/docker-library/wordpress/issues/588 (WP-CLI will load this file 2x)
	function getenv_docker($env, $default)
	{
	    if ($fileEnv = getenv($env . '_FILE')) {
		 return rtrim(file_get_contents($fileEnv), "\r\n");
	    } else if (($val = getenv($env)) !== false) {
		 return $val;
	    } else {
		 return $default;
	    }
	}
   }
   

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'domain' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'wordpress_db' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'wHWX8zCV7wvsIDTtQ6e=n4]H5] 5euV6.Dvm8B)ou@Gq^g!S+FLkAaoakEiSG1M7' );
define( 'SECURE_AUTH_KEY',  'yS/h`ECG,GZYvN&f@_RMo8n%^(6Qz-vmd]cLK1#6>;;(ASM0#7-RJ%z}#i)@rk9i' );
define( 'LOGGED_IN_KEY',    'y6+}@w0=$oss%D+bgcmEhK?k^y}f$+~cQY.5c5*%C2.U~0Ag.0 RSn;y_yh%NDG&' );
define( 'NONCE_KEY',        'V?;vm|6;Q}8+jUiP<ZzEb#$SUr;jN7R/:p.z(AWW`9J_h|myJZOG$ar>LlO7Hg+p' );
define( 'AUTH_SALT',        'J<VLS-1df;e,o 6-?Ie[ik<RD(G)7*.S%KIR0yq#OsPmj|HPVC|#]g4xR0@`)!K]' );
define( 'SECURE_AUTH_SALT', '@g,oa;eq KzA>U)r~vp<x1ror?rs{P`=57-2:9za+Mnu(tcrj]i5dQ=/fmvT;_OE' );
define( 'LOGGED_IN_SALT',   '@gR]@idi*v+<e]R)d:)3t-hG}X{JEB39 2;%5}|KD(>YZ!VO9!>j1uDZA|L(;Y!g' );
define( 'NONCE_SALT',       '=z>r=/O3&laA0<].CcgW!m.i*H@vj$yv7~WCMB>sO4Z=K5~43d}OQ!i?@?0fXMFv' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */
define( 'WP_SITEURL', 'http://' . $_SERVER['HTTP_HOST']  );
define( 'WP_HOME', 'http://' . $_SERVER['HTTP_HOST']  );
define( 'WP_REDIS_HOST', getenv_docker( 'WORDPRESS_CACHE_HOST', 'wordpress_cache' ) );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
