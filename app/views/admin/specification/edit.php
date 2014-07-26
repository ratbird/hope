<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if (isset($flash['error'])) : ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['info'])): ?>
    <?= MessageBox::info($flash['info']) ?>
<? endif ?>

<form action="<?= $controller->url_for('admin/specification/edit') ?><?= ($rule) ? '/' . $rule['lock_id'] : '' ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="nohover default">
    <caption>
        <? if($rule) : ?>
            <?= sprintf(_('Regel "%s" editieren'), htmlReady($rule['name'])) ?>
        <? else : ?>
            <?= _('Eine neue Regel definieren') ?>
        <? endif ?>
    </caption>
    <tbody>
       <tr>
            <td><?= _("Name der Regel:") ?> <span style="color: red; font-size: 1.6em">*</span></td>
            <td>
                <input type="text" name="rulename" value="<?= htmlReady(Request::get('rulename', $rule['name'])) ?>" style="width: 350px;" required="required">
            </td>
        </tr>
        <tr>
            <td><?= _("Beschreibung:") ?> </td>
            <td>
                <textarea cols="60" rows="5" name="description" style="width: 350px;"><?= htmlReady(Request::get('description', $rule['description'])) ?></textarea>
            </td>
        </tr>
    </tbody>
    </table>
    <table class="default">
    <thead>
        <tr>
            <th><?= _("Feld:") ?></th>
            <th><?= _("Sortierung:") ?></th>
            <th><?= _("aktivieren:") ?></th>
        </tr>
    </thead>
    <tbody>
        <? if (count($entries_semdata) > 0) : ?>
        <tr>
            <th colspan="3"><b><?= _("Zusatzinformationen") ?></b></th>
        </tr>
        <? foreach ($entries_semdata as $id => $entry) : ?>
          <?= $this->render_partial('admin/specification/_field', array_merge(compact('rule', 'id'), array('name' => $entry->getName()), array('required' => true))) ?>
        <? endforeach ?>
        <? endif ?>
        <? if (count($semFields) > 0) : ?>
    </tbody>
    <tbody>
        <tr>
            <th colspan="3"><b><?= _("Veranstaltungsinformationen") ?></b></th>
        </tr>
        <? foreach ($semFields as $id => $name) : ?>
          <?= $this->render_partial('admin/specification/_field', compact('rule', 'id', 'name')) ?>
        <? endforeach ?>
        <? endif ?>
        <? if(count($entries_user) > 0) : ?>
    </tbody>
    <tbody>
        <tr>
            <th colspan="3"><b><?= _("Personenbezogene Informationen") ?></b></th>
        </tr>
        <? foreach ($entries_user as $id => $entry) : ?>
          <?= $this->render_partial('admin/specification/_field', array_merge(compact('rule', 'id'), array('name' => $entry->getName()))) ?>
        <? endforeach ?>
        <? endif ?>
    </tbody>
    <tfoot>
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
    </tfoot>
    </table>
</form>

<?
$sidebar = Sidebar::Get();
$sidebar->setImage(Assets::image_path('sidebar/admin-sidebar.png'));
$sidebar->setTitle(_('Zusatzangaben'));
if ($GLOBALS['perm']->have_perm('root')) {
    $actions = new ActionsWidget();
    $actions->addLink(_('Datenfelder bearbeiten'), URLHelper::getLink('dispatch.php/admin/datafields'), 'icons/16/blue/add.png');
    $sidebar->addWidget($actions);
}
?>
