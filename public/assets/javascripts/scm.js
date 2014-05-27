/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */


$(function () {
    $('#counter').text($('#tab_name').attr('maxlength'));
    $('#tab_name').on("propertychange input textInput", function () {
        var left = $('#tab_name').attr('maxlength') - $(this).val().length;
        if (left < 0) {	
            left = 0; 
        }
        $('#counter').text(left);
    });
});
