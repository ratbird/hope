<?php
use Studip\LinkButton;

if ($errormsg) {
    if ($via_ajax) {
        $errormsg = studip_utf8encode($errormsg);
    }
    echo MessageBox::error($errormsg);
} else if ($successmsg) {
    if ($via_ajax) {
        $successmsg = studip_utf8encode($successmsg);
    }
    echo MessageBox::success($successmsg);
}
$btnText = _('Schließen');
?>
<div class="submit_wrapper">
<?= LinkButton::create($via_ajax ? studip_utf8encode($btnText) : $btnText, $controller->url_for('admission/ruleadministration')) ?>
</div>