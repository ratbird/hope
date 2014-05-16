<?php
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

$base_url = "studygroup/browse/1/";
$link = "dispatch.php/studygroup/browse/%s/".$sort;

?>
<form action="<?= $controller->url_for('studygroup/browse') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <div class="search_box" align="center">
        <input name="searchtext" aria-label="<?= _("Geben Sie einen Suchbegriff für Studiengruppen ein.") ?>" type="text" size="45" style="vertical-align: middle;" value="<?= htmlReady($search) ?>" />
        <?= Button::create(_('Suchen'))?>
        <?= LinkButton::create(_('Zurücksetzen'), URLHelper::getURL('',array('action' => 'deny')), array('title' => _('Suche zurücksetzen')))?>
    </div>
</form>
<br>

<?= $this->render_partial("course/studygroup/_feedback") ?>

<? if ($anzahl >= 1):?>
    <?=$this->render_partial("studygroup/_overview", array('base_url' => $base_url, 'link' => $link))?>
<? endif;?>
