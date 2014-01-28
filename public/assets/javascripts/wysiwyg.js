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
jQuery(function($){
    if (!STUDIP.WYSIWYG){
        return;
    }

    // workaround: application.js sets base_url too late
    STUDIP.URLHelper.base_url = STUDIP.ABSOLUTE_URI_STUDIP;

    function replaceTextarea(textarea){
        var uiColor = '#7788AA';  // same as studip's tab navigation background

        // convert plain text entries to html
        function isHtml(text) {
            text = text.trim();
            return text[0] == '<' && text[text.length - 1] == '>';
        }
        function encodeHtmlEntities(text) {
            return $('<div>').text(text).html();
        }
        function replaceMultiNewlinesWithP(text) {
            return '<p>' + text.replace(/(\r?\n|\r){2,}/, '</p><p>') + '</p>';
        }
        function replaceNewlineWithBr(text) {
            return text.replace(/(\r?\n|\r)/g, '<br>\n');
        }
        function replaceNewlines(text) {
            return replaceNewlineWithBr(replaceMultiNewlinesWithP(text));
        }
        function convertToHtml(text) {
            return replaceNewlines(encodeHtmlEntities(text));
        }
        function getHtml(text) {
            return isHtml(text) ? text : convertToHtml(text);
        }
        textarea.val(getHtml(textarea.val()));

        // find an unused toolbarId
        // toolbarId is needed for sharedSpaces
        var toolbarPrefix = 'cktoolbar',
            toolbarIndex = 0,
            toolbarId = toolbarPrefix + toolbarIndex;

        while ($('#' + toolbarId).length != 0) {
            toolbarIndex += 1;
            toolbarId = toolbarPrefix + toolbarIndex;
        }

        // create new toolbar
        var toolbar = $('<div>').attr('id', toolbarId);
        var toolbar_placeholder = $('<div>');
        toolbar_placeholder.insertBefore(textarea);
        toolbar.insertBefore(textarea);

        // replace textarea with editor
        CKEDITOR.replace(textarea[0], {
            customConfig: '',
            uiColor: uiColor,
            removePlugins: 'about,anchor,bidi,blockquote,div,elementspath,flash'
                           + ',forms,iframe,maximize,newpage,preview,resize'
                           + ',showblocks,stylescombo,templates,save,smiley',
            extraPlugins: 'autogrow,divarea,sharedspace,studip-wiki,studip-upload',
            studipUpload_url: STUDIP.URLHelper.getURL('dispatch.php/wysiwyg/upload'),
            autoGrow_onStartup: true,
            sharedSpaces: { // needed for sticky toolbar (see stickyTools())
            top: toolbarId
            },
            toolbarGroups: [
                {name: 'basicstyles', groups: ['mode', 'basicstyles', 'cleanup']},
                {name: 'paragraph',   groups: ['list', 'indent', 'blocks', 'align']},
                {name: 'links'},
                '/',
                {name: 'styles'},
                {name: 'colors'},
                {name: 'tools'},
                {name: 'insert'},
                {name: 'others'},
                {name: 'about'}
            ],

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
                (function() {
                    greek = [];
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

        // handle drag'n'drop events
        CKEDITOR.on('instanceReady', function(event){
            var editor = event.editor;

            // auto-resize editor area in source view mode, and keep focus!
            editor.on('mode', function(event) {
                if (event.editor.mode === 'source') {
                    source = $(event.editor.container.$).find('.cke_source');
                    source.addClass('animated-height-change');
                    source.autosize();
                    source.focus();
                } else {
                    editor.focus();
                }
            });

            // make CKEditor clean up HTML edited in source mode before submit
            var form = textarea.closest('form');
            form.submit(function(event){
                if (editor.mode != 'wysiwyg') {
                    event.preventDefault();
                    editor.setMode('wysiwyg', function(){
                        form.submit();
                    });
                    return false;
                }
            });

            // focus editor if corresponding textarea is focused
            textarea.focus(function(){ editor.focus(); });

            // synchronize editor and textarea
            var textValue = textarea.val();
            function updateTextArea(){
                editor.updateElement();
                textValue = textarea.val();
            };
            editor.on('focus', function(){
                var newValue = textarea.val();
                if (newValue != textValue) {
                    editor.setData(getHtml(newValue));
                    updateTextArea();
                }
            });
            editor.on('blur', function(){
                // update textarea for other JS code (e.g. Stud.IP Forum)
                updateTextArea();
            });

            // TODO find a better solution than blurDelay = 0
            // it's an ugly hack to be faster than Stud.IP forum's save
            // function; might produce "strange" behaviour
            CKEDITOR.focusManager._.blurDelay = 0;

            // display shadow when editor area is focused
            var editorArea = textarea.siblings('.cke_chrome');
            editor.on('focus', function(){
                // add editor area shadow
                editorArea.css('box-shadow', '0 3px 15px ' + uiColor);
            });

            editor.on('blur', function(){
                // remove editor area shadow
                editorArea.css('box-shadow', '');
                if (toolbar.has(':focus').length > 0) {
                    editor.focus();
                }
            });

            // do not scroll toolbar out of viewport
            function stickyTools() {
                var MARGIN = 30;
                // is(':visible'): offset() is wrong for hidden nodes
                if (($(window).scrollTop() + MARGIN > toolbar_placeholder.offset().top)
                        && toolbar.is(':visible')) {
                    toolbar.css({
                        position: 'fixed',
                        top: MARGIN
                    });
                    toolbar_placeholder
                        .css('height', toolbar.height());
                } else {
                    toolbar.css({
                        position: 'relative',
                        top: ''
                    });
                    toolbar_placeholder
                        .css('height', 0);
                }
            };
            $(window).scroll(stickyTools);
            // if toolbar is hidden during scrolling it might scroll off screen
            editor.on('focus', stickyTools);

            var editorZ = Number(editorArea.css('z-index')) || 0;
            toolbar.css('z-index', editorZ + 1);

            // hide "source" button's text label
            $('.cke_button__source_label').hide();

            // focus the editor so the user can immediately hack away...
            editor.focus();
        });
    }

    $('textarea.add_toolbar').each(function(){
        if (!CKEDITOR.instances[this]) {
            replaceTextarea($(this));
        }
    });
}); // jQuery(function($){
