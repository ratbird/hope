<?= (isset($flash['error']))?MessageBox::error($flash['error'], $flash['error_detail']):'' ?>
<? if (empty($via_ajax)): ?>
<h2><?=_("Bearbeiten von Konfigurationsparameter für den Nutzer: ")?><?=$search_user['fullname']?></h2>
<? endif; ?>
<form action="<?= $controller->url_for('admin/configuration/user_configuration/update') ?>" method=post>
    <table class="default">
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?=_("Name:")?>(<em>field</em>) </td>
            <td>
                <input type="hidden" name = "field" value = "<?= htmlReady($search_user['field'])?>">
                <input type="hidden" name = "user_id" value = "<?= htmlReady($user_id)?>">
                <?= htmlReady($search_user['field']) ?>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?=_("Inhalt:")?>(<em>value</em>) </td>
            <td><textarea cols="55" rows="4" name="value"><?= htmlReady($search_user['value'])?></textarea></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?=_("Beschreibung:")?>(<em>description</em>) </td>
            <td><?= htmlReady($search_user['description'])?></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td></td>
            <td>
                <?= makeButton('uebernehmen2','input',_('Änderungen übernehmen'),'uebernehmen') ?>
                <a class="cancel" href="<?=$controller->url_for('admin/configuration/configuration')?>"><?= makebutton('abbrechen', 'img', _('Zurück zur Übersicht'))?></a>
            </td>
        </tr>
    </table>
</form>