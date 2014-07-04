/*jslint browser: true, sloppy: true */
/*global jQuery, STUDIP */

/**
 * Tooltip library for Stud.IP
 *
 * @author Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @copyright Stud.IP Core Group 2014
 * @license GPL2 or any later version
 * @since Stud.IP 3.1
 */
(function ($, STUDIP) {

    /**
     * Constructs a new tooltip at given location with given content.
     * The applied css class may be changed by the fourth parameter.
     *
     * @class
     * @classdesc Stud.IP tooltips provide an improved layout and handling
     *            of contents (including html) than the browser's default
     *            tooltip through title attribute would
     *
     * @param {int} x - Horizontal position of the tooltip
     * @param {int} y - Vertical position of the tooltip
     * @param {string} content - Content of the tooltip (may be html)
     * @param {string} css_class - Optional name of the applied css class /
     *                             defaults to 'studip-tooltip'
     */
    function Tooltip(x, y, content, css_class) {
        // Obtain unique id of the tooltip
        this.id = Tooltip.getId();

        // Create dom element of the tooltip, apply id and class and attach
        // to dom
        this.element = $('<div>');
        this.element.addClass(css_class || 'studip-tooltip');
        this.element.attr('id', this.id);
        this.element.appendTo('body');

        // Set position and content and paint the tooltip
        this.position(x, y);
        this.update(content);
        this.paint();
    }

    // Provide unique ids for each tooltip (neccessary for css styles)
    Tooltip.count = 0;

    /**
     * Returns a new unique id of a tooltip.
     *
     * @return {string} Unique id
     * @static
     */
    Tooltip.getId = function () {
        var id = 'studip-tooltip-' + Tooltip.count;
        Tooltip.count += 1;
        return id;
    };

    /**
     * Translates the arrow(s) under a tooltip using css3 translate
     * transforms. This is needed at the edges of the screen.
     * This implies that a current browser is used. The translation could
     * also be achieved by adjusting margins but that way we would need
     * to hardcode values into this function since it's a struggle to
     * obtain the neccessary values from the CSS pseudo selectors in JS.
     *
     * Internal, css rules are dynamically created and applied to the current
     * document by using the methods provided in the file studip-css.js.
     *
     * @param {int} x - Horizontal offset
     * @param {int} y - Vertical offset
     */
    Tooltip.prototype.translateArrows = function (x, y) {
        STUDIP.CSS.removeRule('#' + this.id + ':before');
        STUDIP.CSS.removeRule('#' + this.id + ':after');

        if (x !== 0 || y !== 0) {
            var rule = 'translate(' + x + 'px, ' + y + 'px);';
            STUDIP.CSS.addRule('#' + this.id + ':before', {transform: rule}, ['-ms-', '-webkit-']);
            STUDIP.CSS.addRule('#' + this.id + ':after',  {transform: rule}, ['-ms-', '-webkit-']);
        }
    };

    /**
     * Updates the position of the tooltip.
     *
     * @param {int} x - Horizontal position of the tooltip
     * @param {int} y - Vertical position of the tooltip
     */
    Tooltip.prototype.position = function (x, y) {
        this.x = x;
        this.y = y;
    };

    /**
     * Updates the contents of the tooltip.
     *
     * @param {string} content - Content of the tooltip (may be html)
     */
    Tooltip.prototype.update = function (content) {
        this.element.html(content);
    };

    // Threshold used for "edge detection" (imagine a padding along the edges)
    Tooltip.threshold = 0;

    /**
     * "Paints" the tooltip. This method actually computes the dimensions of
     * the tooltips, checks for screen edges and calculates the actual offset
     * in the current document.
     * This method is neccessary due to the fact that position and content
     * can be changed apart from each other.
     * Thus: Don't forget to repaint after adjusting any of the two.
     */
    Tooltip.prototype.paint = function () {
        var width       = this.element.outerWidth(true),
            height      = this.element.outerHeight(true),
            maxWidth    = $(document).width(),
            x           = this.x - width / 2,
            y           = this.y - height,
            arrowOffset = 0;

        if (x < Tooltip.threshold) {
            arrowOffset = x - Tooltip.threshold;
            x = Tooltip.threshold;
        } else if (x + width > maxWidth - Tooltip.threshold) {
            arrowOffset = x + width - maxWidth + Tooltip.threshold;
            x = maxWidth - width - Tooltip.threshold;
        }
        this.translateArrows(arrowOffset, 0);

        this.element.css({
            left: x,
            top: y
        });
    };

    /**
     * Toggles the visibility of the tooltip. If no state is provided,
     * the tooltip will be hidden if visible and vice versa. Pretty straight
     * forward and no surprises here.
     * This method implicitely calls paint before a tooltip is shown (in case
     * it was forgotten).
     *
     * @param {bool} visible - Optional visibility parameter to set the
     *                         tooltip to a certain state
     */
    Tooltip.prototype.toggle = function (visible) {
        if (visible) {
            this.paint();
        }
        this.element.toggle(visible);
    };

    /**
     * Reveals the tooltip.
     *
     * @see Tooltip.toggle
     */
    Tooltip.prototype.show = function () {
        this.toggle(true);
    };

    /**
     * Hides the tooltip.
     *
     * @see Tooltip.toggle
     */
    Tooltip.prototype.hide = function () {
        this.toggle(false);
    };

    // Expose tooltip to global STUDIP object
    STUDIP.Tooltip = Tooltip;

}(jQuery, STUDIP));

// Attach global hover handler for tooltips.
// Applies to all elements having a "data-tooltip" attribute.
// Tooltip may be provided in the data-attribute itself or by
// defining a title attribute. The latter is prefered due to
// the obvious accessibility issues.
(function ($, STUDIP) {

    STUDIP.Tooltip.threshold = 6;
    
    jQuery(document).on('hover', '[data-tooltip]', function (event) {
        var data    = $(this).data(),
            visible = event.type === 'mouseenter',
            content,
            offset  = $(this).offset(),
            x       = offset.left + $(this).outerWidth(true) / 2,
            y       = offset.top;

        if (!data.tooltipObject) {
            // If tooltip has not yet been created (first hover), obtain it's
            // contents and create the actual tooltip object.
            content = data.tooltip || $(this).attr('title') || $(this).find('.tooltip-content').remove().html();
            $(this).attr('title', '');

            data.tooltipObject = new STUDIP.Tooltip(x, y, content);
        } else if (visible) {
            // If tooltip has already been created, update it's position.
            // This is neccessary if the surrounding content is scrollable AND has
            // been scrolled. Otherwise the tooltip would appear at it's previous
            // and now wrong location.
            data.tooltipObject.position(x, y);
        }

        data.tooltipObject.toggle(visible);
    });
}(jQuery, STUDIP));
