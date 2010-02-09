module("markup toolbar", {

  setup: function () {
    this.klon = $("textarea-editor").clone(true).writeAttribute({id: null});
    $("textarea-editor").insert({after: this.klon});
    this.textarea = this.klon.down("textarea");
  },

  teardown: function () {
    delete this.textarea;
    this.klon.remove();
    delete this.klon;
  },

  addToolbar: function (buttonSet) {
    STUDIP.Markup.addToolbar(this.textarea, buttonSet);
    return this.textarea.previous(".editor_toolbar");
  }
});


test("default toolbar for textarea", function () {
  STUDIP.Markup.addToolbar(this.textarea);
  var toolbar = this.textarea.previous(".editor_toolbar");
  ok(toolbar);
  equals(toolbar.select("button").size(), STUDIP.Markup.buttonSet.length);
});

test("empty toolbar for textarea", function () {
  var toolbar = this.addToolbar({});
  ok(toolbar);
  equals(toolbar.select("button").size(), 0);
});

test("add button set to toolbar", function () {
  var toolbar = this.addToolbar({});
  equals(toolbar.select("button").size(), 0);
  this.textarea.addButtonSet(STUDIP.Markup.buttonSet);
  equals(toolbar.select("button").size(), STUDIP.Markup.buttonSet.length);
});

test("add single button to toolbar", function () {
  var toolbar = this.addToolbar({});
  this.textarea.addButton({name: "klass", "label": "label", open: "<", close: ">"});

  var buttons = toolbar.select("button");
  equals(buttons.size(), 1);

  ok(buttons[0].hasClassName("klass"));
  equals(buttons[0].innerHTML, "label");
  $("main").show(); buttons[0].simulate('click'); $("main").hide();
  equals(this.textarea.value, "<>");
});

