<?php
defined('ABSPATH') || exit;

use PhotoBuzz\Box_Assignments;
use PhotoBuzz\Event_Images;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

require_once __DIR__ . "/../vendor/autoload.php";

function get_duration_diff_string($datetime)
{
    $timearr = [];
    $interval = ($datetime->diff(new DateTime()));
    if ($interval->d >= 2) $timearr[] = $interval->format("%d Tagen");
    else if ($interval->d >= 1) $timearr[] = $interval->format("%d Tag");
    if ($interval->h >= 2) $timearr[] = $interval->format("%h Stunden");
    else if ($interval->h >= 1) $timearr[] = $interval->format("%d Stunde");
    if ($interval->i >= 2) $timearr[] = $interval->format("%i Minuten");
    else if ($interval->i >= 1) $timearr[] = $interval->format("%i Minute");
    if ($interval->s >= 2) $timearr[] = $interval->format("%s Sekunden");
    else if ($interval->s >= 1) $timearr[] = $interval->format("%s Sekunde");
    return implode(", ", $timearr);
}

function print_box_card_content($box, $handshakes, $location = null)
{
    $formatter = new IntlDateFormatter('de_DE', IntlDateFormatter::FULL, IntlDateFormatter::FULL, new DateTimeZone("Europe/Berlin"));
    $formatter->setPattern('E, dd.MM.Y HH:mm');
    $assignments = new Box_Assignments();

    if (!empty($box)) {
?>

        <h2>Box ID: <a href="<?= get_edit_term_link($box) ?>"><?= $box->name ?></a><? $aka = get_term_meta($box->term_id, "alt_id");
                                                                                    if (!empty($aka)) {
                                                                                        echo ' <span class="sub">aka ' . $aka[0] . "</span>";
                                                                                    } ?></h2>
        <p><?= $box->description ?></p>
        <?php

        $ipid = null;
        if (array_key_exists($box->name, $handshakes)) $ipid = $box->name;
        else if (!empty($aka) && array_key_exists($aka[0], $handshakes)) $ipid = $aka[0];
        if (!is_null($ipid)) {
        ?>
            <h3>Letzter Handshake</h3>
        <?php
            echo $formatter->format($handshakes[$ipid]);
            echo "<br>vor ";
            echo get_duration_diff_string($handshakes[$ipid]);
        }
        $photos = Event_Images::getImagesByBoxID($box->name, 1);
        if (!empty($photos)) {
        ?>
            <h3>Letztes Foto</h3>
        <?php
            $lastpic = $photos[0]["date"];
            $lastpic = new DateTime($lastpic->format("Y-m-d\\TH:i:s"), new DateTimeZone("Europe/Berlin"));

            echo $formatter->format($lastpic);
            echo "<br>vor ";
            echo get_duration_diff_string($lastpic);
        }
    } else {
        ?>
        <h2>Aktuell keine Box zugeordnet</h2>
    <?php
    }
    ?>
    <h3>Events</h3>
    <?php
    if (isset($location)) {
        $ass = $assignments->get_assignments_by_location($location->term_id, 10);
    } else {
        $ass = $assignments->get_assignments($box->name, null, null, 10);
    }
    $now = new DateTime(current_time("c"));
    
    $current_assignment=null;
    $current_assignment_id=null;
    if ($box) {
        $current_assignment = $assignments->get_current_assignment($box->name);
        $current_assignment_id = empty($current_assignment) ? null : $current_assignment->id;
    }

    foreach ($ass as $as) {
        $from = new DateTime($as->from_date, new DateTimeZone("Europe/Berlin"));
        $to = new DateTime($as->to_date, new DateTimeZone("Europe/Berlin"));

        $class = $to < $now ? "past" : ($from < $now ? "present" : "future");
        if ($current_assignment_id == $as->id) {
            $class .= " current";
        }
        switch_to_blog($as->blog_id);

    ?>
        <div class="card event <?= $class ?>">
            <div><?= $formatter->format($from) ?> - <?= $formatter->format($to)  ?></div>
            <a class="row-title" href="<?= get_edit_post_link($as->event_id) ?>"><?= get_the_title($as->event_id) ?></a>
        </div>
<?php
        restore_current_blog();
    }
}



$handshakes = [];
try {
    $client = new Client([
        // Base URI is used with relative requests
        'base_uri' => 'http://host.docker.internal:8080',
        // You can set any number of default request options.
        'timeout'  => 2.0,
    ]);

    $response = $client->request('POST', '', [
        'headers' => [
            "Authorization" => "Token Wo4qMhqxqfzFqycKNJ25i5KEtH7y38ftDpbRfhJJEoTJ4X9U3PdqUxzzGk3yCoVejvsnLbwLPUtCE938oupKH2xMWk9dd3xGSjuK6nxLYxktCgbRkTVoFJiFU3zg3d35",
            "Content-Type" => "application/json",

        ],
        "body" => '{"jsonrpc": "2.0", "method": "ListPeers", "params": {}}'
    ]);

    $resp = json_decode($response->getBody())->result->peers;
    foreach ($resp as $itm) {
        if (!empty($itm->allowed_ips)) {
            $id = explode("/", explode(".", $itm->allowed_ips[0])[3])[0];
            if ($itm->last_handshake != "0001-01-01T00:00:00Z") {
                $handshakes[$id] = new DateTime($itm->last_handshake);
            }
        }
    }
} catch (ConnectException $e) {
}


?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Fotoboxen', 'admin-table-tut'); ?></h1>


    <hr class="wp-header-end">

    <?php do_action('admin_notices') ?>
    <div class="fotobox-table">
        <?php
        $locations = get_terms([
            "taxonomy" => 'location',
            'hide_empty' => false,
            'meta_key' => 'location_type',
            'meta_value' => 'partner'
        ]);
        $assigned_boxes = [];
        foreach ($locations as $location) {
        ?>
            <div class="card">
                <h3 class="sub">Location</h3>
                <h1><a href="<?= get_edit_term_link($location) ?>"><?= $location->name ?></a></h1>
                <?php if (!empty($box_assigned = get_term_meta($location->term_id, "assigned_box", true))) {
                    $assigned_boxes[] = $box_assigned;
                    print_box_card_content(get_term_by("name", $box_assigned, "fotobox"), $handshakes, $location);
                } else {
                    print_box_card_content(get_term_by("name", null, "fotobox"), $handshakes, $location);
                } ?>
            </div>
            <?php
        }

        $boxen = get_terms([
            "taxonomy" => 'fotobox',
            'hide_empty' => false,
            //'meta_key' => 'location_type',
            //'meta_value' => 'partner'
        ]);
        foreach ($boxen as $box) {
            if (!in_array($box->name, $assigned_boxes)) {
            ?>
                <div class="card">
                    <?php print_box_card_content($box, $handshakes); ?>
                </div>
        <?php
            }
        }
        ?>
    </div>


</div>
<style>
    .fotobox-table {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .fotobox-table>div {
        flex-grow: 1;
    }

    .fotobox-table a {
        text-decoration: none;
    }

    .fotobox-table h1 {
        padding: 0px;
        line-height: 1;
    }

    .fotobox-table h3.sub {
        margin-bottom: 5px;
    }

    .fotobox-table h2,
    .fotobox-table h3 {
        margin-top: 1em;
        margin-bottom: 0px;
    }

    .fotobox-table .event {
        margin-top: 10px;
    }

    .past {
        background-color: #d9d9d9;
    }

    .future {
        background-color: #ebb529;
    }

    .present {
        background-color: #1fe61f;

        background: repeating-linear-gradient(-45deg,
                #1fe61f,
                #1fe61f 10px,
                #d9d9d9 10px,
                #d9d9d9 20px);
    }

    .present.current {
        background: none;
        background-color: #1fe61f;
    }


    .sub {
        color: #aaa;
    }
</style>