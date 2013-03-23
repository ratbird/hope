<form method="post" action="<?= $controller->url_for('calendar/schedule/index') ?>">
    <select name="semester_id">
    <? foreach ($semesters as $semester) : ?>
        <? if ($semester['ende'] > time() - strtotime('1year 1day')) : ?>
        <option value="<?= $semester['semester_id'] ?>" <?= $current_semester['semester_id'] == $semester['semester_id'] ? 'selected="selected"' : '' ?>>
            <?= htmlReady($semester['name']) ?>
            <?= $semester['beginn'] < time() && $semester['ende'] > time() ? '('. _('akt. Semester') .')' : '' ?>
        </option>
        <? endif ?>
    <? endforeach ?>
    </select>
    <?= Assets::input("icons/16/green/accept.png", array('type' => "image", 'class' => "middle", 'title' => _('auswählen'))) ?>
</form>