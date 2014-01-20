<form name="select_rule_type" class="studip_form" action="<?= $controller->url_for('admission/rule/configure') ?>" method="post">
<?php
use Studip\Button, Studip\LinkButton;

foreach ($ruleTypes as $className => $classDetail) {
?>
    <div id="<?= $className ?>">
        <label>
        	<input type="radio" name="ruletype" value="<?= $className ?>"/><?= $via_ajax ? studip_utf8encode($classDetail['name']) : $classDetail['name'] ?>
	        <?= Assets::img('icons/16/blue/question-circle.png', 
	                tooltip2($via_ajax ? studip_utf8encode($classDetail['description']) : $classDetail['description'], true, true)) ?>
        </label>
    </div>
    <br/>
<?php
}
?>
    <div class="submit_wrapper">
        <?= CSRFProtection::tokenTag() ?>
        <?= Button::create(_('Weiter >>'), 'configure', array(
            'onclick' => "return STUDIP.Admission.configureRule($('input[name=ruletype]:checked').val(), '".
                $controller->url_for('admission/rule/configure')."')")) ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admission/courseset/configure'), array('onclick' => "return STUDIP.Admission.closeDialog('configurerule')")) ?>
    </div>
</form>