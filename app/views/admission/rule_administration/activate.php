<?php
use Studip\LinkButton;

if ($errormsg) {
    echo MessageBox::error($errormsg);
} else if ($successmsg) {
    echo MessageBox::success($successmsg);
}
$btnText = _('Schlie�en');
?>
<div class="submit_wrapper">
<?= LinkButton::create($btnText, $controller->url_for('admission/ruleadministration')) ?>
</div>