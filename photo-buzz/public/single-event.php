<?php
$jsctr = 0;
require_once(__DIR__ . "/../includes/single_event_functions.php");
require_once(__DIR__ . "/../vendor/autoload.php");
if (isset($_GET['json'])) {

  the_post();
  header('Content-Type: application/json');
  $out   = array();
  $start = $_GET['start'] ?? 0;
  $num   = $_GET['num'] ?? 100000;

  $out['status'] = 'success';

  $images       = new PhotoBuzz\Event_Images(get_post_meta(get_the_ID(), 'directory', true));
  $event_images = $images->getImages($start, $num);

  if ($_GET['json'] == "diashow") {
    echo json_encode($event_images);
  } else {
    $hours = array();
    $current;
    $ctr = 0;
    foreach ($event_images as $img) {

      $date = $img['date'];
      $hour = intval($date->format('H'));
      if (!isset($current)) {
        $current = $hour;
      }
      if ($current == $hour) {
        if (count($hours) <= $ctr) {
          $hours[$ctr]['date']    = $date->format('m-d-H') . 'h';
          $hours[$ctr]['hournum'] = $date->format('H');
          $hours[$ctr]['markup'] = hour_markup($date, '', false);
        }

        $hours[$ctr]['data'][]  = $img;
      } else {
        $ctr++;
        $current                  = $hour;
        $hours[$ctr]['date']    = $date->format('m-d-H') . 'h';
        $hours[$ctr]['data'][]  = $img;
        $hours[$ctr]['hournum'] = $date->format('H');
        $hours[$ctr]['markup'] = hour_markup($date, '', false);
      }
    }

    $out['data'] = $hours;

    echo json_encode($out);
  }
} else if (isset($_GET["diashow"])) {
  include "single-event-diashow.php";
} else if (get_query_var("image-code", False)) {
  include "single-event-single-image.php";
} else {

  if (has_post_thumbnail()) $thumb_url = get_the_post_thumbnail_url(null, "HD");
  else $thumb_url = wp_get_attachment_image_url(get_theme_mod("event_header"), "HD ");

  if (has_post_thumbnail()) $square_thumb_url = get_the_post_thumbnail_url(null, "square");
  else $square_thumb_url = wp_get_attachment_image_url(get_theme_mod("event_header"), "square");

  get_header(null, array("preview_image" => $square_thumb_url, "title" => get_the_title())); ?>
  <?php if (is_main()) : ?>
    <div class="top-section grid-x" style="background-image:url('<?= $thumb_url ?>')">
      <div class="overlay cell show-for-medium">
        <h1 class="grid-container"><?php the_title() ?></h1>
      </div>
    </div>

    <div class="section dark hide-for-medium">
      <h1><?php the_title() ?></h1>
    </div>
  <?php endif; ?>

  <div class="content e-flex e-con-boxed e-con e-parent">

    <?php
    the_post();
    // Password
    if (post_password_required() && !(isset($_GET["pw"]) && $_GET["pw"] == get_post()->post_password)) {
      echo get_the_password_form();
    } else {
      if (is_fcb()) : ?>

        <h1><?php the_title() ?></h1>

        <?php the_content() ?>

      <?php endif;


      echo '<div class="section e-con-inner"';
      if (is_photolead()) echo ' style="display: flex"';
      echo '><div id="event-container" class="gallery">';

      if (!function_exists('get_home_path')) {
        require_once(dirname(__FILE__) . '/../../../wp-admin/includes/file.php');
      }
      $dir = get_home_path() . 'wp-content/photobuzz-images/' . get_post_meta(get_the_ID(), 'directory', true);

      $terms = get_the_terms(get_the_ID(), "eventtype");
      if ($terms) {
        $terms = array_map(fn ($value) => $value->slug, $terms);
      }
      if (!is_dir($dir)) {
        echo '<div class="small-12 column text-center callout alert">' . __("Diese Galerie ist leider leer!", "textdomain") . '</div>';
      } else if ($terms && (in_array("public", $terms) || in_array("photoset", $terms)) && !can_delete_image(get_the_ID())) {
        echo "<h4>Nutze deinen QR-Code, um dein Foto aufzurufen</h4>";
      } else if (in_array("photoset", $terms)) {

        $images = new PhotoBuzz\Event_Images(get_post_meta(get_the_ID(), 'directory', true));
        if (isset($_GET['scan'])) {
          $images->scan();
        }
        $event_images = array_reverse($images->getImages());
        while (!empty($event_images)) {
          $img = end($event_images);
          echo "<div>";
          echo $img["date"]->format("H:i:s");
          echo " (letztes Foto) Link: <a href='/p/";
          $code = $img["code"];
          echo $code;
          echo "'>LINK</a>";
          while (end($event_images)["code"] == $code) {
            array_pop($event_images);
          }
          echo "</div>";
        }
      } else {


        function comp($a, $b)
        {
          return $a['datetime']->getTimestamp() - $b['datetime']->getTimestamp();
        }

        //Images

        //$imgs= scandir($dir);

        $images = new PhotoBuzz\Event_Images(get_post_meta(get_the_ID(), 'directory', true));
        if (isset($_GET['scan'])) {
          $images->scan();
        }
        $event_images = $images->getImages(0, 100);
        if (count($event_images) == 0) {
          echo "<h4>Leider hat noch keiner den Buzzer gedrückt &nbsp;😔</h4>";
        }







        $hours = array();
        $current;
        $ctr = 0;
        foreach ($event_images as $img) {
          $date = $img['date'];
          $hour = intval($date->format('H'));
          if (!isset($current)) {
            $current = $hour;
          }
          if ($current == $hour) {
            $hours[$ctr][] = $img;
          } else {
            $ctr++;
            $current         = $hour;
            $hours[$ctr][] = $img;
          }
        }
        $jsctr = 0;

        $items = array();
        // Schleife durch die stunden zur anzeige 
        foreach ($hours as $imgs) {
          $content = '';
          foreach ($imgs as $img) {
            if ($img["extension"] == "mp4") {
              $items[] = array(
                "html" => '<video autoplay loop muted playsinline class="pswp__img" src="' . $img['image_url'] . '"></video>',
                "vidsrc" => $img['image_url'],
                "date" => $img['date']->getTimestamp()
              );
            } else {
              $items[] = array(
                'src' => $img['image_url'],
                'w' => $img['width'],
                'h' => $img['height'],
                'msrc' => $img['thumbnail_url'],
                "date" => $img['date']->getTimestamp()
              );
            }

            if ($img['width'] > 0) {
              $dimension = $img['height'] / $img['width'] * 100;
            } else {
              $dimension = 3 / 2 * 100;
            }
            if ($img["extension"] == "mp4") {
              $content .= '<div class="pswp-thumb masonry-grid-item" style="opacity: 0"><a class="pswp-open" style="padding-top:' . $dimension . '%" id="gallery-link-' . $jsctr . '" data-pswp-item-id="' . $jsctr . '"><video style="width: 100%" loop muted playsinline  preload="metadata" src="' . $img['thumbnail_url'] . '"></a></div>';
            } else {
              $content .= '<div class="pswp-thumb masonry-grid-item" style="opacity: 0"><a class="pswp-open" style="padding-top:' . $dimension . '%" id="gallery-link-' . $jsctr . '" data-pswp-item-id="' . $jsctr . '"><img src="' . $img['thumbnail_url'] . '" ></a></div>';
            }
            $jsctr++;
          }
          hour_markup($imgs[0]['date'], $content);
        }
      }



      echo '</div></div>';
      ?>
      <?php if (isset($event_images) && sizeof($event_images) >= 100) { ?>
        <div class="text-center"> <a id="moreButton" class="button large"><?= __("Mehr laden...", "textdomain") ?></a>
        </div> <?php }
            } ?>




  </div>
<?php
  add_pswp_html();
  if (isset($items))
    add_javascript($items, $jsctr);
  get_footer();
}
