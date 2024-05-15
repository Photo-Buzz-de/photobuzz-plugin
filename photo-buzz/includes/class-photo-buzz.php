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
				if (!is_fcb()) {
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
						require_once(plugin_dir_path(__FILE__) . "../admin/photobuzz-stats.php");
					});
					add_submenu_page("photobuzz-stats", "Locations", "Locations", "edit_pages", "edit-tags.php?taxonomy=location&post_type=event");
					add_submenu_page("photobuzz-stats", "Boxen", "Boxen", "edit_pages", "edit-tags.php?taxonomy=fotobox&post_type=event");
					add_submenu_page("photobuzz-stats", "Alle Zuordnungen", "Alle Zuordnungen", "edit_pages", "photobuzz-manage-menu", function () {
						require_once(plugin_dir_path(__FILE__) . "../admin/photobuzz-manage-page.php");
					});
					add_submenu_page("photobuzz-stats", "Neue Zuordnung", "Neue Zuordnung", "edit_pages", "photobuzz-new-assignment", function () {
						require_once(plugin_dir_path(__FILE__) . "../admin/photobuzz-new-assignment.php");
					});
				} else {
					add_menu_page(
						"Fanfoto-Statistik",
						"Fanfoto-Statistik",
						"edit_pages",
						"fcb-statistics-menu",
						function () {
							require_once(plugin_dir_path(__FILE__) . "../admin/fcb-statistics.php");
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

		require_once "metabox_admin.php";
		foreach ($admin_metabox_functions as $metabox) {
			$this->loader->add_action($metabox[0], $plugin_admin, $metabox[1], $metabox[2], $metabox[3]);
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

		$this->loader->add_action('init', $plugin_public, function () { 	//Post Type
			$labels = array(
				'name'               => _x('Events', 'post type general name'),
				'singular_name'      => _x('Event', 'post type singular name'),
				'add_new'            => _x('Erstellen', 'book'),
				'add_new_item'       => __('Neues Event hinzufügen'),
				'edit_item'          => __('Event bearbeiten'),
				'new_item'           => __('Neues Event'),
				'all_items'          => __('Alle Events'),
				'view_item'          => __('Event ansehen'),
				'search_items'       => __('Events durchsuchen'),
				'not_found'          => __('Keine Events gefunden'),
				'not_found_in_trash' => __('Keine Events im Papierkorb gefunden'),
				'parent_item_colon'  => '',
				'menu_name'          => 'Events'
			);
			$args   = array(
				'labels'        => $labels,
				'description'   => 'Ein Event.',
				'public'        => true,
				'menu_position' => 5,
				'supports'      => array('title'), //comments
				'has_archive'   => true,
				//'taxonomies' 	=> array('category'),
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-tickets-alt',

			);
			register_post_type('event', $args);

			//Post Type
			$labels = array(
				'name'               => _x('Gewinnspiele', 'post type general name'),
				'singular_name'      => _x('Gewinnspiel', 'post type singular name'),
				'add_new'            => _x('Erstellen', 'book'),
				'add_new_item'       => __('Neues Gewinnspiel hinzufügen'),
				'edit_item'          => __('Gewinnspiel bearbeiten'),
				'new_item'           => __('Neues Gewinnspiel'),
				'all_items'          => __('Alle Gewinnspiele'),
				'view_item'          => __('Gewinnspiel ansehen'),
				'search_items'       => __('Gewinnspiele durchsuchen'),
				'not_found'          => __('Keine Gewinnspiele gefunden'),
				'not_found_in_trash' => __('Keine Gewinnspiele im Papierkorb gefunden'),
				'parent_item_colon'  => '',
				'menu_name'          => 'Gewinnspiele'
			);
			$args   = array(
				'labels'        => $labels,
				'description'   => 'Ein Gewinnspiel.',
				'public'        => true,
				'menu_position' => 20,
				'supports'      => array('title', "editor"), //comments
				'has_archive'   => true,
				//'taxonomies' 	=> array('category'),
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-awards',

			);

			register_post_type('raffle', $args);


			// create a new taxonomy
			$labels = array(
				'name'          => _x('Eventtypen', 'taxonomy general name', 'textdomain'),
				'singular_name' => _x('Eventtyp', 'taxonomy singular name', 'textdomain'),
				'search_items'  => __('Eventtypen durchsuchen', 'textdomain'),
				'all_items'     => __('Alle Eventtypen', 'textdomain'),
				'edit_item'     => __('Eventtyp Bearbeiten', 'textdomain'),
				'update_item'   => __('Eventtyp aktualisieren', 'textdomain'),
				'add_new_item'  => __('Neuen Eventtyp hinzfügen', 'textdomain'),
				'new_item_name' => __('Name des neuen Eventtyp', 'textdomain'),
				'menu_name'     => __('Eventtypen', 'textdomain'),
			);
			register_taxonomy(
				'eventtype',
				'event',
				array(
					'label'   => __('Eventtyp'),
					'labels'  => $labels,
					'rewrite' => array('slug' => 'eventtype'),
					'default_term' => get_current_blog_id() == 1 ? array(array()) : array(),

				)
			);
		});



		$this->loader->add_filter("single_template", $plugin_public, function ($template, $type, $templates) {
			if (in_array("single-event.php", $templates)) {
				return realpath(plugin_dir_path(__FILE__) . "/../public/single-event.php");
			}
		}, 10, 4);

		$this->loader->add_action('wp_ajax_delete_image', $plugin_public, function () {
			error_log("DELETE");
			$dir = $_POST["dir"];
			$name = $_POST["name"];
			if (can_delete_image($_POST["event"])) {
				$imgs = new PhotoBuzz\Event_Images($dir);
				$imgs->deleteImage($name);
				echo "Success";
			} else {
				echo "Not authorized";
			}
			wp_die();
		});

		$this->loader->add_action('pre_get_posts', $plugin_public, function ($qry) {
			if ($qry->is_main_query() && !is_admin() && is_post_type_archive('event') && isset($_GET['has_password'])) {
				$has_password = urldecode($_GET['has_password']) === "true";
				$qry->set('has_password', $has_password);
			}
		});

		$this->loader->add_action('template_include', $plugin_public, function ($template) {
			global $wp_query;
			// our query
			if (isset($wp_query->query['p']) && !$wp_query->is_404) {
				$template = get_query_template("single", array("single-event.php"));
			}
			if (get_query_var('calendar')) {
				$template = locate_template(['calendar.php']);
			}

			return $template;
		});

		$this->loader->add_action('pre_get_posts', $plugin_public, function () {
			global $wp_query;
			if (isset($wp_query->query['p'])) {
				

				$imgdetail = PhotoBuzz\Event_Images::getImageDetailsByCode($wp_query->query['p']);
				if (!empty($imgdetail)) {
					$assignments = new PhotoBuzz\Box_Assignments();
					$ass = $assignments->get_assignments($imgdetail["box_id"], NULL, $imgdetail["date"]);

					$wp_query->set("p", $ass[0]->event_id);
					$wp_query->set("post_type", "any");
					$wp_query->is_singular = true;
					//$wp_query->set("error", "");
					set_query_var("image-code", $wp_query->query['p']);

					//Workaround für seltenen FCB Fehler
					$wp_query->set("error", "");
					$wp_query->is_404 = false;

					if (empty($wp_query->query['p'])) {
						$wp_query->is_404 = true;
					}
					
				} else {
					$wp_query->is_404 = true;
				}
			}
		});
		// Rewrite event link

		$this->loader->add_action('init', $plugin_public,  function () {
			add_rewrite_rule('event/([a-z0-9-]+)/([\w-]+)[/]?$', 'index.php?event=$matches[1]&image-code=$matches[2]', 'top');
			add_rewrite_endpoint("p", EP_ROOT);
			add_rewrite_endpoint("calendar", EP_ROOT);
			if (is_fcb()) {
				add_rewrite_endpoint("teilnahmebedingungen", EP_PERMALINK);
			}
		});

		$this->loader->add_filter('query_vars', $plugin_public, function ($query_vars) {
			$query_vars[] = 'image-code';
			return $query_vars;
		});
		$this->loader->add_filter('request', $plugin_public,  function ($vars) {
			if (isset($vars['teilnahmebedingungen'])) $vars['teilnahmebedingungen'] = true;
			if (isset($vars['calendar'])) $vars['calendar'] = $vars['calendar'];
			return $vars;
		});
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
