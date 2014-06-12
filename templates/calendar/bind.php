<? use Studip\Button, Studip\LinkButton; ?>

<? if(!empty($result)) : ?>
<? if (!empty($calendar_sess_control_data['view_prv'])): ?>
<form action="<?= URLHelper::getLink('?cmd=' . $calendar_sess_control_data['view_prv']) ?>" method="post">
<? else: ?>
<form action="<?= URLHelper::getLink('?cmd=showweek') ?>" method="post">
<? endif; ?>
<input type="hidden" name="selected_sem" value="<?=$selected_sem?>" />
<?= CSRFProtection::tokenTag() ?>
<table class="default zebra-hover" id="main_content">
    <colgroup>
        <col width="8px">
        <col width="8px">
        <col>
        <col width="7%">
        <col width="13%">
        <col width="13%">
        <col width="2%">
    </colgroup>
    <thead>
        <tr>
            <th colspan="2" align="center">
                <a href="<?= URLHelper::getLink('dispatch.php/my_courses/groups') ?>">
                    <?= Assets::img('icons/16/blue/group', tooltip2(_('Gruppe ändern'))) ?>
                </a>
            </th>
            <th align="left">
                <a href="<?= URLHelper::getLink('calendar.php', 
                        array('cmd' => 'bind', 'sortby' => 'Name', 'order' => $order, 'selected_sem' => $selected_sem))?>">
                    <?= _('Name') ?>
                </a>
            </th>
            <th>
                <a href="<?= URLHelper::getLink('calendar.php', 
                        array('cmd' => 'bind', 'sortby' => 'count', 'order' => $order, 'selected_sem' => $selected_sem))?>">
                    <?= _('Termine') ?>
                </a>
            </th>
            <th><?= _('besucht') ?></th>
            <th>
                <a href="<?= URLHelper::getLink('calendar.php', 
                        array('cmd' => 'bind', 'sortby' => 'status', 'order' => $order, 'selected_sem' => $selected_sem))?>">
                    <?= _('Status') ?>
                </a>
            </th>
            <th><input type="checkbox" name="all" value="1" checked="checked" aria-label="Alle auswählen" data-proxyfor=":checkbox[name^=sem]"/></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($result as $row): ?>
        <?
            $name  = $row['Name'];
            $name .= ' (' . $row['startsem'];
            $name .= ($row['startsem'] != $row['endsem'] ? ' - ' . $row['endsem'] : '');
            $name .= ')';
        ?>
        <tr>
            <td class="gruppe<?= $row['gruppe'] ?>">
                <?= Assets::img('blank.gif', array('alt' => _('Gruppe'), 'width' => 8, 'height' => 12)) ?>
            </td>
            <td>&nbsp;</td>
            <td>
                <a href="<?= URLHelper::getLink('seminar_main.php?auswahl=' . $row['Seminar_id']) ?>">
                    <?= htmlReady(mila($name)) ?>
                </a>
            </td>
            <td align="center"><?= $row['count'] ?></td>
            <td align="center">
            <? if ($row['visitdate'] == 0): ?>
                <?= _('nicht besucht') ?>
            <? else: ?>
                <?= strftime('%x', $row['visitdate']) ?>
            <? endif; ?>
            </td>
            <td align="center"><?= $row['status'] ?></td>
            <td>
                <input type="checkbox" name="sem[<?= $row['Seminar_id'] ?>]" value="1"
                       <? if ($row['bind_calendar']) echo 'checked'; ?>>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" align="center">
                <?= Button::create(_('Auswählen')) ?>

                <? // Dummy-Wert damit $sem auch ohne ausgewaehlte Seminare ausgewertet wird ?>
                <input type="hidden" name="sem[1]" value="FALSE">
                <input type="hidden" name="atime" value="<?= $atime ?>">
            </td>
        </tr>
    </tfoot>
</table>
</form>
<? else : ?>
    <?= MessageBox::info(_('Keine Veranstaltungen zum Anzeigen vorhanden.'));?>
<? endif; ?>
