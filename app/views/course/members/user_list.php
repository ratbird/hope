<? use \Studip\Button; ?>
<a name="users"></a>

<form action="<?= $controller->url_for('course/members/edit_user/') ?>" method="post">
    <table class="default collapsable zebra-hover">
        <colgroup>
            <? if($is_tutor) :?>
            <col width="20">
            <? endif ?>
            <col width="20">
            <col>
            <? if($is_tutor) :?>
            <col width="15%">
            <col width="40%">
            <? endif ?>
            <col width="80">
        </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="<?=($is_tutor) ? 5 : 2?>">
                    <?= $status_groups['user'] ?>
                </th>
                <th class="table_header_bold" style="text-align: right">
                    <? if($is_tutor) :?>
                        <?=$controller->getEmailLinkByStatus('user')?>
                        <a href="<?= URLHelper::getLink('sms_send.php',
                                array('filter' => 'send_sms_to_all',
                                    'who' => 'user',
                                    'sms_source_page' => 'dispatch.php/course/members?cid=' . $course_id,
                                    'course_id' => $course_id,
                                    'subject' => $subject))
                        ?>">
                            <?= Assets::img('icons/16/white/inbox.png',
                                    tooltip2(sprintf(_('Nachricht an alle %s versenden'), $status_groups['user'])))?>
                        </a>
                        <? if ($is_tutor) : ?>
                        <a href="<?= $controller->url_for('course/members/add_member/user/')?>">
                            <?= Assets::img('icons/16/white/add/community.png',
                                    tooltip2(sprintf(_('Neue/n %s in der Veranstaltung eintragen'),$status_groups['user']))) ?>
                        </a>
                        <? endif ?>
                    <? endif ?>
                </th>
            </tr>
            <tr class="sortable">
                <? if($is_tutor) :?>
                <th><input aria-label="<?= sprintf(_('Alle %s ausw�hlen'), $status_groups['user']) ?>"
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
                <? if($is_tutor) : ?>
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
                <? if($is_tutor) :?>
                <td>
                    <input aria-label="<?= sprintf(_('%s ausw�hlen'), $status_groups['user']) ?>"
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
                <? if($is_tutor) : ?>
                    <td>
                        <? if(!empty($leser['mkdate'])) : ?>
                            <?= strftime('%x %X', $leser['mkdate'])?>
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
                                <?= tooltipIcon('<strong>' . _('Weitere Studieng�nge') . '</strong><br>' . $course_res, false, true) ?>
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
                                tooltip2(sprintf(_('Nachricht an %s senden'), htmlReady($fullname)))) ?>
                    </a>
                    <? endif ?>
                                        
                    <? if ($is_tutor) : ?>
                    <a href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/user/%s',
                                $leser['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/door-leave.png',
                                tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                    </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
        <? if ($is_tutor) : ?>
        <tfoot>
            <tr>
                <td class="printhead" colspan="6">
                    <select name="action_user" id="user_action" aria-label="<?= _('Aktion ausf�hren') ?>">
                        <option value="">- <?= _('Aktion ausw�hlen') ?></option>
                        <option value="upgrade"><?= sprintf(_('Als %s hochstufen'),
                                htmlReady($status_groups['autor'])) ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
                        <option value="message"><?=_('Nachricht senden')?></option>
                        <!--<option value="copy_to_course"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= Button::create(_('Ausf�hren'), 'submit_user') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>
