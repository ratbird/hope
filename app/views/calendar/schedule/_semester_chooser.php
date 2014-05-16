<form method="get" action="<?= $controller->url_for('calendar/schedule/index') ?>">
    <select name="semester_id" onchange="jQuery(this).closest('form').submit();">
    <? foreach ($semesters as $semester) : ?>
        <? if ($semester['ende'] > time() - strtotime('1year 1day')) : ?>
        <option value="<?= $semester['semester_id'] ?>" <?= $current_semester['semester_id'] == $semester['semester_id'] ? 'selected="selected"' : '' ?>>
            <?= htmlReady($semester['name']) ?>
            <?= $semester['beginn'] < time() && $semester['ende'] > time() ?  _('*')  : '' ?>
        </option>
        <? endif ?>
    <? endforeach ?>
    </select>
    <noscript>
        <?= Assets::input("icons/16/green/accept.png", array('type' => "image", 'class' => "middle", 'title' => _('auswählen'))) ?>
    </noscript>
</form>