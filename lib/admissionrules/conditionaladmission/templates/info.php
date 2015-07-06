<?php
if ($rule->getStartTime() && $rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt von %s bis %s.'), strftime('%d.%m.%Y',
        $rule->getStartTime()), strftime('%d.%m.%Y', $rule->getEndTime())).'<br/>';
} else if ($rule->getStartTime() && !$rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt ab %s.'), strftime('%d.%m.%Y', $rule->getStartTime())).'<br/>';
} else if (!$rule->getStartTime() && $rule->getEndTime()) {
    echo sprintf(_('Diese Regel gilt bis %s.'), strftime('%d.%m.%Y', $rule->getEndTime())).'<br/>';
}
?>
<?php if (count($rule->getConditions()) == 1) { ?>
    <?= _('Folgende Bedingung muss zur Anmeldung erfüllt sein:') ?>
    <br/>
    <div id="conditions">
        <?php
        $conditions = $rule->getConditions();
        $condition = reset($conditions);
        ?>
        <div id="condition_<?= $condition->getId() ?>">
            <i><?= $condition->toString() ?></i>
        </div>
    </div>
<?php } else { ?>
    <?= _('Mindestens eine der folgenden Bedingungen muss zur Anmeldung '.
        'erfüllt sein:') ?>
    <br/>
    <ul id="conditions">
        <?php
        $i = 0;
        foreach ($rule->getConditions() as $condition) {
        ?>
        <li id="condition_<?= $condition->getId() ?>">
            <i><?= $condition->toString() ?></i>
        </li>
        <?php
            $i++;
        }
        ?>
    </ul>
<?php } ?>
