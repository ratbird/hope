<? use Studip\Button; ?>

<h3><?= _('Ich bin folgenden Nutzerdom�nen zugeordnet:') ?></h3>

<? if ($allow_change): ?>
<form action="<?= $controller->url_for('settings/userdomains/store') ?>" method="post">
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>
<? endif; ?>
<table class="deafault" id="assigned_userdomains">
    <colgroup>
        <col>
        <col width="100px">
    </colgroup>
    <thead>
        <tr>
            <th><?= _('Nutzerdom�ne') ?></th>
            <th>
            <? if ($allow_change): ?>
                <?= _('austragen') ?>
            <? else: ?>
                &nbsp;
            <? endif; ?>
            </th>
        </tr>
    </thead>
    <tbody>
    <? if (count($about->user_userdomains) === 0 && $allow_change): ?>
        <tr>
            <td colspan="2">
                <strong><?= _('Sie sind noch keiner Nutzerdom�ne zugeordnet.') ?></strong>
            </td>
        </tr>
    <? endif; ?>
    <? foreach ($about->user_userdomains as $domain): ?>
        <tr>
            <td><?= htmlReady($domain->getName()) ?></td>
            <td style="text-align:center">
            <? if ($allow_change): ?>
                <input type="checkbox" name="userdomain_delete[]" value="<?= $domain->getID() ?>">
            <? else: ?>
                <?= Assets::img('icons/16/grey/accept.png', array('class' => 'text-top')) ?>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" id="select_userdomains">
            <? if($allow_change): ?>
                <strong><?= _('W�hlen Sie eine Nutzerdom�ne aus der folgenden Liste aus:') ?></strong><br>
                <br>
                
                <a name="userdomains"></a>
                <?= $about->select_userdomain() ?><br>
                <br>
                
                <?= _('Wenn Sie Nutzerdom�nen wieder entfernen m�chten, markieren '
                     .'Sie die entsprechenden Felder in der linken Tabelle.') ?><br>
                <?= _('Mit einem Klick auf <b>&Uuml;bernehmen</b> werden die gew�hlten �nderungen durchgef�hrt.') ?><br>
                <br>
                
                <?= Button::create(_('�bernehmen'), 'store', array('title' => _('�nderungen �bernehmen'))) ?>
            <? else: ?>
                <?= _('Die Informationen zu Ihren Nutzerdom�nen werden vom System verwaltet und k&ouml;nnen daher von Ihnen nicht ge&auml;ndert werden.') ?>
            <? endif; ?>
            </td>
        </tr>
    </tfoot>
</table>
<? if ($allow_change): ?>
</form>
<? endif; ?>
