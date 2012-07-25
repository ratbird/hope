(function() {

  if (window.STUDIP == null) window.STUDIP = {};

  STUDIP.Tabs = (function() {
    /*
      STUDIP.Tabs
      -----------
      Create a new STUDIP.Tabs object from an element selector
      whose childrens´ texts are compressed dynamically to fit the
      tabs into a single row.
    */
    var findCompressable, needsCompression, truncate;

    function Tabs(element, childSelector) {
      _.bindAll(this, 'compress', 'uncompress');
      if ((this.list = $(element)).length) {
        if (childSelector == null) childSelector = 'li a span';
        this.items = $(childSelector, this.list);
        _.each(this.items.text(function() {
          return $.trim($(this).text());
        }), function(item) {
          return $(item).data('orig-text', $(item).text());
        });
        $(window).resize(this.compress);
      }
    }

    /*
      Calling this will re-truncate the children of the Tabs object
      trying to fit all the children into a single line.
      This is called automatically as soon as the browser window is resized.
    */

    Tabs.prototype.compress = function() {
      var item, newWidth;
      newWidth = $(window).width();
      if (this.oldWidth) if (newWidth > this.oldWidth) this.uncompress();
      this.oldWidth = newWidth;
      while (needsCompression(this.list) && (item = findCompressable(this.items))) {
        truncate(item);
      }
      return this;
    };

    /*
      This method  undoes the compression of the childrens´ texts and
      reset them to their original values.
    */

    Tabs.prototype.uncompress = function() {
      this.items.text(function() {
        return $(this).data('orig-text');
      });
      return this;
    };

    needsCompression = function(list) {
      return list.height() > _.max(_.map(list.children(), function(kid) {
        return $(kid).outerHeight(true);
      }));
    };

    findCompressable = function(items) {
      var largest;
      largest = _.max(items, function(item) {
        return $(item).text().length;
      });
      if (largest && $(largest).text().length > 5) return largest;
    };

    truncate = function(item) {
      var length, text;
      text = $(item).text().replace("\u2026", "");
      length = Math.max(text.length - 3, 4);
      return $(item).text(text.slice(0, length) + "\u2026");
    };

    return Tabs;

  })();

  $(function() {
    var tabs;
    tabs = new STUDIP.Tabs($("#tabs"));
    return tabs.compress();
  });

}).call(this);
