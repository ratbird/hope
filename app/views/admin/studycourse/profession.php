<?
# Lifter010: TODO
?>
<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::success($flash['success'], $flash['success_detail']) ?>
<? elseif (isset($flash['delete'])): ?>
    <?= createQuestion(sprintf(_('Wollen Sie den Studiengang "%s" wirklich löschen?'), $flash['delete'][0]['name']), array('delete' => 1), array('back' => 1), $controller->url_for('admin/studycourse/delete_profession') .'/'. $flash['delete'][0]['studiengang_id']); ?>
<? endif; ?>
<table class="default collapsable">
    <thead>
        <tr>
        <th><a href="<?= $controller->url_for('admin/studycourse/profession/') ?>?sortby=name"><b> <?=_("Name des Studienganges")?></b> <?= (Request::get('sortby', 'name') == 'name') ? Assets::img('dreieck_down.png'): ''?></a></th>
        <th><a href="<?= $controller->url_for('admin/studycourse/profession/') ?>?sortby=seminars"><b> <?=_("Veranstaltungen")?></b> <?= (Request::get('sortby') == 'seminars') ? Assets::img('dreieck_down.png'): ''?></a></th>
        <th><a href="<?= $controller->url_for('admin/studycourse/profession/') ?>?sortby=users"><b> <?=_("Nutzer")?></b> <?= (Request::get('sortby') == 'users') ? Assets::img('dreieck_down.png'): ''?></a></th>
        <th colspan="3"><b> <?=_("Aktion")?></b></th>
        </tr>
    </thead>
    <? foreach ($studycourses as $fach_id => $studycourse): ?>
    <tbody class="<?= count($studycourse['degree'])?'':'empty' ?> collapsed">
    <tr class="table_header header-row" valign="bottom">
        <td class="toggle-indicator"><? if (count($studycourse['degree']) < 1): ?><?=$fach_id+1 ?>. <?= htmlReady($studycourse['name']) ?> <? else: ?> <a class="toggler" href="#"><?=$fach_id+1 ?>. <?= htmlReady($studycourse['name']) ?></a><? endif; ?></td>
        <td><?= $studycourse['count_sem'] ?> </td>
        <td><?= $studycourse['count_user'] ?> </td>
        <td width="20">
            <? if ($studycourse['count_user'] > 0): ?><a href="<?=URLHelper::getLink("sms_send.php?sms_source_page=sms_box.php&sp_id=".$studycourse['studiengang_id']."&emailrequest=1&subject="._("Informationen zum Studiengang:")." ". $studycourse['name']) ?>">
                <?= Assets::img('icons/16/blue/mail.png', array('title' => _('Nachricht an alle Benutzer schicken'), 'class' => 'text-top')) ?>
            </a><? endif;?>
        </td>
        <td width="20">
            <a href="<?=$controller->url_for('admin/studycourse/edit_profession/'.$studycourse['studiengang_id'])?>">
                <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Studiengang bearbeiten'), 'class' => 'text-top')) ?>
            </a>
        </td>
        <td width="20">
            <? if ($studycourse['count_user'] == 0 && $studycourse['count_sem'] == 0): ?> <a href="<?=$controller->url_for('admin/studycourse/delete_profession')?>/<?= $studycourse['studiengang_id'] ?>">
                <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Studiengang löschen'), 'class' => 'text-top')) ?>
            </a><? endif;?>
        </td>
    </tr>
    <? foreach ($studycourse['degree'] as $index => $degree): ?>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td class="label-cell">
           <?= $fach_id + 1 ?>.<?= $index + 1 ?>
           <?= htmlReady($degree['name']) ?>
        </td>
        <td></td>
        <td><?= $degree['count_user'] ?></td>
        <td><a href="<?=URLHelper::getLink("sms_send.php?sms_source_page=sms_box.php&prof_id=".$studycourse['studiengang_id']."&deg_id=".$degree['abschluss_id']."&emailrequest=1&subject="._("Informationen zum Studiengang:")." ". $studycourse['name'])." (".$degree['name'].")"?>"><?= Assets::img('icons/16/blue/mail.png', array('title' => 'Nachricht an alle Nutzer schicken')) ?></a></td>
        <td></td>
        <td></td>
    </tr>
    <? endforeach; TextHelper::reset_cycle(); ?>
    </tbody>
    <? endforeach ?>
</table>
