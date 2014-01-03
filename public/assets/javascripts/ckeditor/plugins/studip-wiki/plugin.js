CKEDITOR.plugins.add('studip-wiki', {
    icons: 'wikilink',
    init: function (editor) {
        // utilities
        function isWikiLink(element) {
            var link = element.getAscendant('a', true);
            var wiki = STUDIP.URLHelper.getURL('wiki.php');
            return link && link.getAttribute('href').indexOf(wiki) == 0;
        }

        // add toolbar button and dialog for editing Stud.IP wiki links
        editor.addCommand('wikiDialog', new CKEDITOR.dialogCommand('wikiDialog'));
        editor.ui.addButton('wikilink', {
            label: 'Stud.IP-Wiki Link einfügen',
            command: 'wikiDialog',
            toolbar: 'insert,70'
        });
        CKEDITOR.dialog.add('wikiDialog', this.path + 'dialogs/wikilink.js.php' );

        // add context menu for existing Stud.IP wiki links
        if (editor.contextMenu) {
            editor.addMenuGroup('studipGroup');
            editor.addMenuItem('wikilinkItem', {
                label: 'Stud.IP-Wiki Link bearbeiten',
                icon: this.path + 'icons/wikilink.png', // same as plugin icon
                command: 'wikiDialog',
                group: 'studipGroup'
            });
            editor.contextMenu.addListener(function(element) {
                if (isWikiLink(element)) {
                    return {
                        wikilinkItem: CKEDITOR.TRISTATE_OFF
                    };
                }
            });
        }

        // open dialog when double-clicking link
        editor.on('doubleclick', function(event) {
            var element = CKEDITOR.plugins.link.getSelectedLink(editor)
                          || event.data.element;

            if (isWikiLink(element)) {
                event.data.dialog = 'wikiDialog';
            }
        });
    }
});
