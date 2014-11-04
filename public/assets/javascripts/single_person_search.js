$(document).ready(function () {

    STUDIP.SinglePersonSearch.init();

});

STUDIP.SinglePersonSearch = {

    // inits all singlepersearch-forms
    init: function() {
        $(".singlepersonsearch_container").each(function() {

            STUDIP.SinglePersonSearch.start($(this).attr('id'));
        });
    },

    // setup
    start: function (name) {
        this.name = name;
        $( '#' + this.name + ' input[type="text"]').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: STUDIP.URLHelper.getURL("dispatch.php/singlepersonsearch/ajax_search/" + name + "?s="  + request.term),
                    dataType: "json",
                    success: function( data ) {
                        response( data );
                    }
                });
            },
            response: function(event, ui) {
                if (ui.content.length === 0) {
                    var searchterm = $('#' + name + ' input[type="text"]').val();
                    if (searchterm.length < 3) {
                        ui.content.push({value:"", label: "Das Suchwort ist zu kurz.".toLocaleString()});
                    } else {
                        ui.content.push({value:"", label: "Kein Ergebnis gefunden.".toLocaleString()});
                    }
                }
            },
            appendTo: "#" + this.name,
            delay: 500,
            minLength: (function () {
                if($('#' + name).attr("data-autocomplete") != 1) {
                    return 100;
                }
                return 3;
            })(),
            select: function(event, ui) {
                $('#' + name + ' input[type="hidden"]').val(ui.item.user_name);
                var func = $( '#' + name).attr('data-jsfunction');
                if (func != undefined) {
                    eval(func + '("' + ui.item.user_name + '","' + ui.item.label + '")');
                }
            }
        }).data( "autocomplete" )._renderItem = function( ul, item ) {
            if (item.avatar !== undefined) {
                var element = $('<li/>')
                .data('item.autocomplete', item )
                .append('<a><img src="'+item.avatar+'">' + item.label.replace(
                new RegExp(this.term, "gi"),
                "<strong>$&</strong>") + '<br>' + item.desc + '</a>')
                .appendTo(ul);
            } else {
                var element = $('<li/>')
                .data('item.autocomplete', item )
                .append( item.label )
                .appendTo(ul);
            }
            return element;
        };

        // trigger search on button click
        $( '#' + this.name + ' input[type="submit"]').click(function(e) {
            e.preventDefault();
            STUDIP.SinglePersonSearch.triggerSearch();
        });

        // trigger search on enter key down
        $( '#' + this.name + ' input[type="text"]').keydown(function(e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                STUDIP.SinglePersonSearch.triggerSearch();
            }
        });

    },

    // start searching now
    triggerSearch: function () {
        var term = $('#' + this.name + ' input[type="text"]').val();
        $('#' + this.name + ' input[type="text"]').autocomplete({ minLength: 1 });
        $('#' + this.name + ' input[type="text"]').autocomplete('search', term);
        $('#' + this.name + ' input[type="text"]').autocomplete({ minLength:  (function () {
                if(!$('#' + name).attr("data-autocomplete")) {
                    return 100;
                }
                return 3;
            })() });
    }

};
