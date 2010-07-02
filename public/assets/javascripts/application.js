/*global window, $, jQuery */
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

(function ($) {

  $.fn.extend({
    showAjaxNotification: function (position) {
      position = position || 'left';
      return this.each(function () {
        if ($(this).data('ajax_notification')) {
          return;
        }

        $(this).wrap('<span class="ajax_notification" />');
        var notification = $('<span class="notification" />').hide().insertBefore(this),
          changes = {marginLeft: 0, marginRight: 0};

        if (position === 'right') {
          changes.marginRight = notification.outerWidth(true) + 'px';
        } else {
          changes.marginLeft = notification.outerWidth(true) + 'px';
        }

        $(this).data({
          ajax_notification: notification
        }).parent().animate(changes, 'fast', function () {
          var offset = $(this).children(':not(.notification)').position(),
            styles = {
              left: offset.left - notification.outerWidth(true),
              top: offset.top + Math.floor(($(this).height() - notification.outerHeight(true)) / 2)
            };
          if (position === 'right') {
            styles.left += $(this).outerWidth(true);
          }
          notification.css(styles).fadeIn('fast');
        });
      });
    },
    hideAjaxNotification: function () {
      return this.each(function () {
        var $this = $(this).stop(),
          notification = $this.data('ajax_notification');
        if (!notification) {
          return;
        }

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

(function ($) {
  $.fn.extend({
    defaultValueActsAsHint: function () {
      return this.each(function () {
        if (!$(this).is('input,textarea') || $(this).data('defaultValueActsAsHint')) {
          return;
        }

        $(this).focus(function () {
          if ($(this).val() === $(this).attr('defaultValue')) {
            $(this).removeClass('hint').val('');
          }
        }).blur(function () {
          if ($(this).val().trim().length === 0) {
            $(this).addClass('hint').val($(this).attr('defaultValue'));
          }
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
        return this;
      }

      return this.each(function () {
        if (!$(this).is('textarea') || $(this).data('toolbar_added')) {
          return;
        }

        var $this = $(this),
          toolbar = $('<div class="editor_toolbar" />');

        jQuery.each(button_set, function (index, value) {
          $('<button />')
            .html(value.label)
            .addClass(value.name)
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
 * URLHelper
 * ------------------------------------------------------------------------ */

/**
 * This class helps to handle URLs of hyperlinks and change their parameters.
 * For example a javascript-page may open an item and the user expects other links
 * on the same page to "know" that this item is now open. But because we don't use
 * PHP session-variables here, this is difficult to use. This class can help. You
 * can overwrite the href-attribute of the link by:
 *
 *  [code]
 *  link.href = STUDIP.URLHelper.getURL("adresse.php?hello=world#anchor");
 *  [/code]
 * Returns something like:
 * "http://uni-adresse.de/studip/adresse.php?hello=world&mandatory=parameter#anchor"
 *
 * That is now extended by a parameter that has earlier been identified as mandatory
 * so that all links returned by the URLHelper extend this adress by the parameter.
 *
 * The javascript-code that opens the item can now manipulate these parameters:
 *  [code]
 *  URLHelper.setParam("data[open]", "Item_id"):
 *  [/code]
 * and
 *  [code]
 *  link.href = STUDIP.URLHelper.getURL("adresse.php?hello=world#anchor");
 *  [/code]
 * returns:
 * "http://uni-adresse.de/studip/adresse.php?hello=world&data[open]=Item_id#anchor"
 *
 * For even bigger purposes you may want to change the URLs of ALL links on a page.
 * Then you write:
 *
 *  [code]
 *  URLHelper.setParam("data[open]", "Item_id"):
 *  URLHelper.updateAllLinks();
 *  [/code]
 */
STUDIP.URLHelper = {
  params: {},     //static variable to save and serve variables
  badParams: {},  //static variable for variables that should not appear in links
  base_url: null, //the base url for all links
  /**
   * base URL for all links generated from relative URLs
   */
  setBaseURL: function (url) {
    this.base_url = url;
  },
  /**
   * method to extend short URLs like "about.php" to "http://.../about.php"
   */
  resolveURL: function (adress) {
    if (this.base_url === null) {
      this.base_url = STUDIP.ABSOLUTE_URI_STUDIP;
    }
    if (this.base_url === "" ||
        adress.match(/^[a-z]+:/) !== null ||
        adress.charAt(0) === "?") {
      //this method cannot do any more:
      return adress;
    }
    var base_url = this.base_url;
    if (adress.charAt(0) === "/") {
      var host = this.base_url.match(/^[a-z]+:\/\/[\w:.\-]+/);
      base_url = host ? host : '';
    }
    return base_url + adress;
  },
  /**
   * Creates an URL with the mandatory parameters
   * @param adress string: any adress-string
   * @param param_object map: associative object for extra values
   * @return: adress with all necessary parameters - non URI-encoded!
   */
  getURL: function (adress, param_object) {
    if (param_object === undefined) {
      param_object = {};
    }
    adress = STUDIP.URLHelper.resolveURL(adress);
    // splitting the adress:
    adress = adress.split("#");
    var anchor = (adress.length > 1) ? adress[adress.length - 1] : "";
    adress = adress[0].split("?");
    var url_parameters = (adress.length > 1) ? adress[adress.length - 1].split("&") : [];
    var parameters = {};
    $.each(url_parameters, function (index, value) {
      var assoc = value.split("=");
      parameters[assoc[0]] = assoc[1];
    });
    adress = adress[0];
    // add new parameter:
    parameters = $.extend(parameters, this.params);
    // delete unwanted parameters:
    $.each(this.badParams, function (badParam) {
      if (parameters[badParam] !== undefined) {
        delete parameters[badParam];
      }
    });
    //merging in the param_object - as you see this has got priority:
    parameters = $.extend(parameters, param_object);
    // glueing together:
    var param_strings = [];
    $.each(parameters, function (param, value) {
      param_strings.push(param + "=" + value);
    });
    if (param_strings.length > 0) {
      adress += "?" + param_strings.join("&");
    }
    if (anchor !== "") {
      adress += "#" + anchor;
    }
    return adress;
  },
  /**
   * Creates a link-adress with the mandatory parameters
   *  - like getURL but URI-encoded.
   * @param adress string: any string as an adress
   * @param param_object map: associative object for extra values
   * @return: URI-encoded adress
   */
  getLink: function (adress, param_object) {
    if (param_object === undefined) {
      param_object = {};
    }
    adress = decodeURI(adress);
    adress = STUDIP.URLHelper.getURL(adress, param_object);
    return encodeURI(adress);
  },
  /**
   * remarks a parameter as mandatory - it will be added to all URLs the
   * URLHelper returns
   * @param param string: name of the parameter
   * @param value string: value of the parameter
   */
  addLinkParam: function (param, value) {
    if (value !== "") {
      this.params[param] = value;
      if (this.badParams[param] === true) {
        delete this.badParams[param];
      }
    } else {
      STUDIP.URLHelper.stronglyRemoveLinkParam(param);
    }
  },
  /**
   * Removes the parameter from the list of the mandatory params
   */
  removeLinkParam: function (param) {
    delete this.params[param];
  },
  /**
   * Removes the parameter from the list of the mandatory params and adds it to
   * a list of unallowed parameters. These parameters will be deleted when found
   * in URLs.
   */
  stronglyRemoveLinkParam: function (param) {
    STUDIP.URLHelper.removeLinkParam(param);
    if (!this.badParams[param]) {
      this.badParams[param] = true;
    }
  },
  /**
   * Actualizes the URL of all link in the document
   */
  updateAllLinks: function (context_selector) {
    if (context_selector === undefined) {
      context_selector = "";
    }
    $(context_selector + ' a:not(.fixed, .extern)').each(function (index, anchor) {
      var href = $(anchor).attr('href');   //the adress of the link to be modified
      href = STUDIP.URLHelper.getLink(href);
      $(anchor).attr('href', href);
    });
  }
};

/* ------------------------------------------------------------------------
 * study area selection for courses
 * ------------------------------------------------------------------------ */
STUDIP.study_area_selection = {

  initialize: function () {
    // Ein bisschen hässlich im Sinne von "DRY", aber wie sonst?
    $('input[name^="study_area_selection[add]"]').live('click', function () {
      var parameters = $(this).metadata();
      if (!(parameters && parameters.id)) {
        return;
      }
      STUDIP.study_area_selection.add(parameters.id, parameters.course_id || '');
      return false;
    });
    $('input[name^="study_area_selection[remove]"]').live('click', function () {
      var parameters = $(this).metadata();
      if (!(parameters && parameters.id)) {
        return;
      }
      STUDIP.study_area_selection.remove(parameters.id, parameters.course_id || '');
      return false;
    });
    $('a.study_area_selection_expand').live('click', function () {
      var parameters = $(this).metadata();
      if (!(parameters && parameters.id)) {
        return;
      }
      STUDIP.study_area_selection.expandSelection(parameters.id, parameters.course_id || '');
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
      url: STUDIP.study_area_selection.url('add', course_id || ''),
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
      url: STUDIP.study_area_selection.url('remove', course_id || ''),
      data: ({id: id}),
      dataType: 'html',
      async: false, // Critical request thus synchronous
      success: function (data) {
        $selection.fadeOut(function () {
          $(this).remove();
        });
        if ($('#study_area_selection_selected li').length === 0) {
          $('#study_area_selection_none').fadeIn();
        }
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
    $.post(STUDIP.study_area_selection.url('expand', course_id || '', id), function (data) {
        $('#study_area_selection_selectables ul').replaceWith(data);
      }, 'html');
  },

  refreshSelection: function () {
    // "even=odd && odd=even ??" - this may seem strange but jQuery and Stud.IP differ in odd/even
    $('#study_area_selection_selected li:odd').removeClass('odd').addClass('even');
    $('#study_area_selection_selected li:even').removeClass('even').addClass('odd');
  }
};

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
};

/* ------------------------------------------------------------------------
 * automatic compression of tabs
 * ------------------------------------------------------------------------ */
STUDIP.Tabs = (function () {

  var list, items;

  // check heights of list and items to check for wrapping
  function needs_compression() {
    return $(list).height() > $('li:first', list).height();
  }

  // returns the largest feasible item
  function getLargest() {
    var largest = 5, item, letters;

    items.each(function () {
      letters = $(this).html().length;
      if (letters > largest) {
        item = this;
        largest = letters;
      }
    });
    return item;
  }

  // truncates an item
  function truncate(item) {
    var text = $(item).html(),
      length = Math.max(text.length - 4, 4);
    if (length < text.length) {
      $(item).html(text.substr(0, length) + "\u2026");
    }
  }

  return {
    // initializes, observes resize events and compresses the tabs
    initialize: function () {
      list = $('#tabs');
      if (list.length === 0) {
        return;
      }
      items = $('li a', list);
      $(list).data('old_width', $(window).width());

      // strip contents and set titles
      items.each(function () {
        $(this).html($(this).html().trim());
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
      }
    },

    // event handler called when resizing the browser
    resize: function () {
      var new_width = $(window).width();
      if (new_width > $(list).data('old_width')) {
        items.each(function () {
          $(this).html($(this).attr('title'));
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
    $("span.anfasser").show();
  }
};


/**
 * deklariert Ordner und Dateien als ziehbare Elemente bzw. macht sie sortierbar
 */
STUDIP.Filesystem.setdraggables = function () {
  $("div.folder_container").each(function () {
    var id = this.getAttribute('id');
    var md5_id = id.substr(id.lastIndexOf('_') + 1);
    //wenn es einen Anfasser gibt, also wenn Nutzer verschieben darf
    if ($('a.drag', this)) {
      $(this).sortable({
        handle: 'a.drag',
        opacity: 0.6,
        revert: 300,
        scroll: true,
        update: function () {
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
        }
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
    over: function () {
      var folder_md5_id = this.getAttribute('id');
      folder_md5_id = folder_md5_id.substr(folder_md5_id.lastIndexOf('_') + 1);
      STUDIP.Filesystem.openhoveredfolder(folder_md5_id);
    },
    drop: function (event, ui) {
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
          success: function () {
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
          success: function () {
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
      STUDIP.URLHelper.removeLinkParam('data[open][' + md5_id + ']');
      STUDIP.URLHelper.updateAllLinks("#filesystem_area");
    } else {
      if ($("#folder_" + md5_id + "_body").html() === "") {
        var adress = STUDIP.Filesystem.getURL();
        $("#folder_" + md5_id + "_body").load(adress, { getfolderbody: md5_id }, function () {
          $("#folder_" + md5_id + "_header").css('fontWeight', 'bold');
          $("#folder_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
          $("#folder_" + md5_id + "_arrow_td").addClass('printhead3');
          $("#folder_" + md5_id + "_arrow_td").removeClass('printhead2');
          STUDIP.Filesystem.unsetarrows();
          STUDIP.Filesystem.setdraggables();
          STUDIP.Filesystem.setdroppables();
          $("#folder_" + md5_id + "_body").slideDown(400);
          STUDIP.URLHelper.addLinkParam('data[open][' + md5_id + ']', 1);
          STUDIP.URLHelper.updateAllLinks("#filesystem_area");
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
        STUDIP.URLHelper.addLinkParam('data[open][' + md5_id + ']', 1);
        STUDIP.URLHelper.updateAllLinks("#filesystem_area");
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
      STUDIP.URLHelper.removeLinkParam('data[open][' + md5_id + ']');
      STUDIP.URLHelper.updateAllLinks("#filesystem_area");
    } else {
      if ($("#file_" + md5_id + "_body").html() === "") {
        var adress = STUDIP.Filesystem.getURL();
        $("#file_" + md5_id + "_body").load(adress, { getfilebody: md5_id }, function () {
          $("#file_" + md5_id + "_header").css('fontWeight', 'bold');
          $("#file_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
          $("#file_" + md5_id + "_arrow_td").addClass('printhead3');
          $("#file_" + md5_id + "_arrow_td").removeClass('printhead2');
          $("#file_" + md5_id + "_body").slideDown(400);
          STUDIP.URLHelper.addLinkParam('data[open][' + md5_id + ']', 1);
          STUDIP.URLHelper.updateAllLinks("#filesystem_area");
        });
      } else {
        //Falls der Dateikörper schon geladen ist.
        $("#file_" + md5_id + "_body_row").show();
        $("#file_" + md5_id + "_header").css('fontWeight', 'bold');
        $("#file_" + md5_id + "_arrow_td").addClass('printhead3');
        $("#file_" + md5_id + "_arrow_td").removeClass('printhead2');
        $("#file_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
        $("#file_" + md5_id + "_body").slideDown(400);
        STUDIP.URLHelper.addLinkParam('data[open][' + md5_id + ']', 1);
        STUDIP.URLHelper.updateAllLinks("#filesystem_area");
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
      $('#user_opt_' + user_id).show('slide', {direction: 'left'}, 400, function () {
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
  openclose: function (id, admin_link) {
    if ($("#news_item_" + id + "_content").is(':visible')) {
      STUDIP.News.close(id);
    } else {
      STUDIP.News.open(id, admin_link);
    }
  },

  open: function (id, admin_link) {
    $("#news_item_" + id + "_content").load(
      STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/news/get_news/' + id,
      {admin_link: admin_link},
      function () {
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

  close: function (id) {
    $("#news_item_" + id + "_content").slideUp(400);
    $("#news_item_" + id + " .printhead3 img")
        .attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.gif");
    $("#news_item_" + id + " .printhead3")
        .removeClass("printhead3")
        .addClass("printhead2");
    $("#news_item_" + id + " .printhead b").css("font-weight", "normal");
    $("#news_item_" + id + " .printhead a.tree").css("font-weight", "normal");
  }
};

/* ------------------------------------------------------------------------
 * ajax_loader
 * ------------------------------------------------------------------------ */
$('a.load_via_ajax').live('click', function () {
  var parameters = $(this).metadata(),
    indicator = parameters.indicator || this,
    target = parameters.target || $(this).next(),
    url = parameters.url || $(this).attr('href');

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
    $(this).closest('.messagebox').fadeOut(function () {
      $(this).remove();
    });
  }
  return false;
}).live('focus', function () {
  $(this).blur(); // Get rid of the ugly "clicked border" due to the text-indent
});

/* ------------------------------------------------------------------------
 * QuickSearch inputs
 * ------------------------------------------------------------------------ */

STUDIP.QuickSearch = {
  /**
   * a helper-function to generate a JS-object filled with the variables of a form
   * like "{ input1_name : input1_value, input2_name: input2_value }"
   * @param selector string: ID of an input in a form-tag
   * @return: JSON-object (not as a string)
   */
  formToJSON: function (selector) {
    selector = $(selector).parents("form");
    var form = {};   //the basic JSON-object that will be returned later
    $(selector).find(':input[name]').each(function () {
      var name = $(this).attr('name');   //name of the input
      if (form[name]) {
        //for double-variables (not arrays):
        form[name] = form[name] + ',' + $(this).val();
      } else {
        form[name] = $(this).val();
      }
    });
    return form;
  },
  /**
   * the function to be called from the QuickSearch class template
   * @param name string: ID of input
   * @param url string: URL of AJAX-response
   * @param func string: name of a possible function executed
   *        when user has selected something
   * @return: void
   */
  autocomplete: function (name, url, func) {
    $('#' + name).autocomplete({
      disabled: true,
      source: function (input, add) {
        //get the variables that should be sent:
        var send_vars = {
          form_data: STUDIP.QuickSearch.formToJSON('#' + name),
          request: input.term
        };
        $.ajax({
          url: url,
          type: "post",
          dataType: "json",
          data: send_vars,
          success: function (data) {
            var stripTags = /<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?>|<\/\w+>/gi;
            var suggestions = [];  //an array of possible selections
            $.each(data, function (i, val) {
              //adding a label and a hidden item_id - don't use "value":
              suggestions.push({
                label: val.item_name,                       //what is displayed in the drobdown-boc
                item_id: val.item_id,                       //the hidden ID of the item
                value: val.item_name !== null ? val.item_name.replace(stripTags, "") : "" //what is inserted in the visible input-box
              });
            });
            //pass it to the function of UI-widget:
            add(suggestions);
          }
        });
      },
      select: function (event, ui) {
        //inserts the ID of the selected item in the hidden input:
        $('#' + name + "_realvalue").attr("value", ui.item.item_id);
        //and execute a special function defined before by the programmer:
        if (func) {
          func(ui.item.item_id, ui.item.label);
        }
      }
    });
  }
};


/* ------------------------------------------------------------------------
 * application wide setup
 * ------------------------------------------------------------------------ */

$(document).ready(function () {
  // AJAX Indicator
  STUDIP.ajax_indicator = true;

  // message highlighting
  $(".effect_highlight").effect('highlight', {}, 2000);
  $('.add_toolbar').addToolbar(STUDIP.Markup.buttonSet);

  // compress tabs
  STUDIP.Tabs.initialize();

  STUDIP.study_area_selection.initialize();

  $('.focus').each(function () {
    if (!$(this).is('.if-empty') || $(this).val().length === 0) {
      $(this).focus();
      return false;
    }
  });
  $('textarea.resizable').resizable({
    handles: 's',
    minHeight: 50
  });
});

/* ------------------------------------------------------------------------
 * application collapsable tablerows
 * ------------------------------------------------------------------------ */
jQuery(function ($) {

  $('table.collapsable .toggler').click(function () {

    $(this).closest('tbody').toggleClass('collapsed');
    return false;
  }).closest('.collapsable').find('tbody').filter(':not(.open)').find('.toggler').click();
});

$('a.load-in-new-row').live('click', function () {
  if ($(this).closest('tr').next().hasClass('loaded-details')) {
    $(this).closest('tr').next().remove();
    return false;
  }

  var that = this;
  $(that).showAjaxNotification();

  var row = $('<tr />').addClass('loaded-details'),
      cell = $('<td />').attr('colspan', $(this).closest('td').siblings().length + 1).appendTo(row);
  $(this).closest('tr').after(row);
  $.get($(this).attr('href'), function (response) {
    cell.html(response);
    $(that).hideAjaxNotification();
  });

  return false;
});

$('.loaded-details a.cancel').live('click', function () {
  $(this).closest('.loaded-details').prev().find('a.load-in-new-row').click();
  return false;
});

/* ------------------------------------------------------------------------
 * only numbers in the input field
 * ------------------------------------------------------------------------ */
$('input.allow-only-numbers').live('keyup', function () {
  $(this).val($(this).val().replace(/\D/, ''));
});

/* ------------------------------------------------------------------------
 * additional jQuery (UI) settings for Stud.IP
 * ------------------------------------------------------------------------ */

$.ui.accordion.prototype.options.icons = {
  header: 'arrow_right',
  headerSelected: 'arrow_down'
};
