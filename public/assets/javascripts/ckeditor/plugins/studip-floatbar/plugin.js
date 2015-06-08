/**
 * plugin.js - Make CKEditor's toolbar stick to the top of the
 * browser window, so it doesn't scroll out.
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
(function (CKEDITOR, $) {

    CKEDITOR.plugins.add('studip-floatbar', {
        // TODO remove dependance on sharedspace
        requires: 'sharedspace',
        init: init
    });

    function init(editor) {
        // create the div for our toolbar
        var toolbar = $('<div>')
        .attr('id', createNewId('cktoolbar'))
        .addClass('cktoolbar')
        .css('max-width', editor.config.width)
        .insertBefore(editor.element.$);

        // insert toolbar placeholder
        // - needed to compute correct toolbar position when floating
        $('<div>')
        .attr('id', toolbar.attr('id') + '-placeholder')
        .insertBefore(toolbar);

        // configure shared spaces plug to use our toolbar
        editor.config.sharedSpaces = { top: toolbar.attr('id') };

        // add listener for later intialization tasks
        CKEDITOR.on('instanceReady', onInstanceReady);
    }

    function onInstanceReady(event) {
        // do not scroll toolbar out of viewport
        function stickyTools() {
            updateStickyTools(event.editor);
        };
        $(window).scroll(stickyTools);
        $(window).resize(stickyTools);
        event.editor.on('focus', stickyTools); // hidden toolbar might scroll off screen
    }

    function updateStickyTools(editor) {
        var MARGIN = $('#barBottomContainer').length ?
                $('#barBottomContainer').height() : 25;

        var toolbarId = editor.config.sharedSpaces.top;

        var $toolbar = $('#' + toolbarId);

        var placeholder = $('#' + toolbarId + '-placeholder');

        if ($toolbar.length === 0 || placeholder.length === 0) {
            // toolbar/editor removed by some JS code (e.g. when sending messages)
            // TODO remove listeners!!
            return;
        }

        var outOfView = $(window).scrollTop() + MARGIN
                > placeholder.offset().top;

        var width = $(editor.container.$).outerWidth(true);

        // is(':visible'): offset() is wrong for hidden elements
        if ($toolbar.is(':visible') && outOfView) {

            $toolbar.css({ // floating toolbar
                position: 'fixed',
                top: MARGIN,
                width: width
            });

            placeholder.css('height', $toolbar.height());

        } else {

            $toolbar.css({ // inline toolbar
                position: 'relative',
                top: '',
                width: width
            });

            placeholder.css('height', 0);
        }
    }

    // create an unused id
    function createNewId(prefix) {
        var i = 0;
        while ($('#' + prefix + i).length > 0) {
            i++;
        }
        return prefix + i;
    }

})(CKEDITOR, jQuery);

