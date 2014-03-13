/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _, CKEDITOR */

/**
 * wysiwyg.js - Replace HTML textareas with WYSIWYG editor.
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
    if (!STUDIP.WYSIWYG) {
        return;
    }

    STUDIP.URLHelper.base_url // workaround: application.js sets base_url too late
        = STUDIP.ABSOLUTE_URI_STUDIP;
    STUDIP.addWysiwyg = replaceTextarea; // for jquery dialogs, see toolbar.js

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
        $('textarea.add_toolbar').each(function(){
            var editor = CKEDITOR.dom.element.get(this).getEditor();
            if ($(this).is(':visible')){
                if (!editor) {
                    if (!$(this).attr('id')) {
                        $(this).attr('id', createNewId('wysiwyg'));
                    }
                    replaceTextarea(this);
                }
            } else if ($(this).parent().css('display') === 'none') {
                if (editor && CKEDITOR.instances[$(this).attr('id')]) {
                    editor.destroy(true);
                }
            }
        });
    }

    function replaceTextarea(textarea) {
        // TODO support jQuery object with multiple textareas
        if (! (textarea instanceof jQuery)) {
            textarea = $(textarea);
        }
        textarea.val(getHtml(textarea.val())); // convert plain text to html

        // create new toolbar
        var textareaWidth = (textarea.width() / textarea.parent().width() * 100) + '%',
            toolbarId = createNewId('cktoolbar'); // needed for sharedSpaces
            toolbar = $('<div>')
                .attr('id', toolbarId)
                .css('max-width', textareaWidth),
            toolbarPlaceholder = $('<div>');

        toolbarPlaceholder.insertBefore(textarea);
        toolbar.insertBefore(textarea);

        // replace textarea with editor
        CKEDITOR.replace(textarea[0], {
            width: textareaWidth,
            skin: 'studip',
            extraPlugins: 'studip-wiki,studip-upload',
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
                {name: 'basicstyles', groups: ['mode', 'basicstyles', 'cleanup']},
                {name: 'paragraph',   groups: ['list', 'indent', 'blocks', 'align']},
                '/',
                {name: 'styles'},
                {name: 'colors'},
                {name: 'tools'},
                {name: 'links'},
                {name: 'insert'}
            ],
            removeButtons: 'Font,FontSize,Anchor',

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
                if (!isHtml(editor.getData())) {
                    editor.setData('<div>' + editor.getData() + '</div>');
                    // update textarea, in case it's accessed by other JS code
                    editor.updateElement();
                }
            });

            // focus editor if corresponding textarea is focused
            $textarea.focus(function (event) { event.editor.focus(); });

            // update textarea on editor blur
            editor.on('blur', function (event) {
                event.editor.updateElement();
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
                var MARGIN = 25;
                // is(':visible'): offset() is wrong for hidden elements
                if (($(window).scrollTop() + MARGIN > toolbarPlaceholder.offset().top)
                        && toolbar.is(':visible')) {
                    toolbar.css({
                        position: 'fixed',
                        top: MARGIN,
                        'max-width': editor.window.getViewPaneSize().width
                    });
                    toolbarPlaceholder.css('height', toolbar.height());
                } else {
                    toolbar.css({
                        position: 'relative',
                        top: '',
                        'max-width': textareaWidth
                    });
                    toolbarPlaceholder.css('height', 0);
                }
            };
            $(window).scroll(stickyTools);
            $(window).resize(stickyTools);
            editor.on('focus', stickyTools); // hidden toolbar might scroll off screen

            // set toolbar's z-index higher than editor's
            // NOTE +1000 because source-view also has higher z-index than editor
            var editorZ = Number(editor.container.getStyle('z-index')) || 0;
            toolbar.css('z-index', editorZ + 1000);

            // focus the editor so the user can immediately hack away...
            editor.focus();
        });
    }

    // convert plain text entries to html
    function getHtml(text) {
        return isHtml(text) ? text : convertToHtml(text);
    }
    function isHtml(text) {
        text = text.trim();
        return text[0] === '<' && text[text.length - 1] === '>';
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
