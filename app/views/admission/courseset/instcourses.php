<table id="courselist" class="default">
    <thead>
        <tr>
            <td colspan="2">
                <span class="actions">
                    <a href="#" onclick="return STUDIP.Admission.checkUncheckAll('courses[]', 'check');"><?= _('alle') ?></a>
                    |
                    <a href="#" onclick="return STUDIP.Admission.checkUncheckAll('courses[]', 'uncheck');"><?= ('keine') ?></a>
                    |
                    <a href="#" onclick="return STUDIP.Admission.checkUncheckAll('courses[]', 'invert');"><?= ('Auswahl umkehren') ?></a>
                </span>
            </td>
        </tr>
    </thead>
    <tbody>
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
        <tr class="course">
            <td width="10">
                <input type="checkbox" name="courses[]" id="<?= $course['seminar_id'] ?>" value="<?= $course['seminar_id'] ?>"<?= $selected ?>/>
            </td>
            <td>
                <label for="<?= $course['seminar_id'] ?>">
                    <?= htmlReady($title) ?>
                </label>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>