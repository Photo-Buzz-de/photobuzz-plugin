<?php

use PhotoBuzz\Box_Assignments;

require_once __DIR__ . "/../vendor/autoload.php";


function generateRandomString($length = 10)
{
    return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}
global $admin_metabox_functions;
$admin_metabox_functions=array();

$admin_metabox_functions[]=["cmb2_override_event_assignments_meta_value", function ($value, $object_id, $args, $field) {
    $assignments_obj = new Box_Assignments();
    $assignments = $assignments_obj->get_assignments_by_event($object_id);
    foreach ($assignments as $key => $assignment) {
        $assignments[$key] = (array) $assignment;
    }
    return $assignments;
}, 10, 4];

$admin_metabox_functions[]=["cmb2_override_event_assignments_meta_save", function ($check, $args, $field_args, $field) {
    $assignments_obj = new Box_Assignments();
    // Delete
    $old_assignments = $assignments_obj->get_assignments_by_event($args["id"]);
    $new_ids = array_column($args["value"], "id");
    foreach ($old_assignments as $key => $assignment) {
        if (!in_array($assignment->id, $new_ids)) {
            $assignments_obj->delete_assignment($assignment->id);
        }
    }
    foreach ($args["value"] as $key => $assignment) {
        if (count($assignment) > 1) {
            try {
                $from_date = new DateTime('@' . ($assignment["from_date"] ?? ""));
                $to_date = new DateTime('@' . $assignment["to_date"]);
            } catch (Exception $e) {
                $from_date = "";
                $to_date = "";
            }
            [$success, $result] = $assignments_obj->create_or_edit_from_input(
                $assignment["id"] ?? "",
                $assignment["box_id"],
                $args["id"],
                $from_date,
                $to_date
            );
            if (!$success) {
                add_flash_notice("Zuordnung Nummer " . ($key + 1) . " konnte nicht gespeichert werden. Fehler: " . $result, "error");
            }
        } else if (!empty($assignment["id"])) {
            $assignments_obj->delete_assignment($assignment["id"]);
        }
    }

    return true;
}, 10, 4];

$admin_metabox_functions[]=["cmb2_save_field_assigned_box", function ($updated, $action, $field) {
    $assignments_obj = new Box_Assignments();
    switch ($action) {
        case "updated":
            $assignments_obj->add_or_update_location_assignment($field->value, $field->object_id)->slug;
            break;
        case "removed":
            $assignments_obj->end_location_assignment($field->object_id);
            break;
    }

    return true;
}, 10, 4];


define("img_dir", "wp-content/photobuzz-images");


function get_options_array_dirs($field)
{
    if (!function_exists('get_home_path')) {
        require_once(dirname(__FILE__) . '/../../../wp-admin/includes/file.php');
    }
    $dirs     = scandir(get_home_path() . "/" . img_dir);
    $dropdown = array();
    foreach ($dirs as $dir) {
        if (is_dir(get_home_path() . "/" . img_dir . '/' . $dir) && $dir != "." && $dir != "..") {
            $dropdown[$dir] = $dir;
        }
    }
    $dirs     = scandir(get_home_path() . "/" . img_dir . "/demo");
    foreach ($dirs as $dir) {
        if (is_dir(get_home_path() . "/" . img_dir . "/demo/" . $dir) && $dir != "." && $dir != "..") {
            $dropdown["demo/" . $dir] = "demo/" . $dir;
        }
    }
    return $dropdown;
}



function box_id_selectors($field_args, $field)
{

    $out = '<div style="margin-top:10px; display:flex; gap:5px;">';
    $js = "";
  
    foreach (get_terms_from_main([
        'taxonomy'   => 'location', 'hide_empty' => false, 'meta_key' => 'location_type', 'meta_value' => 'partner'
    ]) as $term) {
        $out .= '<a id="' . $field_args["id"] . $term->slug . '" class="button-secondary">' . $term->name . "</a>";
        $js .= 'jQuery("#' . $field_args["id"] . $term->slug . '").on("click",function(){
            jQuery("#' . $field_args["id"] . '").val("loc:' . $term->term_id . '");
        });';
    }
    foreach (get_terms_from_main([
        'taxonomy'   => 'fotobox', 'hide_empty' => false
    ]) as $term) {
        $out .= '<a id="' . $field_args["id"] . $term->slug . '" class="button-secondary">' . $term->name . "</a>";
        $js .= 'jQuery("#' . $field_args["id"] . $term->slug . '").on("click",function(){
            jQuery("#' . $field_args["id"] . '").val("' . $term->name . '");
        });';
    }
    $out .= "</div>";
    return $out . "<script>" . $js . "</script>";
}
/**
 * Define the metabox and field configurations.
 */
$admin_metabox_functions[]=['cmb2_admin_init', function () {
    function hide_on_fcb()
    {
        if (is_fcb()) {
            return "hidden";
        } else return "";
    }


    // Forobox zuordnung box
    $cmb = new_cmb2_box(array(
        'id'            => 'assignments_metabox',
        'title'         => __('Zuordnungen', 'cmb2'),
        'object_types'  => array('event',), // Post type
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // Keep the metabox closed by default
    ));


    $group_field_id = $cmb->add_field(array(
        'id'          => 'event_assignments',
        'type'        => 'group',
        'options'     => array(
            'group_title'       => __('Zuordnung {#}', 'cmb2'), // since version 1.1.4, {#} gets replaced by row number
            'add_button'        => __('Add Another Entry', 'cmb2'),
            'remove_button'     => __('Remove Entry', 'cmb2'),
            'sortable'          => false,
            // 'closed'         => true, // true to have the groups closed by default
            // 'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'cmb2' ), // Performs confirmation before removing group.
        ),
    ));

    $cmb->add_group_field($group_field_id, array(
        'name'       => __('Assignment id', 'cmb2'),
        'id'         => 'id',
        'type'       => 'hidden',
    ));
    // Regular text field
    if (!is_fcb()) {
        $cmb->add_group_field($group_field_id, array(
            'name'       => __('Box ID', 'cmb2'),
            'id'         => 'box_id',
            'type'       => 'text',
            'after'      => 'box_id_selectors'
        ));
    } else {
        $cmb->add_group_field($group_field_id, array(
            'name'       => __('Box ID', 'cmb2'),
            'id'         => 'box_id',
            'type'       => 'hidden',
            'default' => "fcb",
        ));
    }

    $cmb->add_group_field($group_field_id, array(
        'name' => __('Anfang', 'cmb2'),
        'id'   => 'from_date',
        'type' => 'text_datetime_timestamp',
        'time_format' => "H:i",
        'date_format' => "d.m.Y",
    ));
    $cmb->add_group_field($group_field_id, array(
        'name' => __('Ende', 'cmb2'),
        'id'   => 'to_date',
        'type' => 'text_datetime_timestamp',
        'time_format' => "H:i",
        'date_format' => "d.m.Y",
    ));


    /**
     * Initiate the metabox
     */
    $cmb = new_cmb2_box(array(
        'id'            => 'bilder_cmb',
        'title'         => __('Fotos', 'cmb2'),
        'object_types'  => array('event',), // Post type
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // Keep the metabox closed by default
    ));

    // Regular text field
    /*$cmb->add_field(array(
		'name'       => __('Box-Nummer', 'cmb2'),
		'desc'		 => "Gilt nur für v1 Fotoboxen",
		'id'         => 'boxid',
		'type'       => 'text_small',
		'show_on_cb' => 'is_main',
	));*/

    // URL text field
    $cmb->add_field(array(
        'name' => __('Verzeichnis', 'cmb2'),
        'desc' => __('unbedingt auswählen', 'cmb2'),
        'id'   => 'directory',
        'type' => 'select',
        'show_option_none' => 'Verzeichnis auswählen',
        'options_cb' => "get_options_array_dirs",
        'show_on_cb' => 'is_not_fcb',
    ));
    if (is_fcb()) {
        $cmb->add_field(array(
            'id'   => 'directory',
            'type' => 'hidden',
            'show_on_cb' => 'is_fcb',
            'default' => 'allianz_arena'
        ));
    }

    function escape_overlay($value, $field_args, $field)
    {
        if (is_string($value) && strlen($value) > 10)
            $value = $value . "?" . generateRandomString();
        return $value;
    }
    $cmb->add_field(array(
        'name'    => 'Overlay',
        'id'      => 'overlay',
        'type'    => 'file',
        'desc'    => 'Transparentes PNG 2306x1534 Pixel',
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
        ),
        // query_args are passed to wp.media's library query.
        'query_args' => array(

            'type' => array(

                'image/png',
            ),
        ),
        'preview_size' => 'large',
        'escape_cb' => 'escape_overlay',
    ));
    $cmb->add_field(array(
        'name'    => 'Gespiegeltes Overlay',
        'id'      => 'overlay_mir',
        'type'    => 'file',
        'desc'    => 'Transparentes PNG 2306x1534 Pixel',
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
        ),
        // query_args are passed to wp.media's library query.
        'query_args' => array(

            'type' => array(

                'image/png',
            ),
        ),
        'preview_size' => 'large',
        'escape_cb' => 'escape_overlay',
    ));
    $cmb->add_field(array(
        'name'    => 'Hochkant Overlay',
        'id'      => 'overlay_pt',
        'type'    => 'file',
        'desc'    => 'Transparentes PNG 720x1080 Pixel',
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
        ),
        // query_args are passed to wp.media's library query.
        'query_args' => array(

            'type' => array(

                'image/png',
            ),
        ),
        'preview_size' => 'large',
        'escape_cb' => 'escape_overlay',
        'show_on_cb' => 'is_main',
    ));
    $cmb->add_field(array(
        'name'       => "Header und Thumbnail-Bild",
        'id'         => '_thumbnail',
        'type'       => 'file',
        'options' => array(
            'url' => false, // Hide the text input for the url
        ),
        'show_on_cb' => 'is_main',
    ));
    $cmb->add_field(array(
        'name'           => 'Eventtyp',
        'id'             => 'fcb_eventtype',
        'taxonomy'       => 'eventtype', //Enter Taxonomy Slug
        'type'           => 'taxonomy_multicheck_inline',
        // Optional :
        'text'           => array(
            'no_terms_text' => 'Sorry, no terms could be found.' // Change default text. Default: "No terms"
        ),
        'remove_default' => 'true', // Removes the default metabox provided by WP core.
        // Optionally override the args sent to the WordPress get_terms function.
        'query_args' => array(
            // 'orderby' => 'slug',
            // 'hide_empty' => true,
        ),
        'default' => is_fcb() ? ["greenscreen", "public"] : [],
        'classes_cb' => "hide_on_fcb",
    ));
    $cmb->add_field(array(
        'name'       => "CSS Klassen",
        'id'         => 'classes',
        'type'       => 'text',
        'show_on_cb' => 'is_main',
    ));
    $cmb->add_field(array(
        'name'    => 'Hintergrund 1',
        'id'      => 'background_1',
        'desc'    => 'JPG 2306x1534 Pixel',
        'type'    => 'file',
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
        ),
        // query_args are passed to wp.media's library query.
        'preview_size' => 'small',
        'show_on_cb' => 'is_fcb',
    ));
    $cmb->add_field(array(
        'name'    => 'Hintergrund 2',
        'id'      => 'background_2',
        'desc'    => 'JPG 2306x1534 Pixel',
        'type'    => 'file',
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
        ),
        // query_args are passed to wp.media's library query.
        'preview_size' => 'small',
        'show_on_cb' => 'is_fcb',
    ));
    $cmb->add_field(array(
        'name'    => 'Kassenzettel Werbeblock',
        'id'      => 'receipt_ads',
        'type'    => 'file',
        'desc'    => "Bild mit 576 Pixel breite, Höhe beliebig. Druckbreite 72mm.",
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
        ),
        // query_args are passed to wp.media's library query.
        'preview_size' => 'small',
        'show_on_cb' => 'is_fcb',
    ));
    $cmb->add_field(array(
        'name'    => 'Gewinnspiel',
        'id'      => 'raffle',
        'desc'    => 'Hier das zugehörige Gewinnspiel verknüpfen',
        'type' => 'custom_attached_posts',
        'column'  => is_fcb(), // Output in the admin post-listing as a custom column. https://github.com/CMB2/CMB2/wiki/Field-Parameters#column
        'options' => array(
            'show_thumbnails' => false, // Show thumbnails on the left
            'filter_boxes'    => false, // Show a text box for filtering the results
            'query_args'      => array(
                'posts_per_page' => 10,
                'post_type'      => 'raffle',
            ), // override the get_posts args
        ),

        //'show_on_cb' => 'is_fcb',
    ));

    /**
     * Initiate the metabox
     */
    $cmb = new_cmb2_box(array(
        'id'            => 'teilnahme_cmb',
        'title'         => __('Teinahme', 'cmb2'),
        'object_types'  => array('raffle',), // Post type
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // Keep the metabox closed by default

    ));

    // Regular text field
    $cmb->add_field(array(
        'name'       => __('Teilnahmebedingungen', 'cmb2'),
        'id'         => 'teilnahme',
        'type'       => 'wysiwyg',
        'options' => array(
            //'tinymce' => array(
            //'wp_skip_init' => true,
            //)
        ),
        'before_row'    => function () {
            echo '<div class="cmb-row"><div class="cmb-th">Teilnehmer herunterladen</div><div class="cmb-td"><a class="button" href="' . get_permalink() . '?download=1">Teilnehmer herunterladen</a></div></div>';
        },
    ));
},10,1];


//Assignment
$admin_metabox_functions[]=['cmb2_admin_init', function ()
{
    // Pre-filled, mehrere metaboxen sind so nich möglich
    if (isset($_GET["edit"])) {
        $assignments = new PhotoBuzz\Box_Assignments();
        $assignment = $assignments->get_assignment($_GET["edit"]);
        $defaults = [
            "box_id" => $assignment->box_id,
            "assigned_event" => $assignment->event_id,
            "from_time" =>  DateTime::createFromFormat("Y-m-d H:i:s", $assignment->from_date)->getTimestamp(),
            "to_time" => DateTime::createFromFormat("Y-m-d H:i:s", $assignment->to_date)->getTimestamp(),
        ];
    }

    if (isset($_POST)) {
        $defaults = [
            "box_id" => $_POST["box_id"] ?? $defaults["box_id"] ?? "",
            "assigned_event" => $_POST["assigned_event"] ?? $defaults["assigned_event"] ?? "",
            "from_time" => isset($_POST["from_time"]) && ($_POST["from_time"]["date"] && $_POST["from_time"]["time"]) ? DateTime::createFromFormat("d.m.Y H:i", $_POST["from_time"]["date"] . " " . $_POST["from_time"]["time"])->getTimestamp()  : $defaults["from_time"] ?? "",
            "to_time" => isset($_POST["to_time"]) && ($_POST["to_time"]["date"] && $_POST["to_time"]["time"])  ? DateTime::createFromFormat("d.m.Y H:i", $_POST["to_time"]["date"] . " " . $_POST["to_time"]["time"])->getTimestamp()  : $defaults["to_time"] ?? "",
        ];
    }



    /**
     * Initiate the metabox
     */
    $cmb = new_cmb2_box(array(
        'id'            => 'assignment_metabox',
        'title'         => __('Zuordnung', 'cmb2'),
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
    ));

    // Regular text field
    $cmb->add_field(array(
        'name'       => __('Box ID', 'cmb2'),
        'id'         => 'box_id',
        'type'       => 'text',
        'default'      => $defaults["box_id"],
    ));

    $cmb->add_field(array(
        'name' => __('Zugeordnetes Event', 'cmb2'),
        'desc' => __('Nur eins wählen', 'cmb2'),
        'id'   => 'assigned_event',
        'type' => 'custom_attached_posts',
        'column'  => true, // Output in the admin post-listing as a custom column. https://github.com/CMB2/CMB2/wiki/Field-Parameters#column
        'default'      => $defaults["assigned_event"],
        'options' => array(
            'show_thumbnails' => true, // Show thumbnails on the left
            'filter_boxes'    => true, // Show a text box for filtering the results
            'query_args'      => array(
                'posts_per_page' => 10,
                'post_type'      => 'event',
                'post_status' => ["publish", "private", "future"]
            ), // override the get_posts args
        ),
    ));
    $cmb->add_field(array(
        'name' => __('Anfang', 'cmb2'),
        'id'   => 'from_time',
        'type' => 'text_datetime_timestamp',
        'time_format' => "H:i",
        'date_format' => "d.m.Y",
        'default'      => $defaults["from_time"]
    ));
    $cmb->add_field(array(
        'name' => __('Ende', 'cmb2'),
        'id'   => 'to_time',
        'type' => 'text_datetime_timestamp',
        'time_format' => "H:i",
        'date_format' => "d.m.Y",
        'default'      => $defaults["to_time"]
    ));
},10,1];
