<? use \Studip\Button; ?>
<br />
<a name="awaiting"></a>
<form action="<?= $controller->url_for('course/members/edit_awaiting/') ?>" method="post">
    <table class="default collapsable zebra-hover">
        <colgroup>
            <col width="20">
            <col width="20">
            <col>
            <col width="5%">
            <col width="25%">
            <col width="80">
        </colgroup>
        <thead>
            <tr>
                <th class="table_header_bold" colspan="5">
                    <?= $waitingTitle ?>
                </th>
                <th class="table_header_bold" style="text-align: right">
                    <?=$controller->getEmailLinkByStatus('awaiting')?>
                    <a href="<?= URLHelper::getLink('sms_send.php',
                            array('sms_source_page' => 'dispatch.php/course/members?cid=' . $course_id,
                                'course_id' => $course_id,
                                'subject' => $subject))?>">
                        <?= Assets::img('icons/16/white/inbox.png', tooltip2( _('Nachricht an alle NutzerInnen verschicken')))?>
                    </a>
                </th>
            </tr>
            <tr class="sortable">
                <? if (!$is_locked) : ?>
                <th><input aria-label="<?= _('NutzerInnen ausw�hlen') ?>"
                            type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=awaiting]" />
                </th>
                <? endif ?>
                <th></th>
                <th <?= ($sort_by == 'nachname' && $sort_status == 'awaiting') ?
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=awaiting&order=%s&toggle=%s',
                            $order, ($sort_by == 'nachname'))) ?>#awaiting">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <th style="text-align: center" <?= ($sort_by == 'position' && $sort_status == 'awaiting') ?
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <? ($sort_status != 'awaiting') ? $order = 'desc' : $order = $order ?>
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=position&sort_status=awaiting&order=%s&toggle=%s',
                            $order, ($sort_by == 'position'))) ?>#awaiting">
                        <?= _('Position') ?>
                    </a>
                </th>
                <th style="text-align: center"><?= _('Kontingent') ?></th>
                <th style="text-align: right"><?= _('Aktion') ?></th>
            </tr>
        </thead>
        <tbody>
        <? $nr = 0 ?>
        <? foreach($awaiting as $waiting) : ?>
        <? $fullname = $waiting->user->getFullName('full_rev');?>
            <tr>
                <td>
                <? if (!$is_locked) : ?>
                    <input aria-label="<?= _('Alle NutzerInnen ausw�hlen') ?>" type="checkbox"
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
                <td style="text-align: center"><?= $waiting['position'] ?></td>
                <td style="text-align: center">
                    <?= ($waiting['studiengang_id'] == 'all') ? _('alle Studieng�nge') : '' ?>
                </td>
                <td style="text-align: right">
                    <? if($user_id != $waiting['user_id']) : ?>
                        <a href="<?= URLHelper::getLink('sms_send.php',
                                    array('filter' => 'send_sms_to_all',
                                    'rec_uname' => $waiting['username'],
                                    'sms_source_page' => sprintf('dispatch.php/course/members?cid=%s', $course_id),
                                    'subject' => $subject))
                                ?>
                        ">
                            <?= Assets::img('icons/16/blue/mail.png',
                                    tooltip2(sprintf(_('Nachricht an %s verschicken'), htmlReady($fullname)))) ?>
                        </a>
                    <? endif?>
                    <? if (!$is_locked) : ?>
                    <a href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/awaiting/%s',
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
                <td class="printhead" colspan="6">
                    <select name="action_awaiting" id="action_awaiting" aria-label="<?= _('Aktion ausf�hren') ?>">
                        <option value="">- <?= _('Aktion w�hlen') ?></option>
                        <option value="upgrade"><?= _('Als NutzerInnen bef�rdern') ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
    <!--                    <option value="copy_to_sem"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= tooltipIcon( _('Mit dieser Einstellung beeinflussen Sie,
                            ob Teilnehmer die Sie hinzuf�gen auf die Kontingentpl�tze angerechnet werden.'))?>
                    <?= _("Kontingent ber�cksichtigen:"); ?>
                    <input type="checkbox" value="1" name="consider_contingent" checked="checked" />
                    <?= Button::create(_('Ausf�hren'), 'submit_awaiting') ?>
                </td>
            </tr>
        </tfoot>
        <? endif ?>
    </table>
</form>