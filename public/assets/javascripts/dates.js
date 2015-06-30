/*jslint browser: true, white: true, undef: true, nomen: true, plusplus: true, bitwise: true, newcap: true, indent: 4 */
/*global jQuery, STUDIP */

(function ($, STUDIP) {
    'use strict';

    STUDIP.Dates = {
        addTopic: function () {
            var topic_name = $('#new_topic').val(),
                termin_id  = $('#new_topic').closest('table[data-termin_id]').data('termin_id');
            $.ajax({
                url: STUDIP.URLHelper.getURL('dispatch.php/course/dates/add_topic'),
                data: {
                    title: topic_name,
                    termin_id: termin_id
                },
                dataType: 'json',
                type: 'post'
            }).done(function (response) {
                $('#new_topic').closest('table').find('.themen_list').append(response.li);
                $('#date_' + termin_id).find('.themen_list').append(response.li);
            });

            $('#new_topic').val('');
        },
        removeTopicFromIcon: function () {
            var topic_id  = $(this).closest('li').data('issue_id'),
                termin_id = $(this).closest('[data-termin_id]').data('termin_id');
            STUDIP.Dates.removeTopic(termin_id, topic_id);
        },
        removeTopic: function (termin_id, topic_id) {
            $.ajax({
                url: STUDIP.URLHelper.getURL('dispatch.php/course/dates/remove_topic'),
                data: {
                    issue_id: topic_id,
                    termin_id: termin_id
                },
                dataType: 'json',
                type: 'post'
            }).done(function () {
                $('.topic_' + termin_id + '_' + topic_id).remove();
            });
        }
    };

    $(document).on('click', '.remove_topic', STUDIP.Dates.removeTopicFromIcon);

    // Drag and drop support for topics in date list
    function createDraggable() {
        $('.dates tbody tr:not(:only-child) .themen_list li > a.title:not(.draggable-topic)').each(function () {
            var table_id = $(this).closest('table').data().tableId;

            // jQuery' addClass does not work on svgs so we need to set the
            // handle class by hand
            // (see http://stackoverflow.com/a/29525743/982902)
            $(this).children().each(function () {
                this.classList.add('draggable-topic-handle');
            });

            $(this).addClass('draggable-topic').attr('data-table-id', table_id).draggable({
                axis: 'y',
                containment: $(this).closest('tbody'),
                handle: '.draggable-topic-handle',
                revert: true
            });
        });
    }

    $(document).ready(function () {
        if ($('body#course-dates-index').length === 0) {
            return;
        }

        $('#course-dates-index .dates').tablesorter({
            textExtraction: function(node) {
                var $node = $(node);
                return String($node.data('timestamp') || $node.text()).trim();
            },
            cssAsc: 'sortasc',
            cssDesc: 'sortdesc',
            sortList: [[0, 0]]
        });

        $(document).ajaxComplete(createDraggable);

        $('.themen_list').each(function () {
            var table_id = $(this).closest('table').data().tableId;
            $(this).closest('td').addClass('topic-droppable').droppable({
                accept: '.draggable-topic[data-table-id="' + table_id + '"]',
                activeClass: 'active',
                hoverClass: 'hovered',
                drop: function (event, ui) {
                    var context = $(ui.draggable.context),
                        topic   = context.closest('li').data().issue_id,
                        source  = context.closest('tr').data().termin_id,
                        target  = $(this).closest('tr').data().termin_id,
                        path    = ['dispatch.php/course/dates/move_topic', topic, source, target].join('/'),
                        url     = STUDIP.URLHelper.getURL(path),
                        cell    = $(this);

                    if (source === target) {
                        return;
                    }

                    ui.draggable.draggable('option', 'revert', false);

                    $.post(url).done(function (response) {
                        ui.draggable.draggable('destroy').closest('li').remove();
                        $('ul', cell).append(response);
                    });
                }
            });
        });

        createDraggable();
    });

}(jQuery, STUDIP));