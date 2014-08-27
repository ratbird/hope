/*jslint browser: true, devel: true, nomen: true, regexp: true, unparam: true, sloppy: true, todo: true */
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
                event.target.disabled = true;
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
        }
    };

    // Creates a dialog from an anchor, a button or a form element.
    // Will update the dialog if it is already open
    STUDIP.Dialog.fromElement = function (element, options) {
        options = options || {};

        if ($(element).is(':disabled') || $(window).innerWidth() < 800 || $(window).innerHeight < 400) {
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
            url = $(element).closest('form').attr('action');
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
            // Trigger event
            $(options.origin || document).trigger('dialog-load', {xhr: xhr, options: options});
            
            // Relocate if appropriate header is set
            if (xhr.getResponseHeader('X-Location')) {
                STUDIP.Dialog.close();
                document.location = xhr.getResponseHeader('X-Location');
                return;
            }
            // Close dialog if appropriate header is set
            if (xhr.getResponseHeader('X-Dialog-Close')) {
                STUDIP.Dialog.close();
                return;
            }

            options.title   = xhr.getResponseHeader('X-Title') || options.title;
            options.buttons = options.buttons && !xhr.getResponseHeader('X-No-Buttons');

            STUDIP.Dialog.show(response, options);
        }).fail(function () {
            if (STUDIP.Overlay) {
                STUDIP.Overlay.hide();
            }
        });

        return true;
    };

    // Opens or updates the dialog
    STUDIP.Dialog.show = function (content, options) {
        options = $.extend({}, STUDIP.Dialog.options, options);

        var scripts = $('<div>' + content + '</div>').filter('script'), // Extract scripts
            dialog_options,
            width  = options.width || $('body').width() * 2 / 3,
            height = options.height || $('body').height()  * 2 / 3,
            temp,
            helper,
            instance = STUDIP.Dialog.getInstance(options.id);

        if (instance.open) {
            options.title = options.title || instance.element.dialog('option', 'title');
        }
        instance.options = options;

        // Hide and update container
        instance.element.hide().html(content);

        // Adjust size if neccessary
        if (options.size && options.size === 'auto') {
            // Render off screen
            helper = $('<div style="position: absolute;left:-1000px;top:-1000px;">').html(content).appendTo('body');
            width  = Math.max(300, Math.min(helper.width(), width));
            height = Math.max(200, Math.min(helper.height() + 130, height));
            helper.remove();
        } else if (options.size && options.size.match(/^\d+x\d+$/)) {
            temp = options.size.split('x');
            width = temp[0];
            height = temp[2];
        } else if (options.size && !options.size.match(/\D/)) {
            width = height = options.size;
        }

        dialog_options = {
            width:   width,
            height:  height,
            buttons: {},
            title:   $('<div>').text(options.title || '').html(), // kinda like htmlReady()
            modal:   true,
            open: function () {
                instance.open = true;
                // Execute scripts
                $('head').append(scripts);

                $(options.origin || document).trigger('dialog-open', {dialog: this, options: options});
            },
            close: function () {
                $(options.origin || document).trigger('dialog-close', {dialog: this, options: options});

                STUDIP.Dialog.close(options);
            }
        };

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

        // Create/update dialog
        instance.element.dialog(dialog_options);

        // Remove overlay
        if (STUDIP.Overlay) {
            STUDIP.Overlay.hide();
        }
    };

    // Closes the dialog for good
    STUDIP.Dialog.close = function (options) {
        options = options || {};

        if (STUDIP.Dialog.hasInstance(options.id)) {
            var instance = STUDIP.Dialog.getInstance(options.id);

            try {
                instance.element.dialog('close');
                instance.element.dialog('destroy');
                instance.element.remove();
            } catch (ignore) {
            } finally {
                STUDIP.Dialog.removeInstance(options.id);
            }
        }
    };

    // Actual dialog handler
    function dialogHandler(event) {
        var options = $(this).data().dialog;
        if (STUDIP.Dialog.fromElement(this, parseOptions(options))) {
            event.preventDefault();
        }
    }
    
    function clickHandler(event) {
        var form  = $(this).closest('form');
        form.data('triggeredBy', {
            name: $(this).attr('name'),
            value: $(this).val()
        });
    }

    // Handle links, buttons and forms
    $(document)
        .on('click', 'a[data-dialog],button[data-dialog]', dialogHandler)
        .on('click', 'form[data-dialog] :submit', clickHandler)
        .on('click', 'form[data-dialog] input[type=image]', clickHandler)
        .on('submit', 'form[data-dialog]', dialogHandler)

    // Extra: Expose parseOptions to STUDIP object
    STUDIP.parseOptions = parseOptions;

    // Legacy handler
    // TODO: Remove this after Stud.IP 3.2 or 3.3 has been released
    function legacyDialogHandler(event) {
        var rel  = $(this).attr('rel');
        if (/\blightbox(\s|\[|$)/.test(rel)) {
            if (STUDIP.Dialog.fromElement(this, parseOptions(rel, 'lightbox'))) {
                event.preventDefault();
            }
        }
    }
    $(document)
        .on('click', 'a[rel*=lightbox],button[rel*=lightbox]', legacyDialogHandler)
        .on('submit', 'form[rel*=lightbox]', legacyDialogHandler);

}(jQuery, STUDIP));
