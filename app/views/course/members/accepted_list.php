<? use \Studip\Button; ?>
<br />
<a name="users"></a>

<form action="<?= $controller->url_for('course/members/edit_accepted/') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default collapsable">
        <caption>
            <span class="actions">
                <?=$controller->getEmailLinkByStatus('accepted', $accepted)?>
                    <a href="<?= URLHelper::getLink('dispatch.php/messages/write',
                            array('filter' => 'prelim',
                                'course_id' => $course_id,
                                'default_subject' => $subject))
                    ?>" data-dialog>
                        <?= Assets::img('icons/16/blue/inbox.png',
                                tooltip2(sprintf(_('Nachricht an alle %s versenden'), 'vorläufig akzeptierten NutzerInnen')))?>
                    </a>
            </span>
            <?= _('Vorläufig akzeptierte TeilnehmerInnen') ?>
        </caption>
        <colgroup>
            <? if (!$is_locked) : ?>
            <col width="20">
            <? endif ?>
            <col width="20">
            <col>
            <col width="15%">
            <col width="40%">
            <col width="80">
        </colgroup>
        <thead>
            <tr class="sortable">
                <? if (!$is_locked) : ?>
                <th>
                    <input aria-label="<?= sprintf(_('Alle %s auswählen'), 'vorläufig akzeptierten NutzerInnen') ?>"
                               type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=accepted]">
                </th>
                <? endif ?>
                <th></th>
                <th <?= ($sort_by == 'nachname' && $sort_status == 'accepted') ?
                sprintf('class="sort%s"', $order) : '' ?>>
                    <? ($sort_status != 'accepted') ? $order = 'desc' : $order = $order ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=accepted&order=%s&toggle=%s',
                            $order, ($sort_by == 'nachname'))) ?>#users">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <th <?= ($sort_by == 'mkdate' && $sort_status == 'accepted') ? sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=mkdate&sort_status=accepted&order=%s&toggle=%s',
                       $order, ($sort_by == 'mkdate'))) ?>#accepted">
                        <?= _('Anmeldedatum') ?>
                    </a>
                </th>
                <th><?=_('Studiengang')?></th>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr= 0; foreach($accepted as $accept) : ?>
        <? $fullname = $accept['fullname'];?>
            <tr>
                <? if (!$is_locked) : ?>
                <td>
                    <input aria-label="<?= sprintf(_('%s auswählen'), 'Vorläufig akzeptierte/n NutzerIn') ?>"
                        type="checkbox" name="accepted[<?= $accept['user_id'] ?>]" value="1" />
                </td>
                <? endif ?>
                <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a style="position: relative" href="<?= $controller->url_for(sprintf('profile?username=%s',$accept['username'])) ?>">
                    <?= Avatar::getAvatar($accept['user_id'], $accept['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px','title' => htmlReady($fullname))); ?>
                    <?= $accept['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    </a>
                </td>
                <td>
                    <? if(!empty($accept['mkdate'])) : ?>
                        <?= strftime('%x %X', $accept['mkdate'])?>
                    <? endif ?>
                </td>
                <td>
                    <?= $this->render_partial("course/members/_studycourse.php", array('study_courses' => UserModel::getUserStudycourse($accept['user_id']))) ?>
                </td>
                <td style="text-align: right">
                    <? if($user_id != $accept['user_id']) : ?>
                        <a href="<?= URLHelper::getLink('dispatch.php/messages/write',
                                    array('filter' => 'send_sms_to_all',
                                    'rec_uname' => $accept['username'],
                                    'default_subject' => $subject))
                                ?>
                        "  data-dialog>
                            <?= Assets::img('icons/16/blue/mail.png',
                                    tooltip2(sprintf(_('Nachricht an %s senden'), htmlReady($fullname)))) ?>
                        </a>
                    <? endif?>
                    <? if (!$is_locked) : ?>
                    <a href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/accepted/%s',
                                $accept['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/door-leave.png',
                                tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                    </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
        <? if (!$is_locked) : ?>
        <tfoot>
            <tr>
                <td class="printhead" colspan="6">
                    <select name="action_accepted" id="action_accepted" aria-label="<?= _('Aktion ausführen') ?>">
                        <option value="">- <?= _('Aktion wählen') ?></option>
                        <option value="upgrade"><?= _('Akzeptieren') ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
                        <option value="message"><?=_('Nachricht senden')?></option>
                        <!--<option value="copy_to_course"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= Button::create(_('Ausführen'), 'submit_accepted') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>
