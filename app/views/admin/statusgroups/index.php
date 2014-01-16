<?= $question ?>
<style>
    .tree-seperator {
        list-style-type:none;
    }
</style>

<? foreach ($path as $name => $p): ?>
    <input type="hidden" id="<?= $name ?>" value="<?= $p ?>" />
<? endforeach; ?>
<? if (!$groups): ?>
    <?= _('Es wurden noch keine Gruppen angelegt') ?>
<? endif; ?>
<? foreach ($groups as $group): ?>
    <?= $this->render_partial('admin/statusgroups/_group.php', array('group' => $group)) ?>
<? endforeach; ?>