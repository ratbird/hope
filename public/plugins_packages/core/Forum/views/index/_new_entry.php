<?/*  $this->flash['new_entry_title'] */ ?>
<div id="new_entry_box" <?= $this->flash['edit_entry'] ? '' : 'style="display: none;"' ?>>
    <a name="create"></a>
    <form action="<?= PluginEngine::getLink('coreforum/index/add_entry') ?>" method="post" id="forum_new_entry" onSubmit="$(window).off('beforeunload')">
        <div class="posting bg2">
            <div class="postbody" <?= $constraint['depth'] == 0 ? 'style="width: 97%"' : '' ?>>
            <? if ($constraint['depth'] == 1) : ?>
                <span class="title"><?= _('Neues Thema erstellen') ?></span>
                <p class="content" style="margin-bottom: 0pt">
                    <? if ($GLOBALS['user']->id == 'nobody') : ?>
                    <input type="text" name="author" style="width: 99%" placeholder="<?= _('Ihr Name') ?>" required tabindex="1"><br>
                    <br>
                    <? endif ?>
                    <input type="text" name="name" style="width: 99%" value="<?= $this->flash['new_entry_title'] ?>"
                        <?= $constraint['depth'] == 1 ? 'required' : '' ?> placeholder="<?= _('Titel') ?>" tabindex="2">
                    <br>
                    <br>
                </p>
            <? elseif ($GLOBALS['user']->id == 'nobody') : ?>
                <p class="content" style="margin-bottom: 0pt">
                    <input type="text" name="author" style="width: 99%" placeholder="<?= _('Ihr Name') ?>" required tabindex="1"><br>
                    <br>
                </p>
            <? endif; ?>
            </div>

            <div class="postbody">
                <textarea class="add_toolbar wysiwyg" data-textarea="new_entry" name="content" required tabindex="3"
                    placeholder="<?= _('Schreiben Sie hier Ihren Beitrag. Hilfe zu Formatierungen'
                        . ' finden Sie rechts neben diesem Textfeld.') ?>"><?= $this->flash['new_entry_content'] ?></textarea>
            </div>

            <dl class="postprofile">
                <dt>
                    <?= $this->render_partial('index/_smiley_favorites', array('textarea_id' => 'new_entry')) ?>
                </dt>
            </dl>

            <div class="buttons">
                <div class="button-group">
                    <?= Studip\Button::createAccept(_('Beitrag erstellen'), array('tabindex' => '3')) ?>

                    <?= Studip\LinkButton::createCancel(_('Abbrechen'), '', array(
                        'onClick' => "return STUDIP.Forum.cancelNewEntry();",
                        'tabindex' => '4')) ?>
                    
                    <? if ($previewActivated) : ?>
                        <?= Studip\LinkButton::create(_('Vorschau'), "javascript:STUDIP.Forum.preview('new_entry', 'new_entry_preview');", array('tabindex' => '5', 'class' => 'js')) ?>
                    <? endif; ?>
                    <? if (Config::get()->FORUM_ANONYMOUS_POSTINGS): ?>
                        <div style="float: left; margin-top: 14px; margin-left: 14px;">    
                            <label><?= _('Anonym') ?>
                                <input type="checkbox" name="anonymous" value="1">
                            </label>
                        </div>
                    <? endif; ?>
                </div>
            </div>
        </div>
        
        <? if ($previewActivated) : ?> 
            <?= $this->render_partial('index/_preview', array('preview_id' => 'new_entry_preview')) ?>
        <? endif; ?>
        
        <input type="hidden" name="parent" value="<?= $topic_id ?>">
        <input type="text" name="nixda" style="display: none;">
    </form>
    <br>
</div>
