/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Bedingungen zur Auswahl von Stud.IP-Nutzern
 * ------------------------------------------------------------------------ */

STUDIP.UserFilter = {

    configureCondition: function (targetId, targetUrl) {
        STUDIP.Dialog.fromURL(targetUrl, {
            title: 'Bedingung konfigurieren'.toLocaleString(),
            size: Math.min(Math.round(0.9 * $(window).width()), 850) + 'x400',
            method: 'post',
            id: 'configurecondition'
        });
        return false;
    },

    /**
     * Adds a new user filter to the list of set filters. 
     * @param String containerId
     * @param String targetUrl
     */
    addCondition: function (containerId, targetUrl) {
        var children = $('.conditionfield');
        var query = '';
        $('.conditionfield').each(function () {
            query += '&field[]=' +
                encodeURIComponent($(this).children('.conditionfield_class:first').val()) +
                '&compare_operator[]=' +
                encodeURIComponent($(this).children('.conditionfield_compare_op:first').val()) +
                '&value[]=' +
                encodeURIComponent($(this).children('.conditionfield_value:first').val());
        });
        $.ajax({
            type: 'post',
            url: targetUrl,
            data: query,
            dataType: 'html',
            success: function (data, textStatus, jqXHR) {
                var result = '';
                if ($('#' + containerId).children('.nofilter').length > 0) {
                    $('#' + containerId).children('.nofilter').remove();
                    $('#' + containerId).prepend('<div class="userfilter"></div>');
                } else {
                    result += '<b>' + 'oder'.toLocaleString() + '</b>';
                }
                result += data;
                $('#' + containerId).find('.userfilter').append(result);
            }
        });
        STUDIP.Dialog.close({id: 'configurecondition'});
    },

    getConditionFieldConfiguration: function (element, targetUrl) {
        var target = $(element).parent();
        $.ajax(targetUrl, {
            url: targetUrl,
            data: { 'fieldtype': $(element).val() },
            success: function (data, textStatus, jqXHR) {
                target.children('.conditionfield_compare_op').remove();
                target.children('.conditionfield_value').remove();
                target.children('.conditionfield_delete').first().before(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Status: ' + textStatus + "\nError: " + errorThrown);
            }
        });
        return false;
    },

    addConditionField: function (targetId, targetUrl) {
        $.ajax({
            url: targetUrl,
            success: function (data, textStatus, jqXHR) {
                $('#' + targetId).append(data);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Status: ' + textStatus + "\nError: " + errorThrown);
            }
        });
        return false;
    },

    removeConditionField: function (element) {
        element.remove();
        STUDIP.Dialogs.closeConfirmDialog();
        return false;
    },

    closeDialog: function (button) {
        var dialog = $(button).parents('div[role=dialog]').first();
        dialog.remove();
        return false;
    }

};
