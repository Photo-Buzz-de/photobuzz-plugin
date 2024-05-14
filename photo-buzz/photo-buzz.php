<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 * @package           Photo_Buzz
 *
 * @wordpress-plugin
 * Plugin Name:       Photo-Buzz Plugin
 * Version:           1.0.0
 * Author:            Martin
 * Author URI:        http://example.com/
 * Text Domain:       photobuzz
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-photo-buzz-activator.php
 */
function activate_photo_buzz() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-photo-buzz-activator.php';
	Photo_Buzz_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-photo-buzz-deactivator.php
 */
function deactivate_photo_buzz() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-photo-buzz-deactivator.php';
	Photo_Buzz_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_photo_buzz' );
register_deactivation_hook( __FILE__, 'deactivate_photo_buzz' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-photo-buzz.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_photo_buzz() {

	$plugin = new Photo_Buzz();
	$plugin->run();

}
run_photo_buzz();
