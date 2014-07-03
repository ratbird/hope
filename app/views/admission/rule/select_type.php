<form name="select_rule_type" class="studip_form" action="<?= $controller->url_for('admission/rule/configure') ?>" method="post">
<?php
use Studip\Button, Studip\LinkButton;

foreach ($ruleTypes as $className => $classDetail) {
    $disabled = $courseset && !$courseset->isAdmissionRuleAllowed($className) ? 'disabled' : '';
?>
    <div id="<?= $className ?>">
        <label>
            <input <?=$disabled ?> type="radio" name="ruletype" value="<?= $className ?>"/>
            <span <?=($disabled ? 'style="text-decoration:line-through"' : '')?>><?=$classDetail['name'] ?></span>
            <?= Assets::img('icons/16/blue/question-circle.png', 
                    tooltip2($classDetail['description'], true, true)) ?>
        </label>
    </div>
    <br/>
<?php
}
?>
    <div class="submit_wrapper">
        <?= CSRFProtection::tokenTag() ?>
        <?= Button::create(_('Weiter >>'), 'configure', array(
            'onclick' => "return $('input[name=ruletype]:checked').val() ? STUDIP.Admission.configureRule($('input[name=ruletype]:checked').val(), '".
                $controller->url_for('admission/rule/configure')."') : false")) ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admission/courseset/configure'), array('onclick' => "STUDIP.Admission.closeDialog('configurerule'); return false;")) ?>
    </div>
</form>