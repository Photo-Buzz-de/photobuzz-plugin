<?php

namespace PhotoBuzz;

use DateTime;
use DateTimeZone;

class Box_Assignments
{

    function __construct()
    {
    }

    function get_current_assignment($box_id, $time = null)
    {
        if (is_null($time)) $time = new DateTime(current_time("c"));
        $assignments = $this->get_assignments($box_id, null, $time);
        if (empty($assignments)) {
            return null;
        }
        foreach ($assignments as $ass) {
            $terms = wp_get_post_terms($ass->event_id, "eventtype");
            $term_names = array_map(fn ($x) => $x->name, $terms);
            if (!in_array("Demo", $term_names)) {
                return $ass;
            }
        }
        return $assignments[0];
    }

    function get_assignments($box_id = NULL, $event_id = NULL, $time_in_range = NULL, $limit = null)
    {
        global $wpdb;
        $fields = array();
        $values = array();
        if ($box_id) {
            $fields[] = "(b.box_id = %s OR l.box_id = %s)";
            $values[] = $box_id;
            $values[] = $box_id;
        }
        if ($event_id) {
            $fields[] = "event_id = %s";
            $values[] = $event_id;
        }
        $query = "SELECT b.id as id, b.from_date as from_date, b.to_date as to_date, b.event_id as event_id, b.blog_id as blog_id,b.box_id as box_id, l.location_id as location_id FROM wp_photobuzz_box_assignment b LEFT JOIN wp_photobuzz_location_assignment l ON CONCAT('loc:',l.location_id) = b.box_id AND b.from_date>l.from_date";
        if ($fields) {
            $query .= " WHERE " . implode(' AND ', $fields);
            $query = $wpdb->prepare($query, $values);
        }
        if ($time_in_range) {
            if ($fields) {
                $query .= " AND ";
            } else {
                $query .= " WHERE ";
            }
            $time_str = $time_in_range->format("Y-m-d H:i:s");
            $query = $wpdb->prepare($query . "b.from_date <= %s AND b.to_date >= %s", [$time_str, $time_str]);
        }

        $query .= " ORDER BY from_date DESC";
        if (!is_null($limit)) {
            $query = $wpdb->prepare($query . " LIMIT %d", [$limit]);
        }

        return $wpdb->get_results($query);
    }
    function get_assignments_by_location($location_id,$limit=null){
        global $wpdb;
        $query=$wpdb->prepare("SELECT b.id,b.from_date,b.to_date,b.event_id,b.blog_id,b.box_id FROM `wp_photobuzz_box_assignment` b 
                                LEFT JOIN wp_photobuzz_location_assignment l ON b.box_id=l.box_id AND b.from_date>l.from_date AND b.to_date<l.to_date 
                                WHERE b.box_id='loc:%d' OR l.location_id=%d ORDER BY b.to_date DESC",$location_id,$location_id);
        if (!is_null($limit)) {
            $query = $wpdb->prepare($query . " LIMIT %d", $limit);
        }
        return $wpdb->get_results($query);                       
    }

    function get_assignment($id)
    {
        global $wpdb;

        $query = "SELECT * FROM wp_photobuzz_box_assignment";
        $query .= " WHERE id=%s";
        $query = $wpdb->prepare($query, $id);

        return $wpdb->get_results($query)[0];
    }
    function get_assignments_by_event($event_id)
    {
        global $wpdb;

        $query = "SELECT * FROM wp_photobuzz_box_assignment";
        $query .= " WHERE event_id=%s";
        $query = $wpdb->prepare($query, $event_id);

        return $wpdb->get_results($query);
    }

    function delete_assignment($id)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("DELETE FROM wp_photobuzz_box_assignment WHERE id = %s", $id));
    }
    function add_assignment($data)
    {
        global $wpdb;
        $data["from_date"] = $data["from_date"]->format("Y-m-d H:i:s");
        $data["to_date"] = $data["to_date"]->format("Y-m-d H:i:s");
        if ($wpdb->insert("wp_photobuzz_box_assignment", $data)) {
            return $wpdb->insert_id;
        } else return false;
    }
    function edit_assignment($data)
    {
        global $wpdb;
        $data["from_date"] = $data["from_date"]->format("Y-m-d H:i:s");
        $data["to_date"] = $data["to_date"]->format("Y-m-d H:i:s");
        $id = $data["id"];
        unset($data["id"]);
        if ($wpdb->update("wp_photobuzz_box_assignment", $data, ["id" => $id]) !== false) {
            return true;
        } else return false;
    }
    function create_or_edit_from_input($id, $box_id, $assigned_event, $from_date, $to_date, $blog_id = null)
    {
        $new_assigment = [];
        $new_assigment["box_id"] = $box_id;
        $new_assigment["event_id"] = $assigned_event;
        $new_assigment["from_date"] = $from_date;
        $new_assigment["to_date"] = $to_date;
        $new_assigment["blog_id"] = $blog_id ?? get_current_blog_id();
        if (empty($new_assigment["box_id"])) {
            return [false, "Box ID darf nich leer sein!"];
        } else if (empty($new_assigment["event_id"])) {
            return [false, "Event darf nich leer sein!"];
        } else if (!$new_assigment["from_date"]) {
            return [false, "Anfangszeit darf nicht leer und muss eine gültige Zeit sein!"];
        } else if (!$new_assigment["to_date"]) {
            return [false, "Endzeit darf nicht leer und muss eine gültige Zeit sein!"];
        } else if ($new_assigment["to_date"] <= $new_assigment["from_date"]) {
            return [false, "Endzeit muss nach der Anfangszeit sein!"];
        } else {
            //hinzufügen
            if (empty($id)) {
                $result = $this->add_assignment($new_assigment);
            } else {
                $new_assigment["id"] = $id;
                $result = $this->edit_assignment($new_assigment);
            }
            if (!$result) {
                return [false, "Datenbankfehler!"];
            } else {
                return [true, $result];
            }
        }
    }

    function add_or_update_location_assignment($box_id, $location_id)
    {
        global $wpdb;
        $now=(new DateTime("now",new DateTimeZone("Europe/Berlin")))->format("Y-m-d H:i:s");
        $data=[];
        $data["box_id"]=$box_id;
        $data["location_id"]=$location_id;
        $data["from_date"] = $now;
        $data["to_date"] = "9999-12-31 00:00:00";
        
        $this->end_location_assignment($location_id);
        if ($wpdb->insert("wp_photobuzz_location_assignment", $data)) {
            return $wpdb->insert_id;
        } else return false;
    }

    function end_location_assignment($location_id){
        global $wpdb;
        $now=(new DateTime("now",new DateTimeZone("Europe/Berlin")))->format("Y-m-d H:i:s");
        $query=$wpdb->prepare("UPDATE wp_photobuzz_location_assignment SET to_date=%s WHERE to_date>%s AND location_id=%s",$now,$now,$location_id);
        $wpdb->query($query);
    }

    function get_current_location_assignment($location_id)
    {
        global $wpdb;

        $query = "SELECT * FROM wp_photobuzz_location_assignment";
        $query .= " WHERE location_id=%s";
        $query = $wpdb->prepare($query, $location_id);

        return $wpdb->get_results($query);
    }
}


