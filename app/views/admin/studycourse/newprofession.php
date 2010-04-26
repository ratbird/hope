<?= (isset($flash['error'])) ? MessageBox::error($flash['error'], $flash['error_detail']) : '' ?>
<form action="<?= $controller->url_for('admin/studycourse/newprofession/'.Request::get('professionname').'/'.Request::get('description')) ?>" method=post>
    <table class="default">
        <tr class="<?=TextHelper::cycle('steelgraulight', 'steel1')?>">
            <td><?=_("Name des Studienfaches:")?> </td>
            <td><input type="text" name="professionname" size=57 maxlength=254 value="<?= htmlReady($this->flash['request']['professionname'])?>"></td>
        </tr>
        <tr class="<?=TextHelper::cycle('steelgraulight', 'steel1')?>">
            <td><?=_("Beschreibung:")?> </td>
            <td><textarea cols=55 ROWS=4 name="description" value="<?= htmlReady($this->flash['request']['description'])?>"></textarea></td>
        </tr>
        <tr class="steel2">
            <td></td>
            <td><?= makeButton('anlegen','input',_('Neues Fach anlegen'),'anlegen') ?></td>
        </tr>
    </table>
</form>