(function ($) {
    $.fn.listfiles = function (options) {

        var $widget = getWidget(this);
        var a_href;
        function getWidget($element)
        {
            return $element.parents('.listfiles_widget');
        }


        /*
         * Display popover image
         */
        function displayPopupImage(obj, answer) {
            if (answer) {
                popOver(obj);
            }
        }

        /*
         * Check if image is valid
         */
        function IsValidImageUrl(obj, callback) {
            var img = new Image();
            var a_href = $(obj).attr('href');
            img.src = a_href;
            img.onerror = function () {
                console.log('onerror');
                callback(obj, false);

            },
                    img.onload = function () {
                        console.log('onload');
                        var a_href = $(obj).attr('href');
                        $(obj).popover({
                            placement: 'top',
                            trigger: 'hover',
                            html: true,
                            content: '<div class="media"><a href="#" class="pull-left"><img src="' + a_href + '" class="media-object" alt="Sample Image"></a><div class="media-body"><h4 class="media-heading"></h4><p></p></div></div>'
                        });
                        //           callback(obj, true);
                    };

        }

        function popOver(obj) {
            var a_href = $(obj).attr('href');
            $(obj).popover({
                placement: 'top',
                trigger: 'hover',
                html: true,
                content: '<div class="media"><a href="#" class="pull-left"><img src="' + a_href + '" class="media-object" alt="Sample Image"></a><div class="media-body"><h4 class="media-heading"></h4><p></p></div></div>'
            });
        }
        $widget.popover({
            html: true,
            trigger: 'hover',
            placement: 'top',
            selector: ".flist a[rel=popover]",
            content: function () {
                var a_href = $(this).attr('href');
                var img = new Image();
                img.src = a_href;
                if (isImageOk(img)) {
                    return'<div class="media"><a href="#" class="pull-left"><img src="' + a_href + '" class="media-object" alt="Sample Image"></a><div class="media-body"><h4 class="media-heading"></h4><p></p></div></div>';
                }
            }
        });


        function isImageOk(img) {
            // During the onload event, IE correctly identifies any images that
            // weren't downloaded as not complete. Others should too. Gecko-based
            // browsers act like NS4 in that they report this incorrectly.
            if (!img.complete) {
                return false;
            }

            // However, they do have two very useful properties: naturalWidth and
            // naturalHeight. These give the true size of the image. If it failed
            // to load, either of these should be zero.
            if (typeof img.naturalWidth !== "undefined" && img.naturalWidth === 0) {
                return false;
            }

            // No other way of checking: assume it's ok.
            return true;
        }

        //});
    };

})(jQuery);