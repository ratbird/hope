<? use \Studip\Button; ?>

<a name="tutoren"></a>
<? if($rechte) : ?>
<div style="float: right">
    <?=Course_MembersController::getEmailLinkByStatus($course_id, 'tutor')?>
    <a href="<?= URLHelper::getLink('sms_send.php', 
            array('filter' => 'send_sms_to_all', 
                'who' => 'tutor', 
                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s',$course_id), 
                'course_id' => $course_id, 
                'subject' => $subject)) 
    ?>">
        <?= Assets::img('icons/16/blue/inbox.png', 
                tooltip2(sprintf(_('Nachricht an alle %s verschicken'), $status_groups['tutor'])))?>
    </a>
    <? if ($is_dozent && !$is_tutor_locked) : ?>
    <a href="<?= $controller->url_for('course/members/add_tutor/')?>">
        <?= Assets::img('icons/16/blue/add/community.png', 
                tooltip2(sprintf(_('Neue/n %s in der Veranstaltung eintragen'), $status_groups['tutor']))) ?>
    </a>
    <? endif ?>
</div>
<div class="clear"></div>
<? endif ?>

<form action="<?= $controller->url_for(sprintf('course/members/edit_tutor/%s',$page)) ?>" 
      method="post" onsubmit="if ($('#tutor_action').val() == 'remove') 
          return confirm('<?= sprintf(_('Wollen Sie die markierten %s wirklich austragen?'), 
                  $status_groups['tutor']) ?>');">
    <table class="default collapsable zebra">
        <colgroup>
        <? if($rechte) : ?>
        <col width="3%">
        <? endif ?>
        <col width="3%">
        <col width="<?=($rechte) ? '79%' : '82%'?>">
        <col width="15%">
    </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="<?=($rechte) ? 4 : 3?>">
                    <?= $status_groups['tutor'] ?>
                    <?= tooltipIcon(sprintf(_('%s haben Verwaltungsrechte, können jedoch keine %s hinzufügen.'), 
                            $status_groups['tutor'], $status_groups['dozent'])) ?>
                </th>
            </tr>
            <tr class="sortable">
                <th colspan="<?=($rechte) ? 3 : 2 ?>" <?= ($sort_by == 'nachname' && $sort_status == 'tutor') ? 
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <? if ($rechte) : ?>
                        <input aria-label="<?= _('NutzerInnen auswählen') ?>" 
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=tutor]">
                    <? endif ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=tutor&order=%s&toggle=%s', 
                            $order, ($sort_by == 'nachname'))) ?>#tutoren">
                        <?=_('Nachname, Vorname')?>
                    </a>
                    
                </th>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr= 0; foreach($tutoren as $tutor) : ?>
            <tr>
                <? if ($rechte) : ?>
                <td>
                    <input aria-label="<?= sprintf(_('Alle %s auswählen'), $status_groups['tutor']) ?>" 
                           type="checkbox" name="tutor[<?= $tutor['user_id'] ?>]" value="1" />
                </td>
                <? endif ?>
                <td><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a href="<?= $controller->url_for(sprintf('profile?username=%s',$tutor['username'])) ?>">
                    <?= Avatar::getAvatar($tutor['user_id'])->getImageTag(Avatar::SMALL, 
                            array('style' => 'margin-right: 5px')); ?> 
                    <?= $tutor['mkdate'] >= $last_visitdate ? Assets::img('red_star.png', 
                        array('style' => 'position: relative; top: -5px; left: -15px; margin: 0px; right: 0px')) : '' ?>
                    <?= htmlReady($tutor->user->getFullName()) ?>                    
                    </a>
                </td>
                <td style="text-align: right">
                    <a href="<?= URLHelper::getLink('sms_send.php', 
                                array('filter' => 'send_sms_to_all', 
                                'rec_uname' => $tutor['username'], 
                                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id), 
                                'subject' => $subject)) 
                            ?>
                    ">
                        <?= Assets::img('icons/16/blue/mail.png', 
                                tooltip2(sprintf(_('Nachricht an %s verschicken'), htmlReady($tutor->user->getFullName())))) ?>
                    </a>
                    
                    <? if ($rechte && $is_dozent && $user_id != $tutor['user_id'] && count($tutoren) > 1) : ?>
                    <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'), 
                            htmlReady($tutor->user->getFullName())) ?>');" 
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/tutor/%s/%s',
                                $page, $tutor['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/remove/person.png', 
                                tooltip2(sprintf(_('%s austragen'), htmlReady($tutor->user->getFullName())))) ?>
                    </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
        <? if ($rechte && $is_dozent) : ?>
        <tfoot>
            <tr>
                <td class="printhead" colspan="4">
                    <select name="action_tutor" id="tutor_action" aria-label="<?= _('Aktion ausführen') ?>">
                        <option value="">- <?= _('Aktion auswählen') ?></option>
                        <option value="downgrade"><?= sprintf(_('Zu %s herabstufen'), $status_groups['autor']) ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
                        <!--<option value="copy_to_course"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= Button::create(_('Ausführen'), 'submit_autor') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>