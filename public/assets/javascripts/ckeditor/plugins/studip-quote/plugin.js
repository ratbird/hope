// widget for handling studip-style quoting with author name
(function studipQuotePlugin(CKEDITOR) {
    CKEDITOR.plugins.add('studip-quote', {
        icons: 'blockquote',
        hidpi: true,
        init: initPlugin
    });

    function initPlugin(editor) {        
        editor.addCommand('insertStudipQuote', {
            exec: insertStudipQuote
        });

        editor.ui.addButton('blockquote', {
            label: 'Insert Quotation',
            command: 'insertStudipQuote',
            toolbar: 'insert'
        });
    }

    function insertStudipQuote(editor) {
        // If quoting is changed update these functions:
        // - StudipFormat::markupQuote
        //   lib/classes/StudipFormat.php
        // - quotes_encode lib/visual.inc.php
        // - STUDIP.Forum.citeEntry > quote
        //   public/plugins_packages/core/Forum/javascript/forum.js
        // - studipQuotePlugin > insertStudipQuote
        //   public/assets/javascripts/ckeditor/plugins/studip-quote/plugin.js

        var writtenBy = '%s hat geschrieben:'.toLocaleString();

        // TODO generate HTML tags with JS/jQuery functions
        editor.insertHtml(
            '<blockquote><div class="author">'
            + writtenBy.replace('%s', '"Name"')
            + '</div><p>&nbsp</p></blockquote>'
        );
    }
})(CKEDITOR);

