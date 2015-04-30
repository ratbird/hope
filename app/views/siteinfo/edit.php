<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<form action="<?= $controller->url_for('siteinfo/save') ?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
<? if ($edit_rubric): ?>
    <label for="rubric_name"><?= _('Titel der Rubrik')?></label><br>
    <input type="text" name="rubric_name" id="rubric_name" value="<?= htmlReady($rubric_name) ?>"><br>
    <input type="hidden" name="rubric_id" value="<?= htmlReady($rubric_id) ?>">
<? else: ?>

    <label for="rubric_name"><?= _('Rubrik-Zuordnung')?></label><br>
    <select name="rubric_id">
  <? foreach ($rubrics as $option): ?>
        <option value="<?= htmlReady($option['rubric_id']) ?>" <? if ($currentrubric == $option['rubric_id']) echo 'selected'; ?>>
            <?= htmlReady(language_filter($option['name'])) ?>
        </option>
  <? endforeach; ?>
    </select><br>

    <label for="detail_name"><?= _('Seitentitel')?></label><br>
    <input style="width: 90%;" type="text" name="detail_name" id="detail_name" value="<?= htmlReady($detail_name) ?>"><br>

    <label for="content"><?= _('Seiteninhalt')?></label><br>
    <textarea style="width: 90%;height: 15em;" name="content" id="content" class="add_toolbar"><?= htmlReady($content) ?></textarea><br>

    <input type="hidden" name="detail_id" value="<?= $currentdetail?>">
<? endif; ?>

    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Abschicken')) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('siteinfo/show/'.$currentrubric.'/'.$currentdetail)) ?>
    </div>
</form>

 <? if (!$edit_rubric): ?>
    <?= $this->render_partial('siteinfo/help.php') ?>
<? endif; ?>
