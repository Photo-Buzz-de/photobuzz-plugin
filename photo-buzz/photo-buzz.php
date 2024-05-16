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
 * Requires Plugins:  cmb2
 * Version:           1.0.0
 * Author:            Martin
 * Text Domain:       photobuzz
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('PLUGIN_NAME_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-photo-buzz-activator.php
 */
function activate_photo_buzz()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-photo-buzz-activator.php';
	Photo_Buzz_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-photo-buzz-deactivator.php
 */
function deactivate_photo_buzz()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-photo-buzz-deactivator.php';
	Photo_Buzz_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_photo_buzz');
register_deactivation_hook(__FILE__, 'deactivate_photo_buzz');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-photo-buzz.php';



function is_fcb()
{
	return get_current_blog_id() == 2;
}
function is_not_fcb()
{
	return get_current_blog_id() != 2;
}
function is_main()
{
	return get_current_blog_id() == 1;
}
function is_photolead()
{
	return get_current_blog_id() == 3;
}

function can_delete_image($id = null)
{
	if (current_user_can('manage_options')) {
		//admin
		return true;
	} else if (is_user_logged_in()) {
		$orders = wc_get_orders([
			'customer' => get_current_user_id(),

		]);
		foreach ($orders as $order) {
			foreach ($order->get_meta("event", false) as $meta) {

				if ($meta->get_data()["value"] == $id) {
					return true;
				}
			}
		}
	}
	return false;
}
function user_has_rights_for_raffle($id = null)
{
	if (current_user_can('manage_options')) {
		//admin
		return true;
	} else if (is_user_logged_in()) {
		$orders = wc_get_orders([
			'customer' => get_current_user_id(),

		]);
		foreach ($orders as $order) {
			foreach ($order->get_meta("event", false) as $meta) {

				$raffles = get_post_meta($meta->get_data()["value"], "raffle", true);
				$raffle = !empty($raffles) ? $raffles[0] : null;
				if (!empty($raffle)) {
					if ($raffle == $id) return true;
				}
			}
		}
	}
	return false;
}




function get_assignments()
{
	return new PhotoBuzz\Box_Assignments();
}

function get_event_images($dir)
{
	return new PhotoBuzz\Event_Images($dir);
}


function get_terms_from_main($args = array())
{
	global $wp_taxonomies;
	switch_to_blog(1);
	$taxonomies = $args["taxonomy"];
	if (!is_array($taxonomies)) {
		$taxonomies = [$taxonomies];
	}

	$check_later = array();
	global $wp_taxonomies;
	foreach ($taxonomies as $taxonomy) {
		if (isset($wp_taxonomies[$taxonomy])) {
			$check_later[$taxonomy] = false;
		} else {
			$wp_taxonomies[$taxonomy] = (object) array('hierarchical' => false);
			$check_later[$taxonomy] = true;
		}
	}

	$terms      = get_terms($args);


	if (isset($check_later))
		foreach ($check_later as $taxonomy => $unset)
			if ($unset == true)
				unset($wp_taxonomies[$taxonomy]);

	restore_current_blog();
	return $terms;
}
function get_term_by_from_main(string $field, string|int|null $value, string $taxonomy = '')
{
	global $wp_taxonomies;
	switch_to_blog(1);
	$taxonomy;


	$check_later = array();
	global $wp_taxonomies;

	if (isset($wp_taxonomies[$taxonomy])) {
		$check_later[$taxonomy] = false;
	} else {
		$wp_taxonomies[$taxonomy] = (object) array('hierarchical' => false);
		$check_later[$taxonomy] = true;
	}


	$term      = get_term_by($field, $value, $taxonomy);


	if (isset($check_later))
		foreach ($check_later as $taxonomy => $unset)
			if ($unset == true)
				unset($wp_taxonomies[$taxonomy]);

	restore_current_blog();
	return $term;
}
function wp_get_post_terms_from_blog(int $post_id, $taxonomies, $blog_id)
{
	global $wp_taxonomies;
	switch_to_blog($blog_id);
	if (!is_array($taxonomies)) {
		$taxonomies = [$taxonomies];
	}

	$check_later = array();
	global $wp_taxonomies;
	foreach ($taxonomies as $taxonomy) {
		if (isset($wp_taxonomies[$taxonomy])) {
			$check_later[$taxonomy] = false;
		} else {
			$wp_taxonomies[$taxonomy] = true;
			$check_later[$taxonomy] = true;
		}
	}

	$terms = wp_get_post_terms($post_id, $taxonomies);


	if (isset($check_later))
		foreach ($check_later as $taxonomy => $unset)
			if ($unset == true)
				unset($wp_taxonomies[$taxonomy]);

	restore_current_blog();
	return $terms;
}


require_once WPMU_PLUGIN_DIR . '/cmb2-attached-posts/cmb2-attached-posts-field.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_photo_buzz()
{

	$plugin = new Photo_Buzz();
	$plugin->run();
}
run_photo_buzz();
