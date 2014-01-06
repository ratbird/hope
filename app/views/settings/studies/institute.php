<?
    use Studip\Button;

    $institutes = array_filter($about->user_inst, function ($item) {
        return $item['inst_perms'] === 'user';
    });
?>

<h3 style="text-align: center;"><?= _('Meine Einrichtungen:') ?></h3>

<? if ($allow_change['in']): ?>
<form action="<?= $controller->url_for('settings/studies/store_in') ?>" method="post">
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>
<? endif; ?>
<table class="zebra-hover settings" id="select_institute">
    <colgroup>
        <col>
        <col width="100px">
    </colgroup>
    <thead>
        <tr>
            <th><?= _('Einrichtung') ?></th>
            <th>
            <? if ($allow_change['in']): ?>
                <?= _('austragen') ?>
            <? else: ?>
                &nbsp;
            <? endif; ?>
        </th>
    </thead>
    <tbody>
    <? if (count($institutes) === 0 && $allow_change['in']): ?>
        <tr>
            <td colspan="2" style="background: inherit;">
                <strong><?= _('Sie haben sich noch keinen Einrichtungen zugeordnet.') ?></strong><br>
                <br>
                <?= _('Wenn Sie auf Ihrem Profil Ihre Einrichtungen '
                     .'auflisten wollen, k&ouml;nnen Sie diese Einrichtungen hier eintragen.') ?>
            </td>
        </tr>
    <? endif; ?>
    <? foreach ($institutes as $inst_id => $details): ?>
        <tr>
            <td><label for="inst_delete_<?= $inst_id?>"><?= htmlReady($details['Name']) ?></label></td>
            <td style="text-align:center">
            <? if ($allow_change['in']): ?>
                <input type="checkbox" name="inst_delete[]" id="inst_delete_<?= $inst_id?>" value="<?= $inst_id ?>">
            <? else: ?>
                <?= Assets::img('icons/16/grey/accept.png', array('class' => 'text-top')) ?>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">
            <? if ($allow_change['in']): ?>
                <label for="select_new_inst">
                    <?= _('Um sich einer Einrichtung zuzuordnen, w�hlen '
                         .'Sie die entsprechende Einrichtung aus der folgenden Liste aus:') ?>
                </label>
                <br>
                <br>

                <a name="einrichtungen"></a>
                <?= $about->select_inst() ?>
                <br>
                <br>

                <?= _('Wenn Sie aus Einrichtungen wieder ausgetragen werden m�chten, '
                     .'markieren Sie die entsprechenden Felder in der linken Tabelle.') ?><br>
                <?= _('Mit einem Klick auf <b>&Uuml;bernehmen</b> werden die gew�hlten �nderungen durchgef�hrt.') ?><br>
                <br>

                <?= Button::create(_('�bernehmen'), 'store_in', array('title' => _('�nderungen �bernehmen'))) ?>
            <? else: ?>
                <?= _('Die Informationen zu Ihrer Einrichtung werden vom System verwaltet, '
                     .'und k&ouml;nnen daher von Ihnen nicht ge&auml;ndert werden.') ?>
            <? endif; ?>
            </td>
        </tr>
    </tfoot>
</table>
<? if ($allow_change['in']): ?>
</form>
<? endif; ?>
