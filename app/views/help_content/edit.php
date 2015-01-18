<? use Studip\Button, Studip\LinkButton ?>
<div id="edit_help_content" class="edit_help_content">
<? if (Request::isXhr()) : ?>
    <? foreach (PageLayout::getMessages() as $msg) : ?>
        <?=$msg?>
    <? endforeach ?>
<? endif ?>
<form id="edit_help_content_form" class="studip_form" action="<?=URLHelper::getURL('dispatch.php/help_content/edit/'.$help_content_id)?>" method="POST">
    <?=CSRFProtection::tokenTag(); ?>
    <fieldset>
        <input type="hidden" name="help_content_route" value="<?=$help_content->route?>">
        <legend><?= sprintf(_('Seite %s'), $help_content->route) ?></legend>
        <label for="help_content_content" class="caption">
            <?= _('Hilfe-Text:') ?>
        </label>
        <textarea cols="60" rows="5" name="help_content_content"
            placeholder="<?= _('Bitte geben Sie den Text ein') ?>"><?= $help_content->content ? htmlReady($help_content->content) : '' ?></textarea>
        <div "data-dialog-button" = "1">
            <?= CSRFProtection::tokenTag() ?>
            <? if ($via_ajax): ?>
                <?= Button::create(_('Speichern'), 'save_help_content', array('data-dialog' => '1', 'data-dialog-button' => '1')) ?>
            <? else: ?>
                <?= Button::createAccept(_('Speichern'), 'save_help_content') ?>
                <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('help_content/admin_overview'), array()) ?>
            <? endif; ?>
        </div>
    </fieldset>
</form>
</div>