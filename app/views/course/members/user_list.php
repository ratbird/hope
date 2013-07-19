<? use \Studip\Button; ?>
<a name="users"></a>

<form action="<?= $controller->url_for(sprintf('course/members/edit_user/%s',$page)) ?>"
      method="post" onsubmit="if ($('#user_action').val() == 'remove')
          return confirm('<?= sprintf(_('Wollen Sie die markierten %s wirklich austragen?'),
                  $status_groups['user']) ?>');">
    <table class="default collapsable zebra-hover">
        <colgroup>
            <? if($rechte) :?>
            <col width="20">
            <? endif ?>
            <col width="20">
            <col>
            <? if($rechte) :?>
            <col width="15%">
            <col width="40%">
            <? endif ?>
            <col width="80">
        </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="<?=($rechte) ? 5 : 2?>">
                    <?= $status_groups['user'] ?>
                </th>
                <th class="table_header_bold" style="text-align: right">
                    <? if($rechte) :?>
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
                    <? endif ?>
                </th>
            </tr>
            <tr class="sortable">
                <? if($rechte) :?>
                <th><input aria-label="<?= _('NutzerInnen auswählen') ?>"
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=user]"></th>
                <? endif ?>
                <th></th>
                <th <?= ($sort_by == 'nachname' && $sort_status == 'user') ? sprintf('class="sort%s"', $order) : '' ?>>
                    <? ($sort_status != 'user') ? $order = 'desc' : $order = $order ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=user&order=%s&toggle=%s',
                            $order, ($sort_by == 'nachname'))) ?>#users">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <? if($rechte) : ?>
                <th <?= ($sort_by == 'mkdate' && $sort_status == 'user') ? sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=mkdate&sort_status=user&order=%s&toggle=%s',
                       $order, ($sort_by == 'mkdate'))) ?>#user">
                        <?= _('Anmeldedatum') ?>
                    </a>
                </th>
                <th><?=_('Studiengang')?></th>
                <? endif ?>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr= 0; foreach($users as $leser) : ?>
        <? $fullname = $leser->user->getFullName('full_rev');?>
            <tr>
                <? if($rechte) :?>
                <td>
                    <input aria-label="<?= sprintf(_('Alle %s auswählen'), $status_groups['user']) ?>"
                           type="checkbox" name="user[<?= $leser['user_id'] ?>]" value="1" />
                </td>
                <? endif ?>
                <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a style="position: relative" href="<?= $controller->url_for(sprintf('profile?username=%s',$leser['username'])) ?>">
                    <?= Avatar::getAvatar($leser['user_id'],$leser['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px','title' => htmlReady($fullname))); ?>
                    <?= $leser['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    </a>
                </td>
                <? if($rechte) : ?>
                    <td>
                        <? if(!empty($leser['mkdate'])) : ?>
                            <?= date("d.m.y, H:i:s", $leser['mkdate']) ?>
                        <? endif ?>
                    </td>
                    <td>
                        <? $study_courses = UserModel::getUserStudycourse($leser['user_id']) ?>
                        <? if(!empty($study_courses)) : ?>
                            <? if (count($study_courses) < 2) : ?>
                                <? for ($i = 0; $i < 1; $i++) : ?>
                                    <?= htmlReady($study_courses[$i]['fach']) ?>
                                    (<?= htmlReady($study_courses[$i]['abschluss']) ?>)
                                <? endfor ?>
                            <? else : ?>
                                <?= htmlReady($study_courses[0]['fach']) ?>
                                (<?= htmlReady($study_courses[0]['abschluss']) ?>)
                                [...]
                                <? foreach($study_courses as $course) : ?>
                                    <? $course_res .= sprintf('- %s (%s)<br>',
                                                              htmlReady($course['fach']),
                                                              htmlReady($course['abschluss'])) ?>
                                <? endforeach ?>
                                <?= tooltipIcon('<strong>' . _('Weitere Studiengänge') . '</strong><br>' . $course_res, false, true) ?>
                                <? unset($course_res); ?>
                            <? endif ?>
                        <? endif ?>
                    </td>
                <? endif ?>
                <td style="text-align: right">
                    <? if($user_id != $leser['user_id']) : ?>
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
                    <? else : ?>
                        <?= Assets::img('icons/16/grey/mail.png') ?>
                    <? endif ?>
                                        
                    <? if ($rechte && $is_tutor) : ?>
                    <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'),
                            htmlReady($fullname)) ?>');"
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/user/%s/%s',
                                $page, $leser['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/door-leave.png',
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
                <td class="printhead" colspan="6">
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
