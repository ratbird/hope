STUDIP.WelcomeWidget = {
    adaptiveResize: function() {
        alert('Now.');
        var divWidth = jQuery('#welcomewidget').width();
        var bgImage = jQuery('#welcomewidget').css('background-image').replace(/url\((['"])?(.*?)\1\)/gi, '$2').split(',')[0];
        var img = new Image();
        img.src = bgImage;
        var ratio = img.height / img.width;
        var newHeight = Math.round(divWidth*ratio);
        jQuery('#welcomewidget').css('height', newHeight + 'px');
        if (newHeight < 200) {
            jQuery('#welcomewidget').css('font-size', '0.75em');
        }
    }
};

jQuery(function() {
    adaptiveResize();
    jQuery('.start-widgetcontainer')[0].addEventListener('DOMSubtreeModified', adaptiveResize, false);
    function adaptiveResize() {
        var widget = jQuery('#welcomewidget');
        var divWidth = widget.width();
        var bgImage = widget.css('background-image').replace(/url\((['"])?(.*?)\1\)/gi, '$2').split(',')[0];
        var img = new Image();
        img.src = bgImage;
        var ratio = img.height / img.width;
        var newHeight = Math.round(divWidth*ratio);
        widget.css('height', newHeight + 'px');
        if (newHeight < 200) {
            widget.css('font-size', '1.5em');
        } else {
            widget.css('font-size', '2.5em');
        }
    }
});
