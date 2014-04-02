<?php
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

$infobox['picture'] = 'sidebar/studygroup-sidebar.png';
$infobox['content'] = array(
    array(
        'kategorie'=>_("Information"),
        'eintrag'=>array(
            array(
                "text" => _("Studiengruppen sind eine einfache M�glichkeit, mit Kommilitonen, Kollegen und anderen zusammenzuarbeiten. Jeder kann Studiengruppen gr�nden. Auf dieser Seite haben k�nnen Sie nach Studiengruppen suchen. Klicken Sie auf die �berschriften der Ergebnistabelle, um die jeweiligen Spalten zu sortieren."),
                "icon" => "icons/16/black/info.png")
        )
    )
);
$base_url = "studygroup/browse/1/";
$link = "dispatch.php/studygroup/browse/%s/".$sort;

?>
<form action="<?= $controller->url_for('studygroup/browse') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <div class="search_box" align="center">
        <input name="searchtext" aria-label="<?= _("Geben Sie einen Suchbegriff f�r Studiengruppen ein.") ?>" type="text" size="45" style="vertical-align: middle;" value="<?= htmlReady($search) ?>" />
        <?= Button::create(_('Suchen'))?>
        <?= LinkButton::create(_('Zur�cksetzen'), URLHelper::getURL('',array('action' => 'deny')), array('title' => _('Suche zur�cksetzen')))?>
    </div>
</form>
<br>

<?= $this->render_partial("course/studygroup/_feedback") ?>

<? if ($anzahl >= 1):?>
    <?=$this->render_partial("studygroup/_overview", array('base_url' => $base_url, 'link' => $link))?>
<? endif;?>
