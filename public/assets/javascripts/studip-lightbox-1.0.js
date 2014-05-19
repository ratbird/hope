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
                if (!inval && token === '=') {
                    inval = true;
                    skip = true;
                } else if (inval && value.length === 0 && (token === '"' || token === "'")) {
                    inquotes = token;
                } else if (inval && inquotes && token === inquotes) {
                    inquotes = false;
                } else if (!inquotes && token === ';') {
                    write = true;
                    skip = true;
                }
                if (!skip) {
                    if (inval) {
                        value += token;
                    } else {
                        index += token;
                    }
                }
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
        $('[rel~="lightbox-button"],[rel~="option"],[data-lightbox-button]', element).hide().children('a,button').andSelf().filter('a,button').each(function () {
            var label = $(this).text(),
                handler,
                form,
                input;

            // Submit form if element is a real button
            if ($(this).is('button')) {
                input = $('<input type="hidden">');
                input.attr('name', $(this).attr('name'));
                input.val($(this).val());

                form = $(this).closest('form');

                handler = function () {
                    form.append(input).submit();
                };
            }
            // Trigger click if element is a link
            if ($(this).is('a')) {
                handler = function () {
                    this.click();
                };
                handler = handler.bind(this);
            }

            // Store button and remove from response
            buttons[label] = handler;
        });

        return buttons;
    }

    STUDIP.Lightbox = {
        element: null,
        options: {}
    };

    // Creates a lightbox from an anchor, a button or a form element.
    // Will update the lightbox if it is already open
    STUDIP.Lightbox.fromElement = function (element, options) {
        if (options.close) {
            this.close();
            return;
        }

        if (!$(element).is('a,button,form')) {
            throw 'Lightbox.fromElement called on an unsupported element.';
        }

        var title = options.title || STUDIP.Lightbox.options.title || $(element).attr('title') || $(element).filter('a,button').text(),
            url,
            method = 'get',
            data = {};

        // Predefine options
        if ($(element).is('form,button')) {
            url = $(element).closest('form').attr('action');
            method = $(element).closest('form').attr('method');
            data = $(element).closest('form').serializeArray();
            
            if ($(element).is('button')) {
                data.push({
                    name: $(element).attr('name'),
                    value: $(element).val()
                });
            }
        } else {
            url = $(element).attr('href');
        }

        // Append overlay
        if (STUDIP.Overlay) {
            if (this.element !== null) {
                STUDIP.Overlay.show(true, this.element.parent());
            } else {
                STUDIP.Overlay.show(true);
            }
        }

        // Send ajax request
        $.ajax({
            url: url,
            type: (method || 'get').toUpperCase(),
            data: data || {},
            headers: {'X-Lightbox': true}
        }).done(function (response, status, xhr) {
            // Relocate if appropriate header is set
            if (xhr.getResponseHeader('X-Location')) {
                document.location = xhr.getResponseHeader('X-Location');
                return;
            }
            // Close lightbox if appropriate header is set
            if (xhr.getResponseHeader('X-Lightbox-Close')) {
                STUDIP.Lightbox.close();
                return;
            }

            options.title   = xhr.getResponseHeader('X-Title') || title;
            options.buttons = options.buttons && !xhr.getResponseHeader('X-No-Buttons');

            STUDIP.Lightbox.show(response, options);
        }).fail(function () {
            if (STUDIP.Overlay) {
                STUDIP.Overlay.hide();
            }
        });
    };

    // Opens or updates the lightbox
    STUDIP.Lightbox.show = function (content, options) {
        options = $.extend({}, STUDIP.Lightbox.options, options);
        STUDIP.Lightbox.options = options;

        if (this.element === null) {
            this.element = $('<div>');
        } else {
            options.title = options.title || this.element.dialog('option', 'title');
        }

        // Hide and update container
        this.element.hide().html(content);

        var scripts = $('<div>' + content + '</div>').filter('script'), // Extract scripts
            dialog_options,
            width  = options.width || $('body').width() * 2 / 3,
            height = options.height || $('body').height()  * 2 / 3,
            temp,
            helper;

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

        var lightbox = this.element;

        dialog_options = {
            width:   width,
            height:  height,
            buttons: {},
            title:   options.title || '',
            modal:   true,
            open: function () {
                // Execute scripts
                $('head').append(scripts);

                if (options.onopen.length > 0) {
                    var nodes = options.onopen.split('.'),
                        func  = window[nodes.shift()],
                        node = nodes.shift();
                    while (node && func.hasOwnProperty(node)) {
                        func = func[node];
                        node = nodes.shift();
                    }
                    if (nodes.length === 0 && $.isFunction(func)) {
                        func(lightbox);
                    }
                }
            },
            close: function () {
                STUDIP.Lightbox.close();
            }
        };

        // Create buttons
        if (!options.hasOwnProperty('buttons') || options.buttons) {
            dialog_options.buttons = extractButtons.call(this, this.element);
            // Create 'close' button
            dialog_options.buttons['Abbrechen'.toLocaleString()] = function () {
                STUDIP.Lightbox.close();
            };
        }

        // Create/update dialog
        this.element.dialog(dialog_options);

        // Remove overlay
        if (STUDIP.Overlay) {
            STUDIP.Overlay.hide();
        }
    };

    // Closes the lightbox for good
    STUDIP.Lightbox.close = function () {
        if (this.element !== null) {
            this.options = {};
            try {
                this.element.dialog('close');
                this.element.dialog('destroy');
                this.element.remove();
            } catch (ignore) {
            } finally {
                this.element = null;
            }
        }
    };

    // Actual lightbox handler
    function lightboxHandler(event) {
        var options = $(this).data().lightbox;
        STUDIP.Lightbox.fromElement(this, parseOptions(options));
        event.preventDefault();
    }

    // Handle links, buttons and forms
    $(document)
        .on('click', 'a[data-lightbox],button[data-lightbox]', lightboxHandler)
        .on('submit', 'form[data-lightbox]', lightboxHandler);

    // Legacy handler
    // TODO: Remove this after Stud.IP 3.2 or 3.3 has been released
    function legacyLightboxHandler(event) {
        var rel  = $(this).attr('rel');
        if (/\blightbox(\s|\[|$)/.test(rel)) {
            STUDIP.Lightbox.fromElement(this, parseOptions(rel, 'lightbox'));
            event.preventDefault();
        }
    }
    $(document)
        .on('click', 'a[rel*=lightbox],button[rel*=lightbox]', legacyLightboxHandler)
        .on('submit', 'form[rel*=lightbox]', legacyLightboxHandler);

}(jQuery, STUDIP));
