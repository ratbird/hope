<form class="studip_form" action="<?= $controller->url_for('admin/statusgroups/editGroup/' . $group->id) ?>#group-<?= $group->id ?>" method="post">
    <label class="caption">
        <?= _('Gruppenname') ?>
        <input required type="text" name="name" class="groupname" size="50"
                value="<?= htmlReady($group->name) ?>"
                placeholder="<?= _('Mitarbeiterinnen und Mitarbeiter') ?>">
    </label>
    <label class="caption">
        <?= _('Weibliche Bezeichnung') ?>
        <input type="text" name="name_w" size="50"
                value="<?= htmlReady($group->name_w) ?>"
               placeholder="<?= _('Mitarbeiterin') ?>">
    </label>
    <label class="caption">
        <?= _('Männliche Bezeichnung') ?>
        <input type="text" name="name_m" size="50"
               value="<?= htmlReady($group->name_m) ?>"
               placeholder="<?= _('Mitarbeiter') ?>">
    </label>
<? if ($type['needs_size']): ?>
    <label class="caption">
        <?= _('Größe') ?>
        <input name="size" type="text" size="10"
               value="<?= htmlReady($group->size) ?>"
               placeholder="<?= _('Unbegrenzt') ?>">
    </label>
<? endif; ?>
<? foreach ($group->getDatafields() as $field): ?>
    <label class="caption">
        <?= htmlReady($field->getName()) ?>
        <?= $field->getHTML('datafields') ?>
    </label>
<? endforeach; ?>
<? if ($type['needs_self_assign']): ?>
    <label class="caption">
        <?= _('Selbsteintrag') ?>
        <input name="selfassign" type="checkbox" value="1"
               <? if ($group->selfassign) echo 'checked'; ?>>
    </label>
<? endif; ?>
    <noscript>
        <label class="caption">
            <?= _('Position') ?>
            <input name="size" type="text" size="10"
                   value="<?= htmlReady($group->position) ?>"
                   placeholder="0">
        </label>
    </noscript>
    <label class="caption">
        <?= _('Einordnen unter') ?>
        <select name="range_id">
            <option value="<?= htmlReady($_SESSION['SessionSeminar']) ?>">
                - <?= _('Hauptebene') ?> -
            </option>
            <?= $this->render_partial("admin/statusgroups/_edit_subgroupselect.php", array('groups' => $groups, 'selected' => $group, 'level' => 0)) ?>
        </select>
    </label>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern'), 'save') ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/statusgroups')) ?>
    </div>
</form>
