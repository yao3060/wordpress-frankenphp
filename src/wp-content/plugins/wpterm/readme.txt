=== WPTerm ===
Contributors: bruandet
Tags: xterm, terminal, command, bash, shell, linux, unix, BSD
Requires at least: 3.3.0
Tested up to: 5.9
Stable tag: 1.1.9
License: GPLv3 or later
Requires PHP: 5.3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

An xterm-like plugin to run non-interactive shell commands.

== Description ==

= Terminal =

WPTerm is an xterm-like plugin. It can be used to run non-interactive shell commands from the WordPress admin dashboard.

> Just like a terminal, WPTerm lets you do almost everything you want (e.g., changing file permissions, viewing network connections or current processes etc). That's great, but if you aren't familiar with Unix shell commands, you can also damage your blog. Therefore, each time you use WPTerm, please follow this rule of thumb: **if you don't know what you're doing, don't do it!**


[youtube http://www.youtube.com/watch?v=NHlWrEK6JfE]


= Compatibility =

WPTerm is not compatible with Microsoft Windows; it works on Unix-like servers only.

Because it makes use of PHP program execution functions such as `exec` or `shell_exec`, it may not be compatible with some shared hosts that have disabled these functions. To make sure your server is compatible, follow these steps:

* Download [this script](https://nintechnet.com/bruandet/wpterm-check.txt "").
* Rename it to "wpterm-check.php".
* Upload it inside your website root folder.
* Go to http://YOUR WEBSITE/wpterm-check.php
* Delete it afterwards.


= Password Protection =

You can (and probably should!) password protect the access to WPTerm. Consult the contextual help, or type `help` at the terminal prompt to get more details about how to enable this feature.

= Features =

* Selectable PHP program execution function to run commands.
* Custom fonts family, size and color.
* Custom background color.
* History and scrollback buffer.
* Terminal bell (audible / visible).
* Optional password protection.
* Contextual help.
* Multisite compatible (only accessible to the SuperAdmin).

= Supported Languages =

* English
* French

= Requirements =

* WordPress 3.3+
* PHP 5.3+
* Unix-like OS (Linux, *BSD etc) only. WPTerm is **NOT** compatible with Microsoft Windows.

== Frequently Asked Questions ==

= Is there any Microsoft Windows version ? =

WPTerm works on Unix-like servers only.

== Installation ==

1. Upload `wpterm` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' page in WordPress.
3. Plugin settings are located in the 'Tools > WPTerm' sub-menu.

== Screenshots ==

1. Terminal (default colors and welcome message).
2. Terminal (custom colors and welcome message).
3. Terminal (custom colors and welcome message).
4. Password protection.
5. Settings (fonts and colors).
6. Settings (terminal).
7. Contextual help.

== Changelog ==

= 1.1.9 =

* Compatibility with WordPress 5.9.

= 1.1.8 =

* Fixed the "Your site could not complete a loopback request" error message in Site Health due to the PHP session when WPTerm's password protection was enabled.
* On multisite installations the plugin is now accessible from the main site only. Since there's just one physical WordPress install, there's no need to use it on other sites.


= 1.1.7 =

* Added right to left language support to the terminal (compatible wit Firefox, Chrome, Opera and Safari browsers).

= 1.1.6 =

* Fixed an issue where the PHP session required by WPTerm's password protection was always started, even when a non-admin user visited the site.

= 1.1.5 =

* Added `popen` to the list of PHP functions that you can select to run commands (see "Settings > Terminal > Use the following PHP function for command execution").

= 1.1.4 =

* WordPress 4.9 compatibility.
