<?php function add_javascript($items, $jsctr)
{
?>
    <script type="module">
        import {
            observeElementInViewport,
            isInViewport
        } from 'https://unpkg.com/observe-element-in-viewport?module'

        var $grid = jQuery('.masonry-grid').ready().masonry({
            // options
            itemSelector: '.masonry-grid-item',
            //horizontalOrder: true,
            percentPosition: true,
            columnWidth: '.grid-sizer',
            gutter: '.gutter-sizer',
        });
        jQuery('.masonry-grid-item').ready().animate({
            opacity: 1
        }, 500)
        // photoswipe
        var items = <?= json_encode($items) ?>;

        jQuery(".pswp-open").click(function() {
            openPhotoSwipe(jQuery(this).data("pswp-item-id"))
        });

        init_share()



        var openPhotoSwipe = function(index) {
            var pswpElement = document.querySelectorAll('.pswp')[0];
            var shareButtons = [];
            if (navigator.share) {
                shareButtons.push({
                    id: 'share',
                    label: '<?= __("Teilen", "textdomain") ?>',
                    url: 'javascript:{{raw_image_url}}',
                    download: false
                });
            }
            shareButtons.push({
                id: 'download',
                label: 'Herunterladen',
                url: '{{raw_image_url}}',
                download: true
            });

            // define options (if needed)
            var options = {
                history: false,
                index: index,
                zoomEl: false,
                getThumbBoundsFn: function(index) {
                    var thumbnail = jQuery('*[data-pswp-item-id="' + index + '"]')[0];
                    var pageYScroll = window.pageYOffset || document.documentElement.scrollTop;
                    var rect = thumbnail.getBoundingClientRect();

                    return {
                        x: rect.left,
                        y: rect.top + pageYScroll,
                        w: rect.width
                    };
                },
                shareButtons: shareButtons,

                getImageURLForShare: function(shareButtonData) {
                    return gallery.currItem.src || gallery.currItem.vidsrc || '';
                },
                parseShareButtonOut: function(shareButtonData, shareButtonOut) {
                    return shareButtonOut.replace('href="javascript:', 'data-share-url="');
                },
            };

            var gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
            gallery.init();
            gallery.listen('shareLinkClick', function(e, target) {
                // e - original click event
                // target - link that was clicked

                // If `target` has `href` attribute and 
                // does not have `download` attribute - 
                // share modal window will popup
                if (target.hasAttribute("data-share-url")) {
                    share(jQuery(target).data("share-url"))
                }
            });
            jQuery(".pswp__button--delete").on("click", function() {
                const url = new URL(gallery.currItem.src);
                console.log(gallery.currItem);
                if (confirm("Das Bild " + url.pathname.split("/").slice(-1)[0] +" wirklich löschen?")) {
                    jQuery.post('<?= admin_url('admin-ajax.php') ?>', {
                            action: "delete_image",
                            name: url.pathname.split("/").slice(-1)[0],
                            dir: url.pathname.split("/").slice(-2)[0],
                            event: <?=get_the_ID()?>,
                        },
                        function(response) {
                            location.reload()
                        });
                }
            })


        };

        // Init for single image view
        function init_share() {
            jQuery(document).ready(function() {
                if (navigator.share) {
                    jQuery(".share-container").css("display", "block")
                    jQuery(".share").click(function() {
                        share(jQuery(this).data("share-link"))
                    })
                }
            })
        }

        function share(url) {
            if (navigator.share) {
                fetch(url)
                    .then(res => res.blob()) // Gets the response and returns it as a blob
                    .then(blob => {
                        var file = new File([blob], url.substring(url.lastIndexOf('/') + 1), {
                            type: 'image/jpeg'
                        });
                        var toShare = {
                            //title: 'PHOTO-BUZZ',
                            <?php if (!is_fcb()) : ?>
                                //text: '<?= __("Schau dir die Fotos 📷 unserer Feier an 😊 ", "textdomain") ?>'+window.location.href,
                            <?php endif; ?>
                            //url: window.location.href,
                            files: [file]
                        };
                        if (navigator.canShare && navigator.canShare(toShare)) {
                            navigator.share(toShare);
                        } else {

                            navigator.share({
                                title: 'PHOTO-BUZZ',
                                <?php if (!is_fcb()) : ?>
                                    text: '<?= __("Schau dir das Foto 📷 unserer Feier an 😊 ", "textdomain") ?>',
                                <?php endif; ?>
                                url: url,
                            });
                        }

                    });
            } else {
                var anchor = document.createElement('a');
                anchor.href = url;
                anchor.target = '_blank';
                anchor.download = '';
                anchor.click();


            }

            return false;
        }

        var jsctr = <?= $jsctr ?>;


        function loadMore() {
            jQuery("#moreButton").off('click')
            jQuery("#moreButton").addClass('disabled')
            jQuery.getJSON(jQuery(location).attr('pathname') + "?json=1&num=100&start=" + items[items.length - 1].date, null, function(data) {

                var hours = data.data;
                if (data.data.length == 0) jQuery("#moreButton").addClass("hide");
                for (var i = 0; i < hours.length; ++i) {

                    if (!jQuery("#" + hours[i].date + " .img-container").length) {
                        jQuery("#event-container").append(hours[i].markup);
                        jQuery('#' + hours[i].date + ' .masonry-grid').masonry({
                            // options
                            itemSelector: '.masonry-grid-item',
                            //horizontalOrder: true,
                            percentPosition: true,
                            columnWidth: '.grid-sizer',
                            gutter: '.gutter-sizer',
                        });
                    }

                    for (var j = 0; j < hours[i].data.length; j++) {
                        var newelem = "";
                        var dimension = hours[i].data[j].height / hours[i].data[j].width * 100;
                        if (hours[i].data[j].extension == "mp4") {
                            newelem = '<div class="pswp-thumb masonry-grid-item"><a class="pswp-open" style="padding-top:' + dimension + '%" id="gallery-link-' + jsctr + '" data-pswp-item-id="' + jsctr + '"><video style="width: 100%" loop muted playsinline src="' + hours[i].data[j].thumbnail_url + '"></a></div>';
                            var index = items.push({
                                html: '<video autoplay loop muted playsinline preload="metadata" class="pswp__img" src="' + hours[i].data[j].image_url + '"></video>',
                                vidsrc: hours[i].data[j].image_url,
                                date: new Date(hours[i].data[j].date).getTime() / 1000

                            });
                        } else {
                            newelem = '<div class="pswp-thumb masonry-grid-item"><a class="pswp-open" style="padding-top:' + dimension + '%" id="gallery-link-' + jsctr + '" data-pswp-item-id="' + jsctr + '"><img src="' + hours[i].data[j].thumbnail_url + '" ></a></div>';
                            var index = items.push({
                                src: hours[i].data[j].image_url,
                                w: hours[i].data[j].width,
                                h: hours[i].data[j].height,
                                msrc: hours[i].data[j].thumbnail_url,
                                date: new Date(hours[i].data[j].date).getTime() / 1000
                            });
                        }

                        newelem = jQuery(newelem);
                        jQuery("#" + hours[i].date + " .gallery-grid").append(newelem).masonry("appended", newelem);
                        newelem.find("video").each(function() {
                            observe_video(this)
                        })
                        newelem.children(".pswp-open").click(function() {
                            openPhotoSwipe(jQuery(this).data("pswp-item-id"))
                        });
                        jsctr++;



                    }
                    jQuery("#" + hours[i].date + " .gallery-grid").masonry();
                }
                /*jQuery(".pswp-open").click(function() {
                  openPhotoSwipe(jQuery(this).data("pswp-item-id"))
                });*/

                jQuery("#moreButton").click(loadMore);
                jQuery("#moreButton").removeClass('disabled');
            });



        }
        jQuery("#moreButton").click(loadMore);

        jQuery(window).scroll(function() {
            /*if (typeof Foundation!== undefined && Foundation.MediaQuery.atLeast('medium')) var offset = 700;
            else*/ var offset = 1400;
            if (jQuery(window).scrollTop() + offset >= jQuery(document).height() - jQuery(window).height()) {
                jQuery("#moreButton").click();
            }
        });

        var unobserve = []

        function observe_video(vid) {
            // to use window as viewport, pass this option as null
            const viewport = null
            // handler for when target is in viewport
            const inHandler = (entry, unobserve, targetEl) => targetEl.play()
            // handler for when target is NOT in viewport
            const outHandler = (entry, unobserve, targetEl) => targetEl.pause()
            // the returned function, when called, stops tracking the target element in the
            // given viewport 
            unobserve.push(observeElementInViewport(vid, inHandler, outHandler, {
                // set viewport
                viewport,
                // decrease viewport top by 100px
                // similar to this, modRight, modBottom and modLeft exist
                modTop: '0px',
                // threshold tells us when to trigger the handlers.
                // a threshold of 90 means, trigger the inHandler when atleast 90%
                // of target is visible. It triggers the outHandler when the amount of
                // visible portion of the target falls below 90%.
                // If this array has more than one value, the lowest threshold is what
                // marks the target as having left the viewport
                threshold: [50]
            }))

        }

        jQuery(document).ready(function() {
            jQuery('video').each(function(index) {
                observe_video(this)
            })
        });
    </script>
<?php
}
function hour_markup($date, $content = '', $echo = true)
{

    $out = '<div class="divider"></div>
    <div class="hour-container" id="' . $date->format("m-d-H") . 'h">

      <div class="time">
        <div class="arrow"><span>
            ' . $date->format('H') . ':00
          </span></div>
        <div class="arrowtip"></div>
      </div>
      <div class="img-container">
        <div class="gallery-grid masonry-grid" id="images-' . $date->format("m-d-H") . 'h">
          <div class="grid-sizer"></div>
          <div class="gutter-sizer"></div>
          ' . $content . '
        </div>
      </div>
    </div>';
    if ($echo) echo $out;
    else return $out;
}

function add_pswp_html()
{
?>
    <!-- Root element of PhotoSwipe. Must have class pswp. -->
    <div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

        <!-- Background of PhotoSwipe. 
           It's a separate element as animating opacity is faster than rgba(). -->
        <div class="pswp__bg"></div>

        <!-- Slides wrapper with overflow:hidden. -->
        <div class="pswp__scroll-wrap" <?php if (is_admin_bar_showing()) {
                                            echo 'style="top:32px"';
                                        } ?>>

            <!-- Container that holds slides. 
              PhotoSwipe keeps only 3 of them in the DOM to save memory.
              Don't modify these 3 pswp__item elements, data is added later on. -->
            <div class="pswp__container">
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
            </div>

            <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
            <div class="pswp__ui pswp__ui--hidden">

                <div class="pswp__top-bar">

                    <!--  Controls are self-explanatory. Order can be changed. -->

                    <div class="pswp__counter"></div>

                    <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>

                    <button class="pswp__button pswp__button--share" title="Share"></button>

                    <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>

                    <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
                    <?php if (can_delete_image(get_the_ID())) : ?>
                        <button class="pswp__button pswp__button--delete" title="Bild löschen"><i class="icon-trash"></i></button>
                    <?php endif; ?>

                    <!-- Preloader demo https://codepen.io/dimsemenov/pen/yyBWoR -->
                    <!-- element will get class pswp__preloader--active when preloader is running -->
                    <div class="pswp__preloader">
                        <div class="pswp__preloader__icn">
                            <div class="pswp__preloader__cut">
                                <div class="pswp__preloader__donut"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                    <div class="pswp__share-tooltip"></div>
                </div>

                <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
                </button>

                <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
                </button>

                <div class="pswp__caption">
                    <div class="pswp__caption__center"></div>
                </div>

            </div>
        </div>
    </div><?php
        }
