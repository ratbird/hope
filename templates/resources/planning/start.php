<? use Studip\Button, Studip\LinkButton; ?>

<form method="post" name="tools_requests_form" action="<?= URLHelper::getLink('?tools_requests_start=1') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="view" value="edit_request">

<table class="default zebra" style="margin: 0 1%; width: 98%;">
    <colgroup>
        <col width="4%">
        <col>
    </colgroup>
    <tbody>
        <tr>
            <td>&nbsp;</td>
            <td>

                <table border="0" cellpadding="2" cellspacing="2">
                    <tr>
                        <td>
                            <?= SemesterData::GetSemesterSelector(array(
                                    'name' => 'tools_requests_sem_choose',
                                    'onChange' => 'document.tools_requests_form.submit()'
                                ), $this->semester_id, 'semester_id',false) ?>
                            <?= Button::create(_('Semester auswählen'), 'tools_requests_sem_choose_button') ?>
                        </td>
                        <td style="padding-left:10px">
                            <b><?= _('Status:') ?></b><br>
                        <? if ($open_requests): ?>
                            <?= sprintf(_('Es liegen insgesamt <b>%s</b> nicht aufgel&ouml;ste Anfragen vor '
                                        . '- <br>davon <b>%s</b> von Veranstaltungen und <b>%s</b> auf '
                                        . 'Ressourcen, auf die Sie Zugriff haben.'),
                                        $open_requests, (int)$open_sem_requests, (int)$open_res_requests) ?>
                        <? else: ?>
                            <?= _('Es liegen im Augenblick keine unaufgel&ouml;sten Anfragen vor.') ?>
                        <? endif; ?>
                    <? if ($no_time): ?>
                            <br>
                        <? if (!$display_no_time): ?>
                            <?= sprintf(_('(<b>%s</b> weitere Anfragen haben keine Zeiten eingetragen, '
                                        . 'oder beziehen sich auf vergangene Termine.)'),
                                        $no_time) ?>
                        <? else: ?>
                            <?= sprintf(_('(<b>%s</b> der Anfragen haben keine Zeiten eingetragen, oder'
                                        . ' beziehen sich auf vergangene Termine.)'),
                                        $no_time) ?>
                        <? endif; ?>
                    <? endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <label>
                                <input onchange="document.tools_requests_form.submit()"
                                       name="resolve_requests_no_time"
                                       type="checkbox" value="1"
                                       <? if (!$display_no_time) echo 'checked'; ?>>
                                <?= _('Anfragen ohne eingetragene Zeiten oder auf vergangene Termine ausblenden') ?>
                            </label>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    <? if ($open_requests): ?>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Optionen beim Aufl&ouml;sen') ?></b><br>
                <?= _('Sie k&ouml;nnen die vorliegenden Anfragen mit folgenden Optionen aufl&ouml;sen:') ?><br>
                <br>

                <table border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td width="48%" valign="top">
                            <?= _('Art der Anfragen:') ?><br>
                            <br>

                            <label>
                                <input type="radio" name="resolve_requests_mode" value="all" checked>
                                <?= _('alle Anfragen') ?>
                            </label>
                            <br>

                            <label>
                                <input type="radio" name="resolve_requests_mode" value="sem">
                                <?= _('nur Anfragen von meinen Veranstaltungen') ?>
                            </label>
                            <br>

                            <label>
                                <input type="radio" name="resolve_requests_mode" value="res">
                                <?= _('nur Anfragen auf meine R&auml;ume') ?>
                            </label>
                            <br>

                            <label>
                                <input type="radio" name="resolve_requests_mode" value="one_res">
                                <?= _('nur Anfragen auf einen Raum:') ?>
                            </label>
                            <br>

                            <select name="resolve_requests_one_res" style="margin-left:20px;"
                                    onchange="$('input[name=resolve_requests_mode][value=one_res]').attr('checked', true);">
                                <option value=""><?= _(' -keine Auswahl - ') ?></option>
                            <? foreach ($my_rooms as $room): ?>
                                <option value="<?= $one['resource_id'] ?>">
                                    <?= htmlready($one['name']) ?>
                                <? if ($one['anzahl'] > 0): ?>
                                    (<?= $one['anzahl'] ?>)
                                <? endif; ?>
                                </option>
                            <? endforeach; ?>
                            </select>

                        </td>
                        <td width="4%">&nbsp;</td>
                        <td width="48%">
                            <?= _('Sortierung der Anfragen:') ?><br>
                            <br>

                            <label>
                                <input type="radio" name="resolve_requests_order" value="complex" checked>
                                <?= _('komplexere zuerst (Raumgr&ouml;&szlig;e und  gew&uuml;nschte Eigenschaften)') ?>
                            </label>
                            <br>

                            <label>
                                <input type="radio" name="resolve_requests_order" value="oldest">
                                <?= _('&auml;ltere zuerst') ?>
                            </label>
                            <br>

                            <label>
                                <input type="radio" name="resolve_requests_order" value="newest">
                                <?= _('neue zuerst') ?>
                            </label>
                            <br>

                            <label>
                                <input type="radio" name="resolve_requests_order" value="urgent">
                                <?= _('dringendere zuerst') ?>
                            </label>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    <? endif; ?>
    </tbody>
<? if (count($open_requests) > 0): ?>
    <tfoot>
        <tr class="table_footer">
            <td>&nbsp;</td>
            <td style="text-align: center">
                <?= Button::createAccept(_('Starten'), 'start_multiple_mode') ?>
            </td>
        </tr>
    </tfoot>
<? endif; ?>
</table>

</form>
<br><br>
