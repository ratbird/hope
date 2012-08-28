<? use Studip\Button, Studip\LinkButton; ?>
<div style="margin-bottom: 1em;">
    <label for="subject">
        <b><?= _('Betreff:') ?></b>
        <small>(<?= _('erforderliches Feld') ?>)</small>
    </label>
    <br>
    <input type="text" id="subject" name="messagesubject" style="width: 99%"
           value="<?= trim(htmlready($messagesubject)) ?>"
           <? if ($cmd == 'write_chatinv') echo 'disabled'; ?>>
    </label>
</div>

<div style="margin-bottom: 1em;">
    <label for="message">
        <b><?= _('Nachricht:') ?></b>
    </label>
    <br>
    <textarea id="message" name="message" class="add_toolbar"
              style="width: 99%; height: 10em;"><?= htmlReady($message) ?></textarea>
</div>

<div style="text-align: center">
    <div class="button-group">
    <? if ($show_submit): ?>
        <?= Button::createAccept(_('Abschicken'), 'cmd_insert') ?>
    <? endif; ?>

        <?= Button::create(_('Vorschau'), 'cmd') ?>
        <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL($return_to)) ?>
    </div>
    
    <br><br>
</div>
