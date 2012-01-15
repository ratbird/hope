<?
use Studip\Button, Studip\LinkButton;
?>
<form action="<?= URLHelper::getLink('?cmd=upload') ?>" method="post" enctype="multipart/form-data">
    <?= CSRFProtection::tokenTag() ?>

    <table align="center" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th colspan="2"><b><?= _('Neues Smiley hochladen') ?></b></th>
            </tr>
        </thead>
        <tbody>
            <tr class="steelgraulight">
                <td>
                    <label for="replace"><?= _('existierende Datei überschreiben') ?></label>
                </td>
                <td>
                    <input type="checkbox" id="replace" name="replace" value="1">
                </td>
            </tr>
            <tr class="steel1">
                <td>
                    <label for="file"><?= _('1. Bilddatei auswählen:') ?></label>
                </td>
                <td>
                    <input type="file" id="file" name="imgfile" cols="45">
                </td>
            </tr>
            <tr class="steelgraulight">
                <td><?= _('2. Bilddatei hochladen:') ?></td>
                <td><?= Button::create('absenden') ?></td>
            </tr>
        </tbody>
    </table>

    <br>
</form>
