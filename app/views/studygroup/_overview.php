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
    <? if($anzahl>20) :?>
    <div style="text-align:right; padding-top: 2px; padding-bottom: 2px" class="steelgraudunkel">
    <?= $GLOBALS['template_factory']->render('shared/pagechooser', array("perPage" => 20, "num_postings" => $anzahl, "page" => $page, "pagelink" => $link)) ?>
    </div>
    <? endif;?>
