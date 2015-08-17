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

        var count_template = _.template('Sie haben <%= count %> Personen ausgewählt'.toLocaleString());

        this.name = name;

        $('#' + name + '_selectbox').multiSelect({
            selectableHeader: "<div>" + "Suchergebnisse".toLocaleString() + "</div>",
            selectionHeader: "<div>" + count_template({count: "<span id='" + this.name + "_count'>0</span>"}) + ".</div>",
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

        if (count == 0) {
            STUDIP.MultiPersonSearch.append('--', ' Dieser Filter enthält keine (neuen) Personen.'.toLocaleString(), true);
        }

        STUDIP.MultiPersonSearch.refresh();
    },

    isAlreadyMember: function(user_id) {
        if ($('#' + this.name + '_selectbox_default option[value="' + user_id + '"]').length > 0) {
            return true;
        } else {
            return false;
        }
    },

    search: function () {
        var searchterm = $("#" + this.name + "_searchinput").val(),
            name = this.name,
            not_found_template = _.template('Es wurden keine neuen Ergebnisse für "<%= needle %>" gefunden.'.toLocaleString());
        $.getJSON(  STUDIP.URLHelper.getURL("dispatch.php/multipersonsearch/ajax_search/" + this.name + "?s="  + searchterm), function( data ) {
            STUDIP.MultiPersonSearch.removeAllNotSelected();
            var searchcount = 0;
            $.each( data, function( i, item ) {
                searchcount += STUDIP.MultiPersonSearch.append(item.user_id, item.avatar + ' -- ' + item.text, item.member)
            });
            STUDIP.MultiPersonSearch.refresh();

            if (searchcount == 0) {
                STUDIP.MultiPersonSearch.append('--', not_found_template({needle: searchterm}), true);
                STUDIP.MultiPersonSearch.refresh();
            }
        });
        return false;
    },

    selectAll: function () {
       $('#' + this.name + '_selectbox').multiSelect('select_all');
       this.count();
    },

    unselectAll: function () {
        $('#' + this.name + '_selectbox').multiSelect('deselect_all');
        this.count();
    },

    removeAll: function () {
        $('#' + this.name + '_selectbox option').remove();
        this.refresh();
    },

    removeAllNotSelected: function () {
        $('#' + this.name + '_selectbox option:not(:selected)').remove();
        this.refresh();
    },

    resetSearch: function() {
        $("#" + this.name + "_searchinput").val('');
        STUDIP.MultiPersonSearch.removeAllNotSelected();
    },

    append: function (value, text, selected) {
        if ($('#' + this.name + '_selectbox option[value=' + value + ']').length == 0) {
            $('#' + this.name + '_selectbox').multiSelect('addOption', {
                value: value,
                text: text,
                disabled: selected
            });
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
