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
    <?= createQuestion(sprintf(_('Wollen Sie den Abschluss "%s" wirklich löschen?'), $flash['delete'][0]['name']), array('delete' => 1), array('back' => 1), $controller->url_for('admin/studycourse/delete_degree') .'/'. $flash['delete'][0]['abschluss_id']); ?>
<? endif; ?>
<table class="default collapsable">
    <tr>
        <th><a href="<?= $controller->url_for('admin/studycourse/degree/') ?>?sortby=name"><b> <?=_("Name der Abschlüsse")?></b> <?= (Request::get('sortby', 'name') == 'name') ? Assets::img('dreieck_down.png'): ''?></a></th>
        <th><a href="<?= $controller->url_for('admin/studycourse/degree/') ?>?sortby=users"><b> <?=_("Nutzer")?></b> <?= (Request::get('sortby') == 'users') ? Assets::img('dreieck_down.png'): ''?></a></th>
        <th colspan="3"><b> <?=_("Aktion")?></b></th>
    </tr>
    <? foreach ($studydegrees as $abschluss_id => $studydegree) : ?>
    <tbody class="<?= count($studydegree['profession'])?'':'empty' ?> collapsed ">
    <tr class="steel header-row">
        <td class="toggle-indicator"><? if (count($studydegree['profession']) < 1): ?><?=$abschluss_id+1 ?>. <?= htmlReady($studydegree['name']) ?> <? else: ?> <a class="toggler" href="#"><?=$abschluss_id+1 ?>. <?= htmlReady($studydegree['name']) ?> </a><? endif; ?></td>
        <td> <?= $studydegree['count_user'] ?> </td>
        <td width="20">
            <? if ($studydegree['count_user'] > 0): ?><a href="<?=URLHelper::getLink("sms_send.php?sms_source_page=sms_box.php&sd_id=".$studydegree['abschluss_id']."&emailrequest=1&subject="._("Informationen zum Studienabschluss:")." ". $studydegree['name']) ?>">
                <?= Assets::img('icons/16/blue/mail.png', array('title' => _('Nachricht an alle Nutzer schicken'), 'class' => 'text-top')) ?>
            </a><? endif;?>
        </td>
        <td width="20">
            <a href="<?=$controller->url_for('admin/studycourse/edit_degree/'.$studydegree['abschluss_id'])?>">
                <?= Assets::img('icons/16/blue/edit.png', array('title' => _('Abschluss bearbeiten'), 'class' => 'text-top')) ?>
            </a>
        </td>
        <td width="20">
            <? if ($studydegree['count_user'] == 0): ?><a href="<?=$controller->url_for('admin/studycourse/delete_degree')?>/<?= $studydegree['abschluss_id'] ?>">
                <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Abschluss löschen'), 'class' => 'text-top')) ?>
            </a><? endif; ?>
        </td>
    </tr>
    <?php foreach ($studydegree['profession'] as $index => $studycourse): ?>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td class="label-cell">
           <?=$abschluss_id + 1 ?>.<?=$index + 1 ?>
           <?= htmlReady($studycourse['name']) ?>
        </td>
        <td><?= $studycourse['count_user'] ?></td>
        <td><a href="<?=URLHelper::getLink("sms_send.php?sms_source_page=sms_box.php&prof_id=".$studycourse['studiengang_id']."&deg_id=".$studydegree['abschluss_id']."&emailrequest=1&subject="._("Informationen zum Studiengang:")." ". htmlReady($studycourse['name']))." (".htmlReady($studydegree['name']).")" ?>"><?= Assets::img('icons/16/blue/mail.png', array('title' => _('Eine Nachricht an alle Nutzer schicken'))) ?></a> </td>
        <td></td>
        <td></td>
    </tr>
    <? endforeach; TextHelper::reset_cycle(); ?>
    </tbody>
    <? endforeach; ?>
 </table>
