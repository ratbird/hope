<table id="courselist" class="default">
    <thead>
        <colgroup>
            <col width="15"/>
            <col width="75"/>
            <col/>
        </colgroup>
        <tr>
            <th colspan="3">
                <?= _('Veranstaltungszuordnung:') ?>
                <span class="actions">
                    <a href="#" onclick="return STUDIP.Admission.checkUncheckAll('courses[]', 'check');"><?= _('alle') ?></a>
                    |
                    <a href="#" onclick="return STUDIP.Admission.checkUncheckAll('courses[]', 'uncheck');"><?= ('keine') ?></a>
                    |
                    <a href="#" onclick="return STUDIP.Admission.checkUncheckAll('courses[]', 'invert');"><?= ('Auswahl umkehren') ?></a>
                </span>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($allCourses as $course) {
            $title = $course['Name'];
            $title .= " (" . (int)$course['admission_turnout'] . ")";
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
                    <?= htmlReady($course['VeranstaltungsNummer']) ?>
                </label>
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