<?= (isset($flash['error']))?MessageBox::error($flash['error'], $flash['error_detail']):'' ?>
<form action="<?= $controller->url_for('admin/studycourse/edit_degree/'.$edit['abschluss_id']) ?>" method=post>
    <table class="default">
        <tr class="<?=TextHelper::cycle('steelgraulight', 'steel1')?>">
            <td><?=_("Name des Studienabschlusses:")?> </td>
            <td><input type="text" name="degreename" size=57 maxlength=254 value="<?= htmlReady($edit['name'])?>"></td>
        </tr>
        <tr class="<?=TextHelper::cycle('steelgraulight', 'steel1')?>">
            <td><?=_("Beschreibung:")?> </td>
            <td><textarea cols=55 ROWS=4 name="description" value="<?= htmlReady($edit['beschreibung'])?>"><?= htmlReady($edit['beschreibung'])?></textarea></td>
        </tr>
        <tr class="steel2">
            <td></td>
            <td>
                <?= makeButton('uebernehmen2','input',_('Änderungen übernehmen'),'uebernehmen') ?>
                <a href="<?=$controller->url_for('admin/studycourse/degree')?>"><?= makebutton('abbrechen', 'img', _('Zurück zur Übersicht'))?></a>
            </td>
        </tr>
    </table>
</form>