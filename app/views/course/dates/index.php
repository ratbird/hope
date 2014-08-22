<style>
    .themen_list, .dozenten_list {
        margin: 0px;
        padding: 0px;
        list-style-type: none;
    }
</style>
<?
$lastSemester = null;
$allSsemesters = array();
foreach ($dates as $key => $date) {
    $currentSemester = Semester::findByTimestamp($date['date']);
    if ($currentSemester && (!$lastSemester || ($currentSemester->getId() !== $lastSemester->getId()))) {
        $allSsemesters[] = $currentSemester;
        $lastSemester = $currentSemester;
    }
}
$lostDateKeys = array();
?>

<? if (!count($dates)) : ?>
    <? PageLayout::postMessage(MessageBox::info(_("Keine Termine vorhanden"))) ?>
<? endif ?>

<? foreach ($allSsemesters as $semester) : ?>
<table class="dates default">
    <caption><?= htmlReady($semester['name']) ?></caption>
    <thead>
        <tr class="sortable">
            <th class="sortasc"><?= _("Zeit") ?></th>
            <th><?= _("Typ") ?></th>
            <th><?= _("Thema") ?></th>
            <th><?= _("Raum") ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($dates as $key => $date) : ?>
        <? $dateSemester = Semester::findByTimestamp($date['date']) ;?>
        <? if ($dateSemester && ($semester->getId() === $dateSemester->getId())) : ?>
        <?= $this->render_partial("course/dates/_date_row.php", compact("date", "dates", "key")) ?>
        <? elseif(!$dateSemester && !in_array($key, $lostDateKeys)) : ?>
            <? $lostDateKeys[] = $key ?>
        <? endif ?>
    <? endforeach ?>
    </tbody>
</table>
<? endforeach ?>

<? if (count($lostDateKeys)) : ?>
<table class="dates default">
    <caption><?= _("Ohne Semester") ?></caption>
    <thead>
    <tr class="sortable">
        <th class="sortasc"><?= _("Zeit") ?></th>
        <th><?= _("Typ") ?></th>
        <th><?= _("Thema") ?></th>
        <th><?= _("Raum") ?></th>
    </tr>
    </thead>
    <tbody>
    <? foreach ($lostDateKeys as $key) : ?>
        <? $date = $dates[$key] ?>
        <?= $this->render_partial("course/dates/_date_row.php", compact("date", "dates", "key")) ?>
    <? endforeach ?>
    </tbody>
</table>
<? endif ?>

<script>
    jQuery(function () {
        jQuery(".dates").tablesorter({
            textExtraction: function (node) { return jQuery(node).data('timestamp') ? jQuery(node).data('timestamp') : jQuery(node).text();},
            cssAsc: 'sortasc',
            cssDesc: 'sortdesc'
        });
    });
</script>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/date-sidebar.png"));

$actions = new ActionsWidget();
$actions->addLink(
    _("Als Doc-Datei runterladen"),
    URLhelper::getURL("dispatch.php/course/dates/export")
);
$sidebar->addWidget($actions);