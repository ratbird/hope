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
        $('textarea.add_toolbar').each(function() {
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

        // convert plain text to html
        textarea.val(getHtml(textarea.val()));

        // create new toolbar container
        var textareaWidth = (textarea.width() / textarea.parent().width() * 100) + '%',
            toolbarId = createNewId('cktoolbar'); // needed for sharedSpaces
            toolbar = $('<div>')
                .attr('id', toolbarId)
                .css('max-width', textareaWidth),
            toolbarPlaceholder = $('<div>').attr('id', toolbarId + '-placeholder');

        toolbarPlaceholder.insertBefore(textarea);
        toolbar.insertBefore(textarea);

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
                br: {},
                caption: {},
                em: {},
                div: {
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
                    classes: 'wiki-link',

                    // note that allowed (background-)colors should be further
                    // restricted
                    styles: ['color', 'background-color']
                },
                strong: {},
                u: {},
                ul: {},
                s: {},
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
                tr: {}
            },
            width: textareaWidth,
            skin: 'studip',
            // NOTE codemirror crashes when not explicitely loaded in CKEditor 4.4.7
            extraPlugins: 'codemirror,studip-settings,studip-wiki'
                // only enable uploads in courses with a file section
                + ($('li#nav_course_files').length > 0 ? ',studip-upload' : ''),
            enterMode: CKEDITOR.ENTER_BR,
            studipUpload_url: STUDIP.URLHelper.getURL('dispatch.php/wysiwyg/upload'),
            codemirror: {
                showSearchButton: false,
                showFormatButton: false,
                showCommentButton: false,
                showUncommentButton: false,
                showAutoCompleteButton: false
            },
            autoGrow_onStartup: true,

            // configure toolbar
            sharedSpaces: { // needed for sticky toolbar (see stickyTools())
                top: toolbarId
            },
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
            )
        }); // CKEDITOR.replace(textarea[0], {

        CKEDITOR.on('instanceReady', function (event) {
            var editor = event.editor,
                $textarea = $(editor.element.$);
        
            // disable default browser drop action on iframe body
            var iframe_body = $(editor.container.$).find('iframe')[0]
            .contentWindow.document.getElementsByTagName('body')[0];
            iframe_body.setAttribute('ondragstart', 'return false');
            iframe_body.setAttribute('ondrop', 'return false');

            // NOTE some HTML elements are output on their own line so that old
            // markup code and older plugins run into less problems

            // output divivisons as
            // text before
            // <div>
            // Text
            // </div>
            // text after
            editor.dataProcessor.writer.setRules('div', {
                indent: false,
                breakBeforeOpen: true,
                breakAfterOpen: true,
                breakBeforeClose: true,
                breakAfterClose: true
            });

            // output paragraphs as
            // text before
            // <p>
            // Text
            // </p>
            // text after
            editor.dataProcessor.writer.setRules('p', {
                indent: false,
                breakBeforeOpen: true,
                breakAfterOpen: true,
                breakBeforeClose: true,
                breakAfterClose: true
            });

            // auto-resize editor area in source view mode, and keep focus!
            editor.on('mode', function (event) {
                var editor = event.editor;
                if (editor.mode === 'source') {
                    $(editor.container.$).find('.cke_source').focus();
                } else {
                    editor.focus();
                }
            });

            // clean up HTML edited in source mode before submit
            var form = $textarea.closest('form');
            form.submit(function (event) {
                // make sure HTML marker is always set, in
                // case contents are cut-off by the backend
                var w = STUDIP.wysiwyg;
                editor.setData(w.markAsHtml(editor.getData()));
                editor.updateElement(); // update textarea, in case it's accessed by other JS code
            });

            // focus editor if corresponding textarea is focused
            $textarea.focus(function (event) { event.editor.focus(); });

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

            // do not scroll toolbar out of viewport
            function stickyTools() {
                updateStickyTools(editor);
            };
            $(window).scroll(stickyTools);
            $(window).resize(stickyTools);
            editor.on('focus', stickyTools); // hidden toolbar might scroll off screen

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

    // editor utilities
    function updateStickyTools(editor) {
        var MARGIN = $('#barBottomContainer').length ? $('#barBottomContainer').height() : 25,
            toolbarId = editor.config.sharedSpaces.top,
            toolbar = $('#' + toolbarId),
            placeholder = $('#' + toolbarId + '-placeholder');

        if (toolbar.length === 0 || placeholder.length === 0) {
            // toolbar/editor removed by some JS code (e.g. when sending messages)
            return;
        }

        var outOfView = $(window).scrollTop() + MARGIN
                        > placeholder.offset().top,
            width = $(editor.container.$).outerWidth(true);

        // is(':visible'): offset() is wrong for hidden elements
        if (toolbar.is(':visible') && outOfView) {
            toolbar.css({
                position: 'fixed',
                top: MARGIN,
                width: width
            });
            placeholder.css('height', toolbar.height());
        } else {
            toolbar.css({
                position: 'relative',
                top: '',
                width: width
            });
            placeholder.css('height', 0);
        }
    }

    // convert plain text entries to html
    function getHtml(text) {
        return STUDIP.wysiwyg.isHtml(text) ? text : convertToHtml(text);
    }
    function convertToHtml(text) {
        var quote = getQuote(text);
        if (quote) {
            var quotedHtml = getHtml(quote[2].trim());
            return '<p>' + quote[1] + quotedHtml + quote[3] + '</p>';
        }
        return replaceNewlines(encodeHtmlEntities(text));
    }
    function getQuote(text) {
        // matches[1] = quote start  \[quote(=.*?)?\]
        // matches[2] = quoted text  [\s\S]*   (multiline)
        // matches[3] = quote end    \[\/quote\]
        return text.match(
            /^\s*(\[quote(?:=.*?)?\])([\s\S]*)(\[\/quote\])\s*$/) || null;
    }
    function encodeHtmlEntities(text) {
        return $('<div>').text(text).html();
    }
    function replaceNewlines(text) {
        return replaceNewlineWithBr(replaceMultiNewlinesWithP(text));
    }
    function replaceMultiNewlinesWithP(text) {
        return '<p>' + text.replace(/(\r?\n|\r){2,}/, '</p><p>') + '</p>';
    }
    function replaceNewlineWithBr(text) {
        return text.replace(/(\r?\n|\r)/g, '<br>\n');
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
