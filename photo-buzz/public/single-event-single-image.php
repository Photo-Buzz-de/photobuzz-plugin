<?php
require_once(__DIR__ . "/../includes/single_event_functions.php");
require_once(__DIR__ . "/../vendor/autoload.php");
$jsctr = 0;
$items = array();

$terms = get_the_terms(get_the_ID(), "eventtype");

if ($terms) {
    $terms = array_map(fn ($value) => $value->slug, $terms);
}

get_header(null, array("title" => get_the_title()));
if (has_post_thumbnail()) $thumb_url = get_the_post_thumbnail_url(null, "HD");
else $thumb_url = wp_get_attachment_image_url(get_theme_mod("event_header"), "HD ");

if (has_post_thumbnail()) $square_thumb_url = get_the_post_thumbnail_url(null, "square");
else $square_thumb_url = wp_get_attachment_image_url(get_theme_mod("event_header"), "square");

if ($terms && (in_array("photoset", $terms))) : ?>
    <div class="top-section grid-x" style="background-image:url('<?= $thumb_url ?>')">
        <div class="overlay cell show-for-medium">
            <h1 class="grid-container"><?php the_title() ?></h1>
        </div>
    </div>

    <div class="section dark hide-for-medium">
        <h1><?php the_title() ?></h1>
    </div>
<?php endif;


$dir = get_post_meta(get_the_ID(), 'directory', true);
$images = new PhotoBuzz\Event_Images($dir);
$imgs = $images->getImageByCode(get_query_var("image-code"));


?>
<div class="content">
    <?php

    $raffles = get_post_meta(get_the_ID(), 'raffle', true);
    $raffle = !empty($raffles) ? $raffles[0] : null;
    $raffle_obj = new PhotoBuzz\Raffle($raffle);
    if (isset($raffle)) {
        $participated = $raffle_obj->has_participated(get_query_var("image-code"));
    } else {
        $participated = true;
    }

    // FCB Landingpage
    if (!$participated && $_SERVER['REQUEST_METHOD'] == "GET" &&  !can_delete_image(get_the_ID())) {
        echo get_the_content(null, false, $raffles[0]);
        if ($raffle) {

            if (is_fcb()) {
    ?>

                <div class="raffle">
                    <form style="display:none" id="show_pics" target="" method="post">
                        <input type="hidden" name="show_pics" value="true">
                    </form>
                    <form class="fcb" method="post" target="">
                        <div class="grid-container">
                            <div class="grid-x grid-padding-x">
                                <div class="medium-6 cell">
                                    <label>Ihr Name <span class="required">*</span>
                                        <input name="pb_name" type="text" placeholder="Thomas Müller" required>
                                    </label>
                                </div>
                                <div class="medium-6 cell">
                                    <label>Ihre Mailadresse <span class="required">*</span>
                                        <input name="pb_email" type="email" placeholder="mueller@example.com" required>
                                    </label>
                                </div>

                            </div>
                            <fieldset class="cell">
                                <div class="cb-row"><input name="pb_raffle" id="checkbox2" type="checkbox" required><label for="checkbox2">Hiermit nehme ich am Gewinnspiel teil und akzeptiere die <a target="_blank" href="<?= get_permalink($raffle) . "teilnahmebedingungen" ?>">Teilnahmebedingungen</a>.&nbsp;<span class="required">*</span></label></div>
                                <div class="cb-row"><input name="pb_newsletter" id="checkbox1" type="checkbox"><label for="checkbox1">Hiermit möchte ich den FC Bayern Newsletter-abonnieren
                                        Die Einwilligung kann jederzeit per <a href="mailto:feedback-newsletter@fcbayern.com">E-Mail</a> widerrufen werden. Wir versenden unseren Newsletter entsprechend unserer <a href="https://fcbayern.com/de/datenschutz">Datenschutzerklärung</a>.
                                        Falls der Anmelder minderjährig sein sollte, bestätigt dieser mit seiner Anmeldung, dass die Einwilligung den Erziehungsberechtigten zum Empfang des Newsletters vorliegt.</label></div>

                            </fieldset>
                            <div>Informationen zum Datenschutz bei Verwendung der Photobox erhalten Sie <a href="https://allianz-arena.com/fanfoto/photobox-datenschutzhinweis/">HIER</a></div>
                            <div class="grid-x submit-buttons">
                                <div class="medium-6 small-order-2 medium-order-1 cell"><input type="submit" form="show_pics" class="button large hollow small-only-expanded" value="Weiter ohne Gewinnspiel"></div>
                                <div class="medium-6 small-order-1 medium-order-2 cell text-right"><input type="submit" class="button large small-only-expanded" value="Am Gewinnspiel teilnehmen"></div>
                                <div class="small-order-3 cell text-right"><span class="required">*</span>&nbsp;Pflichtfeld&nbsp;&nbsp;&nbsp;&nbsp;</div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php
            } else {
            ?>

                <div class="raffle">
                    <form style="display:none" id="show_pics" target="" method="post">
                        <input type="hidden" name="show_pics" value="true">
                    </form>
                    <form class="fcb" method="post" target="">
                        <div class="grid-container">
                            <div class="grid-x grid-padding-x">
                                <div class="medium-6 cell">
                                    <label>Your name <span class="required">*</span>
                                        <input name="pb_name" type="text" placeholder="John Doe" required>
                                    </label>
                                </div>
                                <div class="medium-6 cell">
                                    <label>Your e-mail address <span class="required">*</span>
                                        <input name="pb_email" type="email" placeholder="j.doe@example.com" required>
                                    </label>
                                </div>

                            </div>
                            <fieldset class="cell">
                                <div class="cb-row"><input name="pb_raffle" id="checkbox2" type="checkbox" required><label for="checkbox2">By providing your email address, you agree to sign up to email notifications from The Little Car Company. You can opt out at any time.&nbsp;<span class="required">*</span></label></div>

                            </fieldset>
                            <div class="grid-x submit-buttons">
                                <div class="medium-6 small-order-2 medium-order-1 cell"></div>
                                <div class="medium-6 small-order-1 medium-order-2 cell text-right"><input type="submit" class="button large hollow small-only-expanded" value="Submit"></div>
                                <div class="small-order-3 cell text-right"><span class="required">*</span>&nbsp;required&nbsp;&nbsp;&nbsp;&nbsp;</div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php
            }
        }
    } else {
        if (is_fcb()) : ?>
            <!--<h1><?php the_title() ?></h1>-->
        <?php
            $angebotsheader = get_page_by_path("angebots-header");
            echo apply_filters('the_content', get_the_content(null, false, $angebotsheader));
        endif;
        if (isset($raffle)) {

            if ($participated) {
                //echo "<h3>Vielen Dank für Ihre Teilnahme</h3>";
            } else if ($_SERVER['REQUEST_METHOD'] == "POST" && !isset($_POST["show_pics"])) {
                $raffle_obj = new PhotoBuzz\Raffle($raffle);
                $wants_to_participate = $_POST["pb_raffle"]
                    && $_POST["pb_email"] && strlen($_POST["pb_email"]) < 200
                    && filter_var($_POST["pb_email"], FILTER_VALIDATE_EMAIL)
                    && $_POST["pb_name"] && strlen($_POST["pb_name"]) < 200;
                if ($wants_to_participate) {
                    if ($raffle_obj->add_participant($_POST["pb_name"], $_POST["pb_email"], get_query_var("image-code"), isset($_POST["pb_newsletter"]))) {
                        if (is_fcb()) echo "<br><h3>Vielen Dank für Ihre Teilnahme an unserem Gewinnspiel!</h3>";
                        $participated = True;
                    } else {
                        echo "<br><h3>Es ist ein Fehler aufgetreten</h3>";
                    }
                } else echo "<br><h3>Ungültige Eingabe</h3>";
                if (isset($_POST["pb_newsletter"])) {
                    if (is_fcb()) {
                        try {
                            $raffle_obj->add_newsletter($_POST["pb_name"], $_POST["pb_email"]);
                        } catch (GuzzleHttp\Exception\ConnectException $e) {
                            //echo "<br><h4>Bei der Anmeldung zum Newsletter ist ein Fehler aufgetreten</h4>";
                            error_log($e);
                        }
                    }
                }
            } else if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST["show_pics"])) {
                if (isset($_POST["pb_newsletter"])) {
                    if (
                        $_POST["pb_email"] && strlen($_POST["pb_email"]) < 200
                        && filter_var($_POST["pb_email"], FILTER_VALIDATE_EMAIL)
                        && $_POST["pb_name"] && strlen($_POST["pb_name"]) < 200
                    ) {
                        $raffle_obj->add_newsletter($_POST["pb_name"], $_POST["pb_email"]);
                    } else echo "<br><h3>Ungültige Eingabe</h3>";
                }
            }
        }


        ?>
        <?php
        if (empty($imgs)) {
        ?><div class="single-image-view-box">
                <h4>Foto nicht gefunden. Überprüfe den eigegebenen Link und probiere es später noch einmal.</h4>
            </div>
            <?
        } else {
            if ($terms && (in_array("photoset", $terms))) {
            ?>
                <div class="img-container photoset grid-x grid-padding-x grid-padding-y small-up-2 large-up-3">
                    <?php
                    foreach ($imgs as $img) {
                        $imgurl = $img["image_url"];
                        $items[] = array(
                            'src' => $img['image_url'],
                            'w' => $img['width'],
                            'h' => $img['height'],
                            'msrc' => $img['thumbnail_url'],
                            "date" => $img['date']->getTimestamp()
                        );
                        echo '<div class="pswp-thumb masonry-grid-item cell" style="opacity: 0"><a class="pswp-open" id="gallery-link-' . $jsctr . '" data-pswp-item-id="' . $jsctr . '"><img src="' . $img['image_url'] . '" ></a></div>';
                        $jsctr++;
                    }
                    ?>
                </div>
            <?php


            } else {
                # Single image content
            ?>
                <div class="single-image-view-container">
                    <?php

                    foreach ($imgs as $img) {
                        $imgurl = $img["image_url"];
                        $items[] = array(
                            'src' => $img['image_url'],
                            'w' => $img['width'],
                            'h' => $img['height'],
                            'msrc' => $img['thumbnail_url'],
                            "date" => $img['date']->getTimestamp()
                        );
                    ?>
                        <div class="single-image-view-box">

                            <?php if (!is_fcb()) { ?><h4>Dein Foto von <?php the_title() ?></h4><?php } ?>
                            <div class="pswp-thumb"><a class="pswp-open" id="gallery-link-<?= $jsctr ?>" data-pswp-item-id="<?= $jsctr ?>"><img id="single-image" src="<?= $imgurl ?>"></a></div>
                            <div class="buttons">
                                <div>
                                    <?php if (!is_fcb()) : ?>
                                        <a class="oncolor" href=<?= $imgurl ?> download><i class="icon-download"></i><br>Herunterladen</a>
                                    <?php else : ?>
                                        <a class="button" href=<?= $imgurl ?> download>Download</a>
                                    <?php endif; ?>
                                </div>
                                <div class="share-container" style="display:none">
                                    <?php if (!is_fcb()) : ?>
                                        <a class="share oncolor" data-share-link="<?= $imgurl ?>"><i class="icon-share"></i><br>Teilen</a>
                                    <?php else : ?>
                                        <a class="share button" data-share-link="<?= $imgurl ?>">Teilen</a>
                                    <?php endif; ?>
                                </div>
                                <?php
                                $terms = get_the_terms(get_the_ID(), "eventtype");
                                if ($terms) {
                                    $terms = array_map(fn ($value) => $value->slug, $terms);
                                }
                                if (!($terms && in_array("public", $terms))) {
                                ?>
                                    <div><a class="oncolor" href="<?= get_permalink() ?>"><i class="icon-picture"></i><br>zur Galerie</a></div>
                                <?php
                                }
                                ?>
                            </div>

                        </div><?php
                                $jsctr++;
                            } ?>
                </div><?php
                    }
                } ?>
        <p></p>
        <?php if (!is_fcb()) : ?>
            <p class="text-center ad">Buche jetzt eine Fotobox auch für deine Feier!<br><a class="button" href="/">Mehr erfahren</a></p>
    <?php
        else :
            $angebotsheader = get_page_by_path("angebots-teaser");
            echo apply_filters('the_content', get_the_content(null, false, $angebotsheader));
        endif;
    } ?>
</div>
<?php
add_pswp_html();
get_footer();
add_javascript($items, $jsctr);
