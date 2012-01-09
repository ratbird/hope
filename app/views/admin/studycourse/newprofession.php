<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<?= (isset($flash['error'])) ? MessageBox::error($flash['error'], $flash['error_detail']) : '' ?>
<form action="<?= $controller->url_for('admin/studycourse/newprofession/'.Request::get('professionname').'/'.Request::get('description')) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="steelgraulight">
            <td><?= _("Name des Studienfaches:") ?> </td>
            <td><input type="text" name="professionname" size="60" maxlength="254" value="<?= htmlReady($this->flash['request']['professionname']) ?>"></td>
        </tr>
        <tr class="steel1">
            <td><?= _("Beschreibung:") ?> </td>
            <td><textarea cols="57" rows="5" name="description"><?= htmlReady($this->flash['request']['description']) ?></textarea></td>
        </tr>
        <tr class="steel2">
            <td></td>
            <td><?= Button::create(_('anlegen'),'anlegen', array('title' => _('Neues Fach anlegen'))) ?></td>
        </tr>
    </table>
</form>