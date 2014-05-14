<?
# Lifter010: TODO
?>
<? if (isset($flash['delete'])): ?>
    <? if ($flash['mode']=='semester'): ?>
        <?= createQuestion(sprintf(_('Wollen Sie das Semester "%s" wirklich löschen?'), $flash['delete']['name']), array('delete' => 1), array('back' => 1), $controller->url_for('admin/semester/delete/') . $flash['delete']['semester_id']."/semester"); ?>
    <? elseif ($flash['mode']=='holiday'): ?>
        <?= createQuestion(sprintf(_('Wollen Sie die Ferien "%s" wirklich löschen?'), $flash['delete']['name']), array('delete' => 1), array('back' => 1), $controller->url_for('admin/semester/delete/') . $flash['delete']['holiday_id']."/holiday"); ?>
    <? endif; ?>
<? endif; ?>

<table class="default">
<caption>
    <?= _("Semester") ?>
</caption>
<thead>
<tr>
    <th><?= _("Name") ?></th>
    <th><?= _("Beginn") ?></th>
    <th><?= _("Ende") ?></th>
    <th><?= _("Vorlesungsbeginn") ?></th>
    <th><?= _("Vorlesungsende") ?></th>
    <th><?= _("Anzahl der Veranstaltungen") ?></th>
    <th colspan="2" width="2%"></th>
</tr>
</thead>
<? foreach ($semesters as $single): ?>
<tr>
    <td title="<?= htmlReady($single["description"]) ?>"><?= htmlReady($single["name"]) ?></td>
    <td><?= date("d.m.Y", $single["beginn"]) ?></td>
    <td><?= date("d.m.Y", $single["ende"]) ?></td>
    <td><?= date("d.m.Y", $single["vorles_beginn"]) ?></td>
    <td><?= date("d.m.Y", $single["vorles_ende"]) ?></td>
    <td>
        <?= ($disallow_delete = Semester::countAbsolutSeminars($single["semester_id"])) ?>
        <?= sprintf(_('(+ %s implizit)'), Semester::countContinuousSeminars($single["semester_id"])+Semester::countDurationSeminars($single["semester_id"])) ?>
    </td>
    <td class="actions">
        <a class="load-in-new-row" href="<?= URLHelper::getLink('dispatch.php/admin/semester/edit_semester/' . $single["semester_id"]) ?>">
            <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Semesterangaben bearbeiten'))) ?>
        </a>
    </td>
    <td class="actions">
        <? if (!$disallow_delete) : ?>
        <a href="<?= URLHelper::getLink('dispatch.php/admin/semester/delete/' . $single["semester_id"] . '/semester') ?>">
            <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Semester löschen'))) ?>
        </a>
        <? endif ?>
    </td>
</tr>
<? endforeach ?>
<tfoot>
<tr>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td class="actions">
        <a class="load-in-new-row" href="<?=URLHelper::getLink('dispatch.php/admin/semester/edit_semester') ?>">
            <?= Assets::img('icons/16/blue/add.png', array('title' => _('Neues Semester anlegen'))) ?>
        </a>
    </td>
</tr>
</tfoot>
</table>
<br>

<table class="default">
<caption>
    <?= _("Ferien") ?>
</caption>
<thead>
<tr>
    <th><?= _("Name") ?></th>
    <th><?= _("Beginn") ?></th>
    <th><?= _("Ende") ?></th>
    <th></th>
</tr>
</thead>
<? foreach ($holidays as $single): ?>
<tr>
    <td title="<?= htmlReady($single["description"]) ?>"><?= htmlReady($single["name"]) ?></td>
    <td><?= date("d.m.Y", $single["beginn"]) ?></td>
    <td><?= date("d.m.Y", $single["ende"]) ?></td>
    <td class="actions">
        <a class="load-in-new-row" href="<?=URLHelper::getLink('dispatch.php/admin/semester/edit_holidays/' . $single["holiday_id"]) ?>">
            <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Ferienangaben bearbeiten'))) ?>
        </a>
        <a href="<?=URLHelper::getLink('dispatch.php/admin/semester/delete/' . $single["holiday_id"] . '/holiday') ?>">
            <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Ferien löschen'))) ?>
        </a>
    </td>
</tr>
<? endforeach ?>
<tfoot>
<tr>
    <td></td>
    <td></td>
    <td></td>
    <td class="actions">
        <a class="load-in-new-row" href="<?=URLHelper::getLink('dispatch.php/admin/semester/edit_holidays') ?>">
            <?= Assets::img('icons/16/blue/add.png', array('title' => _('Neue Ferien anlegen'))) ?>
        </a>
    </td>
</tr>
</tfoot>
</table>
<script>
    jQuery('body').bind('ajaxLoaded', function(){
        jQuery('#beginn').datepicker();
        jQuery('#ende').datepicker();
        jQuery('#vorles_beginn').datepicker();
        jQuery('#vorles_ende').datepicker();
    });
</script>
