<div id="courselist">
    <ul>
    <?php
    foreach ($allCourses as $semesterId => $semester) {
    ?>
        <li id="<?= $semesterId ?>" rel="semester">
            <a href=""><?= $semester['name'] ?></a>
            <ul>
        <?php foreach ($semester['courses'] as $course) {
            $title = $via_ajax ? studip_utf8encode($course['Name']) : $course['Name'];
            $title .= " (" . (int)$course['admission_turnout'] . ")";
            if ($course['VeranstaltungsNummer']) {
                $title = $course['VeranstaltungsNummer'].' | '.$title;
            }
            if (in_array($course['seminar_id'], $selectedCourses)) {
                $selected = ' checked="checked"';
            } else {
                $selected = '';
            }
            ?>
                <li id="<?= $course['seminar_id'] ?>" rel="course">
                    <input type="checkbox" class="studip_checkbox" name="courses[]" value="<?= $course['seminar_id'] ?>"<?= $selected ?>/> <a href=""><?= $title ?></a>
                </li>
        <?php } ?>
            </ul>
        </li>
    <?php } ?>
    </ul>
    <script>
        $(function() {
            $('#courselist').bind('loaded.jstree', function (event, data) {
                // Show checked checkboxes.
                var checkedItems = $('#courselist').find('.jstree-checked');
                checkedItems.removeClass('jstree-unchecked');
                // Open parent nodes of checked nodes.
                checkedItems.parents().each(function () { data.inst.open_node(this, false, true); });
                // Hide checkbox on all non-courses.
                $(this).find('li[rel!=course]').find('.jstree-checkbox:first').hide();
            });
            var types = {
                'default': {
                    'select_node': function(event) {
                        this.toggle_node(event);
                        return false;
                    }
                },
                'semester': {
                    'icon': {
                        'image': STUDIP.ASSETS_URL+'images/icons/16/blue/group.png'
                    }
                },
                'course': {
                    'icon': {
                        'image': STUDIP.ASSETS_URL+'images/icons/16/blue/seminar.png'
                    }
                }
            };
            STUDIP.Admission.makeTree('courselist', types);
        });
    </script>
</div>