<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if (isset($flash['error'])) : ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['info'])): ?>
    <?= MessageBox::info($flash['info']) ?>
<? endif ?>
<h3>
<? if($rule) : ?>
    <?= sprintf(_('Regel "%s" editieren'), htmlReady($rule['name'])) ?>
<? else : ?>
    <?= _('Eine neue Regel definieren') ?>
<? endif ?>
</h3>
<form action="<?= $controller->url_for('admin/specification/edit') ?><?= ($rule) ? '/' . $rule['lock_id'] : '' ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="table_row_odd">
            <td><?= _("Name der Regel:") ?> <span style="color: red; font-size: 1.6em">*</span></td>
            <td colspan="2">
                <input type="text" name="rulename" value="<?= htmlReady(Request::get('rulename', $rule['name'])) ?>" style="width: 350px;" required="required">
            </td>
        </tr>
        <tr class="table_row_even">
            <td><?= _("Beschreibung:") ?> </td>
            <td colspan="2">
                <textarea cols="60" rows="5" name="description"" style="width: 350px;"><?= htmlReady(Request::get('description', $rule['description'])) ?></textarea>
            </td>
        </tr>
        <tr>
            <th><?= _("Feld:") ?></th>
            <th><?= _("Sortierung:") ?></th>
            <th><?= _("aktivieren:") ?></th>
        </tr>
        <? if (count($entries_semdata) > 0) : ?>
        <tr class="table_header">
            <td colspan="3"><b><?= _("Zusatzinformationen") ?></b></td>
        </tr>
        <? foreach ($entries_semdata as $id => $entry) : ?>
          <?= $this->render_partial('admin/specification/_field', array_merge(compact('rule', 'id'), array('name' => $entry->getName()), array('required' => true))) ?>
        <? endforeach ?>
        <? endif ?>
        <? if (count($semFields) > 0) : ?>
        <tr class="table_header">
            <td colspan="3"><b><?= _("Veranstaltungsinformationen") ?></b></td>
        </tr>
        <? foreach ($semFields as $id => $name) : ?>
          <?= $this->render_partial('admin/specification/_field', compact('rule', 'id', 'name')) ?>
        <? endforeach ?>
        <? endif ?>
        <? if(count($entries_user) > 0) : ?>
        <tr class="table_header">
            <td colspan="3"><b><?= _("Personenbezogene Informationen") ?></b></td>
        </tr>
        <? foreach ($entries_user as $id => $entry) : ?>
          <?= $this->render_partial('admin/specification/_field', array_merge(compact('rule', 'id'), array('name' => $entry->getName()))) ?>
        <? endforeach ?>
        <? endif ?>
        <tr>
            <td colspan="3" align="center">
            <? if($rule) : ?>
                <?= Button::createAccept(_('Übernehmen'), 'uebernehmen', array('title' => _('Änderungen übernehmen')))?>
            <? else : ?>
                <?= Button::create(_('Erstellen'),'erstellen', array('title' => _('Neue Regel erstellen'))) ?>
            <? endif ?>
                <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/specification'), array('title' => _('Zurück zur Übersicht')))?>
            </td>
        </tr>
    </table>
</form>

<? //infobox
$infobox = array(
    'picture' => 'infobox/modules.jpg',
    'content' => array(
        array(
            'kategorie' => _("Hinweis"),
            'eintrag' => array(
                array(
                    "icon" => "icons/16/black/info.png",
                    "text" => sprintf(_('Es können nur neue Regeln für Zusatzangaben erstellt werden, '
                               .'wenn mindestens ein Eintrag im Bereich %sDatenfelder%s in der Kategorie '
                               .'<i>Datenfelder für Nutzer-Zusatzangaben in Veranstaltungen</i> erstellt wurde.'),
                               '<a href="' . URLHelper::getLink('dispatch.php/admin/datafields') . '">', '</a>')
                ),
                array(
                    "icon" => "icons/16/black/info.png",
                    "text" => _('Mit roten Sternchen markierte Felder sind Pflichtfelder.')
                )
            )
        )
    )
);
?>
