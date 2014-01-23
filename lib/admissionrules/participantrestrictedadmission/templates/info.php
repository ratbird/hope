<?= _("Es wird eine festgelegte Anzahl von Pl�tzen in den Veranstaltungen verteilt.") ?>
<br>
<? if ($rule->getDistributionTime()) : ?>
    <? if ($rule->getDistributionTime() > time()) : ?>
    <?= sprintf(_('Die Pl�tze in den betreffenden Veranstaltungen werden am %s '.
    'um %s verteilt.'), date("d.m.Y", $rule->getDistributionTime()), 
    date("H:i", $rule->getDistributionTime())) ?>
    <? else : ?>
    <?= sprintf(_('Die Pl�tze in den betreffenden Veranstaltungen wurden am %s '.
    'um %s verteilt. Weitere Pl�tze werden evtl. �ber Wartelisten zur Verf�gung gestellt.'), date("d.m.Y", $rule->getDistributionTime()), 
    date("H:i", $rule->getDistributionTime())) ?>
    <? endif ?>
<? elseif ($rule->isFCFSallowed()) :?>
    <?= _("Die Pl�tze werden in der Reihenfolge der Anmeldung vergeben.")?>
<? endif ?>