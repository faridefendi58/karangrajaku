(function() {
    'use strict';

    $('.mdl-layout__content').addClass('scroll');

    $(window).on('load', function(){
        $('.loader-bg').hide();
        if ($('.mdl-card__title').length > 0) {
            $('.mdl-card__title').each(function () {
                var data_bg = $(this).attr('data-background');
                if (typeof data_bg !== typeof undefined && data_bg !== false) {
                    var use_default = $(this).attr('use-default-image');
                    if (typeof use_default !== typeof undefined && use_default !== false) {
                        $(this).attr('style', "background:url(" + data_bg + ") center no-repeat;height:200px;");
                    } else {
                        $(this).attr('style', "background:url(" + data_bg + ") center / cover;height:200px;");
                    }
                    $(this).removeAttr('data-background');
                }
            });
        }
    });

    $('.ajax-link').click(function () {
        var href = $(this).attr('href');
        $('.loader-bg').show();
        if ($('.mdl-layout__drawer').hasClass('is-visible')) {
            $('.mdl-layout__drawer').removeClass('is-visible');
        }

        $('.mdl-layout__obfuscator').addClass('is-visible');
        console.log(href);
        window.location.href = href;

        return false;
    });

}());
