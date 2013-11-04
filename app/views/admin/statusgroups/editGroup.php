<form class="studip_form" action="<?= $controller->url_for('admin/statusgroups') ?>" method="POST">
    <input type="hidden" name="id" value="<?= $group->id ?>">
    <label class="caption"><?= _('Gruppenname') ?>
        <input name="name" class="groupname" type="text" size="50" placeholder="<?= _('Mitarbeiter(in)') ?>" value="<?= formatReady($group->name) ?>" >
    </label>
    <label class="caption"><?= _('Weiblicher Name') ?>
        <input name="name_w" type="text" size="50" placeholder="<?= _('Mitarbeiterin') ?>" value="<?= formatReady($group->name_w) ?>" >
    </label>
    <label class="caption"><?= _('Männlicher Name') ?>
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
    <label class="caption"><?= _('Gruppe löschen') ?>
        <input name="delete" type="checkbox" value="1" >
    </label>
    <?= Studip\Button::create(_('Speichern'), 'save') ?>
</form>