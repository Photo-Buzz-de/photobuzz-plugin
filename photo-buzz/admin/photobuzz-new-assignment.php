<div class="wrap">
    <?php
    require_once __DIR__ . "/../vendor/autoload.php";

    use Includes\Box_Assignments;

    $assignments = new Box_Assignments();


    if (isset($_POST) && isset($_POST["box_id"])) {
        [$success, $result] = $assignments->create_or_edit_from_input(
            $_GET["edit"],
            $_POST["box_id"],
            explode(",", $_POST["assigned_event"], 2)[0],
            DateTime::createFromFormat(
                "d.m.Y H:i",
                $_POST["from_time"]["date"] . " " . $_POST["from_time"]["time"],
                new DateTimeZone("Europe/Berlin")
            ),
            DateTime::createFromFormat("d.m.Y H:i", $_POST["to_time"]["date"] . " " . $_POST["to_time"]["time"], new DateTimeZone("Europe/Berlin"))
        );

        if ($success) {
            show_success("Zuordnung gespeichert!");
            if (!isset($_GET["edit"])) {
                $_GET["edit"] = $result;
            }
        } else {
            show_err($result);
        }
    } ?>
    <h1 class="wp-heading-inline"><?= isset($_GET["edit"]) ? "Zuordnung bearbeiten" : "Neue Zuordnung" ?></h1>


    <hr class="wp-header-end">

    <?php do_action('admin_notices');

    echo cmb2_get_metabox_form("assignment_metabox"); ?>

</div>