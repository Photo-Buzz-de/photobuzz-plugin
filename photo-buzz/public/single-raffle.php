<?php
require_once(__DIR__ . "/../vendor/autoload.php");
use PhotoBuzz\Raffle;

if (isset($_GET["download"]) && user_has_rights_for_raffle($post->ID)) {

  

  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="teilnehmer.csv"');
  $raffle = new Raffle(get_the_ID());
  $raffle->echo_csv();
} else {
  if (has_post_thumbnail()) $thumb_url = get_the_post_thumbnail_url(null, "HD");
  else $thumb_url = wp_get_attachment_image_url(get_theme_mod("event_header"), "HD ");

  if (has_post_thumbnail()) $square_thumb_url = get_the_post_thumbnail_url(null, "square");
  else $square_thumb_url = wp_get_attachment_image_url(get_theme_mod("event_header"), "square");

  get_header(null, array("preview_image" => $square_thumb_url, "title" => get_the_title())); ?>
  <?php if (!is_fcb() && !is_checkout()) : ?>
    <div class="top-section grid-x" style="background-image:url('<?= $thumb_url ?>')">
      <div class="overlay cell show-for-medium">
        <h1 class="grid-container"><?php the_title() ?> </h1>
      </div>
    </div>
  <?php endif; ?>




  <div class="content">
    <?php if (get_query_var("teilnahmebedingungen")) :
      the_post();
      echo wpautop(get_post_meta(get_the_ID(), 'teilnahme', true));
    else :

    ?>
      <h1><?php the_title() ?> </h1>

      <?php
      the_post();
      the_content();

      if (get_query_var("preview")) :
      ?>
        <h3>Teilnahmebedingungen</h3>
    <?php
        echo wpautop(get_post_meta(get_the_ID(), 'teilnahme', true));
      endif;
    endif;
    ?>

    <!--<div class="colorelem"></div>-->
  </div>
<?php get_footer();
} ?>