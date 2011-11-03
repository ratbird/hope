<?
# Lifter010: TODO
?>
<?
    $help_url = format_help_url("Basis.VeranstaltungenVerwaltenGruppen");
?>
<br>
<p class="info">
    <?= $this->render_partial('statusgruppen/sem_edit_role.php', array('no_breaks' => true)) ?>
</p>

<p class="info">
    <?= _("Es sind noch keine Gruppen oder Funktionen angelegt worden.") ?><br>
    <?= _("Um für diesen Bereich Gruppen oder Funktionen anzulegen, nutzen Sie bitte die obere Zeile!") ?><br>
    <br>
    <?= _("Mit dem Feld 'Gruppengröße' haben Sie die Möglichkeit, die Sollstärke für eine Gruppe festzulegen. Dieser Wert wird nur für die Anzeige benutzt - es können auch mehr Personen eingetragen werden.") ?><br>
    <?= _("Wenn Sie Gruppen angelegt haben, können Sie diesen Personen zuordnen. Jeder Gruppe können beliebig viele Personen zugeordnet werden. Jede Person kann beliebig vielen Gruppen zugeordnet werden.") ?><br>
    <br>
    <?= sprintf(_("Lesen Sie weitere Bedienungshinweise in der %sHilfe%s nach!"), '<a href="' . $help_url . '" target="_blank">', '</a>') ?>
</p>
