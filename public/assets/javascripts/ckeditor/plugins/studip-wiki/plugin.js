CKEDITOR.plugins.add('studip-wiki', {
    requires: 'widget',
    icons: 'wikilink',
    init: function (editor) {
        editor.widgets.add('wikilink', {
            // TODO place label in editor.lang.studip-wiki.* to localize it
            button: 'Stud.IP-Wiki Link einf&uuml;gen',
            dialog: 'wikiDialog',
            template: '<span class="wiki-link">[[Wikiseite]]</span>',
            allowedContent: 'span(!wiki-link)',
            requiredContent: 'span(wiki-link)',
            upcast: function (element) {
               return element.name == 'span' && element.hasClass('wiki-link');
            },
            init: function () {
                // NOTE regex has to accept invalid link-markup to correct
                //      user errors (e.g. when editing in source mode);
                //      the dialog however will output valid data only;
                var matches = this.element.getText().match( // [[link|text]]
                    /^\s*\[?\[?(.*?)(?:\|(.*?))?\]?\]?\s*$/
                );
                this.setData('link', matches[1] || '');
                this.setData('text', matches[2] || '');
            },
            data: function () {
                var text = this.data.text ? ('|' + this.data.text) : '';
                this.element.setText('[[' + this.data.link + text + ']]');
            }
        });
        CKEDITOR.dialog.add('wikiDialog', this.path + 'dialogs/wikilink.js');
    }
});
