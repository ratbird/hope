<?php
require_once 'lib/classes/StudygroupAvatar.class.php';
require_once 'lib/classes/Avatar.class.php';

$infobox = array();
$infobox['picture'] = 'infoboxbild_studygroup.jpg';
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
<? $sort_url =$controller->url_for('studygroup/search/1/') ?>
<table class border="0" cellpadding="2" cellspacing="0" width="100%">
<tr style="background: url(<?=Assets::image_path('steelgraudunkel.gif')?>);cursor: pointer;" title="<?=_("Klicken, um die Sortierung zu ändern")?>">
        <th class="nosort" width="1%"></th>
        <th width="59%" <?= ($sort_type == 'name') ? 'class="sort'. $sort_order .'"' : '' ?>> 
          <a href =<? if($sort == "name_asc") :?>"<?=$sort_url?>name_desc">
						<? else: ?>"<?=$sort_url?>name_asc">
            <? endif;?><?= _("Name") ?>
					</a>
				</th>
        <th width="10%" <?= ($sort_type == 'founded') ? 'class="sort'. $sort_order .'"' : '' ?>>
					<a href =<? if($sort == "founded_asc") :?>"<?=$sort_url?>founded_desc">
          	<? else: ?>"<?=$sort_url?>founded_asc">
						<? endif;?><?= _("gegründet") ?>
					</a>
				</th>
				<th width="5%" <?= ($sort_type == 'member') ? 'class="sort'. $sort_order .'"' : '' ?>> 
					<a href =<? if($sort == "member_asc") :?>"<?=$sort_url?>member_desc">
          	<? else: ?>"<?=$sort_url?>member_asc">
						<? endif;?><?= _("Mitglieder") ?>
					</a>
				</th>
				<th width="15%" <?= ($sort_type == 'founder') ? 'class="sort'. $sort_order .'"' : '' ?>>
					<a href =<? if($sort == "founder_asc") :?>"<?=$sort_url?>founder_desc">
          	<? else: ?>"<?=$sort_url?>founder_asc">
						<? endif;?><?= _("GründerIn") ?>
					</a>
				</th>
				<th width="5%" <?= ($sort_type == 'ismember') ? 'class="sort'. $sort_order .'"' : '' ?>> 
					<a href =<? if($sort == "ismember_asc") :?>"<?=$sort_url?>ismember_desc">
          	<? else: ?>"<?=$sort_url?>ismember_asc">
						<? endif;?><?= _("Mitglied") ?>
					</a>
				</th>
				<th width="5%" <?= ($sort_type == 'access') ? 'class="sort'. $sort_order .'"' : '' ?>> 
					<a href =<? if($sort == "access_asc") :?><?=$sort_url?>access_desc>
          	<? else: ?><?=$sort_url?>access_asc>
						<? endif; ?><?= _("Zugang") ?>
					</a>
				</th>
    </tr>
    <? foreach ($groups as $group) : ?>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td>
               <img src="<?=StudygroupAvatar::getAvatar($group['Seminar_id'])->getUrl(Avatar::SMALL);?>" style="vertical-align:middle;">
            </td>    
            <td style="text-align:left;">
                <? if (StudygroupModel::isMember($this->userid,$group['Seminar_id'] )): ?>
                    <a href="<?=URLHelper::getlink("seminar_main.php?auswahl=".$group['Seminar_id'])?>">
                <? else: ?>
                   <a href="<?=URLHelper::getlink("dispatch.php/course/studygroup/details/".$group['Seminar_id'])?>">
                <? endif; ?>
                   <?=htmlready($group['Name'])?></a>
             </td>
             <td align="center"><?=strftime('%x', $group['mkdate']);?>
            </td>
            <td align="center">
                <?=StudygroupModel::countMembers($group['Seminar_id']);?>
            </td>
            <td style="text-align:left;white-space:nowrap;">
                <? $founders = StudygroupModel::getFounder($group['Seminar_id']);
                foreach ($founders as $founder) : ?>
                <img src="<?=Avatar::getAvatar($founder['user_id'])->getUrl(Avatar::SMALL);?>" style="vertical-align:middle;">
                <a href="<?=URLHelper::getlink('about.php?username='.$founder['uname'])?>"><?=htmlready($founder['fullname'])?></a>
                <br>
                <? endforeach; ?>
            </td>
            <td align="center">
                <? if (StudygroupModel::isMember($this->userid,$group['Seminar_id'] )) :?>
                    <?=Assets::img("members.png",array('title' => _('Sie sind Mitglied in dieser Gruppe')))?>
                <? endif;?>
            </td>
            <td align="center">
                <? if ($group['admission_prelim'] == 1) :?>
                    <?=Assets::img("closelock",array('title' => _('Mitgliedschaft muss beantragt werden')))?>
                <? endif;?>
            </td>
        </tr>
<? endforeach ; ?>

</table>
<?
$link = "dispatch.php/studygroup/search/%s/".$sort;
?>
<? if($anzahl>20) :?>
<div style="text-align:right; padding-top: 2px; padding-bottom: 2px" class="steelgraudunkel"><?=
 $pages = $GLOBALS['template_factory']->open('shared/pagechooser');
 $pages->set_attributes(array("perPage" => 20, "num_postings" => $anzahl, "page"=>$page, "pagelink" => $link));
 
 echo $this->render_partial($pages);
?></div>
<? endif;?>
