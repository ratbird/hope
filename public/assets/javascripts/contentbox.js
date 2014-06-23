$(document).ready(function() {
    $('article h1 a').click(function(e) {
        e.preventDefault();
        var article = $(this).closest('article');

        // If the contentbox article is new send an ajax request
        if (article.hasClass('new')) {
            $.ajax({
                url: $(this).attr('href')
            });
        }

        // Open the contentbox
        article.toggleClass('open').removeClass('new');
    });
});