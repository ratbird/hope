<? use \Studip\Button; ?>
<br />
<a name="awaiting"></a>
<form action="<?= $controller->url_for('course/members/edit_awaiting/') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default collapsable ">
        <caption>
            <?= $waitingTitle ?>
            <span class="actions">
                <?=$controller->getEmailLinkByStatus($waiting_type, $awaiting)?>
                    <a href="<?= URLHelper::getLink('dispatch.php/messages/write',
                            array('filter' => 'waiting',
                                'course_id' => $course_id,
                                'default_subject' => $subject))?>" data-dialog>
                        <?= Assets::img('icons/16/blue/inbox.png', tooltip2( _('Nachricht an alle Wartenden versenden')))?>
                    </a>
            </span>
        </caption>
        <colgroup>
            <col width="20">
            <col width="20">
            <col>
            <col width="15%">
            <col width="80">
        </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="4">
                </th>
                <th class="table_header_bold" style="text-align: right">
                </th>
            </tr>
            <tr class="sortable">
                <? if (!$is_locked) : ?>
                <th><input aria-label="<?= _('NutzerInnen auswählen') ?>"
                            type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=awaiting]" />
                </th>
                <? endif ?>
                <th></th>
                <th <?= ($sort_by == 'nachname' && $sort_status == $waiting_type) ?
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf("?sortby=nachname&sort_status=$waiting_type&order=%s&toggle=%s",
                            $order, ($sort_by == 'nachname'))) ?>#awaiting">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <? if ($waiting_type === 'awaiting') : ?>
                    <th style="text-align: center" <?= ($sort_by == 'position' && $sort_status == $waiting_type) ?
                        sprintf('class="sort%s"', $order) : '' ?>>
                        <? ($sort_status != $waiting_type) ? $order = 'desc' : $order = $order ?>
                        <a href="<?= URLHelper::getLink(sprintf('?sortby=position&sort_status=awaiting&order=%s&toggle=%s',
                                $order, ($sort_by == 'position'))) ?>#awaiting">
                            <?= _('Position') ?>
                        </a>
                    </th>
                <? else : ?>
                    <th style="text-align: center" <?= ($sort_by == 'mkdate' && $sort_status == $waiting_type) ?
                        sprintf('class="sort%s"', $order) : '' ?>>
                        <? ($sort_status != $waiting_type) ? $order = 'desc' : $order = $order ?>
                        <a href="<?= URLHelper::getLink(sprintf('?sortby=mkdate&sort_status=claiming&order=%s&toggle=%s',
                                $order, ($sort_by == 'mkdate'))) ?>#awaiting">
                            <?= _('Priorität') ?>
                        </a>
                    </th>
                <? endif ?>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr = 0 ?>
        <? foreach($awaiting as $waiting) : ?>
        <? $fullname = $waiting['fullname'] ;?>
            <tr>
                <td>
                <? if (!$is_locked) : ?>
                    <input aria-label="<?= _('Alle NutzerInnen auswählen') ?>" type="checkbox"
                            name="awaiting[<?= $waiting['user_id'] ?>]" value="1" />
                <? endif ?>
                </td>
                <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a style="position: relative" href="<?= $controller->url_for(sprintf('profile?username=%s',$waiting['username'])) ?>">
                    <?= Avatar::getAvatar($waiting['user_id'], $waiting['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px', 'title' => htmlReady($fullname))); ?>
                    <?= $waiting['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: absolute; margin: 0px 0px 0px -15px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    </a>
                </td>
                <td style="text-align: center">
                    <?= $waiting['position'] ?>
                </td>
                <td style="text-align: right">
                    <? if($user_id != $waiting['user_id']) : ?>
                        <a href="<?= URLHelper::getLink('dispatch.php/messages/write',
                                    array('filter' => 'send_sms_to_all',
                                    'rec_uname' => $waiting['username'],
                                    'default_subject' => $subject))
                                ?>
                        " data-dialog>
                            <?= Assets::img('icons/16/blue/mail.png',
                                    tooltip2(sprintf(_('Nachricht an %s senden'), htmlReady($fullname)))) ?>
                        </a>
                    <? endif?>
                    <? if (!$is_locked) : ?>
                    <a href="<?= $controller->url_for(sprintf("course/members/cancel_subscription/singleuser/$waiting_type/%s",
                                $waiting['user_id'])) ?>">
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
                <td colspan="5">
                    <select name="action_awaiting" id="action_awaiting" aria-label="<?= _('Aktion ausführen') ?>">
                        <option value="">- <?= _('Aktion wählen') ?></option>
                        <option value="upgrade"><?= _('Zu NutzerInnen hochstufen') ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
                        <option value="message"><?=_('Nachricht senden')?></option>
    <!--                    <option value="copy_to_sem"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <input type="hidden" value="<?=$waiting_type?>" name="waiting_type"/>
                    <?= Button::create(_('Ausführen'), 'submit_awaiting') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>