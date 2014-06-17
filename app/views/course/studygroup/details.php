<?php
# Lifter010: TODO

$all_mods = $studygroup->getMembers('dozent') + $studygroup->getMembers('tutor');

$mods = array();
foreach($all_mods as $mod) {
    $mods[] = '<a href="'.URLHelper::getLink("dispatch.php/profile?username=".$mod['username']).'">'.htmlready($mod['fullname']).'</a>';
}
/* * * * * * * * * * * *
 * * * O U T P U T * * *
 * * * * * * * * * * * */
?>
<h1><?= htmlReady($studygroup->getName()) ?></h1>
<b><?= _("Moderiert von:") ?></b> <?= implode(',', $mods) ?><br>
<br>
<b><?= _("Beschreibung:") ?></b><br>
<?= formatLinks($studygroup->description) ?>
