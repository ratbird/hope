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
    if (!$lastSemester || ($currentSemester && ($currentSemester->getId() !== $lastSemester->getId()))) {
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
<h2><?= htmlReady($semester['name']) ?></h2>
<table class="dates default">
    <thead>
        <tr>
            <th><?= _("Zeit") ?></th>
            <th><?= _("Typ") ?></th>
            <th><?= _("Thema") ?></th>
            <th><?= _("Raum") ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($dates as $key => $date) : ?>
        <? $dateSemester = Semester::findByTimestamp($date['date']) ?>
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
<h2><?= _("Ohne Semester") ?></h2>
<table class="dates default">
    <thead>
    <tr>
        <th><?= _("Zeit") ?></th>
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
        jQuery.tablesorter.addParser({
            id: 'germandatetime',
            is: function(s) {
                return false;
            },
            format: function(s) {
                var p = s.split(",");
                var t = p[1].split("-")[0].split(":");
                var d = p[0].split('.');
                return new Date(d[2], d[1], d[0], t[0], t[1]).getTime();
            },
            type: 'numeric'
        });
        jQuery(".dates").tablesorter({
            headers: { 0: { sorter:'germandatetime' }},
            textExtraction: function (node) { return jQuery(node).text(); }
        });
    });
</script>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/date-sidebar.png"));

$actions = new ActionsWidget();
$actions->addLink(
    _("Exportieren"),
    URLhelper::getURL("dispatch.php/course/dates/export")
);
$sidebar->addWidget($actions);