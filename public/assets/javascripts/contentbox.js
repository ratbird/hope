(function ($) {
    $(document).on('click', 'section.contentbox article h1 a', function (e) {
        e.preventDefault();
        var article = $(this).closest('article');

        // If the contentbox article is new send an ajax request
        if (article.hasClass('new')) {
            $.ajax({
                type: 'POST',
                url: STUDIP.URLHelper.getURL(decodeURIComponent(article.data('visiturl') + $(this).attr('href')))
            });
        }

        // Open the contentbox
        article.toggleClass('open').removeClass('new');
    });
}(jQuery));