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
 * prototypejs helpers
 * ------------------------------------------------------------------------ */

(function () {
  var methods = {
    defaultValueActsAsHint: function (element) {
      element = $(element);
      element.store("default", element.value);

      return element.observe('focus', function () {
        if (element.retrieve("default") !== element.value) {
          return;
        }
        element.removeClassName('hint').value = '';
      }).observe('blur', function () {
        if (element.value.strip() !== '') {
          return;
        }
        element.addClassName('hint').value = element.retrieve("default");
      }).addClassName('hint');
    }
  };

  $w('input textarea').each(function (tag) {
    Element.addMethods(tag, methods);
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

  url: function (action, args) {
    return STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/course/study_areas/" +
           $A(arguments).join("/");
  },

  swishAndFlick: function (element, target) {

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
  },

  add: function (id, course_id) {

    course_id = course_id || "";

    // may not be visible at the current
    $$(".study_area_selection_add_" + id).each(function (add) {
        // prevent selecting twice
        add.disable();
        var effect = new Effect.Opacity(add, {from: 1, to: 0, duration: 0.25,
          afterFinish: function () {
            add.setStyle({visibility: "hidden"}).enable();
          }
        });
      });

    var request = new Ajax.Request(STUDIP.study_area_selection.url("add", course_id), {
      method: "post",
      parameters: { "id": id },
      onSuccess: function (transport) {
        STUDIP.study_area_selection.swishAndFlick($$(".study_area_selection_add_" + id)[0],
                                                  "study_area_selection_selected");
        $("study_area_selection_none").fade();
        $("study_area_selection_selected").replace(transport.responseText);
        STUDIP.study_area_selection.refreshSelection();
      }
    });
  },

  remove: function (id, course_id) {

    course_id = course_id || "";

    var selection = $("study_area_selection_" + id);

    if (selection.siblings().size() === 0) {
      $("study_area_selection_at_least_one").appear();
      $("study_area_selection_at_least_one").fade({ delay: 5, queue: 'end' });
      selection.shake();
      return;
    }

    var request = new Ajax.Request(STUDIP.study_area_selection.url("remove", course_id), {
      method: "post",
      parameters: { "id": id },
      onSuccess: function (transport) {
        selection.remove();
        if (!$$("#study_area_selection_selected li").length) {
          $("study_area_selection_none").appear();
        }

        $$(".study_area_selection_add_" + id).each(function (add) {
          add.setStyle({opacity: 0, visibility: "visible"});
          var effect = new Effect.Opacity(add, {from: 0, to: 1});
        });

        STUDIP.study_area_selection.refreshSelection();
      },
      onFailure: function () {
        selection.appear();
      }
    });
  },

  expandSelection: function (id, course_id) {

    course_id = course_id || "";

    var request = new Ajax.Request(STUDIP.study_area_selection.url("expand", course_id, id), {
      method: 'post',
      onSuccess: function (transport) {
        $("study_area_selection_selectables").down("ul").replace(transport.responseText);
      }
    });
  },

  refreshSelection: function () {
    $$("#study_area_selection_selected li").each(function (element, index) {
      if (index % 2) {
        element.removeClassName("odd").addClassName("even");
      } else {
        element.removeClassName("even").addClassName("odd");
      }
    });
  }
};

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

/* ------------------------------------------------------------------------
 * Markup toolbar
 * ------------------------------------------------------------------------ */
(function () {

  STUDIP.Markup = {};

  STUDIP.Markup.buttonSet = [
    { "name": "bold",          "label": "<strong>B</strong>", open: "**",     close: "**"},
    { "name": "italic",        "label": "<em>i</em>",         open: "%%",     close: "%%"},
    { "name": "underline",     "label": "<u>u</u>",           open: "__",     close: "__"},
    { "name": "strikethrough", "label": "<del>u</del>",       open: "{-",     close: "-}"},
    { "name": "code",          "label": "code",               open: "[code]", close: "[/code]"},
    { "name": "larger",        "label": "A+",                 open: "++",     close: "++"},
    { "name": "smaller",       "label": "A-",                 open: "--",     close: "--"}
  ];


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

  var createToolbarElement = function (editor) {
    var toolbar = new Element('div', { 'class': 'editor_toolbar' });
    $(editor).insert({before: toolbar});
    return toolbar;
  };

  var createButtonElement = function (editor, options) {
    var button = new Element('button');
    button.update(options.get('label'));
    button.addClassName(options.get('name'));
    $(editor.toolbar).appendChild(button);
    button.observe("click", function (event) {
      event.stop();
      replaceSelection(editor, options.get("open") +
                               getSelection(editor) +
                               options.get("close"));
    });
  };


  STUDIP.Markup.addToolbar = function (editor, buttonSet) {
    var toolbar = createToolbarElement(editor);
    Object.extend(editor, {
      toolbar: toolbar,

      addButtonSet: function (set) {
        $A(set).each(function (button) {
          editor.addButton(button);
        });
      },

      addButton: function (options) {
        options = $H(options);
        createButtonElement(editor, options);
      }
    });
    buttonSet = buttonSet || STUDIP.Markup.buttonSet;
    editor.addButtonSet(buttonSet);
  };

}());

/* ------------------------------------------------------------------------
 * automatic compression of tabs
 * ------------------------------------------------------------------------ */

STUDIP.Tabs = (function () {

  var list, items, list_item_height, viewport_width;

  // check heights of list and items to check for wrapping
  var needs_compression = function () {
    if (!list_item_height) {
      var li = list.down('li');
      if (!li) {
        return false;
      }
      list_item_height = li.getHeight();
    }
    return list.clientHeight > list_item_height;
  };

  // returns the largest feasible item
  var getLargest = function () {

    var i = items.length,
        largest = 5, item, letters;

    while (i) {
      i -= 1;
      letters = items[i].innerHTML.length;
      if (letters > largest) {
        item = items[i];
        largest = letters;
      }
    }
    return item;
  };

  // truncates an item
  var truncate = function (item) {
    var text = item.innerHTML;
    var len = text.length - 4 > 4 ? text.length - 4 : 4;
    if (len < text.length) {
      item.innerHTML = text.substr(0, len) + "\u2026";
    }
  };

  return {

    // initializes, observes resize events and compresses the tabs
    initialize: function () {
      list = $("tabs");
      if (list !== null) {
        items = list.select("li a");
        viewport_width = document.viewport.getWidth();

        // strip contents and set titles
        items.each(function (item) {
          item.title = item.innerHTML = item.innerHTML.strip();
        });

        Event.observe(window, "resize", this.resize.bind(this));
        this.compress();
      }
    },


    // try to fit all the tabs into a single line
    compress: function () {
      var item;
      if (!needs_compression()) {
        return;
      }
      do {
        item = getLargest();
        if (!item) {
          break;
        }
        truncate(item);
      } while (needs_compression());
    },

    // event handler called when resizing the browser
    resize: function () {
      var new_width = document.viewport.getWidth();
      if (new_width > viewport_width) {
        items.each(function (item) {
          item.innerHTML = item.title;
        });
      }
      viewport_width = new_width;
      this.compress();
    }
  };
}());


/* ------------------------------------------------------------------------
 * Dateibereich
 * ------------------------------------------------------------------------ */

STUDIP.Filesystem = {};

// hier ein paar "globale" Variablen, die nur in Funktionen des Filesystem-Namespace verwendet werden:
STUDIP.Filesystem.hover_begin = 0;        //erste Zeit, dass eine Datei über den Ordner ...hovered_folder bewegt wurde.
STUDIP.Filesystem.hovered_folder = '';    //letzter Ordner, über den eine gezogene Datei bewegt wurde.
STUDIP.Filesystem.movelock = false;       //wenn auf true gesetzt, findet gerade eine Animation statt.
STUDIP.Filesystem.sendstop = false;       //wenn auf true gesetzt, wurde eine Datei in einen Ordner gedropped und die Seite lädt sich gerade neu.

STUDIP.Filesystem.getURL = function () {
  return document.URL.split("#", 1)[0];
};

/**
 * Funktion, um Fehlermeldungen zu behandeln.
 */
STUDIP.Filesystem.alertOnError = function (transport) {
  if (transport.responseText) {
    alert(transport.responseText);
  }
};

/**
 * Lässt die gelben Pfeile verschwinden und ersetzt sie durch Anfassersymbole.
 * Wichtig für Javascript-Nichtjavascript Behandlung. Nutzer ohne Javascript
 * sehen nur die gelben Pfeile zum Sortieren.
 */
STUDIP.Filesystem.unsetarrows = function () {
  $$("span.move_arrows, span.updown_marker").invoke("hide");
  $$("span.anfasser").invoke("show");
};

/**
 * deklariert Ordner und Dateien als ziehbare Elemente bzw. macht sie sortierbar
 */
STUDIP.Filesystem.setdraggables = function () {
  $$("div.folder_container").each(function (div) {
    var id = div.getAttribute('id');
    var md5_id = id.substr(id.lastIndexOf('_') + 1);
    //wenn es einen Anfasser gibt, also wenn Nutzer verschieben darf
    if ($$('#' + id + ' a.drag').length > 0) {
      var aufgeklappt = false;
      Sortable.create(id, {
        ghosting: false,
        constraint: false,
        scroll: window,
        tag: 'div',
        starteffect: function (element) {
          var opacity = element.getOpacity();
          element.store("opacity", opacity);
          Draggable._dragging[element] = true;
          var effect = new Effect.Opacity(element, {
            duration: 0.2,
            from: opacity,
            to: 0.7
          });
          //wenn es ein aufgeklappter Ordner ist:
          var id = element.getAttribute('id');
          var element_type = id.substr(0, id.indexOf('_'));
          var md5_id = element.down().innerHTML;
          if (element_type === "folder") {
            if ($("folder_" + md5_id + "_body").style.display !== "none") {
              aufgeklappt = true;
              STUDIP.Filesystem.changefolderbody(md5_id);
            } else {
              aufgeklappt = false;
            }
          }
        },
        endeffect: function (element) {
          var opacity = element.retrieve("opacity");
          var toOpacity = Object.isNumber(opacity) ? opacity : 1.0;
          var effect = new Effect.Opacity(element, {
            duration: 0.2,
            from: 0.7,
            to: toOpacity,
            queue: {
              scope: '_draggable',
              position: 'end'
            },
            afterFinish: function () {
              Draggable._dragging[element] = false;
            }
          });
          //wenn es ein Ordner ist, der vorher aufgeklappt war
          //(User sind ja zum Glück nicht multitaskingfähig):
          if (aufgeklappt) {
            var md5_id = element.down().innerHTML;
            STUDIP.Filesystem.changefolderbody(md5_id);
          }
        },
        onUpdate: function (container) {
          // wenn nicht schon irgendwas in einen Ordner verschoben wird.
          if (!STUDIP.Filesystem.sendstop) {
            var id = container.getAttribute('id');
            var sorttype = id.substr(0, id.lastIndexOf('_'));
            md5_id = id.substr(id.lastIndexOf('_') + 1);
            var order_ids = Sortable.sequence(id).map(function (order_id) {
              if (sorttype === "folder_subfolders") {
                // Unterordner:
                return $("getmd5_fo" + md5_id + "_" + order_id).innerHTML;
              } else {
                // Dateien:
                return $("getmd5_fi" + md5_id + "_"  + order_id).innerHTML;
              }
            });
            var sort_var = md5_id;
            var adress = STUDIP.Filesystem.getURL();
            var request = new Ajax.Request(adress, {
              method: "post",
              parameters: {
                folder_sort: sort_var,
                file_order: order_ids.join(",")
              },
              onSuccess: function () {},
              onFailure: STUDIP.Filesystem.alertOnError
            }); //of Ajax-Request
          }
        },
        //only drawn by '<a class="drag">'s in folder_id
        handles: $$('#' + id + ' a.drag')
      }); //of Sortable.create
    }
  });
};

/**
 * deklariert Ordner als Objekte, in die Dateien gedropped werden können
 */
STUDIP.Filesystem.setdroppables = function () {
  $$("div.droppable").each(function (div) {
    var id = div.getAttribute('id');
    var md5_id = id.substr(id.lastIndexOf('_') + 1);
    Droppables.add(id, {
      accept: 'draggable',
      hoverclass: 'hover',
      onHover: function (datei, folder) {
        var folder_md5_id = folder.getAttribute('id');
        folder_md5_id = folder_md5_id.substr(folder_md5_id.lastIndexOf('_') + 1);
        STUDIP.Filesystem.openhoveredfolder(folder_md5_id);
      },
      onDrop: function (datei, folder, event) {
        var id = datei.getAttribute('id');
        var file_md5_id = id.substr(id.indexOf('_') + 1);
        file_md5_id = $("getmd5_fi" + file_md5_id).innerHTML;
        var folder_md5_id = folder.getAttribute('id');
        folder_md5_id = folder_md5_id.substr(folder_md5_id.lastIndexOf('_') + 1);
        //alert("Drop "+file_md5_id+" on "+folder_md5_id);
        var request;
        var adress = STUDIP.Filesystem.getURL();
        if ((event.keyCode === 17)  || (event.ctrlKey)) {
          request = new Ajax.Request(adress, {
            method: "post",
            parameters: {
              copyintofolder: folder_md5_id,
              copyfile: file_md5_id
            },
            onSuccess: function (transport) {
              location.href = adress + '&cmd=tree&open=' + folder_md5_id;
            },
            onFailure: STUDIP.Filesystem.alertOnError
          });
        } else {
          request = new Ajax.Request(adress, {
            method: "post",
            parameters: {
              moveintofolder: folder_md5_id,
              movefile: file_md5_id
            },
            onSuccess: function (transport) {
              location.href = adress + '&cmd=tree&open=' + folder_md5_id;
            },
            onFailure: STUDIP.Filesystem.alertOnError
          });
        }
        STUDIP.Filesystem.sendstop = true;
      }
    });
  });
};

/**
 * Öffnet einen Dateiordner, wenn eine Datei lange genug drüber gehalten wird.
 */
STUDIP.Filesystem.openhoveredfolder = function (md5_id) {
  var zeit = new Date();
  if (md5_id === STUDIP.Filesystem.hovered_folder) {
    if (STUDIP.Filesystem.hover_begin < zeit.getTime() - 1000) {
      if ($("folder_" + md5_id + "_body").style.display === "none") {
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
  var IE7 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 7;
  var IE6 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 6;
  if (IE6 || IE7) {
    return true;
  }
  if (!STUDIP.Filesystem.movelock) {
    STUDIP.Filesystem.movelock = true;
    window.setTimeout("STUDIP.Filesystem.movelock = false;", 410);
    if ($("folder_" + md5_id + "_body").style.display !== "none") {
      Effect.BlindUp("folder_" + md5_id + "_body", { duration: 0.4 });
      $("folder_" + md5_id + "_header").style.fontWeight = 'normal';
      $("folder_" + md5_id + "_arrow_img").setAttribute('src', STUDIP.ASSETS_URL + "images/forumgrau2.gif");
      $("folder_" + md5_id + "_arrow_td").addClassName('printhead2');
      $("folder_" + md5_id + "_arrow_td").removeClassName('printhead3');
    } else {
      if ($("folder_" + md5_id + "_body").innerHTML === "") {
        var adress = STUDIP.Filesystem.getURL();
        var request = new Ajax.Request(adress, {
          method: 'get',
          parameters: {
            getfolderbody: md5_id
          },
          onSuccess: function (transport) {
            $("folder_" + md5_id + "_body").innerHTML = transport.responseText;
            $("folder_" + md5_id + "_header").style.fontWeight = 'bold';
            $("folder_" + md5_id + "_arrow_img").setAttribute('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
            $("folder_" + md5_id + "_arrow_td").addClassName('printhead3');
            $("folder_" + md5_id + "_arrow_td").removeClassName('printhead2');
            STUDIP.Filesystem.unsetarrows();
            STUDIP.Filesystem.setdraggables();
            STUDIP.Filesystem.setdroppables();
            $("folder_" + md5_id + "_body").style.display = "none";
            Effect.BlindDown("folder_" + md5_id + "_body", { duration: 0.4 });
          },
          onFailure: STUDIP.Filesystem.alertOnError
        });
      } else {
        Effect.BlindDown("folder_" + md5_id + "_body", { duration: 0.4 });
        $("folder_" + md5_id + "_header").style.fontWeight = 'bold';
        $("folder_" + md5_id + "_arrow_img").setAttribute('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
        $("folder_" + md5_id + "_arrow_td").addClassName('printhead3');
        $("folder_" + md5_id + "_arrow_td").removeClassName('printhead2');
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
  var IE7 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 7;
  var IE6 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 6;
  if (IE6 || IE7) {
    return true;
  }
  if (!STUDIP.Filesystem.movelock) {
    STUDIP.Filesystem.movelock = true;
    window.setTimeout("STUDIP.Filesystem.movelock = false;", 410);
    if ($("file_" + md5_id + "_body_row").style.visibility === "visible") {
      Effect.BlindUp("file_" + md5_id + "_body", { duration: 0.3 });
      $("file_" + md5_id + "_header").style.fontWeight = 'normal';
      $("file_" + md5_id + "_arrow_td").addClassName('printhead2');
      $("file_" + md5_id + "_arrow_td").removeClassName('printhead3');
      $("file_" + md5_id + "_arrow_img").setAttribute('src', STUDIP.ASSETS_URL + "images/forumgrau2.gif");
      window.setTimeout("$('file_" + md5_id + "_body_row').style.visibility = 'collapse'", 310);
    } else {
      if ($("file_" + md5_id + "_body").innerHTML === "") {
        var adress = STUDIP.Filesystem.getURL();
        var request = new Ajax.Request(adress, {
          method: 'get',
          parameters: {
            getfilebody: md5_id
          },
          onSuccess: function (transport) {
            $("file_" + md5_id + "_header").style.fontWeight = 'bold';
            $("file_" + md5_id + "_arrow_td").addClassName('printhead3');
            $("file_" + md5_id + "_arrow_td").removeClassName('printhead2');
            $("file_" + md5_id + "_arrow_img").setAttribute('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
            $("file_" + md5_id + "_body").innerHTML = transport.responseText;
            $("file_" + md5_id + "_body").style.display = "none";
            $("file_" + md5_id + "_body_row").style.visibility = "visible";
            Effect.BlindDown("file_" + md5_id + "_body", { duration: 0.3 });
          },
          onFailure: STUDIP.Filesystem.alertOnError
        });
      } else {
        //Falls der Dateikörper schon geladen ist.
        $("file_" + md5_id + "_body_row").style.visibility = "visible";
        $("file_" + md5_id + "_header").style.fontWeight = 'bold';
        $("file_" + md5_id + "_arrow_td").addClassName('printhead3');
        $("file_" + md5_id + "_arrow_td").removeClassName('printhead2');
        $("file_" + md5_id + "_arrow_img").setAttribute('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.gif");
        Effect.BlindDown("file_" + md5_id + "_body", { duration: 0.3 });
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
    if ($('user_opt_' + user_id).visible()) {
      $('user_opt_' + user_id).fade({ duration: 0.2 });
      $('user_' + user_id).morph('width:0px;', { queue: 'end', duration: 0.2 });
    } else {
      $('user_' + user_id).morph('width:110px;', { duration: 0.2 });
      $('user_opt_' + user_id).appear({ queue: 'end', duration: 0.2 });
    }
  }
};

/* ------------------------------------------------------------------------
 * application wide setup
 * ------------------------------------------------------------------------ */
document.observe('dom:loaded', function () {

  // message highlighting
  $$(".effect_highlight").invoke('highlight');

  // ajax responder
  var indicator = $('ajax_notification');
  if (indicator) {
    Ajax.Responders.register({
      onCreate:   function (request) {
        if (Ajax.activeRequestCount) {
          request.usability_timer = setTimeout(function () {
            indicator.show();
          }, 100);
        }
      },
      onComplete: function (request) {
        clearTimeout(request.usability_timer);
        if (!Ajax.activeRequestCount) {
          indicator.hide();
        }
      }
    });
  }

  // compress tabs
  STUDIP.Tabs.initialize();
});
