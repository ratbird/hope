/*jslint browser: true, unparam: true */
/*global jQuery */

jQuery(function ($) {
    'use strict';

    if (window.MutationObserver !== undefined) {
        var observer = new window.MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === "class") {
                    if ($(mutation.target).attr("class").indexOf("open") !== -1) {
                        $(mutation.target).next().find("td").slideDown().find(".detailscontainer").hide().slideDown();
                    } else {
                        $(mutation.target).next().show().find("td").slideUp().find(".detailscontainer").slideUp();
                    }
                }
            });
        });
        $("table.withdetails > tbody > tr:not(.details)").each(function (index, element) {
            observer.observe(element, { attributes: true });
        });
    }

    if ($.hasOwnProperty('tablesorter')) {
        $.tablesorter.addParser({
            id: 'htmldata',
            is: function (s, table, cell, $cell) {
                var c = table.config,
                    p = c.parserMetadataName || 'sortValue';
                return $cell.data()[p] !== undefined;
            },
            format: function (s, table, cell) {
                var c = table.config,
                    p = c.parserMetadataName || 'sortValue';
                return $(cell).data()[p];
            },
            type: 'numeric'
        });

        $('table.sortable-table').each(function () {
            var headers = {};
            $('thead tr:last th', this).each(function (index, element) {
                headers[index] = {
                    sorter: $(element).data().sort || false
                };
            });

            if ($('tbody tr[data-sort-fixed]', this).length > 0) {
                $('tbody tr[data-sort-fixed]', this).each(function () {
                    var index = $(this).index();
                    $(this).attr('data-sort-fixed', index);
                });
                $(this).on('sortEnd', function () {
                    var table = this;
                    $('tbody tr[data-sort-fixed]', table).remove().each(function () {
                        var index = $(this).data('sortFixed');
                        $('tbody tr:eq(' + index + ')', table).before(this);
                    });
                });
            }

            $(this).tablesorter({
                headers: headers
            });

        });
    }
});