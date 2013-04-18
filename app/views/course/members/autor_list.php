<? use \Studip\Button; ?>
<a name="autoren"></a>

<? if ($total > $max_per_page) : ?>
    <? if ($page != 0) : ?>
        <?= Assets::img('icons/16/blue/arr_2right.png') ?>
        <a title="<?= sprintf(_('Lassen Sie sich alle %s anzeigen'), htmlReady($status_groups['autor'])) ?>" 
           href="<?= $controller->url_for('course/members/index/0') ?>#autoren">
            <?= sprintf(_('Alle %s anzeigen'), htmlReady($status_groups['autor'])) ?>
        </a>
    <? else : ?>
        <?= Assets::img('icons/16/blue/arr_2down.png') ?>
        <a title="<?= sprintf(_('Lassen Sie sich eine gek�rzte Liste von %s anzeigen'), htmlReady($status_groups['autor'])) ?>
           " href="<?= $controller->url_for('course/members/index/1') ?>#autoren">
            <?= _('Gek�rzte Liste anzeigen') ?>
        </a>
    <? endif ?>
<? endif ?>

<? if ($rechte) : ?>
<div style="float: right; padding: 10px 0px 0px 28px; width: 60%; text-align: right">
    <? if (!empty($autoren)) : ?>
        <?=Course_MembersController::getEmailLinkByStatus($course_id, 'autor')?>
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


    <? if (!empty($autoren)) : ?>
        <?= $rtfExport ?>
        <?= $csvExport ?>
    <? endif ?>

    <? if ($is_dozent) : ?>
        <a href="<?= $controller->url_for('course/members/add_member')?>">
            <?= Assets::img('icons/16/blue/add/community.png', 
                    tooltip2(sprintf(_('Neuen %s zur Veranstaltung hinzuf�gen'),htmlReady($status_groups['autor'])))) ?>
        </a>
    <? endif ?>
</div>
<div class="clear"></div>
<? endif ?>

<? if (!empty($autoren)) : ?>

<form action="<?= $controller->url_for(sprintf('course/members/edit_autor/%s',$page)) ?>"
          method="post" onsubmit="if ($('#action_autor').val() == 'remove')
              return confirm('<?= sprintf(_('Wollen Sie die markierten %s wirklich austragen?'), 
                      htmlReady($status_groups['autor'])) ?>');">
     
    <table id="autor" class="default collapsable zebra-hover tablesorter">
        <colgroup>
            <col width="3%"> 
            <? if($rechte) : ?>
                <col width="3%">
                <col width="25%">
                <col width="15%">
                <? $cols = 6 ?>
                <? if ($rechte == 'autor' && $semAdmissionEnabled) : ?>
                    <? $cols = 7?>
                    <? $cols_foot = 5?>
                    <? $cols_head = 3?>
                    <col width="29%">
                    <col width="15%">
                <? else : ?>
                    <? $cols_foot = 4?>
                    <? $cols_head = 3?>
                    <col width="39%">
                <? endif ?>
            <? else : ?>
                <col width="82%">
                <? $cols_head = 2?>
                <? $cols = 3 ?>
            <? endif ?>
            <col width="15%">
        </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="<?=$cols?>">
                    <?= $status_groups['autor'] ?>
                    <?= tooltipIcon(sprintf(_('%s haben Schreib- und Leserechte'), htmlReady($status_groups['autor']))) ?>
                </th>
            </tr>
            <tr class="sortable">
                <th colspan="<?=$cols_head?>" <?= ($sort_by == 'nachname' && $sort_status == 'autor') ? 
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <? if ($rechte) : ?>
                        <input aria-label="<?= _('NutzerInnen ausw�hlen') ?>" 
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=autor]">
                    <? endif ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=autor&order=%s&toggle=%s', 
                       $order, ($sort_by == 'nachname'))) ?>#autoren">
                       <?=_('Nachname, Vorname')?>
                   </a>
                </th>
                <? if($rechte) :?>
                <th <?= ($sort_by == 'mkdate' && $sort_status == 'tutor') ? sprintf('class="sort%s"', $order) : '' ?>>
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
            <tr>
                <? if ($rechte) : ?>
                <td>
                    <input aria-label="<?= sprintf(_('Alle %s ausw�hlen'), $status_groups['autor']) ?>" 
                           type="checkbox" name="autor[<?= $autor['user_id'] ?>]" value="1" />
                </td>
                <? endif ?>
                <td><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a href="<?= $controller->url_for(sprintf('profile?username=%s',$autor['username'])) ?>">
                    <?= Avatar::getAvatar($autor['user_id'])->getImageTag(Avatar::SMALL, 
                            array('style' => 'margin-right: 5px')); ?> 
                    <?= $autor['mkdate'] >= $last_visitdate ? Assets::img('red_star.png', 
                        array('style' => 'position: relative; top: -5px; left: -15px; margin: 0px; right: 0px')) : '' ?>
                    <?= htmlReady($autor->user->getFullName()) ?>  
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
                    <a href="<?= URLHelper::getLink('sms_send.php', 
                                array('filter' => 'send_sms_to_all', 
                                'rec_uname' => $autor['username'], 
                                'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id), 
                                'subject' => $subject)) 
                            ?>
                    ">
                        <?= Assets::img('icons/16/blue/mail.png', 
                                tooltip2(sprintf(_('Nachricht an %s verschicken'), htmlReady($autor->user->getFullName())))) ?>
                    </a>
                    
                    <? if ($rechte && $is_tutor) : ?>
                    <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'), 
                            htmlReady($autor->user->getFullname())) ?>');" 
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/autor/%s/%s',
                                $page, $autor['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/remove/person.png', 
                                tooltip2(sprintf(_('%s austragen'), htmlReady($autor->user->getFullName())))) ?>
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
        <tfoot>
            <tr>
            <? if ($rechte) : ?>
                <td class="printhead" colspan="<?=$cols_foot?>">
                    <select name="action_autor" id="action_autor" aria-label="<?= _('Aktion ausf�hren') ?>">
                        <option value="">- <?= _('Aktion w�hlen') ?></option>
                        <option value="upgrade"><?= sprintf(_('Als %s bef�rdern'), 
                                htmlReady($status_groups['tutor'])) ?></option>
                        <option value="downgrade"><?= sprintf(_('Als %s herabstufen'), 
                                htmlReady($status_groups['user'])) ?></option>
                        <!--<option value="to_admission">Auf Warteliste setzen</option>-->
                        <option value="remove"><?= _('Austragen') ?></option>
                        <!--<option value="copy_to_course">In Seminar verschieben/kopieren</option>-->
                    </select>
                    <?= Button::create(_('Ausf�hren'), 'submit_autor') ?>
                </td>
            <? endif ?>
            <td style="text-align: right" class="printhead" colspan="<?=!$rechte ? $cols : 2?>">
            <? if ($total > $max_per_page && $page != 0) : ?>
                <?
                $pagination = $GLOBALS['template_factory']->open('shared/pagechooser');
                $pagination->set_attributes(array(
                    'perPage' => $max_per_page,
                    'num_postings' => $total,
                    'page' => $page,
                    'pagelink' => $controller->url_for('course/members/index/%u#autoren')
                ));
                echo $pagination->render();
                ?>
            <? endif ?>
            </td>
            </tr>
        </tfoot>
    </table>
</form>
<? else : ?>
    <?= MessageBox::info(sprintf(_('Derzeit sind noch keine %s in der Veranstaltung eingetragen.'), 
            htmlReady($status_groups['autor']))) ?>
<? endif; ?>

