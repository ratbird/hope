<? use \Studip\Button; ?>
<br />
<a name="users"></a>
<? if ($rechte) : ?>
<div style="float: right">
    <?=$controller->getEmailLinkByStatus('accepted')?>
    <a href="<?= URLHelper::getLink('sms_send.php',
            array('filter' => 'prelim',
                'sms_source_page' => 'dispatch.php/course/members?cid=' . $course_id,
                'course_id' => $course_id,
                'subject' => $subject))
    ?>">
        <?= Assets::img('icons/16/blue/inbox.png',
                tooltip2(_('Nachricht an alle NutzerInnen verschicken')))?>
    </a>
</div>
<div class="clear"></div>
<? endif ?>
<form action="<?= $controller->url_for(sprintf('course/members/edit_accepted/%s',$page)) ?>"
      method="post" onsubmit="if ($('#action_accepted').val() == 'remove')
          return confirm('<?= _('Wollen Sie die markierten NutzerInnen wirklich austragen?') ?>');">
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
                    <?= _('Vorl�ufig akzeptierte TeilnehmerInnen') ?>
                </th>
            </tr>
            <tr class="sortable">
                <th colspan="<?=($rechte) ? 3 : 2 ?>" <?= ($sort_by == 'nachname' && $sort_status == 'accepted') ?
                sprintf('class="sort%s"', $order) : '' ?>>
                    <? if ($rechte) : ?>
                        <input aria-label="<?= _('NutzerInnen ausw�hlen') ?>"
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=accepted]">
                    <? endif ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=accepted&order=%s&toggle=%s',
                            $order, ($sort_by == 'nachname'))) ?>#users">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr= 0; foreach($accepted as $accept) : ?>
        <? $fullname = $accept->user->getFullName();?>
            <tr>
                <? if ($rechte) : ?>
                <td>
                    <input aria-label="<?= sprintf(_('Alle %s ausw�hlen'), $status_groups['user']) ?>"
                           type="checkbox" name="accepted[<?= $accept['user_id'] ?>]" value="1" />
                </td>
                <? endif ?>
                <td><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a href="<?= $controller->url_for(sprintf('profile?username=%s',$accept['username'])) ?>">
                    <?= Avatar::getAvatar($accept['user_id'], $accept['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px','title' => htmlReady($fullname))); ?>
                    <?= $accept['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: relative; top: -5px; left: -15px; margin: 0px; right: 0px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    </a>
                </td>
                <td style="text-align: right">
                    <a href="<?= URLHelper::getLink('sms_send.php',
                                array('filter' => 'send_sms_to_all',
                                'rec_uname' => $accept['username'],
                                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id),
                                'subject' => $subject))
                            ?>
                    ">
                        <?= Assets::img('icons/16/blue/mail.png',
                                tooltip2(sprintf(_('Nachricht an %s verschicken'), htmlReady($fullname)))) ?>
                    </a>

                    <? if ($rechte && $is_tutor) : ?>
                    <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'),
                            htmlReady($fullname)) ?>');"
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/accepted/%s/%s',
                                $page, $accept['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/remove/person.png',
                                tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
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
                    <select name="action_accepted" id="action_accepted" aria-label="<?= _('Aktion ausf�hren') ?>">
                        <option value="">- <?= _('Aktion w�hlen') ?></option>
                        <option value="upgrade"><?= _('Akzeptieren') ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
                        <!--<option value="copy_to_course"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= Button::create(_('Ausf�hren'), 'submit_accepted') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>
