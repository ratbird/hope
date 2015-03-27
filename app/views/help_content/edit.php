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
        <? if ($help_content->route) : ?>
            <legend><?= sprintf(_('Seite %s'), $help_content->route) ?></legend>
            <input type="hidden" name="help_content_route" value="<?=$help_content->route?>">
        <? else : ?>
            <legend><?= _('Neuer Hilfe-Text') ?></legend>
            <label for="help_content_route" class="caption">
                <?= _('Seite:') ?>
            </label>
            <input type="text" size="60" maxlength="255" name="help_content_route"
                value=""
                placeholder="<?= _('Bitte geben Sie eine Route für den Hilfe-Text an') ?>"/>
        <? endif ?>
        <? if ($GLOBALS['perm']->have_perm('root')) : ?>
            <label for="help_content_language" class="caption">
                <?= _('Sprache des Textes:') ?>
                <span class="required">*</span>
            </label>
            <select name="help_content_language">
                <? foreach ($GLOBALS['INSTALLED_LANGUAGES'] as $key => $language) : ?>
                <option value="<?=substr($key, 0, 2)?>"<?=($help_content->language == substr($key, 0, 2)) ? ' selected' : ''?>><?=$language['name']?></option>
                <? endforeach ?>
            </select>
        <? endif ?>
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