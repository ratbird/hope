/*
	jQuery TextAreaResizer plugin
	Created on 17th January 2008 by Ryan O'Dell
	Adapted on 23rd April 2010 by Jan-Hendrik Willms
	Version 1.0.5

	Converted from Drupal -> textarea.js
	Found source: http://plugins.jquery.com/misc/textarea.js
	$Id: textarea.js,v 1.11.2.1 2007/04/18 02:41:19 drumm Exp $

	1.0.1 Updates to missing global 'var', added extra global variables, fixed multiple instances, improved iFrame support
	1.0.2 Updates according to textarea.focus
	1.0.3 Further updates including removing the textarea.focus and moving private variables to top
	1.0.4 Re-instated the blur/focus events, according to information supplied by dec
	1.0.5 Adapted to jQuery's new possibilities


*/
(function($) {
	/* private variable "oHover" used to determine if you're still hovering over the same element */
	var textarea, staticOffset;  // added the var declaration for 'staticOffset' thanks to issue logged by dec.
	var iLastMousePos = 0;
	var iMin = 32;
	var handle;
	/* TextAreaResizer plugin */
	$.fn.TextAreaResizer = function() {
		return this.each(function() {
			if ($(this).data('TextAreaResizerProcessed'))
				return;
		    textarea = $(this).data('TextAreaResizerProcessed', true), staticOffset = null;

			// 18-01-08 jQuery bind to pass data element rather than direct mousedown - Ryan O'Dell
		    // When wrapping the text area, work around an IE margin bug.  See:
		    // http://jaspan.com/ie-inherited-margin-bug-form-elements-and-haslayout
		    $(this).wrap('<div class="resizable-textarea" />')
		      .parent().append($('<div class="handle" />').bind('mousedown', {el: this} , startDrag));

		    var offset = $(this).position();
		    handle = $('div.handle', $(this).parent())
		      .css('left', Math.floor(offset.left))
		      .css('top', $(this).height() + 3)
		      .width( $(this).width() );
		});
	};
	/* private functions */
	function startDrag(e) {
		textarea = $(e.data.el);
		textarea.blur();
		iLastMousePos = e.pageY;
		staticOffset = textarea.height() - iLastMousePos;
		textarea.fadeTo('fast', 0.25);
		$(document).mousemove(performDrag).mouseup(endDrag);
		return false;
	}

	function performDrag(e) {
		var iThisMousePos = e.pageY;
		var iMousePos = staticOffset + iThisMousePos;
		if (iLastMousePos >= (iThisMousePos)) {
			iMousePos -= 5;
		}
		iLastMousePos = iThisMousePos;
		iMousePos = Math.max(iMin, iMousePos);
		textarea.height(iMousePos + 'px');
		handle.css('top', iMousePos + 3);
		if (iMousePos < iMin) {
			endDrag(e);
		}
		return false;
	}

	function endDrag(e) {
		$(document).unbind('mousemove', performDrag).unbind('mouseup', endDrag);
		textarea.fadeTo('fast', 1).focus();
		textarea = null;
		staticOffset = null;
		iLastMousePos = 0;
	}

})(jQuery);

