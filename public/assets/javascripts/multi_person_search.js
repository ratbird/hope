$(document).ready(function () {
    
    STUDIP.MultiPersonSearch.init();
    
    // init form if it is loaded without ajax
    if ($(".mpscontainer").length) {
        STUDIP.MultiPersonSearch.dialog($(".mpscontainer").attr("data-dialogname"));
    }

});

STUDIP.MultiPersonSearch = {
    
    init: function() {
        $(".multi_person_search_link").each(function() {
            // init js form
            $(this).attr("href", $(this).attr("data-js-form"));
            // init form if it is loaded via ajax
            $(this).on('dialog-open', function (event, parameters) {
                STUDIP.MultiPersonSearch.dialog($(parameters.dialog).find(".mpscontainer").attr('data-dialogname'));
            });
        });
    },
    
    dialog: function (name) {
        
        this.name = name;
        
        $('#' + name + '_selectbox').multiSelect({
            selectableHeader: "<div>" + "Suchergebnisse".toLocaleString() + "</div>",
            selectionHeader: "<div>" + _.template('Sie haben <%= count %> Personen ausgewählt'.toLocaleString(), {count: "<span id='" + this.name + "_count'>0</span>"}) + ".</div>",
            selectableFooter: '<a href="javascript:STUDIP.MultiPersonSearch.selectAll();">' + 'Alle hinzufügen'.toLocaleString() + '</a>',
            selectionFooter: '<a href="javascript:STUDIP.MultiPersonSearch.unselectAll();">' + 'Alle entfernen'.toLocaleString() + '</a>'
        });
        
        $("#" + this.name).on("keyup keypress", function(e) {
            var code = e.keyCode || e.which; 
            if (code  == 13) {               
            e.preventDefault();
            STUDIP.MultiPersonSearch.search();
            return false;
            }
        });
        
        $("#" + this.name + "_selectbox").change(function() {
            STUDIP.MultiPersonSearch.count();
        });
        
        $("#" + this.name + " .quickfilter").click(function() {
            STUDIP.MultiPersonSearch.loadQuickfilter($(this).attr("data-quickfilter"));
            return false;
        });
    },
    
    loadQuickfilter: function(title) {
        STUDIP.MultiPersonSearch.removeAllNotSelected();
        
        var count = 0;
        $('#' + this.name + '_quickfilter_' + title + ' option').each(function() {
           count += STUDIP.MultiPersonSearch.append($(this).val(), $(this).text(), STUDIP.MultiPersonSearch.isAlreadyMember($(this).val()));
        });
        STUDIP.MultiPersonSearch.refresh();
        
        if (count == 0) {
            STUDIP.MultiPersonSearch.append('--', ' Dieser Filter enthält keine Personen.'.toLocaleString(), true);
            STUDIP.MultiPersonSearch.refresh();
        }
    },
    
    isAlreadyMember: function(user_id) {
        if ($('#' + this.name + '_selectbox_default option[value="' + user_id + '"]').length > 0) {
            return true;
        } else {
            return false;
        }
    },
    
    search: function () {
        var searchterm = $("#" + this.name + "_searchinput").val();
        searchterm = $("<div>" + searchterm + "</div>").text(); // remove html
        
        var name = this.name;
        $.getJSON(  STUDIP.URLHelper.getURL("dispatch.php/multipersonsearch/ajax_search/" + this.name + "?s="  + searchterm), function( data ) {
            STUDIP.MultiPersonSearch.removeAllNotSelected();
            var searchcount = 0;
            $.each( data, function( i, item ) {
                searchcount += STUDIP.MultiPersonSearch.append(item.user_id, item.avatar + ' -- ' + item.text, item.member)
            });
            STUDIP.MultiPersonSearch.refresh();
            
            if (searchcount == 0) {
                STUDIP.MultiPersonSearch.append('--', _.template('Es wurden keine neuen Ergebnisse für "<%= needle %>" gefunden.'.toLocaleString(), {needle: searchterm}), true);
                STUDIP.MultiPersonSearch.refresh();
            }
        });
        return false;
    },
    
    selectAll: function () {
       $('#' + this.name + '_selectbox option').prop('selected', true);
       $('#' + this.name + '_selectbox').multiSelect('refresh');
       STUDIP.MultiPersonSearch.count();
    },
    
    unselectAll: function () {
        $('#' + this.name + '_selectbox option').prop('selected', false);
        $('#' + this.name + '_selectbox').multiSelect('refresh');
        STUDIP.MultiPersonSearch.count();
    },
    
    removeAll: function () {
        $('#' + this.name + '_selectbox option').remove();
        $('#' + this.name + '_selectbox').multiSelect('refresh');
    },
    
    removeAllNotSelected: function () {
        $('#' + this.name + '_selectbox option:not(:selected)').remove();
        $('#' + this.name + '_selectbox').multiSelect('refresh');
    },
    
    resetSearch: function() {
        $("#" + this.name + "_searchinput").val('');
        STUDIP.MultiPersonSearch.removeAllNotSelected();
    },
    
    append: function (value, text, selected) {
        if ($('#' + this.name + '_selectbox option[value=' + value + ']').length == 0) {
            var option;
            if (selected) {
                option = $('<option value="' + value + '" disabled>' + text + '</option>');
            } else {
                option = $('<option value="' + value + '">' + text + '</option>');
            }
            $('#' + this.name + '_selectbox').append(option);
            
            return 1;
        }
        return 0;
    },
    
    refresh: function() {
        $('#' + this.name + '_selectbox').multiSelect('refresh');
        STUDIP.MultiPersonSearch.count();
    },
    
    count: function () {
        $('#' + this.name + '_count').text($('#' + this.name + '_selectbox option:enabled:selected').length); 
    }
    
};
