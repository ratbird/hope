<? use Studip\Button, Studip\LinkButton ?>
<div id="edit_tour_step" class="edit_tour_step">
<? if (Request::isXhr()) : ?>
    <? foreach (PageLayout::getMessages() as $msg) : ?>
        <?=$msg?>
    <? endforeach ?>
<? endif ?>
<form id="edit_tour_form" class="studip_form" action="<?=URLHelper::getURL('dispatch.php/tour/edit_step/'.$tour_id.'/'.$step->step.'/save')?>" method="POST">
    <?=CSRFProtection::tokenTag(); ?>
    <fieldset>
        <input type="hidden" name="tour_step_nr" value="<?=$step->step?>">
        <input type="hidden" name="tour_step_editmode" value="<?=$mode?>">
        <legend><?= sprintf(_('Schritt %s'), $step->step) ?></legend>
        <label for="step_title" class="caption">
            <?= _('Titel:') ?>
        </label>
        <input type="text" size="60" maxlength="255" name="step_title"
            value="<?= $step ? htmlReady($step->title) : '' ?>"
            placeholder="<?= _('Bitte geben Sie einen Titel für den Schritt an') ?>"/>
        <label for="step_tip" class="caption">
            <?= _('Inhalt:') ?>
        </label>
        <textarea cols="60" rows="5" name="step_tip"
            placeholder="<?= _('Bitte geben Sie den Text für diesen Schritt ein') ?>"><?= $step ? htmlReady($step->tip) : '' ?></textarea>
        <? if ($force_route) : ?>
            <input type="hidden" name="step_route" value="<?= $force_route ?>">
            <input type="hidden" name="step_css" value="<?= $step->css_selector ?>">
        <? else : ?>
            <label for="step_route" class="caption">
                <?= _('Seite:') ?>
                <span class="required">*</span>
            </label>
            <input type="text" size="60" maxlength="255" name="step_route"
                value="<?= $step ? htmlReady($step->route) : '' ?>"
                placeholder="<?= _('Route für den Schritt (z.B. "dispatch.php/profile")') ?>"/>
            <label for="step_css" class="caption">
                <?= _('CSS-Selektor:') ?>
            </label>
            <input type="text" size="60" maxlength="255" name="step_css"
                value="<?= $step ? htmlReady($step->css_selector) : '' ?>"
                placeholder="<?= _('Selektor, an dem der Schritt angezeigt wird') ?>"/>
        <? endif ?>
        <label for="step_orientation" class="caption tour_step_orientation" style="<?=$step->css_selector ? 'display: block' : 'display: none' ?>">
            <?= _('Orientierung:') ?>
        </label>
        <div class="tour_step_orientation" style="<?=$step->css_selector ? 'display: block' : 'display: none' ?>">
            <table>
                <tr>
                    <td></td>
                    <td><input type="radio" name="step_orientation" value="TL" <?= ($step->orientation == 'TL') ? 'checked' : ''?>><?=_('oben (links)')?></td>
                    <td><input type="radio" name="step_orientation" value="T" <?= ($step->orientation == 'T') ? 'checked' : ''?>><?=_('oben')?></td>
                    <td><input type="radio" name="step_orientation" value="TR" <?= ($step->orientation == 'TR') ? 'checked' : ''?>><?=_('oben (rechts)')?></td>
                    <td></td>
                </tr>
                <tr>
                    <td><input type="radio" name="step_orientation" value="LT" <?= ($step->orientation == 'LT') ? 'checked' : ''?>><?=_('links (oben)')?></td>
                    <td colspan="3"></td>
                    <td><input type="radio" name="step_orientation" value="RT" <?= ($step->orientation == 'RT') ? 'checked' : ''?>><?=_('rechts (oben)')?></td>
                </tr>
                <tr>
                    <td><input type="radio" name="step_orientation" value="L" <?= ($step->orientation == 'L') ? 'checked' : ''?>><?=_('links')?></td>
                    <td colspan="3" style="text-align: center"><?=_('Selektiertes Element')?></td>
                    <td><input type="radio" name="step_orientation" value="R" <?= ($step->orientation == 'R') ? 'checked' : ''?>><?=_('rechts')?></td>
                </tr>
                <tr>
                    <td><input type="radio" name="step_orientation" value="RB" <?= ($step->orientation == 'RB') ? 'checked' : ''?>><?=_('links (unten)')?></td>
                    <td colspan="3"></td>
                    <td><input type="radio" name="step_orientation" value="LB" <?= ($step->orientation == 'LB') ? 'checked' : ''?>><?=_('rechts (unten)')?></td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="radio" name="step_orientation" value="BL" <?= ($step->orientation == 'BL') ? 'checked' : ''?>><?=_('unten (links)')?></td>
                    <td><input type="radio" name="step_orientation" value="B" <?= ($step->orientation == 'B') ? 'checked' : ''?>><?=_('unten')?></td>
                    <td><input type="radio" name="step_orientation" value="BR" <?= ($step->orientation == 'BR') ? 'checked' : ''?>><?=_('unten (rechts)')?></td>
                    <td></td>
                </tr>
            </table>
        </div>
        <br>
        <div class="submit_wrapper">
            <?= CSRFProtection::tokenTag() ?>
            <?= LinkButton::createAccept(_('Speichern'), '#', array('onclick' => "STUDIP.Tour.saveStep(".$tour_id.", ".$step->step."); return false;")) ?>
            <?= LinkButton::createCancel(_('Abbrechen'), '#', array('rel' => 'close')) ?>
        </div>
    </fieldset>
</form>
</div>
<script>
    jQuery('input[name=step_css]').live('change', function (event) {
        if (jQuery('input[name=step_css]').val())
            jQuery('.tour_step_orientation').show();
        else
            jQuery('.tour_step_orientation').hide();
    });
    if (STUDIP.Tour.started) {
        jQuery('#tour_controls').hide();
        jQuery('#tour_tip').hide();
        jQuery('#selector_overlay').hide();
        jQuery('#edit_tour_step').parent().dialog({
            beforeClose: function( event, ui ) {
                jQuery('#tour_controls').show();
                jQuery('#tour_tip').show();
                <? if ($step->css_selector) : ?>
                    jQuery('#selector_overlay').show();
                <? endif ?>
            }
        });
    }
</script>