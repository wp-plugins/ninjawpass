=== NinjaWPass ===
Contributors: nintechnet
Tags: keylogger, security, alert, admin, login, password, protect, malware, brute-force, attack
Requires at least: 2.5
Tested up to: 3.5.2
Stable tag: 1.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Protect WordPress against keyloggers and stolen passwords

== Description ==

> Warning: we do not support this plugin any longer. If you want to protect your blog, check our [NinjaFirewall web application firewall plugin](https://wordpress.org/plugins/ninjafirewall/ "") instead. It offers many more options and some very advanced and powerful features not found in any other plugin.


NinjaWPass is a WordPress plugin used to protect your blog administration
console. It makes it basically impossible for a hacker who stole your
password to log in to your console.

The way it works is simple but very efficient:
All you need to do is to define a second password (AKA the NinjaWPass
password) from 10 to 30 characters.
At the WordPress login prompt, besides your current password, you will
be asked to enter 3 randomly chosen characters from your NinjaWPass
password. Whether your computer is infected by a keylogger or someone is
spying over your shoulder, this protection will keep them away.

Additionally, the plugin offers the possibility to receive an alert by
email whenever someone logs into your WordPress admin interface and to
block brute-force attacks.

== Installation ==

1. Upload `ninjawpass` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Plugin settings are located in 'Settings', 'NinjaWPass'.

== Screenshots ==

1. NinjaWPass integrated into WordPress login form.
2. NinjaWPass options page.

== Changelog ==

= 1.0.5 =
* Added uninstaller.
* Fixed `tabindex` issue on login page.

= 1.0.4 =
* Fixed $capability in add_submenu_page() that could display an error
message when WordPress was in DEBUG mode.

= 1.0.3 =
* Added calls to the built-in wp_die() function.

= 1.0.2 =
* Options to ban IPs in case of brute-force attacks.

= 1.0.1 =
* Fixed a HTML bug (DIV tag) in the settings page.
* Auto add admin email in the login alert input field.

= 1.0.0 =
* First release.
