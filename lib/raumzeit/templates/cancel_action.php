<?
use Studip\Button, Studip\LinkButton;
?>
<div>
    <?= _('Wenn Sie die nicht stattfindenden Termine mit einem Kommentar versehen, werden die Ausfalltermine im Ablaufplan weiterhin dargestellt und auch im Terminkalender eingeblendet.') ?>
    <br>
    <label style="font-weight:bold" for="cancel_comment">
        <?=_("Kommentar:")?>
    </label>
    <br>
    <textarea style="width: 100%" rows="4" name="cancel_comment" id="cancel_comment"><?= htmlReady($tpl['comment'])?></textarea>
</div>

<div>
    <label style="font-weight:bold" for="cancel_send_message">
        <?=_("Benachrichtigung über ausfallende Termine an alle Teilnehmer verschicken:")?>
    </label>
    <input style="vertical-align:middle" type="checkbox" id="cancel_send_message" name="cancel_send_message" value="1">
</div>
    

