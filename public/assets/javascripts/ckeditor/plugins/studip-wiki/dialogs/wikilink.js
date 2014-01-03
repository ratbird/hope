CKEDITOR.dialog.add('wikiDialog', function (editor) {
    // studip wiki link specification
    // * allowed characters: a-z.-:()_§/@# äöüß
    // * enclose in double-brackets: [[wiki link]]
    // * leading or trailing whitespace is allowed!!
    // * extended: [[wiki link| displayed text]]
    // * displayed text characters can be anything but ]

    // utilities

    function getParameterByName(name, query) {
        query = typeof query === 'undefined' ? location.search : query;
        // http://stackoverflow.com/a/901144/641481
        name = name.replace(/[\[]/, '\\\[').replace(/[\]]/, '\\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)'),
            results = regex.exec(query);
        return results == null ? '' : decodeURIComponent(
            results[1].replace(/\+/g, ' '));
    }

    function getQueryString(href) {
        return href ? ((href.split('?')[1] || '').split('#')[0] || '') : '';
    }

    function array_flip(trans) {
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

    function getWikiPage(href) {
        return getParameterByName(
            'keyword', '?' + fromWindows1252(getQueryString(href)));
    }

    function getWikiLink(wikipage) {
        return STUDIP.URLHelper.getURL('wiki.php', {
            cid: getParameterByName('cid')
        }) + '&keyword=' + toWindows1252(wikipage);
    }

    // dialog
    return {
        title: "Stud.IP-Wiki Link",
        minWidth: 400,
        minHeight: 200,
        contents: [{
            id: 'tab-link',
            label: "Stud.IP-Wiki Link",
            elements: [{
                type: 'text',
                id: 'wikipage',
                label: "Titel der Wiki-Seite",
                validate: CKEDITOR.dialog.validate.regex(
                    /^[\w\.\-\:\(\)§\/@# ÄÖÜäöüß]+$/i,
                    "Der Seitenname muss aus mindestens einem Zeichen bestehen"
                    + " und darf nur folgende Zeichen enthalten:"
                    + " a-z A-Z ÄÖÜ äöü ß 0-9 -_:.( )/@#§ und das Leerzeichen."),
                setup: function(link) {
                    this.setValue(getWikiPage(link.getAttribute('href')));
                },
                commit: function(link) {
                    var href = getWikiLink(this.getValue());
                    link.setAttribute('href', href);
                    link.setAttribute('data-cke-saved-href', href);
                    link.setAttribute('class', 'wiki-link');
                }
            }, {
                type: 'text',
                id: 'usertext',
                label: "Angezeigter Text (optional)",
                validate: CKEDITOR.dialog.validate.regex(
                    /^[^\]]*$/i,
                    "Die schließende eckige Klammer, ], ist im angezeigten"
                    + " Text leider nicht erlaubt."),
                setup: function(link) {
                    var usertext = link.getText(),
                        wikipage = getWikiPage(link.getAttribute('href'));
                    this.setValue(usertext === wikipage ? '' : usertext);
                },
                commit: function(link) {
                    var usertext = this.getValue(),
                        wikipage = this._.dialog.getValueOf('tab-link', 'wikipage');
                    link.setText(usertext || wikipage);
                }
            }]
        }],
        onShow: function() {
            // get selected element
            var element = editor.getSelection().getStartElement();
            if (element) {
                element = element.getAscendant('a', true);
            }

            // if no link is selected, insert a new one
            this.insertMode = !element || element.getName() != 'a';
            if (this.insertMode) {
                element = editor.document.createElement('a');
                var text = editor.getSelection().getSelectedText();
                if (text) {
                    this.setValueOf('tab-link', 'wikipage', text);
                }
            } else {
                this.setupContent(element);
            }

            this.link = element;
        },
        onOk: function() {
            this.commitContent(this.link);
            if (this.insertMode) {
                editor.insertElement(this.link);
            }
        }
    };
});
