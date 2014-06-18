$(document).ready(function() {
    $('article h1 a').click(function(e) {
        e.preventDefault();
        $(this).closest('article').toggleClass('open').removeClass('new');
        $.ajax({
        url: $(this).attr('href')
        }).done(function(msg) {});
    });
});