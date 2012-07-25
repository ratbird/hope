/* ------------------------------------------------------------------------
 * QuickSearch inputs
 * ------------------------------------------------------------------------ */

STUDIP.QuickSearch = {
    /**
     * a helper-function to generate a JS-object filled with the variables of a form
     * like "{ input1_name : input1_value, input2_name: input2_value }"
     * @param selector string: ID of an input in a form-tag
     * @return: JSON-object (not as a string)
     */
    formToJSON: function (selector) {
        selector = jQuery(selector).parents("form");
        var form = {};   //the basic JSON-object that will be returned later
        jQuery(selector).find(':input[name]').each(function () {
            var name = jQuery(this).attr('name');   //name of the input
            if (form[name]) {
                //for double-variables (not arrays):
                form[name] = form[name] + ',' + jQuery(this).val();
            } else {
                form[name] = jQuery(this).val();
            }
        });
        return form;
    },
    /**
     * the function to be called from the QuickSearch class template
     * @param name string: ID of input
     * @param url string: URL of AJAX-response
     * @param func string: name of a possible function executed
     *        when user has selected something
     * @return: void
     */
    autocomplete: function (name, url, func, disabled) {
        if (typeof disabled === "undefined" || disabled !== true) {
            jQuery('#' + name).autocomplete({
                delay: 500,
                minLength: 3,
                source: function (input, add) {
                    //get the variables that should be sent:
                    var send_vars = {
                        form_data: STUDIP.QuickSearch.formToJSON('#' + name),
                        request: input.term
                    };
                    jQuery.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: send_vars,
                        success: function (data) {
                            var stripTags = /<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?>|<\/\w+>/gi;
                            //an array of possible selections
                            var suggestions = _.map(data, function (val) {
                                //adding a label and a hidden item_id - don't use "value":
                                return {
                                    //what is displayed in the drop down box
                                    label: val.item_name,
                                    //the hidden ID of the item
                                    item_id: val.item_id,
                                    //what is inserted in the visible input box
                                    value: val.item_search_name !== null ? val.item_search_name : jQuery("<div/>").html(val.item_name.replace(stripTags, "")).text()
                                };
                            });
                            //pass it to the function of UI-widget:
                            add(suggestions);
                        }
                    });
                },
                select: function (event, ui) {
                    //inserts the ID of the selected item in the hidden input:
                    jQuery('#' + name + "_realvalue").attr("value", ui.item.item_id);
                    //and execute a special function defined before by the programmer:
                    if (func) {
                        func(ui.item.item_id, ui.item.label);
                    }
                }
            });
        }
        jQuery('#' + name).placehold();
    }
};

//must be overridden to display html in autocomplete like avatars:
(function () {
    var method_name = "_renderItem";
    jQuery.ui.autocomplete.prototype[method_name] = function (ul, item) {
        return jQuery("<li></li>")
            .data("item.autocomplete", item)
            .append(jQuery("<a></a>").html(item.label))
            .appendTo(ul);
    };
}());
