<? if(!empty($study_courses)) : ?>
    <? if (count($study_courses) < 2) : ?>
        <? for ($i = 0; $i < 1; $i++) : ?>
            <?= htmlReady($study_courses[$i]['fach']) ?>
            <?= htmlReady($study_courses[$i]['abschluss']) ?>
            (<?= htmlReady($study_courses[$i]['semester']) ?>)
        <? endfor ?>
    <? else : ?>
        <?= htmlReady($study_courses[0]['fach']) ?>
        <?= htmlReady($study_courses[0]['abschluss']) ?>
        (<?= htmlReady($study_courses[0]['semester']) ?>)
        [...]
        <? foreach($study_courses as $course) : ?>
            <? $course_res .= sprintf('- %s (%s)<br>',
                                      htmlReady(trim($course['fach'] . ' ' . $course['abschluss'])),
                                      htmlReady($course['semester'])) ?>
        <? endforeach ?>
        <?= tooltipIcon('<strong>' . _('Weitere Studiengänge') . '</strong><br>' . $course_res, false, true) ?>
        <? unset($course_res); ?>
    <? endif ?>
<? endif ?>
