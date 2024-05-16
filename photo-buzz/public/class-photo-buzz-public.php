<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Photo_Buzz
 * @subpackage Photo_Buzz/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Photo_Buzz
 * @subpackage Photo_Buzz/public
 * @author     Your Name <email@example.com>
 */
class Photo_Buzz_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $photo_buzz    The ID of this plugin.
	 */
	private $photo_buzz;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $photo_buzz       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($photo_buzz, $version)
	{

		$this->photo_buzz = $photo_buzz;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Photo_Buzz_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Photo_Buzz_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_style($this->photo_buzz, plugin_dir_url(__FILE__) . 'css/photo-buzz-public.css', array(), $this->version."zzsz", 'all');
		wp_enqueue_style("photoswipe", plugin_dir_url(__FILE__) . 'css/photoswipe.css', array(), $this->version, 'all');
		wp_enqueue_style("photoswipe-default-skin", plugin_dir_url(__FILE__) . 'css/default-skin/default-skin.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Photo_Buzz_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Photo_Buzz_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->photo_buzz, plugin_dir_url(__FILE__) . 'js/photo-buzz-public.js', array('jquery'), $this->version, false);
		wp_enqueue_script('jquery-masonry');
		wp_enqueue_script('photoswipe', plugin_dir_url(__FILE__)  . '/js/photoswipe.min.js', array(), null, true);
		wp_enqueue_script('photoswipe-ui', plugin_dir_url(__FILE__)  . '/js/photoswipe-ui-default.min.js', array('photoswipe'), null, true);
	}
}
