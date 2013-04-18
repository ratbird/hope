<? use \Studip\Button; ?>
<br />
<a name="awaiting"></a>
<? if ($rechte) : ?>
<div style="float: right">
    <?=Course_MembersController::getEmailLinkByStatus($course_id, 'awaiting')?>
    <a href="<?= URLHelper::getLink('sms_send.php', 
            array('sms_source_page' => 'dispatch.php/course/members?cid=' . $course_id, 
                'course_id' => $course_id, 
                'subject' => $subject)) 
    ?>">
        <?= Assets::img('icons/16/blue/inbox.png', tooltip2( _('Nachricht an alle NutzerInnen verschicken')))?>
    </a>
</div>
<div class="clear"></div>
<? endif ?>

<form action="<?= $controller->url_for(sprintf('course/members/edit_awaiting/%s/?cid=%s', $page, Request::get('cid'))) ?>" 
      method="post" onsubmit="if ($('#action_awaiting').val() == 'remove')
          return confirm('<?= _('Wollen Sie die markierten NutzerInnen wirklich austragen?') ?>');">
    <table class="default collapsable zebra-hover">
        <colgroup>
            <? if($rechte) : ?>
            <col width="3%">
            <? endif ?>
            <col width="3%">
            <col width="<?=($rechte) ? '49%' : '82%'?>">
            <? if($rechte) : ?>
            <col width="5%">
            <col width="25%"
            <? endif ?>
            <col width="15%">
        </colgroup>
        <thead>
            <tr class="sortable">
                <th colspan="<?=($rechte) ? 3 : 2?>"<?= ($sort_by == 'nachname' && $sort_status == 'awaiting') ? 
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <? if ($rechte) : ?>
                        <input aria-label="<?= _('NutzerInnen ausw�hlen') ?>" 
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=awaiting]">
                    <? endif ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=awaiting&order=%s&toggle=%s', 
                            $order, ($sort_by == 'nachname'))) ?>#awaiting"> 
                        <?= $waitingTitle ?>
                    </a>
                </th>
                <? if ($rechte) : ?>
                <th style="text-align: center" <?= ($sort_by == 'position' && $sort_status == 'awaiting') ? 
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=position&sort_status=awaiting&order=%s&toggle=%s',
                            $order, ($sort_by == 'position'))) ?>#awaiting">
                        <?= _('Position') ?>
                    </a>
                </th>
                <th style="text-align: center"><?= _('Kontingent') ?></th>
                <? endif ?>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr = 0 ?>
        <? foreach($awaiting as $waiting) : ?>
            <tr>
                <? if ($rechte) : ?>
                    <td><input aria-label="<?= _('Alle NutzerInnen ausw�hlen') ?>" type="checkbox" 
                               name="awaiting[<?= $user['user_id'] ?>]" value="1" /></td>
                <? endif ?>
                <td><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a href="<?= $controller->url_for(sprintf('profile?username=%s',$waiting['username'])) ?>">
                    <?= Avatar::getAvatar($waiting['user_id'])->getImageTag(Avatar::SMALL, 
                            array('style' => 'margin-right: 5px')); ?> 
                    <?= $waiting['mkdate'] >= $last_visitdate ? Assets::img('red_star.png', 
                        array('style' => 'position: relative; top: -5px; left: -15px; margin: 0px; right: 0px')) : '' ?>
                    <?= $waiting->user->getFullName() ?>                    
                    </a>
                </td>
                <? if ($rechte) : ?>
                    <td style="text-align: center"><?= $waiting['position'] ?></td>
                    <td style="text-align: center">
                        <?= ($autor['admission_studiengang_id'] == 'all') ? _('alle Studieng�nge') : '' ?>
                    </td>
                <? endif ?>
                <td style="text-align: right">
                    <a href="<?= URLHelper::getLink('sms_send.php', 
                                array('filter' => 'send_sms_to_all', 
                                'rec_uname' => $waiting['username'], 
                                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id), 
                                'subject' => $subject)) 
                            ?>
                    ">
                        <?= Assets::img('icons/16/blue/mail.png', 
                                tooltip2(sprintf(_('Nachricht an %s verschicken'), $waiting->user->getFullName()))) ?>
                    </a>
                    <? if ($rechte && $is_tutor) : ?>
                    <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'), 
                            $waiting->user->getFullname()) ?>');" 
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/awaiting/%s/%s',
                                $page, $waiting['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/remove/person.png', 
                                tooltip2(sprintf(_('%s austragen'), $waiting->user->getFullName()))) ?>
                    </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
        <? if ($rechte) : ?>
        <tfoot>
            <tr>
                <td class="printhead" colspan="<?=($rechte) ? 6 : 3?>">
                    <select name="action_awaiting" id="action_awaiting" aria-label="<?= _('Aktion ausf�hren') ?>">
                        <option value="">- <?= _('Aktion w�hlen') ?></option>
                        <option value="upgrade"><?= _('Als NutzerInnen bef�rdern') ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
    <!--                    <option value="copy_to_sem"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= tooltipIcon( _('Mit dieser Einstellung beeinflussen Sie, 
                            ob Teilnehmer die Sie hinzuf�gen auf die Kontingentpl�tze angerechnet werden.'))?>
                    <?= _("Kontingent ber�cksichtigen:"); ?>
                    <input type="checkbox" value="1" name="consider_contingent" checked="checked" />
                    <?= Button::create(_('Ausf�hren'), 'submit_awaiting') ?>  
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>