<? use \Studip\Button; ?>
<a name="users"></a>

<form action="<?= $controller->url_for(sprintf('course/members/edit_user/%s',$page)) ?>"
      method="post" onsubmit="if ($('#user_action').val() == 'remove')
          return confirm('<?= sprintf(_('Wollen Sie die markierten %s wirklich austragen?'),
                  $status_groups['user']) ?>');">
    <table class="default collapsable zebra-hover">
        <colgroup>
            <col width="3%">
            <col width="3%">
            <col width="79%">
            <col width="15%">
        </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="<?=($rechte) ? 3 : 2?>">
                    <?= $status_groups['user'] ?>
                    <?= tooltipIcon(sprintf(_('%s haben keine Schreibrechte.'),
                            $status_groups['user'])) ?>
                </th>
                <th class="table_header_bold" style="text-align: right">
                    <?=$controller->getEmailLinkByStatus('user')?>
                    <a href="<?= URLHelper::getLink('sms_send.php',
                            array('filter' => 'send_sms_to_all',
                                'who' => 'user',
                                'sms_source_page' => 'dispatch.php/course/members?cid=' . $course_id,
                                'course_id' => $course_id,
                                'subject' => $subject))
                    ?>">
                        <?= Assets::img('icons/16/blue/inbox.png',
                                tooltip2(sprintf(_('Nachricht an alle %s verschicken'), $status_groups['user'])))?>
                    </a>
                    <? if ($is_dozent) : ?>
                    <a href="<?= $controller->url_for('course/members/add_member/user/')?>">
                        <?= Assets::img('icons/16/blue/add/community.png',
                                tooltip2(sprintf(_('Neue/n %s in der Veranstaltung eintragen'), $status_groups['user']))) ?>
                    </a>
                    <? endif ?>
                </th>
            </tr>
            <tr class="sortable">
                <? if($rechte) :?>
                <th><input aria-label="<?= _('NutzerInnen auswählen') ?>"
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=user]"></th>
                <? endif ?>
                <th colspan="<?=$rechte ? 2 : 3?>" <?= ($sort_by == 'nachname' && $sort_status == 'user') ? sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=user&order=%s&toggle=%s',
                            $order, ($sort_by == 'nachname'))) ?>#users">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr= 0; foreach($users as $leser) : ?>
        <? $fullname = $leser->user->getFullName('full_rev');?>
            <tr>
                <td>
                    <input aria-label="<?= sprintf(_('Alle %s auswählen'), $status_groups['user']) ?>"
                           type="checkbox" name="user[<?= $leser['user_id'] ?>]" value="1" />
                </td>
                <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a href="<?= $controller->url_for(sprintf('profile?username=%s',$leser['username'])) ?>">
                    <?= Avatar::getAvatar($leser['user_id'],$leser['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px','title' => htmlReady($fullname))); ?>
                    <?= $leser['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    </a>
                </td>
                <td style="text-align: right">
                    <a href="<?= URLHelper::getLink('sms_send.php',
                                array('filter' => 'send_sms_to_all',
                                'rec_uname' => $leser['username'],
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
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/user/%s/%s',
                                $page, $leser['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/remove/person.png',
                                tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                    </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
        <? if ($rechte && $is_tutor) : ?>
        <tfoot>
            <tr>
                <td class="printhead" colspan="4">
                    <select name="action_user" id="user_action" aria-label="<?= _('Aktion ausführen') ?>">
                        <option value="">- <?= _('Aktion auswählen') ?></option>
                        <option value="upgrade"><?= sprintf(_('Als %s befördern'),
                                htmlReady($status_groups['autor'])) ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
                        <!--<option value="copy_to_course"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= Button::create(_('Ausführen'), 'submit_user') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>
