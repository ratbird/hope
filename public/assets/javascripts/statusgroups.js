$(document).ready(function() {

    //prepare group order interface
    $('.dd').nestable({});

    //do everything you would do after a reload
    afterReload();

    //we dont want to reload (hard) the page when enter is pressed in the
    //ppl search input
    $('#ppl_search').keydown(function(e) {
        e = e || event;
        if (e.keyCode === 13) {
            e.preventDefault();
        }
    });

    //interactive ppl search
    $('#ppl_search').keyup(function() {
        var fadeSpeed = 200;
        var people = $(".person");
        var search = $('#ppl_search').val();
        var found = 0;
        if (!search) {
            people.fadeIn(fadeSpeed);
            $('#free_search, #search_result').fadeOut(fadeSpeed);
        } else {
            people.each(function() {
                if ($(this).text().toUpperCase().indexOf(search.toUpperCase()) >= 0) {
                    $(this).fadeIn(fadeSpeed);
                    if ($(this).hasClass('pre')) {
                        found++;
                    }
                } else {
                    $(this).fadeOut(fadeSpeed);
                }
            });
            if (found < 10) {
                delay(function(){
                $.ajax({
                    type: 'POST',
                    url: $('#ajax_search').val(),
                    dataType: 'json',
                    data: {query: search, limit: 10 - found},
                    async: true
                }).done(function(data) {
                    $('#search_result').empty();
                    if (data.length > 0) {
                        jQuery.each(data, function(i, val) {
                            if ($('#' + val.id).length === 0) {
                                $('#search_result').append('<p id="' + (val.id) + '" style="margin: 0px;" class="person">' + val.name + '</p>');
                            }
                        });
                        afterReload();
                    }
                });
                $('#free_search, #search_result').fadeIn(fadeSpeed);
                }, 800 );
            } else {
                $('#free_search, #search_result').fadeOut(fadeSpeed);
            }
        }
    });
    
    var editButtons = {};
    editButtons["Übernehmen".toLocaleString()] = function() {
        var id = $(this).attr('id').substr(5);
        $('#form_' + id).submit();
        $(this).dialog("close");
    };
    editButtons["Abbrechen".toLocaleString()] = function() {
        $(this).dialog("close");
    };

    // Create dialog to edit groups
    $(".edit_dialog").dialog({
        autoOpen: false,
        height: 340,
        width: 300,
        resizable: false,
        buttons: editButtons
    });

    var orderButtons = {};
    orderButtons["Übernehmen".toLocaleString()] = function() {
        var text = JSON.stringify($('.dd').nestable('serialize'));
        $.ajax({
            type: 'POST',
            url: $('#ajax_order').val(),
            dataType: 'html',
            data: {json: text}
        }).done(function() {
            location.reload()
        });
        $(this).dialog("close");
    };
    orderButtons["Abbrechen".toLocaleString()] = function() {
        $(this).dialog("close");
    };
    // Create the dialog to change orders
    $(".order_dialog").dialog({
        autoOpen: false,
        height: 340,
        width: 500,
        resizable: false,
        buttons: orderButtons
    });

    // Clicking the edit Button should 
    $(".edit").click(function() {
        var id = $(this).closest("table").attr('id');
        $('#edit_' + id).dialog("open");
    });
});

/* 
 * Delay function to wait for some time before execution continues 
 * In this context we need this function to wait until the user stops typing
 * in the search for an amount of time, because we do not want to spam ajax
 * commands
 */

var delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();

function newgroup() {
    $('#edit_newgroup').dialog("open");
}

function order() {
    $('#edit_order').dialog("open");
}
//reattach all jQuery stuff after ajax reload
function afterReload() {

//make people moveable
    $(".person").draggable({revert: true,
        scroll: true,
        scrollSensitivity: 100,
        helper: "clone",
        start: function(event, ui) {
            var id = $(this).attr('id');
            $('.dropable').each(function(index, value) {
                var user = $(this).find('#' + id);
                if (user.length > 0) {
                    $(this).fadeTo(400, 0.3);
                    user.fadeTo(400, 1);
                }
            });
        },
        stop: function(event, ui) {
            $('.dropable').fadeTo(400, 1);
        },
        revertDuration: 0
        });

//make tables droppable
    $(".dropable").droppable({
        drop: function(event, ui) {
            var table_id = $(this).attr('id');
            $.ajax({
                type: 'POST',
                url: $('#ajax_add').val(),
                dataType: 'html',
                data: {group: table_id, user: $(ui.draggable).attr('id')},
                async: false
            }).done(function(data) {
                $('#' + table_id + " tbody").html(data);
                afterReload();
            });
        }
    });

    //create Drag n Drop Table
    $('.moveable').tableDnD({
        dragHandle: ".drag",
        onDrop: function(table, row) {
            var rows = table.tBodies[0].rows;
            var newposition = 0;
            while (row !== rows[newposition]) {
                newposition++;
            }
            $.ajax({
                type: 'POST',
                url: $('#ajax_move').val(),
                dataType: 'html',
                data: {group: table.id, user: row.id, pos: newposition},
                async: false
            }).done(function(data) {
                //table.tBodies[0].innerHTML = data;
                afterReload();
            });
        }
    });

    //create Delete Ajax Event
    $(".delete").click(function(e) {
        e.preventDefault();
        var table_id = $(this).closest('table').attr('id');
        var user = $(this).closest('tr');
        $.ajax({
            type: 'POST',
            url: $('#ajax_delete').val(),
            dataType: 'html',
            data: {group: table_id, user: user.attr('id')},
            async: true
        }).done(function(data) {
            $('#' + table_id + " tbody").html(data);
            afterReload();
        });
        user.remove();
    });
}