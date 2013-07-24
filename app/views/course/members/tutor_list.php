<? use \Studip\Button; ?>

<a name="tutoren"></a>
<form action="<?= $controller->url_for('course/members/edit_tutor') ?>"
      method="post" onsubmit="if ($('#tutor_action').val() == 'remove')
          return confirm('<?= sprintf(_('Wollen Sie die markierten %s wirklich austragen?'),
                  $status_groups['tutor']) ?>');">
    <table class="default collapsable zebra-hover">
        <colgroup>
        <? if($is_dozent) : ?>
            <col width="20">
        <? endif ?>
        <col width="<?=(!$is_dozent) ? '20' : '40'?>">
        <col>
        <? if($is_dozent) : ?>
            <col width="15%">
            <? if($semAdmissionEnabled) :?>
            <col width="40%">
            <? else :?>
            <col width="25%">
            <?endif ?>
        <? endif ?>
        <col width="80">
    </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="<?=($is_dozent) ? 5 : 2?>">
                    <?= $status_groups['tutor'] ?>
                </th>
                <th class="table_header_bold" style="text-align: right">
                <? if($is_tutor) : ?>
                    <?=$controller->getEmailLinkByStatus('tutor')?>
                    <a href="<?= URLHelper::getLink('sms_send.php',
                            array('filter' => 'send_sms_to_all',
                                'who' => 'tutor',
                                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s',$course_id),
                                'course_id' => $course_id,
                                'subject' => $subject))
                    ?>">
                        <?= Assets::img('icons/16/white/inbox.png',
                                tooltip2(sprintf(_('Nachricht an alle %s verschicken'), $status_groups['tutor'])))?>
                    </a>
                    <? if ($is_dozent && !$is_tutor_locked) : ?>
                    <a href="<?= $controller->url_for('course/members/add_tutor/')?>">
                        <?= Assets::img('icons/16/white/add/community.png',
                                tooltip2(sprintf(_('Neue/n %s in der Veranstaltung eintragen'), $status_groups['tutor']))) ?>
                    </a>
                    <? endif ?>
                <? endif ?>
                </th>
            </tr>
            <tr class="sortable">
                <? if($is_dozent) : ?>
                <th><input aria-label="<?= _('NutzerInnen auswählen') ?>"
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=tutor]"></th>
                <? endif ?>
                <th></th>
                <th <?= ($sort_by == 'nachname' && $sort_status == 'tutor') ?
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <? ($sort_status != 'tutor') ? $order = 'desc' : $order = $order ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=tutor&order=%s&toggle=%s',
                            $order, ($sort_by == 'nachname'))) ?>#tutoren">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <? if($is_dozent) : ?>
                <th <?= ($sort_by == 'mkdate' && $sort_status == 'tutor') ? sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=mkdate&sort_status=tutor&order=%s&toggle=%s',
                       $order, ($sort_by == 'mkdate'))) ?>#tutoren">
                        <?= _('Anmeldedatum') ?>
                    </a>
                </th>
                <th><?=_('Studiengang')?></th>
                <? endif ?>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr= 0; foreach($tutoren as $tutor) : ?>
        <? $fullname = $tutor->user->getFullName('full_rev');?>
            <tr>
                <? if ($is_dozent) : ?>
                <td>
                    <input aria-label="<?= sprintf(_('Alle %s auswählen'), $status_groups['tutor']) ?>"
                           type="checkbox" name="tutor[<?= $tutor['user_id'] ?>]" value="1" />
                </td>
                <? endif ?>
                <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a style="position: relative" href="<?= $controller->url_for(sprintf('profile?username=%s',$tutor['username'])) ?>">
                    <?= Avatar::getAvatar($tutor['user_id'], $tutor['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px', 'title' => htmlReady($fullname))); ?>
                    <?= $tutor['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    </a>
                </td>
                <? if($is_dozent) : ?>
                    <td>
                        <? if(!empty($tutor['mkdate'])) : ?>
                            <?= strftime('%x %X', $tutor['mkdate'])?>
                        <? endif ?>
                    </td>
                    <td>
                        <? $study_courses = UserModel::getUserStudycourse($tutor['user_id']) ?>
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
                    <? if($user_id != $tutor['user_id']) : ?>
                    <a href="<?= URLHelper::getLink('sms_send.php',
                                array('filter' => 'send_sms_to_all',
                                'rec_uname' => $tutor['username'],
                                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id),
                                'subject' => $subject))
                            ?>
                    ">
                        <?= Assets::img('icons/16/blue/mail.png',
                              tooltip2(sprintf(_('Nachricht an %s verschicken'), htmlReady($fullname)))) ?>
                    </a>
                    <? else :?>
                        <?= Assets::img('icons/16/grey/mail.png') ?>
                    <? endif ?>
                    <? if ($is_dozent && $user_id != $tutor['user_id'] && count($tutoren) >= 1) : ?>
                    <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'),
                            htmlReady($fullname)) ?>');"
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/tutor/%s',
                                $tutor['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/door-leave.png',
                                tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                    </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
        <? if ($is_dozent) : ?>
        <tfoot>
            <tr>
                <td class="printhead" colspan="6">
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