<form class="studip_form" action="<?= $controller->url_for('admin/statusgroups') ?>#group-<?= $group->id ?>" method="POST">
    <input type="hidden" name="id" value="<?= $group->id ?>">
    <label class="caption"><?= _('Gruppenname') ?>
        <input name="name" required="true" class="groupname" type="text" size="50" placeholder="<?= _('Mitarbeiterinnen und Mitarbeiter') ?>" value="<?= formatReady($group->name) ?>" >
    </label>
    <label class="caption"><?= _('Weibliche Bezeichnung') ?>
        <input name="name_w" type="text" size="50" placeholder="<?= _('Mitarbeiterin') ?>" value="<?= formatReady($group->name_w) ?>" >
    </label>
    <label class="caption"><?= _('Männliche Bezeichnung') ?>
        <input name="name_m" type="text" size="50" placeholder="<?= _('Mitarbeiter') ?>" value="<?= formatReady($group->name_m) ?>" >
    </label>
    <? if ($type['needs_size']): ?>
        <label class="caption"><?= _('Größe') ?>
            <input name="size" type="text" size="10" placeholder="<?= _('Unbegrenzt') ?>" value="<?= formatReady($group->size) ?>" >
        </label>
    <? endif; ?>
    <? foreach ($group->getDatafields() as $field): ?>
        <label class="caption"><?= $field->getName() ?>
            <?= $field->getHTML('datafields') ?>
        </label>
    <? endforeach; ?>
    <? if ($type['needs_self_assign']): ?>
        <label class="caption"><?= _('Selbsteintrag') ?>
            <input name="selfassign" type="checkbox" value="1" <?= $group->selfassign ? "CHECKED" : "" ?>>
        </label>
    <? endif; ?>
    <noscript>
    <label class="caption"><?= _('Position') ?>
        <input name="size" type="text" size="10" placeholder="<?= _('0') ?>" value="<?= formatReady($group->position) ?>" >
    </label>
    </noscript>
    <label class="caption"><?= _('Einordnen unter') ?>
        <select name='range_id'>
            <option value='<?= $_SESSION['SessionSeminar'] ?>'>- <?= _('Hauptebene') ?> -</option>
            <?= $this->render_partial("admin/statusgroups/_edit_subgroupselect.php", array('groups' => $groups, 'selected' => $group)) ?>
        </select>
    </label>
    <?= Studip\Button::createAccept(_('Speichern'), 'save', array('data-dialog-button' => '')) ?>
    <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/statusgroups'), array('data-dialog-button' => '', 'data-dialog' => 'close')) ?>
</form>
