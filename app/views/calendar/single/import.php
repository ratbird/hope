<?
use Studip\Button, Studip\LinkButton;
SkipLinks::addIndex(_('Termine importieren'), 'main_content', 100);
?>
<form action="<?= $controller->url_for('calendar/single/import/'
        . $calendar->getRangeId(), array('atime' => $atime, 'last_view' => $last_view)) ?>" method="post" enctype="multipart/form-data">
    <table class="default" id="main_content">
        <caption>
            <?= sprintf(_('Termine importieren')) ?>
        </caption>
        <colgroup>
            <col width="25%">
            <col>
            <col width="60px">
        </colgroup>
        <tbody>
            <tr class="header-row">
                <th colspan="3" class="toggle-indicator">
                    <a class="toggler"><?= _('Einstellungen') ?></a>
                </th>
            </tr>
            <tr>
                <td>
                    <label for="event-type">
                        <?= _('Öffentliche Termine als "privat" importieren') ?>:
                    </label>
                </td>
                <td colspan="2">
                    <input type="checkbox" name="import_privat" value="1" checked>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <label>
                        <?= _('Klicken Sie auf "Durchsuchen", um eine Datei auszuwählen') ?>
                        <input type="file" name="importfile">
                    </label>
                </td>
            </tr>
    </table>
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studip_ticket" value="<?= get_ticket() ?>">
    <div style="text-align: center; clear: both" data-dialog-button>
        <?= Button::createAccept(_('Termine importieren'), 'import', array('title' => _('Termine importieren'))) ?>
        <? if (!Request::isXhr()) : ?>
        <?= LinkButton::create(_('Abbrechen'), $controller->url_for('calendar/single/' . $last_view)) ?>
        <? endif; ?>
    </div>
</form>
