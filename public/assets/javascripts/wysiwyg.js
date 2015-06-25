/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _, CKEDITOR */

/**
 * wysiwyg.js - Replace HTML textareas with WYSIWYG editor.
 *
 * Developer documentation can be found at
 * http://docs.studip.de/develop/Entwickler/Wysiwyg.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Robert Costa <zabbarob@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
jQuery(function ($) {
    if (!STUDIP.wysiwyg || STUDIP.wysiwyg.disabled) {
        return;
    }

    if (!CKEDITOR.env.isCompatible) {
        workaroundIncompatibleEnvironment();
        return;
    }

    STUDIP.URLHelper.base_url // workaround: application.js sets base_url too late
        = STUDIP.ABSOLUTE_URI_STUDIP;
    STUDIP.wysiwyg.replace = replaceTextarea; // for jquery dialogs, see toolbar.js

    // replace areas visible on page load
    replaceVisibleTextareas();

    // replace areas that are created or shown after page load
    // remove editors that become hidden after page load
    // show, hide and create do not raise an event, use interval timer
    setInterval(replaceVisibleTextareas, 300);

    // when attaching to hidden textareas, or textareas who's parents are
    // hidden, the editor does not function properly; therefore attach to
    // visible textareas only
    function replaceVisibleTextareas() {
        $('textarea.wysiwyg').each(function() {
            var editor = CKEDITOR.dom.element.get(this).getEditor();
            if (!editor && $(this).is(':visible')) {
                replaceTextarea(this);
            } else if (editor && editor.container && $(editor.container.$).is(':hidden')) {
                editor.destroy(true);
            }
        });
    }

    function replaceTextarea(textarea) {
        // TODO support jQuery object with multiple textareas
        if (! (textarea instanceof jQuery)) {
            textarea = $(textarea);
        }

        // create ID for textarea if it doesn't have one
        if (!textarea.attr('id')) {
            textarea.attr('id', createNewId('wysiwyg'));
        }

        // create new toolbar container
        var textareaWidth = (textarea.width() / textarea.parent().width() * 100) + '%';

        // replace textarea with editor
        CKEDITOR.replace(textarea[0], {
            allowedContent: {
                // NOTE update the dev docs when changing ACF settings!!
                // at http://docs.studip.de/develop/Entwickler/Wysiwyg
                //
                // note that changes here should also be reflected in
                // HTMLPurifier's settings!!
                a: {
                    // note that external links should always have
                    // class="link-extern", target="_blank" and rel="nofollow"
                    // and internal links should not have any attributes except
                    // for href, but this cannot be enforced here
                    attributes: ['!href', 'target', 'rel'],
                    classes: 'link-extern'
                },
                big: {},
                blockquote: {},
                br: {},
                caption: {},
                em: {},
                div: {
                    classes: 'author', // needed for quotes
                    // only allow left margin and horizontal text alignment to
                    // be set in divs
                    // - margin-left should only be settable in multiples of
                    //   40 pixels
                    // - text-align should only be either "center", "right" or
                    //   "justify"
                    // - note that maybe these two features will be removed
                    //   completely in future versions
                    styles: ['margin-left', 'text-align']
                },
                h1: {},
                h2: {},
                h3: {},
                h4: {},
                h5: {},
                h6: {},
                hr: {},
                img: {
                    attributes: ['alt', '!src', 'height', 'width'],
                    // only float:left and float:right should be allowed
                    styles: ['float']
                },
                li: {},
                ol: {},
                p: {},
                pre: {},
                span: {
                    // note that 'wiki-links' are currently set as a span due
                    // to implementation difficulties, but probably this
                    // might be changed in future versions
                    classes: ['wiki-link', 'math-tex'],

                    // note that allowed (background-)colors should be further
                    // restricted
                    styles: ['color', 'background-color']
                },
                strong: {},
                u: {},
                ul: {},
                s: {},
                small: {},
                sub: {},
                sup: {},
                table: {
                    // note that tables should always have the class "content"
                    // (it should not be optional)
                    classes: 'content'
                },
                tbody: {},
                td: {
                    // attributes and styles should be the same
                    // as for <th>, except for 'scope' attribute
                    attributes: ['colspan', 'rowspan'],
                    styles: ['text-align', 'width', 'height']
                },
                thead: {},
                th: {
                    // attributes and styles should be the same
                    // as for <td>, except for 'scope' attribute
                    //
                    // note that allowed scope values should be restricted to
                    // "col", "row" or "col row", if scope is set
                    attributes: ['colspan', 'rowspan', 'scope'],
                    styles: ['text-align', 'width', 'height']
                },
                tr: {},
                tt: {}
            },
            width: textareaWidth,
            skin: 'studip,' +
                (function () {
                    var skinPath = 'assets/stylesheets/ckeditor-skin/';
                    var a = document.createElement('a');
                    a.href = STUDIP.URLHelper.getURL(skinPath);
                    return a.pathname;
                })(),
            // NOTE codemirror crashes when not explicitely loaded in CKEditor 4.4.7
            extraPlugins: 'codemirror,studip-floatbar,studip-quote,studip-settings,studip-wiki'
                // only enable uploads in courses with a file section
                + ($('li#nav_course_files').length > 0 ? ',studip-upload' : ''),
            enterMode: CKEDITOR.ENTER_BR,
            mathJaxLib: STUDIP.URLHelper.getURL('assets/javascripts/mathjax/MathJax.js?config=TeX-AMS_HTML,default'),
            studipUpload_url: STUDIP.URLHelper.getURL('dispatch.php/wysiwyg/upload'),
            codemirror: {
                autoCloseTags: false,
                autoCloseBrackets: false,
                showSearchButton: false,
                showFormatButton: false,
                showCommentButton: false,
                showUncommentButton: false,
                showAutoCompleteButton: false
            },
            autoGrow_onStartup: true,

            // configure toolbar
            toolbarGroups: [
                {name: 'basicstyles', groups: ['undo', 'basicstyles', 'cleanup']},
                {name: 'paragraph',   groups: ['list', 'indent', 'blocks', 'align']},
                '/',
                {name: 'styles'},
                {name: 'colors'},
                {name: 'tools'},
                {name: 'links'},
                {name: 'insert'},
                {name: 'others', groups: ['mode', 'settings']}
            ],
            removeButtons: 'Font,FontSize,Anchor',
            toolbarCanCollapse: true,
            toolbarStartupExpanded: textarea.width() > 420,

            // configure dialogs
            removeDialogTabs: 'image:Link;image:advanced;'
                + 'link:target;link:advanced;'
                + 'table:advanced',

            // convert special chars except latin ones to html entities
            entities: false,
            entities_latin: false,
            entities_processNumerical: true,

            // configure list of special characters
            // NOTE 17 characters fit in one row of special characters dialog
            specialChars: [].concat(
                [   "&Agrave;", "&Aacute;", "&Acirc;", "&Atilde;", "&Auml;",
                    "&Aring;", "&AElig;", "&Egrave;", "&Eacute;", "&Ecirc;", "&Euml;",
                    "&Igrave;", "&Iacute;", "&Iuml;", "&Icirc;", "", "&Yacute;",

                    "&agrave;", "&aacute;", "&acirc;", "&atilde;", "&auml;",
                    "&aring;", "&aelig;", "&egrave;", "&eacute;", "&ecirc;", "&euml;",
                    "&igrave;", "&iacute;", "&iuml;", "&icirc;", "", "&yacute;",

                    "&Ograve;", "&Oacute;", "&Ocirc;", "&Otilde;", "&Ouml;",
                    "&Oslash;", "&OElig;", "&Ugrave;", "&Uacute;", "&Ucirc;", "&Uuml;",
                    "", "&Ccedil;", "&Ntilde;", "&#372;", "", "&#374",

                    "&ograve;", "&oacute;", "&ocirc;", "&otilde;", "&ouml;",
                    "&oslash;", "&oelig;", "&ugrave;", "&uacute;", "&ucirc;", "&uuml;",
                    "", "&ccedil;", "&ntilde;", "&#373", "", "&#375;",

                    "&szlig;", "&ETH;", "&eth;", "&THORN;", "&thorn;", "", "",
                    "`", "&acute;", "^", "&uml;", "", "&cedil;", "~", "&asymp;", "",
                    "&yuml;"
                ],
                (function () {
                    var greek = [];
                    for (var i = 913; i <= 929; i++) { // 17 uppercase characters
                        greek.push("&#" + String(i));
                    }
                    for (var i = 945; i <= 962; i++) { // 17 lowercase characters
                        greek.push("&#" + String(i));
                    }
                    // NOTE character #930 is not assigned!!
                    for (var i = 931; i <= 937; i++) { // remaining upercase
                        greek.push("&#" + String(i));
                    }
                    greek.push('');
                    for (var i = 963; i <= 969; i++) { // remaining lowercase
                        greek.push("&#" + String(i));
                    }
                    greek.push('');
                    return greek;
                })(),
                [   "&ordf;", "&ordm;", "&deg;", "&sup1;", "&sup2;", "&sup3;",
                    "&frac14;", "&frac12;", "&frac34;",
                    "&lsquo;", "&rsquo;", "&ldquo;", "&rdquo;", "&laquo;", "&raquo;",
                    "&iexcl;", "&iquest;",

                    '@', "&sect;", "&para;", "&micro;",
                    "[", "]", '{', '}',
                    '|', "&brvbar;", "&ndash;", "&mdash;", "&macr;",
                    "&sbquo;", "&#8219;", "&bdquo;", "&hellip;",

                    "&euro;", "&cent;", "&pound;", "&yen;", "&curren;",
                    "&copy;", "&reg;", "&trade;",

                    "&not;", "&middot;", "&times;", "&divide;",

                    "&#9658;", "&bull;",
                    "&rarr;", "&rArr;", "&hArr;",
                    "&diams;",

                    "&#x00B1", // ±
                    "&#x2229", // ∩ INTERSECTION
                    "&#x222A", // ∪ UNION
                    "&#x221E", // ∞ INFINITY
                    "&#x2107", // ℇ EULER CONSTANT
                    "&#x2200", // ∀ FOR ALL
                    "&#x2201", // ∁ COMPLEMENT
                    "&#x2202", // ∂ PARTIAL DIFFERENTIAL
                    "&#x2203", // ∃ THERE EXISTS
                    "&#x2204", // ∄ THERE DOES NOT EXIST
                    "&#x2205", // ∅ EMPTY SET
                    "&#x2206", // ∆ INCREMENT
                    "&#x2207", // ∇ NABLA
                    "&#x2282", // ⊂ SUBSET OF
                    "&#x2283", // ⊃ SUPERSET OF
                    "&#x2284", // ⊄ NOT A SUBSET OF
                    "&#x2286", // ⊆ SUBSET OF OR EQUAL TO
                    "&#x2287", // ⊇ SUPERSET OF OR EQUAL TO
                    "&#x2208", // ∈ ELEMENT OF
                    "&#x2209", // ∉ NOT AN ELEMENT OF
                    "&#x2227", // ∧ LOGICAL AND
                    "&#x2228", // ∨ LOGICAL OR
                    "&#x2264", // ≤ LESS-THAN OR EQUAL TO
                    "&#x2265", // ≥ GREATER-THAN OR EQUAL TO
                    "&#x220E", // ∎ END OF PROOF
                    "&#x220F", // ∏ N-ARY PRODUCT
                    "&#x2211", // ∑ N-ARY SUMMATION
                    "&#x221A", // √ SQUARE ROOT
                    "&#x222B", // ∫ INTEGRAL
                    "&#x2234", // ∴ THEREFORE
                    "&#x2235", // ∵ BECAUSE
                    "&#x2260", // ≠ NOT EQUAL TO
                    "&#x2262", // ≢ NOT IDENTICAL TO
                    "&#x2263", // ≣ STRICTLY EQUIVALENT TO
                    "&#x22A2", // ⊢ RIGHT TACK
                    "&#x22A3", // ⊣ LEFT TACK
                    "&#x22A4", // ⊤ DOWN TACK
                    "&#x22A5", // ⊥ UP TACK
                    "&#x22A7", // ⊧ MODELS
                    "&#x22A8", // ⊨ TRUE
                    "&#x22AC", // ⊬ DOES NOT PROVE
                    "&#x22AD", // ⊭ NOT TRUE
                    "&#x22EE", // ⋮ VERTICAL ELLIPSIS
                    "&#x22EF", // ⋯ MIDLINE HORIZONTAL ELLIPSIS
                    "&#x29FC", // ⧼ LEFT-POINTING CURVED ANGLE BRACKET
                    "&#x29FD", // ⧽ RIGHT-POINTING CURVED ANGLE BRACKET
                    "&#x207F", // ⁿ SUPERSCRIPT LATIN SMALL LETTER N
                    "&#x2295", // ⊕ CIRCLED PLUS
                    "&#x2297", // ⊗ CIRCLED TIMES
                    "&#x2299", // ⊙ CIRCLED DOT OPERATOR
                ]
            ),
            on: { pluginsLoaded: onPluginsLoaded }
        }); // CKEDITOR.replace(textarea[0], {

        CKEDITOR.on('instanceReady', function (event) {
            var editor = event.editor,
                $textarea = $(editor.element.$);

            // auto-resize editor area in source view mode, and keep focus!
            editor.on('mode', function (event) {
                var editor = event.editor;
                if (editor.mode === 'source') {
                    $(editor.container.$).find('.cke_source').focus();
                } else {
                    editor.focus();
                }
            });

            // update textarea on editor blur
            editor.on('blur', function (event) {
                event.editor.updateElement();
            });
            $(editor.container.$).on('blur', '.CodeMirror', function (event) {
                editor.updateElement(); // also update in source mode
            });

            // blurDelay = 0 is an ugly hack to be faster than Stud.IP
            // forum's save function; might produce "strange" behaviour
            CKEDITOR.focusManager._.blurDelay = 0;

            // display "focused"-effect when editor area is focused
            editor.on('focus', function (event) {
                event.editor.container.addClass('cke_chrome_focused');
            });
            editor.on('blur', function (event) {
                event.editor.container.removeClass('cke_chrome_focused');
            });

            // keep the editor focused when a toolbar item gets selected
            editor.on('blur', function (event) {
                var toolbarContainer = $('#' + event.editor.config.sharedSpaces.top);
                if (toolbarContainer.has(':focus').length > 0) {
                    event.editor.focus();
                }
            });

            // Trigger load event for the editor event. Uses the underlying
            // textarea element to ensure that the event will be catchable by
            // jQuery.
            $textarea.trigger('load.wysiwyg');

            // focus the editor so the user can immediately hack away...
            editor.focus();
        });
    }

    // customize existing dialog windows
    CKEDITOR.on('dialogDefinition', function(ev) {
        var dialogName = ev.data.name,
            dialogDefinition = ev.data.definition;

        if (dialogName == 'table') {
            var infoTab = dialogDefinition.getContents('info');
            infoTab.get('txtBorder')['default'] = '';
            infoTab.get('txtWidth')['default'] = '';
            infoTab.get('txtCellSpace')['default'] = '';
            infoTab.get('txtCellPad')['default'] = '';

            var advancedTab = dialogDefinition.getContents('advanced');
            advancedTab.get('advCSSClasses')['default'] = 'content';
        }
    });

    // editor events
    function onPluginsLoaded(event) {
        // tell editor to always remove html comments
        event.editor.dataProcessor.htmlFilter.addRules({
            comment: function () { return false; }
        });
    }

    //// helpers for environments where ckeditor doesn't work

    function workaroundIncompatibleEnvironment() {
        // do nothing when other JS code wants to replace textareas
        STUDIP.wysiwyg.replace = function () { };

        // JS does not emit an event when new DOM nodes are
        // inserted. This interval timer checks for new
        // textareas.
        setInterval(function () {
            $('textarea.wysiwyg').each(function (index, textarea) {
                onSubmitConvertToHtml(textarea);
            });
        }, 300);
    }

    // convert content of wysiwyg textareas to html when
    // user presses submit button of surrounding form
    function onSubmitConvertToHtml(textarea) {
        // detach from invisible textareas
        if (!$(textarea).is(':visible')) {
            delete textarea.containsHtml;
            return;
        }

        // return if we are already attached to this textarea
        if (textarea.hasOwnProperty('containsHtml')) {
            return;
        }

        // remember if contents already are html
        textarea.containsHtml = textarea.value.trim().length > 0;

        // on submit replace plain text with html contents
        var form = $(textarea).closest('form');
        form.submit({ textarea: textarea }, function (event) {
            var t = event.data.textarea;
            if (!t.containsHtml) {
                t.value = text2Html(t.value);
                t.containsHtml = true;
            }
        });
    }

    //// helpers that could be useful not only for wysiwyg

    // convert plain text to html
    function text2Html(text) {
        return newline2br(htmlEncode(text));
    }

    // encode html entities
    function htmlEncode(text) {
        return $('<div/>').text(text).html();
    }

    // convert all newline characters to <br> tags
    function newline2br(text) {
        return text.replace(/(?:\r\n|\r|\n)/g, '<br />\n');
    }

    // create an unused id
    function createNewId(prefix) {
        var i = 0;
        while ($('#' + prefix + i).length > 0) {
            i++;
        }
        return prefix + i;
    }
}); // jQuery(function($){
