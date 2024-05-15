<?php
get_header();
if (post_password_required()) {
    echo "<div class=\"content\">";
    echo get_the_password_form();
    echo "</div>";
} else {
    if (has_post_thumbnail()) $thumb_url = get_the_post_thumbnail_url(null, "HD");
    else $thumb_url = wp_get_attachment_image_url(get_theme_mod("event_header"), "HD ");
?>



    <div class="diashow-container">
        <a class="toggle-fs">
            <img class="diashow-image" src="<?= $thumb_url ?>">
            <video autoplay muted loop class="diashow-gif">
                <source src="" type="video/mp4">
            </video>
        </a>
    </div>
    <script>
        var images = []

        function loadImages() {
            jQuery.getJSON(jQuery(location).attr('pathname') + "?json=diashow", null, function(data) {
                images = data

            })
        }

        function getRandomInt(max) {
            return Math.floor(Math.random() * max);
        }

        function Sleep(milliseconds) {
            return new Promise(resolve => setTimeout(resolve, milliseconds));
        }

        async function run() {
            var loadimg;
            var img;
            var first = false

            for (var i = 0; i < 100; i++) {
                imgelem = jQuery(".diashow-image")
                videlem = jQuery(".diashow-gif")


                if (images.length) {
                    if (img) {
                        if (img.extension == "jpg") {
                            imgelem[0].src = bloburl
                            await Sleep(100)
                            imgelem.css("display", "inline")
                            videlem.css("display", "none")
                        } else {
                            jQuery(".diashow-gif source")[0].src = bloburl
                            videlem[0].load();
                            imgelem.css("display", "none")
                            videlem.css("display", "inline")
                            videlem[0].play();

                        }
                        first = false
                    } else {
                        first = true
                    }

                    img = images[getRandomInt(images.length)]

                    //fetch image file
                    loadimg = fetch(img.image_url)
                        .then(resp => resp.blob())
                        .then(blob => {
                            bloburl = URL.createObjectURL(blob)
                        });
                }
                if (i % 5 == 0) {
                    loadImages()
                }

                if (!first) await Sleep(5000)
                await loadimg

            }
        }
        jQuery(".toggle-fs").click(function() {
            if (window.matchMedia('(display-mode: fullscreen)').matches) {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            } else {
                element = jQuery(".diashow-container")[0]
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if (element.msRequestFullscreen) { // for IE11 (remove June 15, 2022)
                    element.msRequestFullscreen();
                }
            }
        })

        run()
    </script>
<?php
}
get_footer();
