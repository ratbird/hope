<i><?= _('Nachricht bei nicht erfolgreicher Anmeldung:') ?></i>
<br/>
<?= $rule->getMessage() ?>
<br/>
<?php
if ($rule->getStartTime() && $rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt von %s bis %s'), strftime('%d.%m.%Y',
        $rule->getStartTime()), strftime('%d.%m.%Y', $rule->getEndTime()));
} else if ($rule->getStartTime() && !$rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt ab %s'), strftime('%d.%m.%Y', $rule->getStartTime()));
} else if (!$rule->getStartTime() && $rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt bis %s'), strftime('%d.%m.%Y', $rule->getEndTime()));
}
