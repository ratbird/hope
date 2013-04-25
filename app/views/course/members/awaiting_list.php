<? use \Studip\Button; ?>
<br />
<a name="awaiting"></a>
<form action="<?= $controller->url_for(sprintf('course/members/edit_awaiting/%s/?cid=%s', $page, Request::get('cid'))) ?>"
      method="post" onsubmit="if ($('#action_awaiting').val() == 'remove')
          return confirm('<?= _('Wollen Sie die markierten NutzerInnen wirklich austragen?') ?>');">
    <table class="default collapsable zebra-hover">
        <colgroup>
            <col width="3%">
            <col width="3%">
            <col width="49%">
            <col width="5%">
            <col width="25%"
            <col width="15%">
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
                                'subject' => $subject))
                    ?>">
                        <?= Assets::img('icons/16/blue/inbox.png', tooltip2( _('Nachricht an alle NutzerInnen verschicken')))?>
                    </a>
                </th>
            </tr>
            <tr class="sortable">
                <th colspan="3>"<?= ($sort_by == 'nachname' && $sort_status == 'awaiting') ?
                    sprintf('class="sort%s"', $order) : '' ?>>
                    <input aria-label="<?= _('NutzerInnen auswählen') ?>"
                            type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=awaiting]">
                    <a href="<?= URLHelper::getLink(sprintf('?sortby=nachname&sort_status=awaiting&order=%s&toggle=%s',
                            $order, ($sort_by == 'nachname'))) ?>#awaiting">
                        <?=_('Nachname, Vorname')?>
                    </a>
                </th>
                <th style="text-align: center" <?= ($sort_by == 'position' && $sort_status == 'awaiting') ?
                    sprintf('class="sort%s"', $order) : '' ?>>
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
                <td><input aria-label="<?= _('Alle NutzerInnen auswählen') ?>" type="checkbox"
                            name="awaiting[<?= $user['user_id'] ?>]" value="1" /></td>
                <td style="text-align: right"><?= (++$nr < 10) ? sprintf('%02d', $nr) : $nr ?></td>
                <td>
                    <a href="<?= $controller->url_for(sprintf('profile?username=%s',$waiting['username'])) ?>">
                    <?= Avatar::getAvatar($waiting['user_id'], $waiting['username'])->getImageTag(Avatar::SMALL,
                            array('style' => 'margin-right: 5px', 'title' => htmlReady($fullname))); ?>
                    <?= $waiting['mkdate'] >= $last_visitdate ? Assets::img('red_star.png',
                        array('style' => 'position: relative; top: -5px; left: -15px; margin: 0px; right: 0px')) : '' ?>
                    <?= htmlReady($fullname) ?>
                    </a>
                </td>
                <td style="text-align: center"><?= $waiting['position'] ?></td>
                <td style="text-align: center">
                    <?= ($autor['admission_studiengang_id'] == 'all') ? _('alle Studiengänge') : '' ?>
                </td>
                <td style="text-align: right">
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
                    <? if ($rechte && $is_tutor) : ?>
                    <a onclick="return confirm('<?= sprintf(_('Wollen Sie  %s wirklich austragen?'),
                            htmlReady($fullname)) ?>');"
                        href="<?= $controller->url_for(sprintf('course/members/cancel_subscription/singleuser/awaiting/%s/%s',
                                $page, $waiting['user_id'])) ?>">
                        <?= Assets::img('icons/16/blue/remove/person.png',
                                tooltip2(sprintf(_('%s austragen'), htmlReady($fullname)))) ?>
                    </a>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
        <tfoot>
            <tr>
                <td class="printhead" colspan="6">
                    <select name="action_awaiting" id="action_awaiting" aria-label="<?= _('Aktion ausführen') ?>">
                        <option value="">- <?= _('Aktion wählen') ?></option>
                        <option value="upgrade"><?= _('Als NutzerInnen befördern') ?></option>
                        <option value="remove"><?= _('Austragen') ?></option>
    <!--                    <option value="copy_to_sem"><?= _('In Seminar verschieben/kopieren') ?></option>-->
                    </select>
                    <?= tooltipIcon( _('Mit dieser Einstellung beeinflussen Sie,
                            ob Teilnehmer die Sie hinzufügen auf die Kontingentplätze angerechnet werden.'))?>
                    <?= _("Kontingent berücksichtigen:"); ?>
                    <input type="checkbox" value="1" name="consider_contingent" checked="checked" />
                    <?= Button::create(_('Ausführen'), 'submit_awaiting') ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>