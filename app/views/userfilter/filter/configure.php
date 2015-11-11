<?php
use Studip\Button, Studip\LinkButton;
?>
<div id="conditionfields">
    <?= $this->render_partial('userfilter/field/configure.php', array('is_first' => true)); ?>
</div>
<br/>
<a href="#" onclick="return STUDIP.UserFilter.addConditionField('conditionfields', '<?= $controller->url_for('userfilter/field/configure') ?>')">
    <?= Assets::img('icons/16/blue/add.png', array('alt' => _('Auswahlfeld hinzuf�gen'))) ?>
    <?php
        $text = _('Auswahlfeld hinzuf�gen');
        echo $text;
    ?>
</a>
<br/><br/>
<div class="submit_wrapper" data-dialog-button>
    <?= Button::createAccept(_('Speichern'), 'submit', array('onclick' => "STUDIP.UserFilter.addCondition('".$containerId."', '".$controller->url_for('userfilter/filter/add')."');")) ?>
    <?= Button::createCancel(_('Abbrechen')) ?>
</div>
