<?php
require_once 'lib/classes/StudygroupAvatar.class.php';
require_once 'lib/classes/Avatar.class.php';

$infobox = array();
$infobox['picture'] = 'infoboxes/studygroup.jpg';
$infobox['content'] = array(
    array(
        'kategorie'=>_("Information"),
        'eintrag'=>array(
            array("text"=>_("Studiengruppen sind eine einfache Möglichkeit, mit Kommilitonen, Kollegen und anderen zusammenzuarbeiten. Jeder kann Studiengruppen gründen. Auf dieser Seite finden Sie eine Liste aller Studiengruppen. Klicken Sie auf auf die Überschriften um die jeweiligen Spalten zu sortieren."),"icon"=>"ausruf_small2.gif")
        )
    )
);

URLHelper::removeLinkParam('cid');
list($sort_type, $sort_order) = explode('_', $sort);

?>

<style>
.sortasc {
  background-image: url(<?=Assets::image_path('dreieck_up.png')?>);
  background-repeat:no-repeat;
  background-position:center right;
}
.sortdesc {
  background-image: url(<?=Assets::image_path('dreieck_down.png')?>);
  background-repeat:no-repeat;
  background-position:center right;
}
th {
  background: none;
  padding: 2px 15px 2px 15px;
  text-align:center;
}
</style>
<?
    $sort_url =$controller->url_for('studygroup/browse/1/');
    $link = "dispatch.php/studygroup/browse/%s/".$sort;
?>
<?=$this->render_partial("studygroup/_overview", array('sort_url' => $sort_url, 'link' => $link))?>

