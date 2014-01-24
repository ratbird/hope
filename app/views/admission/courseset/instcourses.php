<div id="courselist">
    <?php foreach ($allCourses as $course) {
        $title = $course['Name'];
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
    <div class="course">
        <input type="checkbox" name="courses[]" value="<?= $course['seminar_id'] ?>"<?= $selected ?>/><?= htmlReady($title) ?>
    </div>
    <?php } ?>
</div>