(function() {
    'use strict';

    $('.mdl-layout__content').addClass('scroll');

    $(window).on('load', function(){$('.loader-bg').hide();});

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