STUDIP.enrollment =
        function() {
            var element = null;
            var canHideElement = false;
            var deleteIcon = $('#enrollment .icons .delete');
            $('#enrollment .hidden-js').remove();
            function getIdFromClassname(element) {
                var className = element.attr('class');
                var classFragments = className.split(' ');
                return classFragments[0];
            }
            function update(event, ui, target) {
                target.find('li:not(.empty)').each(function(index) {
                    var that = $(this);
                    var id = getIdFromClassname(that);
                    var hasDeleteButton = that.find('.delete').length > 0;
                    var hiddenElement = that.find('input');
                    var hasHiddenElement = hiddenElement.length > 0;
                    index++;
                    if (!hasDeleteButton)
                        deleteIcon.clone().attr('class', id + ' delete').appendTo(that);

                    if (!hasHiddenElement) {
                        that.append('<input type="hidden" name="admission_prio[' + id + ']" value="' + index + '">');
                        hiddenElement = that.find('input');
                    }
                    hiddenElement.val(index);
                });
            }
            function filterList(text, list) {
                var exp = new RegExp(text, 'gi');

                if (text.length > 0) {
                    list.find('li').each(function(index, element) {
                        //   var name = element.html();
                        var element = $(element);
                        var name = element.text();
                        if (name.search(exp) != -1) {
                            element.show();
                        } else {
                            element.hide()
                        }
                    });
                } else {
                    list.find('li').show();
                }

            }
            function toggleText(target) {
                var countElements = target.find('li').length;
                var textElement = target.find('li.empty');
                if (countElements > 1) {
                    textElement.hide();
                } else {
                    textElement.show();
                }
            }

            $('#enrollment #selected-courses').sortable({
                appendTo: "#enrollment",
                cursor: "move",
                cancel: "li.empty",
                placeholder: "ui-state-highlight",
                helper: "clone",
                update: function(event, ui) {
                    update(event, ui, $(this));
                    toggleText($(this));
                }
            }).on('click', '.delete', function() {
                var that = $(this);
                var parent = that.parent();
                var id = getIdFromClassname(that);
                $('#avaliable-courses').find('.' + id).addClass('visible').show();
                parent.remove();
                toggleText($('#enrollment #selected-courses'));
            });
            var avaliableCoursesDiv = $('#avaliable-courses');
            var selectedCoursesDiv = $('#selected-courses');
            $('#enrollment #avaliable-courses li').draggable({
                cursor: "move",
                helper: "clone",
                revert: "invalid",
                appendTo: "#enrollment",
                connectToSortable: "#selected-courses",
                activeClass: "ui-state-highlight",
                start: function(event, ui) {
                    element = $(this);
                },
                revert: function(valid) {
                    canHideElement = valid;

                },
                        stop: function(event, ui) {

                            if (canHideElement)
                                element.removeClass('visible').hide();
                        }
            });
            $('#enrollment input[name="filter"]').bind('keyup', function() {
                var $this = $(this);
                var list = $this.next('ul');
                filterList($this.val(), list);

            });


        };