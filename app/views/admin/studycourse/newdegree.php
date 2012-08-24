<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<?= (isset($flash['error']))?MessageBox::error($flash['error'], $flash['error_detail']):'' ?>
<form action="<?= $controller->url_for('admin/studycourse/newdegree/'.Request::get('degreenname').'/'.Request::get('description')) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="table_row_odd">
            <td><?= _("Name des Studienabschlusses:") ?> </td>
            <td><input type="text" name="degreename" size="60" maxlength="254" value="<?= htmlReady($this->flash['request']['degreename']) ?>"></td>
        </tr>
        <tr class="table_row_even">
            <td><?= _("Beschreibung:") ?> </td>
            <td><textarea cols="57" rows="5" name="description" value="<?= htmlReady($this->flash['request']['description']) ?>"></textarea></td>
        </tr>
        <tr class="table_footer">
            <td></td>
            <td><?= Button::create(_('Anlegen'),'anlegen', array('title' => _('Abschluss anlegen'))) ?></td>
        </tr>
    </table>
</form>