<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Photo_Buzz
 * @subpackage Photo_Buzz/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Photo_Buzz
 * @subpackage Photo_Buzz/includes
 * @author     Your Name <email@example.com>
 */
class Photo_Buzz
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Photo_Buzz_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('PLUGIN_NAME_VERSION')) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'photo-buzz';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Photo_Buzz_Loader. Orchestrates the hooks of the plugin.
	 * - Photo_Buzz_i18n. Defines internationalization functionality.
	 * - Photo_Buzz_Admin. Defines all hooks for the admin area.
	 * - Photo_Buzz_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-photo-buzz-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-photo-buzz-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-photo-buzz-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-photo-buzz-public.php';

		$this->loader = new Photo_Buzz_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Photo_Buzz_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Photo_Buzz_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Photo_Buzz_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// FOTOBOX ZUORDNUNG ADMIN
		$this->loader->add_action(
			'admin_menu',
			$plugin_admin,
			function () {
				if (is_main()) {
					add_menu_page(
						"Fotobox Verwaltung",
						"Fotoboxen",
						"edit_pages",
						"photobuzz-stats",
						function () {
							require_once(plugin_dir_path(__FILE__) . "../admin/photobuzz-stats.php");
						},
						"dashicons-camera",
						9
					);
					add_submenu_page("photobuzz-stats", "Übersicht", "Übersicht", "edit_pages", "photobuzz-stats", function () {
						require_once(plugin_dir_path(__FILE__) ."../admin/photobuzz-stats.php");
					});
					add_submenu_page("photobuzz-stats", "Locations", "Locations", "edit_pages", "edit-tags.php?taxonomy=location&post_type=event");
					add_submenu_page("photobuzz-stats", "Boxen", "Boxen", "edit_pages", "edit-tags.php?taxonomy=fotobox&post_type=event");
					add_submenu_page("photobuzz-stats", "Alle Zuordnungen", "Alle Zuordnungen", "edit_pages", "photobuzz-manage-menu", function () {
						require_once(plugin_dir_path(__FILE__) ."../admin/photobuzz-manage-page.php");
					});
					add_submenu_page("photobuzz-stats", "Neue Zuordnung", "Neue Zuordnung", "edit_pages", "photobuzz-new-assignment", function () {
						require_once(plugin_dir_path(__FILE__) ."../admin/photobuzz-new-assignment.php");
					});
				} else if (is_fcb()) {
					add_menu_page(
						"Fanfoto-Statistik",
						"Fanfoto-Statistik",
						"edit_pages",
						"fcb-statistics-menu",
						function () {
							require_once(plugin_dir_path(__FILE__) ."../admin/fcb-statistics.php");
						},
						"dashicons-chart-line",
						9
					);
				}
			}
		);

		//FCB Statistik ajax
		$this->loader->add_action('wp_ajax_fcb_statistics', $plugin_admin, 'fcb_statistics');

		function fcb_statistics()
		{

			$statistics = new PhotoBuzz\Statistics($_POST["id"]);
			$statistics_json = (object) $statistics;
			$statistics_json->data = NULL;
			$statistics_json->visited_percentage = $statistics->get_visited_percentage();
			$statistics_json->raffles_percentage = $statistics->get_raffle_percentage();
			$statistics_json->newsletter_percentage = $statistics->get_newsletter_percentage();
			echo json_encode($statistics_json);

			wp_die(); // this is required to terminate immediately and return a proper response
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Photo_Buzz_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Photo_Buzz_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
