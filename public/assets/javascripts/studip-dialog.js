/*jslint browser: true, nomen: true, unparam: true, todo: true, regexp: true */
/*global jQuery, STUDIP */

/**
 * Specialized dialog handler
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @version     1.0
 * @since       Stud.IP 3.1
 * @license     GLP2 or any later version
 * @copyright   2014 Stud.IP Core Group
 * @todo        Handle file uploads <http://goo.gl/PnSra8>
 */
(function ($, STUDIP) {
    'use strict';

    /**
     * Tries to parse a given string into it's appropriate type.
     * Supports boolean, int and float.
     */
    function parseValue(value) {
        if (value.toLowerCase() === 'true') {
            return true;
        }
        if (value.toLowerCase() === 'false') {
            return false;
        }
        if (/^[+\-]\d+$/.test(value)) {
            return parseInt(value, 10);
        }
        if (/^[+\-]\d+\.\d+$/.test(value)) {
            return parseFloat(value, 10);
        }
        return value.replace(/^(["'])(.*)\1$/, '$2');
    }

    /**
     * Parses a given string "foo needle[option1;option2=value;option3=42;option4=false] bar"
     * into the following structure:
     *
     * {option1: true, option2: "value", option3: 42, option4: false}
     */
    function parseOptions(string, needle) {
        var temp = needle ? (string.match(/\w+\[(.*?)\]/g) || []) : [string],
            options = {};

        temp.forEach(function (slice) {
            if (needle && (slice.indexOf(needle) !== 0 || slice === needle)) {
                return;
            }
            var split = needle ? slice.replace(/^\w+\[(.*)\]$/, '$1') : slice,
                index = '',
                value = '',
                inval = false,
                escaped = 0,
                inquotes = false,
                l = split.length,
                token,
                write,
                skip,
                i;
            for (i = 0; i < l; i += 1) {
                token = split[i];
                write = false;
                skip = false;
                if (inval && token === '\\' && escaped <= 0) {
                    escaped = 2;
                } else if (!inval && token === '=') {
                    inval = true;
                    skip = true;
                } else if (inval && value.length === 0 && (token === '"' || token === "'")) {
                    inquotes = token;
                } else if (inval && inquotes && escaped <= 0 && token === inquotes) {
                    inquotes = false;
                } else if (!inquotes && token === ';') {
                    write = true;
                    skip = true;
                }
                if (!skip && escaped <= 0) {
                    if (inval) {
                        value += token;
                    } else {
                        index += token;
                    }
                }
                escaped -= 1;

                if (write || i === split.length - 1) {
                    if (i === split.length - 1 && inquotes) {
                        throw 'Invalid data, missing closing quote';
                    }
                    if (index.length > 0) {
                        options[index] = inval ? parseValue(value) : true;
                    }
                    inval = false;
                    inquotes = false;
                    index = '';
                    value = '';
                }
            }
        });
        return options;
    }

    /**
     * Extract buttons from given element.
     */
    function extractButtons(element) {
        var buttons = {};
        // TODO: Remove the rel selector after Stud.IP 3.2 or 3.3 has been released
        $('[rel~="lightbox-button"],[rel~="option"],[data-dialog-button]', element).hide().find('a,button').andSelf().filter('a,button').each(function () {
            var label = $(this).text(),
                cancel = $(this).is('.cancel'),
                handler;

            handler = function (event) {
                // TODO: Find a convenient way to disable buttons
                this.click();
            };
            handler = handler.bind(this);

            if ($(this).is('.accept,.cancel')) {
                buttons[cancel ? 'cancel' : label] = {
                    text: label,
                    click: handler,
                    'class': cancel ? 'cancel' : 'accept'
                };
            } else {
                buttons[label] = handler;
            }
        });

        return buttons;
    }

    STUDIP.Dialog = {
        instances: {},
        hasInstance: function (id) {
            id = id || 'default';
            return this.instances.hasOwnProperty(id);
        },
        getInstance: function (id) {
            id = id || 'default';
            if (!this.hasInstance(id)) {
                this.instances[id] = {
                    open: false,
                    element: $('<div>'),
                    options: {}
                };
            }
            return this.instances[id];
        },
        removeInstance: function (id) {
            id = id || 'default';
            if (this.hasInstance(id)) {
                delete this.instances[id];
            }
        },
        shouldOpen: function () {
            return $(window).innerWidth() >= 800 && $(window).innerHeight() >= 400;
        },
        handlers: {
            header: {}
        }
    };

    // Handler for HTTP header X-Location: Relocate to another location
    STUDIP.Dialog.handlers.header['X-Location'] = function (location) {
        if (document.location.href === location) {
            document.location.reload(true);
        } else {
            $(window).on('hashchange', function () {
                document.location.reload(true);
            }).on('unload', function () {
                $(window).off('hashchange');
            });
        }

        STUDIP.Dialog.close();
        document.location = location;

        return false;
    };
    // Handler for HTTP header X-Dialog-Execute: Execute arbitrary function
    STUDIP.Dialog.handlers.header['X-Dialog-Execute'] = function (value, options, xhr) {
        var chunks = value.trim().split('.'),
            callback = window,
            payload = xhr.getResponseHeader('Content-Type').match(/json/)
                    ? $.parseJSON(xhr.responseText)
                    : xhr.responseText;

        $.each(chunks, function (index, chunk) {
            if (!callback.hasOwnProperty(chunk)) {
                throw 'Dialog: Undefined callback ' + value;
            }
            callback = callback[chunk];
        });

        if (typeof callback !== 'function') {
            throw 'Dialog: Given callback is not a valid function';
        }
        return callback(payload, xhr);
    };
    // Handler for HTTP header X-Dialog-Close: Close the dialog
    STUDIP.Dialog.handlers.header['X-Dialog-Close'] = function (value, options) {
        STUDIP.Dialog.close(options);
        return false;
    };
    // Handler for HTTP header X-Wikilink: Set the options' wiki link
    STUDIP.Dialog.handlers.header['X-Wikilink'] = function (link, options) {
        options.wiki_link = link;
    };
    // Handler for HTTP header X-Title: Set the dialog title
    STUDIP.Dialog.handlers.header['X-Title'] = function (title, options) {
        options.title = title || options.title;
    };
    // Handler for HTTP header X-No-Buttons: Decide whether to show dialog buttons
    STUDIP.Dialog.handlers.header['X-No-Buttons'] = function (value, options) {
        options.buttons = false;
    };

    // Creates a dialog from an anchor, a button or a form element.
    // Will update the dialog if it is already open
    STUDIP.Dialog.fromElement = function (element, options) {
        options = options || {};

        if ($(element).is(':disabled') || !STUDIP.Dialog.shouldOpen()) {
            return;
        }

        if (options.close) {
            STUDIP.Dialog.close(options);
            return;
        }

        if (!$(element).is('a,button,form')) {
            throw 'Dialog.fromElement called on an unsupported element.';
        }

        options.origin = element;
        options.title  = options.title || STUDIP.Dialog.getInstance(options.id).options.title || $(element).attr('title') || $(element).filter('a,button').text();
        options.method = 'get';
        options.data   = {};

        var url;

        // Predefine options
        if ($(element).is('form,button')) {
            url = $(element).attr('formaction') || $(element).closest('form').attr('action');
            options.method = $(element).closest('form').attr('method');
            options.data = $(element).closest('form').serializeArray();

            if ($(element).is('button')) {
                options.data.push({
                    name: $(element).attr('name'),
                    value: $(element).val()
                });
            } else if ($(element).data().triggeredBy) {
                options.data.push($(element).data().triggeredBy);
            }
        } else {
            url = $(element).attr('href');
        }

        return STUDIP.Dialog.fromURL(url, options);
    };

    // Creates a dialog from a passed url
    STUDIP.Dialog.fromURL = function (url, options) {
        options = options || {};

        // Check if dialog should actually open
        if (!STUDIP.Dialog.shouldOpen()) {
            location.href = url;
        }

        // Append overlay
        if (STUDIP.Overlay) {
            if (STUDIP.Dialog.getInstance(options.id).open) {
                STUDIP.Overlay.show(true, STUDIP.Dialog.getInstance(options.id).element.parent());
            } else {
                STUDIP.Overlay.show(true);
            }
        }

        // Send ajax request
        $.ajax({
            url: url,
            type: (options.method || 'get').toUpperCase(),
            data: options.data || {},
            headers: {'X-Dialog': true}
        }).done(function (response, status, xhr) {
            var advance = true;

            // Trigger event
            $(options.origin || document).trigger('dialog-load', {xhr: xhr, options: options});

            // Execute all defined header handlers
            $.each(STUDIP.Dialog.handlers.header, function (header, handler) {
                var value = xhr.getResponseHeader(header),
                    result = true;
                if (value !== null) {
                    result = handler(value, options, xhr);
                }
                advance = advance && result !== false;
                return result;
            });

            if (advance) {
                STUDIP.Dialog.show(response, options);
            }
        }).always(function () {
            if (STUDIP.Overlay) {
                STUDIP.Overlay.hide();
            }
        });

        return true;
    };

    // Calculate dialogs margins (outer width - inner width of the dialog) in
    // order to properly calculated needed dialog widths. Otherwise horizontal
    // scrollbars will occur. This is located here because it is only
    // used in Dialog.show().
    var dialog_margin = 0;
    $(document).ready(function () {
        var temp = $('<div class="ui-dialog" style="position: absolute;left:-1000px;top:-1000px;"></div>');
        temp.html('<div class="ui-dialog-content ui-widget-content"><div style="width: 100%">foo</div></div>');
        temp.appendTo('body');
        dialog_margin = temp.outerWidth(true) - $('.ui-dialog-content', temp).width();
        temp.remove();
    });

    // Opens or updates the dialog
    STUDIP.Dialog.show = function (content, options) {
        options = $.extend({}, STUDIP.Dialog.options, options);

        var scripts = $('<div>' + content + '</div>').filter('script'), // Extract scripts
            dialog_options = {},
            width  = options.width || $('body').width() * 2 / 3,
            height = options.height || $('body').height()  * 2 / 3,
            temp,
            helper,
            instance = STUDIP.Dialog.getInstance(options.id);

        if (instance.open) {
            options.title = options.title || instance.element.dialog('option', 'title');
        }
        instance.options = options;

        if (options['center-content']) {
            content = '<div class="studip-dialog-centered-helper">' + content + '</div>';
            dialog_options.dialogClass = 'studip-dialog-centered';
        }

        // Hide and update container
        instance.element.hide().html(content);

        // Adjust size if neccessary
        if (options.size && options.size === 'auto') {
            // Render off screen
            helper = $('<div style="position:absolute;left:-1000px;top:-1000px;">').html(content).appendTo('body');
            // Hide buttons so they do not account to width or height
            $('[data-dialog-button]', helper).hide();
            // Calculate width and height
            width  = Math.max(300, Math.min(helper.outerWidth(true) + dialog_margin, width));
            height = Math.max(200, Math.min(helper.height() + 130, height));
            // Remove helper element
            helper.remove();
        } else if (options.size && options.size.match(/^\d+x\d+$/)) {
            temp = options.size.split('x');
            width = temp[0];
            height = temp[2];
        } else if (options.size && !options.size.match(/\D/)) {
            width = height = options.size;
        }

        dialog_options = $.extend(dialog_options, {
            width:   width,
            height:  height,
            buttons: {},
            title:   $('<div>').text(options.title || '').html(), // kinda like htmlReady()
            modal:   true,
            resizable: options.hasOwnProperty('resize') ? options.resize : true,
            open: function () {
                var helpbar_element = $('.helpbar a[href*="docs.studip.de"]'),
                    tooltip = helpbar_element.text(),
                    link    = options.wiki_link || helpbar_element.attr('href'),
                    element = $('<a class="ui-dialog-titlebar-wiki" target="_blank">').attr('href', link).attr('title', tooltip);

                $(this).siblings('.ui-dialog-titlebar').find('.ui-dialog-titlebar-close').before(element);

                instance.open = true;
                // Execute scripts
                $('head').append(scripts);

                $(options.origin || document).trigger('dialog-open', {dialog: this, options: options});
            },
            close: function (event) {
                $(options.origin || document).trigger('dialog-close', {dialog: this, options: options});

                STUDIP.Dialog.close(options);
            }
        });

        // Create buttons
        if (!options.hasOwnProperty('buttons') || options.buttons) {
            dialog_options.buttons = extractButtons.call(this, instance.element);
            // Create 'close' button
            if (!dialog_options.buttons.hasOwnProperty('cancel')) {
                dialog_options.buttons.cancel = {
                    text: 'Schlieﬂen'.toLocaleString()
                };
            }
            dialog_options.buttons.cancel.click = function () {
                STUDIP.Dialog.close(options);
            };
        }

        // Trigger update event on document since options.origin might have been removed
        $(document).trigger('dialog-update', {dialog: instance.element, options: options});

        // Create/update dialog
        instance.element.dialog(dialog_options);
    };

    // Closes the dialog for good
    STUDIP.Dialog.close = function (options) {
        options = options || {};

        if (STUDIP.Dialog.hasInstance(options.id)) {
            var instance = STUDIP.Dialog.getInstance(options.id);

            if (instance.open) {
                instance.open = false;
                try {
                    instance.element.dialog('close');
                    instance.open = instance.element.dialog('isOpen');
                } catch (ignore) {
                }

                // Apparently the close event has been canceled, so don't force
                // a close
                if (instance.open) {
                    return false;
                }

                try {
                    instance.element.dialog('destroy');
                    instance.element.remove();
                } catch (ignore_again) {
                }
            }

            STUDIP.Dialog.removeInstance(options.id);
        }

        if (options['reload-on-close']) {
            window.location.reload();
        }
    };

    // Actual dialog handler
    function dialogHandler(event) {
        if (!event.isDefaultPrevented()) {
            var options = $(event.target).data().dialog;
            if (STUDIP.Dialog.fromElement(event.target, parseOptions(options))) {
                event.preventDefault();
            }
        }
    }

    function clickHandler(event) {
        if (!event.isDefaultPrevented()) {
            var form  = $(event.target).closest('form');
            form.data('triggeredBy', {
                name: $(event.target).attr('name'),
                value: $(event.target).val()
            });
        }
    }

    // Handle links, buttons and forms
    $(document)
        .on('click', 'a[data-dialog],button[data-dialog]', dialogHandler)
        .on('click', 'form[data-dialog] :submit', clickHandler)
        .on('click', 'form[data-dialog] input[type=image]', clickHandler)
        .on('submit', 'form[data-dialog]', dialogHandler);

    // Extra: Expose parseOptions to STUDIP object
    STUDIP.parseOptions = parseOptions;

    // Legacy handler
    // TODO: Remove this after Stud.IP 3.2 or 3.3 has been released
    function legacyDialogHandler(event) {
        var rel  = $(event.target).attr('rel');
        if (/\blightbox(\s|\[|$)/.test(rel)) {
            if (STUDIP.Dialog.fromElement(event.target, parseOptions(rel, 'lightbox'))) {
                event.preventDefault();
            }
        }
    }
    $(document)
        .on('click', 'a[rel*=lightbox],button[rel*=lightbox]', legacyDialogHandler)
        .on('submit', 'form[rel*=lightbox]', legacyDialogHandler);

}(jQuery, STUDIP));
