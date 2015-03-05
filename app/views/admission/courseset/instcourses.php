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
            $title .= (!$course['visible'] ? ' (' . _("versteckt") . ')' : '');
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
                <a href="<?=URLHelper::getScriptLink('dispatch.php/course/details/index/' . $course['seminar_id']) ?>"  data-dialog>
                <?= Assets::img('icons/16/grey/info-circle.png', array('title' =>_('Veranstaltungsdetails aufrufen')))?>
                </a>
                    <?= htmlReady($title) ?>
                <? if ($course['admission_type']) : ?>
                <? $typename = call_user_func($course['admission_type'] . '::getName') ?>
                    <?= Assets::img('icons/16/red/exclaim-circle.png', array('title' => sprintf(_("vorhandene Anmelderegel: %s"), $typename))); ?>
                <? endif ?>
                </label>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>