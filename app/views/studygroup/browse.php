<?php
require_once 'lib/classes/StudygroupAvatar.class.php';


$infobox['picture'] = 'infobox/studygroup.jpg';
$infobox['content'] = array(
    array(
        'kategorie'=>_("Information"),
        'eintrag'=>array(
            array(
                "text" => _("Studiengruppen sind eine einfache Möglichkeit, mit Kommilitonen, Kollegen und anderen zusammenzuarbeiten. Jeder kann Studiengruppen gründen. Auf dieser Seite haben können Sie nach Studiengruppen suchen. Klicken Sie auf die Überschriften der Ergebnistabelle, um die jeweiligen Spalten zu sortieren."),
                "icon" => "icons/16/black/info.png")
        )
    )
);
$sort_url = $controller->url_for("studygroup/browse/1/");
$link = "dispatch.php/studygroup/browse/%s/".$sort;

?>
<form action="<?= $controller->url_for('studygroup/browse') ?>" method=post>
    <?= CSRFProtection::insertToken() ?>
    <div class="search_box" align="center">
        <input name="searchtext" type="text" size="45" style="vertical-align: middle;" value="<?=$search?>" />
        <input type="image" <?= makeButton('suchestarten','src')?> style="vertical-align: middle;"/>
         <a href="<?=URLHelper::getLink('',array('action' => 'deny'))?>">
            <?= makeButton('zuruecksetzen', 'img', _('Suche zurücksetzen')) ?>
        </a>
    </div>
</form>
<br>

<?= $this->render_partial("course/studygroup/_feedback") ?>

<? if ($anzahl >= 1):?>
    <?=$this->render_partial("studygroup/_overview", array('sort_url' => $sort_url, 'link' => $link))?>
<? endif;?>
