<form method="post" action="<?= $controller->url_for(
    isset($inst_mode) && $inst_mode == true ? 'calendar/instschedule/index' : 'calendar/schedule/index'
) ?>">
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
        <?= Icon::create('accept', 'accept', ['title' => _('auswählen')])->asInput(["type" => "image", "class" => "middle"]) ?>
    </noscript>
</form>