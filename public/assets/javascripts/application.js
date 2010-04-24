/*global window, $, $$, $A, $H, $w, Ajax, Class, Draggable, Droppables, Effect, Element, Event, Sortable */
/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 2, onevar: false */
/* ------------------------------------------------------------------------
 * application.js
 * This file is part of Stud.IP - http://www.studip.de
 *
 * Stud.IP is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Stud.IP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Stud.IP; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301  USA
 */

/* ------------------------------------------------------------------------
 * jQuery plugin "elementAjaxNotifications"
 * ------------------------------------------------------------------------ */

;(function ($) {

  $.fn.extend({
    showAjaxNotification: function (position) {
      position = position || 'left';
      return this.each(function () {
        if ($(this).data('ajax_notification'))
          return;

        $(this).wrap('<span class="ajax_notification" />');
        var notification = $('<span class="notification" />').hide().insertBefore(this),
          changes = {marginLeft: 0, marginRight: 0};

        if (position == 'right')
          changes.marginRight = notification.outerWidth(true) + 'px';
        else
          changes.marginLeft = notification.outerWidth(true) + 'px';

        $(this).data({
          ajax_notification: notification,
        }).parent().animate(changes, 'fast', function () {
          var offset = $(this).children(':not(.notification)').position(),
            styles = {
              left: offset.left - notification.outerWidth(true),
              top: offset.top + Math.floor(($(this).height() - notification.outerHeight(true))/2)
            };
          if (position == 'right')
            styles.left += $(this).outerWidth(true);
          notification.css(styles).fadeIn('fast');
        });
      });
    },
    hideAjaxNotification: function () {
      return this.each(function () {
        var $this = $(this).stop(),
          notification = $this.data('ajax_notification');
        if (!notification)
          return;

        notification.stop().fadeOut('fast', function () {
          $this.animate({marginLeft: 0, marginRight: 0}, 'fast', function () {
          	$this.unwrap();
          });
          $(this).remove();
        });
        $(this).removeData('ajax_notification');
      });
    }
  });

}(jQuery));

/* ------------------------------------------------------------------------
 * jQuery plugin "defaultValueActsAsHint"
 * ------------------------------------------------------------------------ */

;(function ($) {
  $.fn.extend({
    defaultValueActsAsHint: function () {
      return this.each(function () {
        if (!$(this).is('input,textarea') || $(this).data('defaultValueActsAsHint'))
          return;

        $(this).focus(function () {
          if ($(this).val() == $(this).attr('defaultValue'))
            $(this).removeClass('hint').val('');
        }).blur(function () {
          if ($(this).val().trim().length == 0)
            $(this).addClass('hint').val($(this).attr('defaultValue'));
        }).addClass('hint');

        $(this).data('defaultValueActsAsHint', true);
      });
    }
  });
  $(function () {
    $('.defaultValueActsAsHint').defaultValueActsAsHint();
  });
}(jQuery));

/* ------------------------------------------------------------------------
 * jQuery plugin "addToolbar"
 * ------------------------------------------------------------------------ */
(function () {

  var getSelection = function (element)  {
    if (!!document.selection) {
      return document.selection.createRange().text;
    } else if (!!element.setSelectionRange) {
      return element.value.substring(element.selectionStart, element.selectionEnd);
    } else {
      return false;
    }
  };

  var replaceSelection = function (element, text) {
    var scroll_top = element.scrollTop;
    if (!!document.selection) {
      element.focus();
      var range = document.selection.createRange();
      range.text = text;
      range.select();
    } else if (!!element.setSelectionRange) {
      var selection_start = element.selectionStart;
      element.value = element.value.substring(0, selection_start) +
                      text +
                      element.value.substring(element.selectionEnd);
      element.setSelectionRange(selection_start + text.length,
                                selection_start + text.length);
    }
    element.focus();
    element.scrollTop = scroll_top;
  };

  $.fn.extend({
    addToolbar: function (button_set) {
      // Bail out if no button set is defined
      if (!button_set) {
        if (window.console) console.log('No button set defined');
        return this;
      }

      return this.each(function () {
        if (!$(this).is('textarea') || $(this).data('toolbar_added'))
          return;

        var $this = $(this),
          toolbar = $('<div class="editor_toolbar" />');

        jQuery.each(button_set, function (index, value) {
          $('<button />')
            .html( value.label )
            .addClass( value.name )
            .appendTo(toolbar)
            .click(function () {
              var replacement = value.open + getSelection($this[0]) + value.close;
              replaceSelection($this[0], replacement);
              return false;
            });
        });

        $this.before(toolbar).data('toolbar_added', true);
      });
    }
  });
}());

/* ------------------------------------------------------------------------
 * the global STUDIP namespace
 * ------------------------------------------------------------------------ */
var STUDIP = STUDIP || {};

/* ------------------------------------------------------------------------
 * study area selection for courses
 * ------------------------------------------------------------------------ */
STUDIP.study_area_selection = {

  initialize: function () {
    // Ein bisschen hässlich im Sinne von "DRY", aber wie sonst?
    $('input[name^="study_area_selection[add]"]').live('click', function () {
      var parameters = $(this).metadata();
      if (!(parameters && parameters.id && parameters.course_id))
        return;
      STUDIP.study_area_selection.add( parameters.id, parameters.course_id );
      return false;
    });
    $('input[name^="study_area_selection[remove]"]').live('click', function () {
      var parameters = $(this).metadata();
      if (!(parameters && parameters.id && parameters.course_id))
        return;
      STUDIP.study_area_selection.remove( parameters.id, parameters.course_id );
      return false;
    });
    $('a.study_area_selection_expand').live('click', function () {
      var parameters = $(this).metadata();
      if (!(parameters && parameters.id && parameters.course_id))
        return;
       STUDIP.study_area_selection.expandSelection( parameters.id, parameters.course_id );
      return false;
    });
  },

  url: function (action, args) {
    return STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/course/study_areas/' +
           $.makeArray(arguments).join('/');
  },

  add: function (id, course_id) {
    // may not be visible at the current
    $('.study_area_selection_add_' + id).attr('disabled', true).fadeTo('slow', 0);

    $.ajax({
      type: 'POST',
      url: STUDIP.study_area_selection.url('add', course_id || ''),
      data: ({id: id}),
      dataType: 'html',
      async: false, // Critical request thus synchronous
      success: function (data) {
//      STUDIP.study_area_selection.swishAndFlick(id, 'study_area_selection_selected');
        $('#study_area_selection_none').fadeOut();
        $('#study_area_selection_selected').replaceWith(data);
        STUDIP.study_area_selection.refreshSelection();
      }
    });
  },

  remove: function (id, course_id) {
    var $selection = $('#study_area_selection_' + id);

    if ($selection.siblings().length === 0) {
      $('#study_area_selection_at_least_one').fadeIn().delay(5000).fadeOut();
      $selection.effect('bounce', 'fast');
      return;
    }

    $.ajax({
      type: 'POST',
      url: STUDIP.study_area_selection.url('remove', course_id || ''),
      data: ({id: id}),
      dataType: 'html',
      async: false, // Critical request thus synchronous
      success: function (data) {
        $selection.fadeOut(function() { $(this).remove(); });
        if ($('#study_area_selection_selected li').length === 0)
          $('#study_area_selection_none').fadeIn();
        $('.study_area_selection_add_' + id).css({
          visibility: 'visible',
          opacity: 0
        }).fadeTo('slow', 1, function () {
          $(this).attr('disabled', false);
        });

         STUDIP.study_area_selection.refreshSelection();
      },
      error: function () {
        $selection.fadeIn();
      }
    });
  },

  expandSelection: function (id, course_id) {
    $.post(STUDIP.study_area_selection.url('expand', course_id || '', id), function (data) {
        $('#study_area_selection_selectables ul').replaceWith(data);
    }, 'html');
  },

  refreshSelection: function () {
    // "even=odd && odd=even ??" - this may seem strange but jQuery and Stud.IP differ in odd/even
    $('#study_area_selection_selected li:odd').removeClass('odd').addClass('even');
    $('#study_area_selection_selected li:even').removeClass('even').addClass('odd');
  },

/*
  swishAndFlick: function (id, target) {
    var element = $('.study_area_selection_add_' + id + ':first')
    // clone element
    var clone = element.cloneNode(true);
    element.parentNode.insertBefore(clone, element);
    clone.absolutize();

    target = $(target);
    var o = target.cumulativeOffset();
    o[1] += target.getHeight();

    var effect = new Effect.Parallel(
      [
        new Effect.Move(clone, { sync: true, x: o[0], y: o[1], mode: "absolute"}),
        new Effect.Opacity(clone, { sync: true, from: 1, to: 0})
      ],
      {
        duration:    0.4,
        delay:       0,
        transition:  Effect.Transitions.sinoidal,
        afterFinish: function () {
          clone.remove();
        }
      });
  }
*/
};

/*
STUDIP.OverDiv = Class.create({
  initialize: function (options) {
    this.options = {
      id: '',
      title: '',
      content: '',
      content_url: '',
      content_element_type: '',
      position: 'bottom right',
      width: 0,
      is_moveable: true,
      initiator: null,
      event_type: 'mouseover'
    };
    this.is_drawn = false;
    this.is_hidden = true;
    this.is_scaled = false;
    this.id = '';
    this.container = null;
    this.title = null;
    this.content = null;
    Object.extend(this.options, options || {});
    this.id = this.options.id;
    this.initiator = $(this.options.initiator);
    if (options.content_element_type) {
      this.options.content_url = STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/content_element/get_formatted/' + options.content_element_type + '/' + this.id;
    }
  },

  draw: function () {
    if (!this.is_drawn) {
      var outer = new Element('div', {className: 'overdiv', id: 'overdiv_' + this.id});
      var inner = new Element('div', {className: 'title'});
      var title = new Element('h4', {className: 'title'});
      var closer = new Element('a', {className: 'title', href: '#'});
      var content = new Element('div', {className: 'content'});
      if (this.options.is_moveable) {
        closer.appendChild(new Element('img', {src: STUDIP.ASSETS_URL + 'images/hide.gif'}));
        Event.observe(closer, 'click', this.hide.bindAsEventListener(this));
        Event.observe(inner, 'dblclick', this.scale.bindAsEventListener(this));
        var draggable = new Draggable(outer, {scroll: window, handle: inner});
      }
      title.update(this.options.title);
      content.update(this.options.content);
      this.title = title;
      this.content = content;
      inner.appendChild(title);
      inner.appendChild(closer);
      outer.appendChild(inner);
      outer.appendChild(content);
      this.container = outer;
      this.container.absolutize();
      this.container.setStyle({width: this.getWidth() + 'px'});
      this.container.hide();
      $('overdiv_container').appendChild(this.container);
      this.is_drawn = true;
      if (this.options.content_url) {
        var self = this;
        var request = new Ajax.Request(this.options.content_url, {
            method: 'get',
            onSuccess: function (transport) {
              self.update(transport);
            }
          }
        );
      }
    }
  },

  update: function (transport) {
    this.title.update(transport.responseJSON.title);
    this.content.update(transport.responseJSON.content);
  },

  getOffset: function () {
    var ho = this.initiator.getWidth() / 2;
    var vo = this.initiator.getHeight() / 2;
    var positions = $w(this.options.position);
    for (var i = 0; i < positions.length; i += 1) {
      switch (positions[i].toLowerCase()) {
      case 'left':
        ho = this.container.getWidth() * -1;
        break;
      case 'right':
        ho = this.initiator.getWidth();
        break;
      case 'center':
        ho = this.initiator.getWidth() / 2;
        break;
      case 'top':
        vo = this.container.getHeight() * -1;
        break;
      case 'middle':
        vo = this.initiator.getHeight() / 2;
        break;
      case 'bottom':
        vo = this.initiator.getHeight();
        break;
      default:
      }
    }
    return {left: Math.floor(ho), top: Math.floor(vo) };
  },

  getWidth: function () {
    return this.options.width > 0 ? this.options.width : Math.floor(document.viewport.getWidth() / 3);
  },

  show: function (event) {
    this.draw();
    var offset = this.getOffset();
    this.container.clonePosition(this.initiator, {setWidth: false, setHeight: false, offsetLeft: offset.left, offsetTop: offset.top});
    this.container.setStyle({width: this.getWidth() + 'px'});
    this.container.show();
    this.is_hidden = false;
    if (this.options.event_type === 'mouseover') {
      Event.observe(this.initiator, 'mouseout', this.hide.bindAsEventListener(this));
    }
    event.stop();
  },

  hide: function (event) {
    if (!(this.options.is_moveable && event.relatedTarget && $(event.relatedTarget).descendantOf(this.container))) {
      this.container.hide();
      this.is_hidden = true;
    }
    if (this.options.event_type === 'mouseover') {
      Event.stopObserving(this.initiator, 'mouseout', this.hide.bindAsEventListener(this));
    }
    event.stop();
  },

  scale: function (event) {
    var effect = new Effect.Scale(this.container, this.is_scaled ? 50 : 200, {
      scaleContent: false,
      scaleY: false
    });
    this.is_scaled = !this.is_scaled;
  }

});

Object.extend(STUDIP.OverDiv, {
    overdivs: {},
    BindInline: function (options, event) {
      event = Event.extend(event);
      if (!this.overdivs[options.id]) {
        options.event_type = event.type;
        this.overdivs[options.id] = new STUDIP.OverDiv(options);
      } else {
        this.overdivs[options.id].initiator = $(options.initiator);
      }
      this.overdivs[options.id].show(event);
      return false;
    },

    BindToEvent: function (options, event_type) {
      event_type = event_type || 'mouseover';
      if (!this.overdivs[options.id]) {
        options.event_type = event.type;
        this.overdivs[options.id] = new STUDIP.OverDiv(options);
        Event.observe($(options.initiator), event_type, this.overdivs[options.id].show.bindAsEventListener(this.overdivs[options.id]));
      }
      return this.overdivs[options.id];
    }
  }
);
*/

/* ------------------------------------------------------------------------
 * Markup toolbar
 * ------------------------------------------------------------------------ */
STUDIP.Markup = {
  buttonSet: [
    { "name": "bold",          "label": "<strong>B</strong>", open: "**",     close: "**"},
    { "name": "italic",        "label": "<em>i</em>",         open: "%%",     close: "%%"},
    { "name": "underline",     "label": "<u>u</u>",           open: "__",     close: "__"},
    { "name": "strikethrough", "label": "<del>u</del>",       open: "{-",     close: "-}"},
    { "name": "code",          "label": "code",               open: "[code]", close: "[/code]"},
    { "name": "larger",        "label": "A+",                 open: "++",     close: "++"},
    { "name": "smaller",       "label": "A-",                 open: "--",     close: "--"}
  ]
}

/* ------------------------------------------------------------------------
 * automatic compression of tabs
 * ------------------------------------------------------------------------ */
STUDIP.Tabs = (function () {

  var list, items, viewport_width;

  // check heights of list and items to check for wrapping
  function needs_compression () {
    return $(list).height() > $('li:first', list).height();
  };

  // returns the largest feasible item
  function getLargest () {
    var largest = 5, item, letters;

    items.each(function () {
      letters = $(this).html().length;
      if (letters > largest) {
        item = this;
        largest = letters;
      }
    });
    return item;
  };

  // truncates an item
  function truncate (item) {
    var text = $(item).html(),
      length = Math.max(text.length - 4, 4);
    if (length < text.length) {
      $(item).html(text.substr(0, length) + "\u2026");
    }
  };

  return {
    // initializes, observes resize events and compresses the tabs
    initialize: function () {
      list = $('#tabs');
      if (list.length === 0)
        return;
      items = $('li a', list);
      $(list).data('old_width', $(window).width());

      // strip contents and set titles
      items.each(function () {
        $(this).html( $(this).html().trim() );
        $(this).attr('title', $(this).html());
      });

      $(window).bind('resize', this.resize);
      this.compress();
    },


    // try to fit all the tabs into a single line
    compress: function () {
      var item;
      while (needs_compression() && (item = getLargest())) {
        truncate(item);
      };
    },

    // event handler called when resizing the browser
    resize: function () {
      var new_width = $(window).width();
      if (new_width > $(list).data('old_width')) {
        items.each(function () {
          $(this).html( $(this).attr('title') );
        });
      }
      $(list).data('old_width', new_width);
      STUDIP.Tabs.compress();
    }
  };
}());

/* ------------------------------------------------------------------------
 * Dateibereich
 * ------------------------------------------------------------------------ */

// hier ein paar "globale" Variablen, die nur in Funktionen des Filesystem-Namespace verwendet werden:
STUDIP.Filesystem = {
  hover_begin    : 0,             //erste Zeit, dass eine Datei über den Ordner ...hovered_folder bewegt wurde.
  hovered_folder : '',            //letzter Ordner, über den eine gezogene Datei bewegt wurde.
  movelock       : false,         //wenn auf true gesetzt, findet gerade eine Animation statt.
  sendstop       : false,         //wenn auf true gesetzt, wurde eine Datei in einen Ordner gedropped und die Seite lädt sich gerade neu.
  getURL         : function () {
    return document.URL.split("#", 1)[0];
  },
  /**
   * Lässt die gelben Pfeile verschwinden und ersetzt sie durch Anfassersymbole.
   * Wichtig für Javascript-Nichtjavascript Behandlung. Nutzer ohne Javascript
   * sehen nur die gelben Pfeile zum Sortieren.
   */
  unsetarrows     : function () {
    $("span.move_arrows,span.updown_marker").hide();
    $("span.anfasser").show()
  }
};


/**
 * deklariert Ordner und Dateien als ziehbare Elemente bzw. macht sie sortierbar
 */
STUDIP.Filesystem.setdraggables = function () {
  $("div.folder_container").each(function() {
    var id = this.getAttribute('id');
    var md5_id = id.substr(id.lastIndexOf('_') + 1);
    //wenn es einen Anfasser gibt, also wenn Nutzer verschieben darf
    if ($('a.drag', this)) {
      var aufgeklappt = false;
      $(this).sortable({
        handle: 'a.drag',
        opacity: 0.6,
        revert: 300,
        scroll: true,
        update: function() {
          var id = this.getAttribute('id');
          var sorttype = (id.lastIndexOf('subfolders') !== -1 ? "folder" : "file");
          md5_id = id.substr(id.lastIndexOf('_') + 1);
          var order = $(this).sortable('serialize', {key: "order"}).split("&");
          order = $.map(order, function (component) {
            return component.substr(component.lastIndexOf('=') + 1);
          });
          var order_ids = $.map(order, function (order_number) {
            if (sorttype === "folder") {
              // Unterordner:
              return $("#getmd5_fo" + md5_id + "_" + order_number).html();
            } else {
              // Dateien:
              return $("#getmd5_fi" + md5_id + "_"  + order_number).html();
            }
          });
          $.ajax({
            url: STUDIP.Filesystem.getURL(),
            data: {
              sorttype: sorttype,
              folder_sort: md5_id,
              file_order: order_ids.join(",")
            }
          });
        }/*,
        start: function () {
          var id = this.getAttribute('id');
          var sorttype = (id.lastIndexOf('subfolders') !== -1 ? "folder" : "file");
          //wenn es ein aufgeklappter Ordner ist:
          var md5_id = $("div > div", this).html();
          if (sorttype === "folder") {
            if ($("#folder_" + md5_id + "_body").is(':visible')) {
              aufgeklappt = true;
              STUDIP.Filesystem.changefolderbody(md5_id);
            } else {
              aufgeklappt = false;
            }
          }
        },
        stop: function () {
          var id = this.getAttribute('id');
          var sorttype = (id.lastIndexOf('subfolders') !== -1 ? "folder" : "file");
          //wenn es ein aufgeklappter Ordner ist:
          var md5_id = $("div > div", this).html();
          if (aufgeklappt === true) {
            STUDIP.Filesystem.changefolderbody(md5_id);
            aufgeklappt = false;
          }
        }*/
      });
    }
  });
};

/**
 * deklariert Ordner als Objekte, in die Dateien gedropped werden können
 */
STUDIP.Filesystem.setdroppables = function () {
  $("div.droppable").droppable({
    accept: '.draggable',
    hoverClass: 'hover',
    over: function() {
      var folder_md5_id = this.getAttribute('id');
      folder_md5_id = folder_md5_id.substr(folder_md5_id.lastIndexOf('_') + 1);
      STUDIP.Filesystem.openhoveredfolder(folder_md5_id);
    },
    drop: function(event, ui) {
      var id = ui.draggable.attr('id');
      var file_md5_id = id.substr(id.indexOf('_') + 1);
      file_md5_id = $("#getmd5_fi" + file_md5_id).html();
      var folder_md5_id = $(this).attr('id');
      folder_md5_id = folder_md5_id.substr(folder_md5_id.lastIndexOf('_') + 1);
      //alert("Drop "+file_md5_id+" on "+folder_md5_id);
      var adress = STUDIP.Filesystem.getURL();
      if ((event.keyCode === 17)  || (event.ctrlKey)) {
        $.ajax({
          url: adress,
          data: {
            copyintofolder: folder_md5_id,
            copyfile: file_md5_id
          },
          success: function() {
            location.href = adress + '&cmd=tree&open=' + folder_md5_id;
          }
        });
      } else {
        $.ajax({
          url: adress,
          data: {
            moveintofolder: folder_md5_id,
            movefile: file_md5_id
          },
          success: function() {
            location.href = adress + '&cmd=tree&open=' + folder_md5_id;
          }
        });
      }
      STUDIP.Filesystem.sendstop = true;
    }
  });
};

/**
 * Öffnet einen Dateiordner, wenn eine Datei lange genug drüber gehalten wird.
 */
STUDIP.Filesystem.openhoveredfolder = function (md5_id) {
  var zeit = new Date();
  if (md5_id === STUDIP.Filesystem.hovered_folder) {
    if (STUDIP.Filesystem.hover_begin < zeit.getTime() - 1000) {
      if ($("#folder_" + md5_id + "_body").is(':hidden')) {
        STUDIP.Filesystem.changefolderbody(md5_id);
        STUDIP.Filesystem.hover_begin = zeit.getTime();
      }
    }
  } else {
    STUDIP.Filesystem.hovered_folder = md5_id;
    STUDIP.Filesystem.hover_begin = zeit.getTime();
  }
};

/**
 * öffnet/schließt einen Dateiordner entweder per AJAX oder nur per Animation,
 * wenn Inhalt schon geladen wurde.
 */
STUDIP.Filesystem.changefolderbody = function (md5_id) {
  if (!STUDIP.Filesystem.movelock) {
    STUDIP.Filesystem.movelock = true;
    window.setTimeout("STUDIP.Filesystem.movelock = false;", 410);
    if ($("#folder_" + md5_id + "_body").is(':visible')) {
      $("#folder_" + md5_id + "_header").css('fontWeight', 'normal');
      $("#folder_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.gif");
      $("#folder_" + md5_id + "_arrow_td").addClass('printhead2');
      $("#folder_" + md5_id + "_arrow_td").removeClass('printhead3');
      $("#folder_" + md5_id + "_body").slideUp(400);
      //URLHelper.removeParam('data[open]['+md5_id+']');
    } else {
      if ($("#folder_" + md5_id + "_body").html() === "") {
        var adress = STUDIP.Filesystem.getURL();
        $("#folder_" + md5_id + "_body").load(adress, { getfolderbody: md5_id }, function() {
          $("#folder_" + md5_id + "_header").css('fontWeight', 'bold');
          $("#folder_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
          $("#folder_" + md5_id + "_arrow_td").addClass('printhead3');
          $("#folder_" + md5_id + "_arrow_td").removeClass('printhead2');
          STUDIP.Filesystem.unsetarrows();
          STUDIP.Filesystem.setdraggables();
          STUDIP.Filesystem.setdroppables();
          $("#folder_" + md5_id + "_body").slideDown(400);
          //URLHelper.setParam('data[open]['+md5_id+']', 1);
        });
      } else {
        $("#folder_" + md5_id + "_header").css('fontWeight', 'bold');
        $("#folder_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
        $("#folder_" + md5_id + "_arrow_td").addClass('printhead3');
        $("#folder_" + md5_id + "_arrow_td").removeClass('printhead2');
        STUDIP.Filesystem.unsetarrows();
        STUDIP.Filesystem.setdraggables();
        STUDIP.Filesystem.setdroppables();
        $("#folder_" + md5_id + "_body").slideDown(400);
        //URLHelper.setParam('data[open]['+md5_id+']', 1);
      }
    }
  }
  return false;
};

/**
 * öffnet/schließt eine Datei entweder per AJAX oder nur per Animation,
 * wenn Inhalt schon geladen wurde.
 */

STUDIP.Filesystem.changefilebody = function (md5_id) {
  if (!STUDIP.Filesystem.movelock) {
    STUDIP.Filesystem.movelock = true;
    window.setTimeout("STUDIP.Filesystem.movelock = false;", 410);
    //if ($("file_" + md5_id + "_body_row").style.visibility === "visible") {

    if ($("#file_" + md5_id + "_body").is(':visible')) {
      $("#file_" + md5_id + "_body").slideUp(400);
      $("#file_" + md5_id + "_header").css("fontWeight", 'normal');
      $("#file_" + md5_id + "_arrow_td").addClass('printhead2');
      $("#file_" + md5_id + "_arrow_td").removeClass('printhead3');
      $("#file_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.gif");
    } else {
      if ($("#file_" + md5_id + "_body").html() === "") {
        var adress = STUDIP.Filesystem.getURL();
        $("#file_" + md5_id + "_body").load(adress, { getfilebody: md5_id }, function() {
          $("#file_" + md5_id + "_header").css('fontWeight', 'bold');
          $("#file_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
          $("#file_" + md5_id + "_arrow_td").addClass('printhead3');
          $("#file_" + md5_id + "_arrow_td").removeClass('printhead2');
          $("#file_" + md5_id + "_body").slideDown(400);
        });
      } else {
        //Falls der Dateikörper schon geladen ist.
        $("#file_" + md5_id + "_body_row").show();
        $("#file_" + md5_id + "_header").css('fontWeight', 'bold');
        $("#file_" + md5_id + "_arrow_td").addClass('printhead3');
        $("#file_" + md5_id + "_arrow_td").removeClass('printhead2');
        $("#file_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
        $("#file_" + md5_id + "_body").slideDown(400);
      }
    }
  }
  return false;
};


/* ------------------------------------------------------------------------
 * Studentische Arbeitsgruppen
 * ------------------------------------------------------------------------ */

STUDIP.Arbeitsgruppen = {

  toggleOption: function (user_id) {
    if ($('#user_opt_' + user_id).is(':hidden')) {
      $('#user_opt_' + user_id).show('slide', {direction: 'left'}, 400, function() {
        $('#user_opt_' + user_id).css("display", "inline-block");
      });
    } else {
      $('#user_opt_' + user_id).hide('slide', {direction: 'left'}, 400);
    }
  }
};

/* ------------------------------------------------------------------------
 * News
 * ------------------------------------------------------------------------ */

STUDIP.News = {
  openclose: function(id) {
    if ($("#news_item_" + id + "_content").is(':visible')) {
      STUDIP.News.close(id);
    } else {
      STUDIP.News.open(id);
    }
  },

  open: function(id) {
    //alert(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/news/get_news/' + id);
    $("#news_item_" + id + "_content").load(
      STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/news/get_news/' + id,
      {},
      function() {
        $("#news_item_" + id + "_content").slideDown(400);
        $("#news_item_" + id + " .printhead2 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
        $("#news_item_" + id + " .printhead2")
            .removeClass("printhead2")
            .addClass("printhead3");
        $("#news_item_" + id + " .printhead b").css("font-weight", "bold");
        $("#news_item_" + id + " .printhead a.tree").css("font-weight", "bold");
      });
  },

  close: function(id) {
    $("#news_item_" + id + "_content").slideUp(400);
    $("#news_item_" + id + " .printhead3 img")
        .attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.gif");
    $("#news_item_" + id + " .printhead3")
        .removeClass("printhead3")
        .addClass("printhead2");
    $("#news_item_" + id + " .printhead b").css("font-weight", "normal");
    $("#news_item_" + id + " .printhead a.tree").css("font-weight", "normal");
  }
}

/* ------------------------------------------------------------------------
 * ajax_loader
 * ------------------------------------------------------------------------ */
$('a.load_via_ajax').live('click', function () {
  var parameters = $(this).metadata(),
    indicator = parameters.indicator || this,
    target = parameters.target || $(this).next(),
    url = parameters.url || $(this).attr('href');

  // Special cases
  if ($(this).is('.internal_message')) {
    target = '#msg_item_' + parameters.id;
    indicator = target + ' a.tree.load_via_ajax.internal_message';
    url = STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/messages/get_msg_body/' +
          parameters.id + '/' + parameters.open + '/' + parameters.count;
  }

  $(indicator).showAjaxNotification('right');
  $(target).load(url, function () {
    $(indicator).hideAjaxNotification();
  });
  return false;
});

/* ------------------------------------------------------------------------
 * messages boxes
 * ------------------------------------------------------------------------ */

$('.messagebox .messagebox_buttons a').live('click', function () {
  if ($(this).is('.details')) {
    $(this).closest('.messagebox').toggleClass('details_hidden');
  } else if ($(this).is('.close')) {
    $(this).closest('.messagebox').fadeOut(function () { $(this).remove(); });
  }
  return false;
}).live('focus', function () {
  $(this).blur(); // Get rid of the ugly "clicked border" due to the text-indent
});

/* ------------------------------------------------------------------------
 * application wide setup
 * ------------------------------------------------------------------------ */

$(document).ready(function () {
  // AJAX Indicator
  $('#ajax_notification').ajaxStart(function () {
    $(this).show();
  }).ajaxStop(function () {
    $(this).hide();
  });

  // message highlighting
  $(".effect_highlight").effect('highlight', {}, 2000);
  $('.add_toolbar').addToolbar(STUDIP.Markup.buttonSet);

  // compress tabs
  STUDIP.Tabs.initialize();

  STUDIP.study_area_selection.initialize();

  $('.focus').each(function () {
  	if (!$(this).is('.if-empty') || $(this).val().length==0) {
  	  $(this).focus();
  	  return false;
    }
  });
  $('textarea.resizable').resizable({
    handles: 's',
    minHeight: 50
  });
});
