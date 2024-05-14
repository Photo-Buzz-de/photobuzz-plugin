<?php

namespace PhotoBuzz;

use DivisionByZeroError;

class Statistics
{

    public $photos = 0;
    public $raffles = 0;
    public $visited = 0;
    public $newsletter=0;
    public $data = [];
    public $median_distance = 0;

    function __construct($id)
    {

        if (get_post_type($id) == "raffle") {

            // The Query.
            $the_query = new \WP_Query([
                "post_type" => "event",
                "meta_key" => "raffle",
                "meta_value" => serialize(array(0 => (string) $id))

            ]);
            $ids = array();

            // The Loop.
            if ($the_query->have_posts()) {
                while ($the_query->have_posts()) {
                    $the_query->the_post();
                    array_push($ids, get_the_ID());
                }
            }
            $this->fetch_data($ids);
        } else {
            $this->fetch_data(array($id));
        }
        if (count($this->data) > 2) {
            $distances = array_map(function ($x) {
                return intval($x->dist);
            }, $this->data);
            sort($distances);
            array_shift($distances);
            if (count($distances) % 2) {
                $this->median_distance = $distances[intdiv(count($distances), 2)];
            } else {
                $this->median_distance = ($distances[intdiv(count($distances), 2)] + $distances[intdiv(count($distances), 2) - 1]) / 2;
            }
        }
    }

    private function fetch_data($ids)
    {
        global $wpdb;
        $where = [];
        foreach ($ids as $id) {
            $assignments = new Box_Assignments();
            $ass = $assignments->get_assignments_by_event($id);

            if (count($ass)) {
                foreach ($ass as $a) {
                    array_push($where, $wpdb->prepare(
                        "(ass.id=%d AND directory='%s' AND date<UNIX_TIMESTAMP(to_date) AND date>UNIX_TIMESTAMP(from_date))",
                        $a->id,
                        get_post_meta($id, "directory", true)
                    ));
                }
            }
        }

        $query =
            "SELECT random_key, date, visited, newsletter, date- LAG(date,1) OVER (ORDER BY date) as dist, raffle.id as raffle FROM `wp_photobuzz_box_assignment` as ass,wp_photobuzz_event_images as img 
        LEFT JOIN wp_photobuzz_raffle_participants as raffle ON raffle.image_code=random_key 
        WHERE " . implode(" OR ", $where) . " 
        GROUP BY random_key 
        ORDER BY date";
        $this->data = $wpdb->get_results($query);


        $query_sum = "SELECT COUNT(random_key) as photos,COUNT(raffle) as raffles, SUM(visited) as visited, SUM(newsletter) as newsletter FROM (" . $query . ") as abc ";
        $row = $wpdb->get_row($query_sum);
        if (!empty($row)) {
            $this->photos = $row->photos;
            $this->raffles = $row->raffles;
            $this->visited = $row->visited ?? 0;
            $this->newsletter = $row->newsletter ?? 0;
        }
    }
    public function get_raffle_percentage()
    {
        try {
            return number_format($this->raffles / $this->photos * 100, 0);
        } catch (DivisionByZeroError $e) {
            return "0";
        }
    }
    public function get_visited_percentage()
    {
        try {
            return number_format($this->visited / $this->photos * 100, 0);
        } catch (DivisionByZeroError $e) {
            return "0";
        }
    }
    public function get_newsletter_percentage()
    {
        try {
            return number_format($this->newsletter / $this->photos * 100, 0);
        } catch (DivisionByZeroError $e) {
            return "0";
        }
    }
}
