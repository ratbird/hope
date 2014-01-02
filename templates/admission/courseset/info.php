<?php if ($courseset->getInfoText()) { ?>
<?= htmlReady($courseset->getInfoText()) ?>
<br>
<?php } ?>
<?php if (!$short) { ?>
<br/>
<i><?= _("Einrichtungszuordnung:") ?></i>
<ul>
    <?php foreach ($institutes as $institute) { ?>
    <li><?= htmlReady($institute) ?></li>
    <?php } ?>
</ul>
<?php } ?>
<i><?= _("Folgende Regeln gelten für die Anmeldung:") ?></i>
<ul>
<?php foreach ($courseset->getAdmissionRules() as $rule) { ?>
    <li>
        <?= $rule->toString() ?>
    </li>
<?php } ?>
</ul>
<?php if (!$short) { ?>
    <i><?= _("Veranstaltungszuordnung:") ?></i>
    <ul>
        <?php foreach ($courses as $course) { ?>
        <li><?= htmlReady($course) ?></li>
        <?php } ?>
    </ul>
<?php } ?>