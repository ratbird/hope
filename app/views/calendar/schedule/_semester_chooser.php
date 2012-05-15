<form method="post" action="<?= $controller->url_for('calendar/schedule/index') ?>">
    <select name="semester_id">
    <? foreach ($semesters as $semester) : ?>
        <? if ($semester['ende'] > time() - strtotime('1year 1day')) : ?>
        <option value="<?= $semester['semester_id'] ?>" <?= $current_semester['semester_id'] == $semester['semester_id'] ? 'selected="selected"' : '' ?>>
            <?= $semester['name'] ?>
            <?= $semester['beginn'] < time() && $semester['ende'] > time() ? '('. _('akt. Semester') .')' : '' ?>
        </option>
        <? endif ?>
    <? endforeach ?>
    </select>
    <input type="image" src="<?= Assets::image_path('icons/16/green/accept.png') ?>">
</form>