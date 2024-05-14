<?php

/**
 * Plugin Name:       Admin Table Tutorial
 * Plugin URI:        www.vijayan.in
 * Description:       This plugin is created for the purpose to understand the WordPress admin table.
 * Author:            Vijayan
 * Author URI:        www.vijayan.in
 * Text Domain:       admin-table-tut
 * Domain Path:       /languages
 * Version:           0.1.0
 * Requires at least: 5.4
 * Requires PHP:      7.2
 *
 * @package         Admin_Table_Tut
 */

namespace Vijayan;

use DateInterval;
use DateTime;
use DateTimeZone;

defined('ABSPATH') || exit;

/**
 * Adding WP List table class if it's not available.
 */
if (!class_exists(\WP_List_Table::class)) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
require_once __DIR__ . "/../vendor/autoload.php";

use PhotoBuzz\Box_Assignments;

/**
 * Class Drafts_List_Table.
 *
 * @since 0.1.0
 * @package Admin_Table_Tut
 * @see WP_List_Table
 */
class Drafts_List_Table extends \WP_List_Table
{

    /**
     * Const to declare number of posts to show per page in the table.
     */
    const POSTS_PER_PAGE = 10;

    /**
     * Draft_List_Table constructor.
     */
    public function __construct()
    {

        parent::__construct(
            array(
                'singular' => 'Draft',
                'plural'   => 'Drafts',
                'ajax'     => false,
            )
        );
    }







    /**
     * Display text for when there are no items.
     */
    public function no_items()
    {
        esc_html_e('No posts found.', 'admin-table-tut');
    }

    /**
     * The Default columns
     *
     * @param  array  $item        The Item being displayed.
     * @param  string $column_name The column we're currently in.
     * @return string              The Content to display
     */
    public function column_default($item, $column_name)
    {
        $result = '';
        switch ($column_name) {
            case 'from_date':
            case 'to_date':
                $result = wp_date(get_option('date_format') . ' \u\m ' . get_option('time_format'), (new DateTime($item[$column_name], new DateTimeZone("Europe/Berlin")))->getTimestamp());
                break;

            case 'duration':
                $diff = (new DateTime($item["to_date"]))->diff(new DateTime($item["from_date"]));
                if ((new DateTime($item["from_date"]))->add(new DateInterval("P1D")) <= (new DateTime($item["to_date"]))) {
                    $result = $diff->format("%a Tage, %h Stunden");
                } else {
                    $result = $diff->format("%h Stunden");
                }
                break;

            default:
                $result = $item[$column_name];
                break;
        }

        return $result;
    }


    /**
     * Get list columns.
     *
     * @return array
     */
    public function get_columns()
    {
        return array(
            'cb'     => '<input type="checkbox"/>',
            'box_id'  => __('Box ID', 'admin-table-tut'),
            'event_id'   => __('Event', 'admin-table-tut'),
            'from_date'   => __('Start', 'admin-table-tut'),
            'to_date' => __('Ende', 'admin-table-tut'),
            'duration' => __('Dauer', 'admin-table-tut'),

        );
    }

    /**
     * Return title column.
     *
     * @param  array $item Item data.
     * @return string
     */
    public function column_box_id($item)
    {
        global $pagenow;
        $edit_url  = "?page=photobuzz-new-assignment&edit=" . urlencode($item["id"]); //get_edit_post_link($item['id']);
        $post_link = "#"; //get_permalink($item['id']);

        $output = '<strong>';

        /* translators: %s: Post Title */
        $output .= '<a class="row-title" href="' . esc_url($edit_url) . '" aria-label="' . sprintf(__("&#8220;%s&#8221; (Edit)"), $item['box_id']) . '">' . esc_html($item['box_id']) . '</a>';
        $now = new DateTime();
        $from = new DateTime($item["from_date"]);
        $to = new DateTime($item["to_date"]);
        if (($now >= $from) && ($now <= $to)) {
            $output .= " — <span class=\"post-state\">Aktuell</span>";
        }
        $output .= '</strong>';

        // Get actions.
        $actions = array(
            'edit'  => '<a href="' . esc_url($edit_url) . '"> Zuordnung ' . __('Edit') . '</a>',
            'trash' => '<a data-assignment-confirm-delete href="' . wp_nonce_url("?page=" . $_GET["page"] . "&action=delete&assignment_id=" . $item['id'], "delete") . '" class="submitdelete">' . "Löschen" . '</a>'
        );

        $row_actions = array();

        foreach ($actions as $action => $link) {
            $row_actions[] = '<span class="' . esc_attr($action) . '">' . $link . '</span>';
        }

        $output .= '<div class="row-actions">' . implode(' | ', $row_actions) . '</div>';

        return $output;
    }

    public function column_event_id($item)
    {
        switch_to_blog($item['blog_id']);
        $edit_url    = get_edit_post_link($item['event_id']);
        $post_link   = get_permalink($item['event_id']);
        $delete_link = get_delete_post_link($item['event_id']);

        $output = '<strong>';

        /* translators: %s: Post Title */
        $output .= '<a class="row-title" href="' . esc_url($edit_url) . '" aria-label="' . sprintf(__('%s (Edit)'), get_the_title($item['event_id'])) . '">' . esc_html(get_the_title($item['event_id'])) . '</a>';
        $output .= _post_states(get_post($item['event_id']), false);
        $output .= '</strong>';

        // Get actions.
        $actions = array(
            'edit'  => '<a href="' . esc_url($edit_url) . '">' . __('Edit') . '</a>',
            'view'  => '<a href="' . esc_url($post_link) . '">' . __('View') . '</a>',
        );

        $row_actions = array();

        foreach ($actions as $action => $link) {
            $row_actions[] = '<span class="' . esc_attr($action) . '">' . $link . '</span>';
        }

        $output .= '<div class="row-actions">' . implode(' | ', $row_actions) . '</div>';

        return $output;
    }

    /**
     * Column cb.
     *
     * @param  array $item Item data.
     * @return string
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s_id[]" value="%2$s" />',
            esc_attr($this->_args['singular']),
            esc_attr($item['id'])
        );
    }

    /**
     * Prepare the data for the WP List Table
     *
     * @return void
     */
    public function prepare_items()
    {
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();
        $hidden                = array();
        $primary               = 'title';
        $this->_column_headers = array($columns, $hidden, $sortable, $primary);
        $data                  = array();

        $this->process_bulk_action();
        $assignments = new Box_Assignments();

        $display_assignments = $assignments->get_assignments();

        if ($display_assignments) {

            foreach ($display_assignments as $assignment) {
                if (isset($_GET["type"]) && $_GET["type"] != "") {
                    $terms = get_the_terms($assignment->event_id, "eventtype");
                    if ($terms) {
                        $terms = array_map(fn ($value) => $value->slug, $terms);
                    } else {
                        $terms = [];
                    }
                }
                if (!isset($_GET["type"]) || $_GET["type"] == "" || ($_GET["type"] == "normal" && !in_array("demo", $terms)) || ($_GET["type"] == "demo" && in_array("demo", $terms))) {

                    $data[$assignment->id] = (array) $assignment;
                }
            }
        }

        $this->items = $data;

        /*$this->set_pagination_args(
            array(
                'total_items' => $get_posts_obj->found_posts,
                'per_page'    => $get_posts_obj->post_count,
                'total_pages' => $get_posts_obj->max_num_pages,
            )
        );*/
    }

    /**
     * Get bulk actions.
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        return array(
            'trash' => __('Move to Trash', 'admin-table-tut'),
        );
    }

    /**
     * Get bulk actions.
     *
     * @return void
     */
    public function process_bulk_action()
    {
        if ('trash' === $this->current_action()) {
            $post_ids = filter_input(INPUT_GET, 'draft_id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

            if (is_array($post_ids)) {
                $post_ids = array_map('intval', $post_ids);

                if (count($post_ids)) {
                    array_map('wp_trash_post', $post_ids);
                }
            }
        }
    }

    /**
     * Generates the table navigation above or below the table
     *
     * @param string $which Position of the navigation, either top or bottom.
     *
     * @return void
     */
    protected function display_tablenav($which)
    {
?>
        <div class="tablenav <?php echo esc_attr($which); ?>">

            <?php if ($this->has_items()) : ?>
                <div class="alignleft actions bulkactions">
                    <?php $this->bulk_actions($which); ?>
                </div>
            <?php
            endif;
            $this->extra_tablenav($which);
            $this->pagination($which);
            ?>

            <br class="clear" />
        </div>
    <?php
    }

    /**
     * Overriden method to add dropdown filters column type.
     *
     * @param string $which Position of the navigation, either top or bottom.
     *
     * @return void
     */
    protected function extra_tablenav($which)
    {

        if ('top' === $which) {
            $drafts_dropdown_arg = array(
                'options'   => array('' => 'Alle', "normal" => "Ohne Demo", "demo" => "Demo"),
                'container' => array(
                    'class' => 'alignleft actions',
                ),
                'label'     => array(
                    'class'      => 'screen-reader-text',
                    'inner_text' => __('Filter by Post Type', 'admin-table-tut'),
                ),
                'select'    => array(
                    'name'     => 'type',
                    'id'       => 'filter-by-type',
                    'selected' => filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING),
                ),
            );

            $this->html_dropdown($drafts_dropdown_arg);

            submit_button(__('Filter', 'admin-table-tut'), 'secondary', 'action', false);
        }
    }

    /**
     * Navigation dropdown HTML generator
     *
     * @param array $args Argument array to generate dropdown.
     *
     * @return void
     */
    private function html_dropdown($args)
    {
    ?>

        <div class="<?php echo (esc_attr($args['container']['class'])); ?>">
            <label for="<?php echo (esc_attr($args['select']['id'])); ?>" class="<?php echo (esc_attr($args['label']['class'])); ?>">
            </label>
            <select name="<?php echo (esc_attr($args['select']['name'])); ?>" id="<?php echo (esc_attr($args['select']['id'])); ?>">
                <?php
                foreach ($args['options'] as $id => $title) {
                ?>
                    <option <?php if ($args['select']['selected'] === $id) { ?> selected="selected" <?php } ?> value="<?php echo (esc_attr($id)); ?>">
                        <?php echo esc_html(\ucwords($title)); ?>
                    </option>
                <?php
                }
                ?>
            </select>
        </div>

    <?php
    }

    /**
     * Include the columns which can be sortable.
     *
     * @return Array $sortable_columns Return array of sortable columns.
     */
    public function get_sortable_columns()
    {

        return array(
            'title'  => array('title', false),
            'type'   => array('type', false),
            'date'   => array('date', false),
            'author' => array('author', false),
        );
    }
}




/**
 * This function is responsible for render the drafts table
 */
function bootload_drafts_table(): void
{
    $drafts_table = new Drafts_List_Table();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Zuordnung der Fotoboxen', 'admin-table-tut'); ?></h1>
        <a href="?page=photobuzz-new-assignment" class="page-title-action">Erstellen</a>


        <hr class="wp-header-end">

        <?php do_action('admin_notices') ?>
        <form id="all-drafts" method="get">
            <input type="hidden" name="page" value="photobuzz-manage-menu" />

            <?php
            $drafts_table->prepare_items();
            $drafts_table->display();
            ?>
        </form>
    </div>
<?php
}
if (isset($_GET["action"])) {
    if ($_GET["action"] == "delete" && isset($_GET["assignment_id"]) && check_admin_referer("delete")) {

        $assignments = new Box_Assignments();
        $result = $assignments->delete_assignment($_GET["assignment_id"]);

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible">
          <p>Zuordnung wurde gelöscht</p>
          </div>';
        });
    }
}

bootload_drafts_table() ?>