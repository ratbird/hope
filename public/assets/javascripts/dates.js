/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.Dates = {
    addTopic: function () {
        var topic_name = jQuery("#new_topic").val();
        var termin_id = jQuery("#new_topic").closest("table[data-termin_id]").data("termin_id");
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/course/dates/add_topic",
            'data': {
                'title': topic_name,
                'termin_id': termin_id
            },
            'dataType': "json",
            'type': "post",
            'success': function (response) {
                jQuery("#new_topic").closest("table").find(".themen_list").append(response.li);
                jQuery("#date_" + termin_id).find(".themen_list").append(response.li);
            }
        });
        jQuery("#new_topic").val('');
    },
    removeTopic: function () {
        var topic_id = jQuery(this).closest("li").data("issue_id");
        var termin_id = jQuery("#new_topic").closest("table[data-termin_id]").data("termin_id");
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/course/dates/remove_topic",
            'data': {
                'issue_id': topic_id,
                'termin_id': termin_id
            },
            'dataType': "json",
            'type': "post",
            'success': function (response) {
                jQuery(".topic_" + topic_id).remove();
            }
        });
    }
};

jQuery(function () {
    jQuery(".remove_topic").live("click", STUDIP.Dates.removeTopic);
})