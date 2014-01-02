<?php
use Studip\Button, Studip\LinkButton;
?>
<div id="conditionfields">
    <?= $this->render_partial('userfilter/field/configure.php'); ?>
</div>
<br/>
<a href="#" onclick="return STUDIP.UserFilter.addConditionField('conditionfields', '<?= $controller->url_for('userfilter/field/configure') ?>')">
    <?= Assets::img('icons/16/blue/add.png', array('alt' => _('Auswahlfeld hinzufügen'))) ?>
    <?php
        $text = _('Auswahlfeld hinzufügen');
        if ($via_ajax) {
            $text = studip_utf8encode($text);
        }
        echo $text;
    ?>
</a>
<br/><br/>
<div class="submit_wrapper">
    <?= Button::createAccept(_('Speichern'), 'submit', array('onclick' => "STUDIP.UserFilter.addCondition('".$containerId."', '".$controller->url_for('userfilter/filter/add', 'condamission_conditions')."');")) ?>
    <?= Button::createCancel(_('Abbrechen'), 'cancel', array('onclick' => '$("#condition").remove()')) ?>
</div>