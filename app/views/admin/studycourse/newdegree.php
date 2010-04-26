<?= (isset($flash['error']))?MessageBox::error($flash['error'], $flash['error_detail']):'' ?>
<form action="<?= $controller->url_for('admin/studycourse/newdegree/'.Request::get('degreenname').'/'.Request::get('description')) ?>" method=post>
    <table class="default">
        <tr class="<?=TextHelper::cycle('steelgraulight', 'steel1')?>">
            <td><?=_("Name des Studienabschlusses:")?> </td>
            <td><input type="text" name="degreename" size=57 maxlength=254 value="<?= htmlReady($this->flash['request']['degreename'])?>"></td>
        </tr>
        <tr class="<?=TextHelper::cycle('steelgraulight', 'steel1')?>">
            <td><?=_("Beschreibung:")?> </td>
            <td><textarea cols=55 ROWS=4 name="description" value="<?= htmlReady($this->flash['request']['description'])?>"></textarea></td>
        </tr>
        <tr class="steel2">
            <td></td>
            <td><?= makeButton('anlegen','input',_('Abschluss anlegen'),'anlegen') ?></td>
        </tr>
    </table>
</form>