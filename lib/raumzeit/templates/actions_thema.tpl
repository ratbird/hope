<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>
<SELECT name="checkboxActionCmd">
    <OPTION value="noSelection">-- <?=_("Aktion ausw�hlen")?> --</OPTION>
    <OPTION value="chooseAll"><?=_("alle ausw�hlen")?></OPTION>
    <OPTION value="chooseNone"><?=_("Auswahl aufheben")?></OPTION>
    <OPTION value="invert"><?=_("Auswahl umkehren")?></OPTION>
    <OPTION value="deleteChoosen"><?=_("ausgew�hlte l�schen")?></OPTION>
    <OPTION value="deleteAll"><?=_("alle l�schen")?></OPTION>
</SELECT>
<?= Button::createAccept(_('Ok'), 'checkboxAction') ?>
