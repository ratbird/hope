<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>
<SELECT name="checkboxActionCmd">
    <OPTION value="noSelection">-- <?=_("Aktion auswählen")?> --</OPTION>
    <OPTION value="chooseAll"><?=_("alle auswählen")?></OPTION>
    <OPTION value="chooseNone"><?=_("Auswahl aufheben")?></OPTION>
    <OPTION value="invert"><?=_("Auswahl umkehren")?></OPTION>
    <OPTION value="deleteChoosen"><?=_("ausgewählte löschen")?></OPTION>
    <OPTION value="deleteAll"><?=_("alle löschen")?></OPTION>
</SELECT>
<?= Button::createAccept(_('Ok'), 'checkboxAction') ?>
