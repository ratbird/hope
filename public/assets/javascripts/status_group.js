STUDIP.StatusGroup = {
    init: function () {
        // make the tables in the div sortable
        jQuery('div.sortable').sortable({
            axis: 'y',
            items: "table.sortable",
            handle: 'tr.handle',

            stop: function () {
                // iterate over the statusgroups and collect the ids
                var statusgroup_ids = {};
                statusgroup_ids.statusgroup_ids = {};
                //areas.areas = {};
                jQuery('div.sortable').find('table.sortable').each(function () {
                    statusgroup_ids.statusgroup_ids[jQuery(this).attr('id')] = jQuery(this).attr('id');
                });

                jQuery.ajax({
                    type: 'POST',
                    url: STUDIP.URLHelper.getURL('contact_statusgruppen.php?cmd=storeSortOrder'),
                    data: statusgroup_ids
                });
            }
        });
    }
};
