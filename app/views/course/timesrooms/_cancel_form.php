<?php
$content = '';
if ($termin instanceof CourseExDate && isset($termin->content)) {
    $content = $termin->content;
}
?>
<p>
    <strong> <?= _('Wenn Sie die nicht stattfindenden Termine mit einem Kommentar versehen, werden die Ausfalltermine im Ablaufplan weiterhin dargestellt und auch im Terminkalender eingeblendet.') ?></strong>
</p>

<label>
    <?= _('Kommentar') ?>

    <textarea rows="5" name="cancel_comment"><?= htmlReady($content) ?></textarea>
</label>

<label for="cancel_send_message">
    <input type="checkbox" id="cancel_send_message" name="cancel_send_message" value="1"/>
    <?= _('Benachrichtigung über ausfallende Termine an alle Teilnehmer verschicken') ?>
</label>
