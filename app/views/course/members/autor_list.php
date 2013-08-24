<? use \Studip\Button; ?>
<a name="autoren"></a>


<form action="<?= $controller->url_for('course/members/edit_autor/') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table id="autor" class="default collapsable tablesorter">
        <caption>
        	 <span class="actions">
                <? if ($is_tutor) : ?>
                        <?=$controller->getEmailLinkByStatus('autor')?>
                        <a href="<?= URLHelper::getLink('sms_send.php',
                            array('filter' => 'send_sms_to_all',
                                'who' => 'autor',
                                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s',$course_id),
                                'course_id' => $course_id,
                                'subject' => $subject))
                        ?>">
                            <?= Assets::img('icons/16/blue/inbox.png',
                                    tooltip2(sprintf(_('Nachricht an alle %s versenden'), htmlReady($status_groups['autor'])))) ?>
                        </a>
                <? endif ?>
 	      	 </span>
        	 <?= $status_groups['autor'] ?>
        </caption>
        <colgroup>
            <col width="20">
            <? if($is_tutor) : ?>
                <? if (!$is_locked) : ?>
                <col width="20">
                <? endif ?>
                <col>
                <col width="15%">
                <? $cols = 6 ?>
                <? if ($semAdmissionEnabled) : ?>
                    <? $cols = 7?>
                    <? $cols_foot = 7?>
                    <? $cols_head = 2?>
                    <col width="25%">
                    <col width="15%">
                <? else : ?>
                    <col width="25%">
                    <? $cols_foot = 6?>
                <? endif ?>
            <? else : ?>
                <col>
                <? $cols = 3 ?>
            <? endif ?>

            <col width="80">
        </colgroup>
        <thead>
            <tr class="sortable">
                <? if ($is_tutor && !$is_locked) : ?>
                    <th><input aria-label="<?= sprintf(_('Alle %s ausw�hlen'), $status_groups['autor']) ?>"
                           type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=autor]">
                    </th>
                <? endif ?>
                <th></th>
                <th <?= ($sort_by == 'nachname' && $sort_status == 'autor') ?
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <? ($sort_status != 'autor') ? $order = 'desc' : $order = $order ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=autor&order=%s&toggle=%s',
                       $order, ($sort_by == 'nachname'))) ?>#autoren">
                       <?=_('Nachname, Vorname')?>
                   </a>
                </th>
                <? if($is_tutor) :?>
                <th <?= ($sort_by == 'mkdate' && $sort_status == 'autor') ? sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=mkdate&sort_status=autor&order=%s&toggle=%s',
                       $order, ($sort_by == 'mkdate'))) ?>#autoren">
                        <?= _('Anmeldedatum') ?>
                    </a>
                </th>
                <th><?= _('Studiengang') ?></th>
                    <? if ($semAdmissionEnabled) : ?>
                    <th><?= _('Kontingent') ?></th>
                    <? endif ?>
                <? endif ?>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr = $autor_nr?>
        <? foreach($autoren as $autor) : ?>
        <? $fullname = $autor->user->getFullName('full_rev');?>
            <tr>
                <? if ($is_tutor && !$is_locked) : ?>
                    <td>
                        <input aria-label="<?= sprintf(_('%s ausw�hlen'), $status_groups['autor']) ?>"
                               type="checkbox" name="autor[<?= $autor['user_id'] ?>]" value="1" />
                    </td>
                <? endif ?>
                <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a style="position: relative" href="<?= $controller->url_for(sprintf('profile?username=%s',$autor['username'])) ?>">
                    <?= Avatar::getAvatar($autor['user_id'], $autor['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px', 'title' => htmlReady($fullname))); ?>
                    <?= $autor['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    <? if ($user_id == $autor['user_id'] && $autor['visible'] == 'no') : ?>
                       (<?= _('unsichtbar') ?>)
                   <? endif ?>
                    </a>
                </td>
                <? if ($is_tutor) : ?>
                    <td>
                        <? if(!empty($autor['mkdate'])) : ?>
                            <?= strftime('%x %X', $autor['mkdate'])?>
                        <? endif ?>
                    </td>
                    <td>
                        <? $study_courses = UserModel::getUserStudycourse($autor['user_id']) ?>
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
                    <? if ($semAdmissionEnabled) : ?>
                        <td>
                            <?= ($autor['admission_studiengang_id'] == 'all') ? _('alle Studieng�nge') : '' ?>
                        </td>
                    <? endif ?>
                <? endif ?>

                <td style="text-align: right">
                    <? if($user_id != $autor['user_id']) : ?>
                        <a href="<?= URLHelper::getLink('sms_send.php',
                                    array('filter' => 'send_sms_to_all',
                                    'rec_uname' => $autor['username'],
                                    'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id),
                                    'subject' => $subject))
                                ?>
                        ">
                            <?= Assets::img('icons/16/blue/mail.png',
                                    tooltip2(sprintf(_('Nachricht an %s senden'), htmlReady($fullname)))) ?>
                        </a>
                    <? endif ?>
                    <? if ($is_tutor && !$is_locked) : ?>
                        <a href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/autor/%s',
                                    $autor['user_id'])) ?>">
                            <?= Assets::img('icons/16/blue/door-leave.png',
                                    tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                        </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        <? if ($invisibles > 0) : ?>
            <tr>
                <td colspan="<?=$cols?>" class="blank"></td>
            </tr>
            <tr>
                <td colspan="<?=$cols?>">+ <?= sprintf(_('%u unsichtbare %s'), $invisibles, $status_groups['autor']) ?></td>
            </tr>
        <? endif ?>

        </tbody>
        <? if ($is_tutor && !$is_locked && count($autoren) >0) : ?>
        <tfoot>
            <tr>
                <td colspan="<?=$cols_foot?>">
                    <select name="action_autor" id="action_autor" aria-label="<?= _('Aktion ausf�hren') ?>">
                        <option value="">- <?= _('Aktion w�hlen') ?></option>
                        <? if($is_dozent) : ?>
                            <option value="upgrade"><?= sprintf(_('Zu %s hochstufen'),
                                htmlReady($status_groups['tutor'])) ?></option>
                        <? endif ?>
                        <option value="downgrade"><?= sprintf(_('Zu %s herunterstufen'),
                                htmlReady($status_groups['user'])) ?></option>
                        <!--<option value="to_admission">Auf Warteliste setzen</option>-->
                        <option value="remove"><?= _('Austragen') ?></option>
                        <option value="message"><?=_('Nachricht senden')?></option>
                        <!--<option value="copy_to_course">In Seminar verschieben/kopieren</option>-->
                    </select>
                    <?= Button::create(_('Ausf�hren'), 'submit_autor') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>


