/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * QuickSelection
 * ------------------------------------------------------------------------ */

QuickSelection = {
    openDialog: function (url) {
        jQuery.ajax ({
                 url: url,

                 success: function(data){
                    $('#quickSelectionDiagWrap').dialog({
                        modal: true,
                        autoOpen:true,

                        title: 'Schnellzugriff konfigurieren',
                        open: function () {
                            $(this).html(data);
                        },
                        buttons: {
                            Ok: function() {
                                $.ajax({
                                    type: 'POST',
                                    url: jQuery("#configure_quickselection").attr('data-url'),
                                    data: jQuery("#configure_quickselection").serialize(),
                                    success: function(data){
                                       jQuery("#quickSelectionWrap").html(data);
                                    }
                                });
                                $(this).dialog('destroy');
                            },
                            Abbrechen: function() {
                                $(this).dialog('destroy');
                            }
                       },
                       height: 400,
                       width: 550,
                       modal: true
                  });
               }
       });

    }
};
