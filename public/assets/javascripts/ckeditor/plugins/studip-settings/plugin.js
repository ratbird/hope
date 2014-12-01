CKEDITOR.plugins.add('studip-settings', {
    icons: 'settings',
    init: function (editor) {
        CKEDITOR.dialog.add(
            'settingsDialog',
            this.path + 'dialogs/settings.js'
        );
        editor.addCommand(
            'settings',
            new CKEDITOR.dialogCommand('settingsDialog')
        );
        editor.ui.addButton('settings', {
            label: 'WYSIWYG Einstellungen\n(Editor deaktivieren, usw.)',
            command: 'settings',
            toolbar: 'others'
        });
    }
});
