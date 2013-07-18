<? use \Studip\Button; ?>
<a name="autoren"></a>


<form action="<?= $controller->url_for(sprintf('course/members/edit_autor/%s',$page)) ?>"
          method="post" onsubmit="if ($('#action_autor').val() == 'remove')
              return confirm('<?= sprintf(_('Wollen Sie die markierten %s wirklich austragen?'),
                      htmlReady($status_groups['autor'])) ?>');">

    <table id="autor" class="default collapsable zebra-hover tablesorter">
        <colgroup>
            <col width="20">
            <? if($rechte) : ?>
                <col width="20">
                <col>
                <col width="15%">
                <? $cols = 6 ?>
                <? if ($rechte == 'autor' && $semAdmissionEnabled) : ?>
                    <? $cols = 7?>
                    <? $cols_foot = 7?>
                    <? $cols_head = 2?>
                    <col width="15%">
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
            <tr>
                <th class="table_header_bold" colspan="<?=($rechte) ? $cols-2 : $cols-1?>">
                    <?= $status_groups['autor'] ?>
                </th>
                <th class="table_header_bold" style="text-align: right" <?= ($rechte) ? 'colspan="2"' : ''?>>
                <? if ($rechte) : ?>
                        <?=$controller->getEmailLinkByStatus('autor')?>
                        <a href="<?= URLHelper::getLink('sms_send.php',
                            array('filter' => 'send_sms_to_all',
                                'who' => 'autor',
                                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s',$course_id),
                                'course_id' => $course_id,
                                'subject' => $subject))
                        ?>">
                            <?= Assets::img('icons/16/blue/inbox.png',
                                    tooltip2(sprintf(_('Nachricht an alle %s verschicken'), htmlReady($status_groups['autor'])))) ?>
                        </a>
                    <? endif ?>
                    <? if ($is_dozent) : ?>
                        <a href="<?= $controller->url_for('course/members/add_member')?>">
                            <?= Assets::img('icons/16/blue/add/community.png',
                                    tooltip2(sprintf(_('Neuen %s zur Veranstaltung hinzufügen'),htmlReady($status_groups['autor'])))) ?>
                        </a>
                    <? endif ?>
                </th>
            </tr>
            <tr class="sortable">
                <? if ($rechte) : ?>
                    <th><input aria-label="<?= _('NutzerInnen auswählen') ?>"
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
                <? if($rechte) :?>
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
                <? if ($rechte) : ?>
                <td>
                    <input aria-label="<?= sprintf(_('Alle %s auswählen'), $status_groups['autor']) ?>"
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
                <? if ($rechte) : ?>
                    <td><?= date("d.m.y,", $autor['mkdate']) ?> <?= date("H:i:s", $autor['mkdate']) ?></td>
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
                                <?= tooltipIcon('<strong>' . _('Weitere Studiengänge') . '</strong><br>' . $course_res, false, true) ?>
                                <? unset($course_res); ?>
                            <? endif ?>
                        <? endif ?>
                    </td>
                    <? if ($semAdmissionEnabled) : ?>
                        <td>
                            <?= ($autor['admission_studiengang_id'] == 'all') ? _('alle Studiengänge') : '' ?>
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
                                    tooltip2(sprintf(_('Nachricht an %s verschicken'), htmlReady($fullname)))) ?>
                        </a>
                    <? else : ?>
                        <?= Assets::img('icons/16/grey/mail.png')?>
                    <? endif ?>
                    <? if ($rechte && $is_tutor) : ?>
                    <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'),
                            htmlReady($fullname)) ?>');"
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/autor/%s/%s',
                                $page, $autor['user_id'])) ?>">
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
        <? if ($rechte && count($autoren) >0) : ?>
        <tfoot>
            <tr>
                <td class="printhead" colspan="<?=$cols_foot?>">
                    <select name="action_autor" id="action_autor" aria-label="<?= _('Aktion ausführen') ?>">
                        <option value="">- <?= _('Aktion wählen') ?></option>
                        <? if($is_dozent) : ?>
                            <option value="upgrade"><?= sprintf(_('Als %s befördern'),
                                htmlReady($status_groups['tutor'])) ?></option>
                        <? endif ?>
                        <option value="downgrade"><?= sprintf(_('Als %s herabstufen'),
                                htmlReady($status_groups['user'])) ?></option>
                        <!--<option value="to_admission">Auf Warteliste setzen</option>-->
                        <option value="remove"><?= _('Austragen') ?></option>
                        <!--<option value="copy_to_course">In Seminar verschieben/kopieren</option>-->
                    </select>
                    <?= Button::create(_('Ausführen'), 'submit_autor') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>


