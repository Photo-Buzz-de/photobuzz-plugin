<?php
require_once __DIR__ . "/../vendor/autoload.php";

use PhotoBuzz\Statistics;

if (!function_exists("is_admin") || !is_admin()) die("Not admin!");
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Statistik</h1><br>
    <h1 class="wp-heading-inline">Übersicht</h1>
    <div class="line">
        <?php
        $the_query = new WP_Query([
            "post_type" => ["event"],
            'posts_per_page' => 20
        ]);
        $photos = [];
        $raffles = [];
        $visited = [];
        $newsletter = [];
        // The Loop.
        if ($the_query->have_posts()) {
            while ($the_query->have_posts()) {
                $the_query->the_post();
                $stats = new Statistics(get_the_id());
                $title = get_the_date() . " " . html_entity_decode(apply_filters('the_title', get_the_title(), get_the_id()));
                $photos[] = array("x" => $title, "y" => $stats->photos);
                $visited[] = array("x" => $title, "y" => $stats->visited);
                $raffles[] = array("x" => $title, "y" => $stats->raffles);
                $newsletter[] = array("x" => $title, "y" => $stats->newsletter);
            }
        }

        ?>
        <canvas id="line"></canvas>
    </div>
    <h1 class="wp-heading-inline">Einzelne Events</h1>

    <div class="line">
        <form>

            <select name="zeitraum" id="event-selector">
                <option value="0" selected>Zeitraum auswählen</option>
                <?php
                // The Query.
                $the_query = new WP_Query([
                    "post_type" => ["raffle", "event"],
                    'posts_per_page' => -1
                ]);

                // The Loop.
                if ($the_query->have_posts()) {
                    while ($the_query->have_posts()) {
                        $the_query->the_post();
                        $prefix = get_post_type() == "raffle" ? "Gewinnspiel" : "Event";
                        $bgcol = get_post_type() == "raffle" ? "#95fca1" : "#95b4fc";
                        echo '<option value="' . get_the_ID() . '" style="background-color:' . $bgcol . ';">' . esc_html(get_the_title()) . '</option>';
                    }
                }
                ?>
            </select>
        </form>
    </div>
    <?php



    ?>
    <div class="line">
        <canvas id="donut"></canvas>
    </div>
    <div class="line">
        <div>
            <div id="photos" class="number" style="color:#DC052D">0</div> Geschossene Fotos
        </div>
        <div>
            <div class="number" style="color:#76232F">
                <span id="visited">0</span>
                (<span id="visited_percentage">0</span>%)
            </div> Abgerufene Fotos
        </div>
        <div>
            <div class="number" style="color:#002F6C">
                <span id="raffles">0</span>
                (<span id="raffles_percentage">0</span>%)
            </div> Gewinnspielteilnahmen
        </div>
        <div>
            <div class="number" style="color:#000">
                <span id="newsletter">0</span>
                (<span id="newsletter_percentage">0</span>%)
            </div> Newsletterabos
        </div>
    </div>
    <div class="line">
        <div>
            <div class="number"><span id="median_distance">0</span>s</div> Mittlerer Abstand zwischen den Fotos
        </div>
    </div>

</div>




<style>
    .number {
        font-size: 50px;
        line-height: normal;
    }

    .percentage {
        font-size: 50px;
        line-height: normal;
    }

    .line {
        display: flex;
        gap: 50px;
        margin-bottom: 50px;
        font-size: 24px;
        text-align: center;
        justify-content: center;
    }

    .line>div {}

    #donut {
        max-width: 500px;
        height: 500px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx_line = document.getElementById('line');

    const chart_line = new Chart(ctx_line, {
        type: 'line',
        data: {
            datasets: [{
                    label: ' Geschossene Fotos',
                    backgroundColor: ["#DC052D", "#DC052D"],
                    data: <?= json_encode(array_reverse($photos)) ?>,
                }, {
                    label: 'Abgerufene Fotos',
                    backgroundColor: ["#76232F", "#76232F"],
                    data: <?= json_encode(array_reverse($visited)) ?>,
                    circumference: 270
                }, {
                    label: 'Gewinnspielteilnahmen',
                    backgroundColor: ["#002F6C", "#002F6C"],
                    data: <?= json_encode(array_reverse($raffles)) ?>,
                    circumference: 270
                },
                {
                    label: 'Newsletterabos',
                    backgroundColor: ["#000000", "#000000"],
                    data: <?= json_encode(array_reverse($newsletter)) ?>,
                    circumference: 270
                }
            ]
        },
        options: {
            pointRadius: 5,
            pointHoverRadius: 7,
            responsive: true,
            aspectRatio: 1,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    //display: false
                }
            }
        }
    });

    const ctx = document.getElementById('donut');

    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Geschossene Fotos', 'Gewinnspielteilnahmen'],
            datasets: [{
                    label: ' Geschossene Fotos',
                    backgroundColor: ["#DC052D", "#DC052D"],
                    data: [1],
                }, {
                    label: 'Abgerufene Fotos',
                    backgroundColor: ["#76232F", "#76232F"],
                    data: [0],
                    circumference: 270
                }, {
                    label: 'Gewinnspielteilnahmen',
                    backgroundColor: ["#002F6C", "#002F6C"],
                    data: [0],
                    circumference: 270
                },
                {
                    label: 'Newsletterabos',
                    backgroundColor: ["#000000", "#000000"],
                    data: [0],
                    circumference: 270
                }
            ]
        },
        options: {
            responsive: true,
            aspectRatio: 1,
            maintainAspectRatio: false,
            rotation: 225,
            circumference: 270,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });






    jQuery("#event-selector").on("change", function(event) {
        if (event.target.value != "0") {
            var data = {
                'action': 'fcb_statistics',
                'id': event.target.value
            };
            jQuery.post(ajaxurl, data, function(response) {
                data = JSON.parse(response)
                jQuery("#photos").html(data.photos)
                jQuery("#visited").html(data.visited)
                jQuery("#visited_percentage").html(data.visited_percentage)
                jQuery("#raffles").html(data.raffles)
                jQuery("#raffles_percentage").html(data.raffles_percentage)
                jQuery("#newsletter").html(data.newsletter)
                jQuery("#newsletter_percentage").html(data.newsletter_percentage)
                jQuery("#median_distance").html(data.median_distance)
                chart.data.datasets[0].data = [data.photos]
                chart.data.datasets[1].data = [data.visited]
                chart.data.datasets[1].circumference = data.visited / data.photos * 270
                chart.data.datasets[2].data = [data.raffles]
                chart.data.datasets[2].circumference = data.raffles / data.photos * 270
                chart.data.datasets[3].data = [data.newsletter]
                chart.data.datasets[3].circumference = data.newsletter / data.photos * 270
                chart.update()

            });
        }

    })
    // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
</script>