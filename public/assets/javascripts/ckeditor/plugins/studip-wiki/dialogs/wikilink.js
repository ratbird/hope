CKEDITOR.dialog.add('wikiDialog', function (editor) {
    // studip wiki link specification
    // * allowed characters: a-z.-:()_§/@# äöüß
    // * enclose in double-brackets: [[wiki link]]
    // * leading or trailing whitespace is allowed!!
    // * extended: [[wiki link| displayed text]]
    // * displayed text characters can be anything but ]

    // utilities
/*    function array_flip(trans) {
        // http://phpjs.org/functions/array_flip/
        // http://kevin.vanzonneveld.net
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +      improved by: Pier Paolo Ramon (http://www.mastersoup.com/)
        // +      improved by: Brett Zamir (http://brett-zamir.me)
        // *     example 1: array_flip( {a: 1, b: 1, c: 2} );
        // *     returns 1: {1: 'b', 2: 'c'}
        // *     example 2: ini_set('phpjs.return_phpjs_arrays', 'on');
        // *     example 2: array_flip(array({a: 0}, {b: 1}, {c: 2}))[1];
        // *     returns 2: 'b'

        // duck-type check for our own array()-created PHPJS_Array
        if (trans && typeof trans === 'object' && trans.change_key_case) {
            return trans.flip();
        }

        var tmp_ar = {};
        for (var key in trans) {
            if (trans.hasOwnProperty(key)) {
                tmp_ar[trans[key]] = key;
            }
        }
        return tmp_ar;
    }

    var translation = {
        ' ': '%20', '#': '%23', '(': '%28', ')': '%29',
        '/': '%2F', ':': '%3A', '@': '%40', '§': '%A7',
        'Ä': '%C4', 'Ö': '%D6', 'Ü': '%DC', 'ß': '%DF',
        'ä': '%E4', 'ö': '%F6', 'ü': '%FC'
    };
    var backtrans = array_flip(translation);

    function toWindows1252(text) {
        // replace special chars with windows 1252 encoding
        // test string: azAZ09_-. #()/:@§ÄÖÜßäöü
        // TODO create regexp from translation keys
        return text.replace(/[ #()/:@§ÄÖÜßäöü]/g, function(match) {
            return translation[match];
      });
    }

    function fromWindows1252(text) {
        // TODO create regexp from backtrans keys
        // don't replace # === 23!!
        return text.replace(/%(20|28|29|2F|3A|40|A7|C4|D6|DC|DF|E4|F6|FC)/g,
                            function(match) {
            return backtrans[match];
        });
    }
*/
    // dialog
    return {
        title: 'Stud.IP-Wiki Link',
        minWidth: 400,
        minHeight: 200,
        contents: [{
            id: 'tab-link',
            label: 'Stud.IP-Wiki Link',
            elements: [{
                type: 'text',
                id: 'wikipage',
                label: 'Titel der Wiki-Seite',
                // TODO regex encoding is not working correctly
                // ==> german umlauts cannot be entered using the wiki widget
                // ==> users have to manually enter wikilinks with umlauts atm.
                validate: CKEDITOR.dialog.validate.regex(
                    /^[\w\.\-\:\(\)§\/@# ÄÖÜäöüß]+$/i,
                    'Der Seitenname muss aus mindestens einem Zeichen bestehen'
                    + ' und darf nur folgende Zeichen enthalten:'
                    + ' a-z A-Z ÄÖÜ äöü ß 0-9 -_:.( )/@#§ und das Leerzeichen.'),
                setup: function(widget) {
                    this.setValue(widget.data.link);
                },
                commit: function(widget) {
                    widget.setData('link', this.getValue());
                }
            }, {
                type: 'text',
                id: 'usertext',
                label: 'Angezeigter Text (optional)',
                validate: CKEDITOR.dialog.validate.regex(
                    /^[^\]]*$/i,
                    'Die schließende eckige Klammer, ], ist im angezeigten'
                    + ' Text leider nicht erlaubt.'),
                setup: function(widget) {
                    this.setValue(widget.data.text);
                },
                commit: function(widget) {
                    widget.setData('text', this.getValue());
                }
            }]
        }]
    };
});
