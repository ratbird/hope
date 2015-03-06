<?php
if ($rule->getStartTime() && $rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt von %s bis %s.'), strftime('%d.%m.%Y',
        $rule->getStartTime()), strftime('%d.%m.%Y', $rule->getEndTime())).'<br/>';
} else if ($rule->getStartTime() && !$rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt ab %s.'), strftime('%d.%m.%Y', $rule->getStartTime())).'<br/>';
} else if (!$rule->getStartTime() && $rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt bis %s.'), strftime('%d.%m.%Y', $rule->getEndTime())).'<br/>';
}
$course = Course::find($rule->mandatory_course_id);
if ($course) {
echo sprintf(!$rule->modus ?
    _('Die Anmeldung ist nur Teilnehmern der Veranstaltung: <b>%s</b> %s erlaubt.') :
    _('Die Anmeldung ist für Teilnehmer der Veranstaltung: <b>%s</b> %s verboten.'),
    $course->getFullname('number-name-semester'), '<a href="'.URLHelper::getScriptLink('dispatch.php/course/details/index/' . $course->id).'"  data-dialog>'.
        Assets::img('icons/16/grey/info-circle.png', array('title' =>_('Veranstaltungsdetails aufrufen'))).'</a>');
}
