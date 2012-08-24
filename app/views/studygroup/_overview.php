<?
# Lifter010: TODO
?>
<table class="default">
    <tr class="sortable" title="<?=_("Klicken, um die Sortierung zu ändern")?>">
            <th class="nosort" width="1%"></th>
            <th width="59%" <?= ($sort_type == 'name') ? 'class="sort'. $sort_order .'"' : '' ?>>
                <a href="<?= $controller->url_for($base_url . ($sort == 'name_asc' ? 'name_desc' : 'name_asc')) ?>"><?= _("Name") ?></a>
            </th>
            <th width="10%" <?= ($sort_type == 'founded') ? 'class="sort'. $sort_order .'"' : '' ?>>
                <a href="<?= $controller->url_for($base_url . ($sort == 'founded_asc' ? 'founded_desc' : 'founded_asc')) ?>"><?= _("gegründet") ?></a>
            </th>
            <th width="6%" <?= ($sort_type == 'member') ? 'class="sort'. $sort_order .'"' : '' ?>>
                <a href="<?= $controller->url_for($base_url . ($sort == 'member_asc' ? 'member_desc' : 'member_asc')) ?>"><?= _("Mitglieder") ?></a>
            </th>
            <th width="14%" <?= ($sort_type == 'founder') ? 'class="sort'. $sort_order .'"' : '' ?>>
                <a href="<?= $controller->url_for($base_url . ($sort == 'founder_asc' ? 'founder_desc' : 'founder_asc')) ?>"><?= _("GründerIn") ?></a>
            </th>
            <th width="5%" <?= ($sort_type == 'ismember') ? 'class="sort'. $sort_order .'"' : '' ?>>
                <a href="<?= $controller->url_for($base_url . ($sort == 'ismember_asc' ? 'ismember_desc' : 'ismember_asc')) ?>"><?= _("Mitglied") ?></a>
            </th>
            <th width="5%" <?= ($sort_type == 'access') ? 'class="sort'. $sort_order .'"' : '' ?>>
                <a href="<?= $controller->url_for($base_url . ($sort == 'access_asc' ? 'access_desc' : 'access_asc')) ?>"><?= _("Zugang") ?></a>
            </th>
        </tr>
        <? foreach ($groups as $group) : ?>
            <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
                <td>
                   <?=StudygroupAvatar::getAvatar($group['Seminar_id'])->getImageTag(Avatar::SMALL)?>
                </td>
                <td>
                    <? if (StudygroupModel::isMember($this->userid,$group['Seminar_id'] )): ?>
                        <a href="<?=URLHelper::getlink("seminar_main.php?auswahl=".$group['Seminar_id'])?>">
                    <? else: ?>
                       <a href="<?=URLHelper::getlink("dispatch.php/course/studygroup/details/".$group['Seminar_id'])?>">
                    <? endif; ?>
                       <?=htmlready($group['Name'])?></a>
                 </td>
                 <td><?=strftime('%x', $group['mkdate'])?>
                </td>
                <td align="center">
                    <?=StudygroupModel::countMembers($group['Seminar_id'])?>
                </td>
                <td style="white-space:nowrap;">
                    <? $founders = StudygroupModel::getFounder($group['Seminar_id']);
                    foreach ($founders as $founder) : ?>
                    <?=Avatar::getAvatar($founder['user_id'])->getImageTag(Avatar::SMALL)?>
                    <a href="<?=URLHelper::getlink('about.php?username='.$founder['uname'])?>"><?=htmlready($founder['fullname'])?></a>
                    <br>
                    <? endforeach; ?>
                </td>
                <td align="center">
                    <? if (StudygroupModel::isMember($this->userid,$group['Seminar_id'] )) :?>
                        <?=Assets::img("icons/16/grey/person.png", array('title' => _('Sie sind Mitglied in dieser Gruppe')))?>
                    <? endif;?>
                </td>
                <td align="center">
                    <? if ($group['admission_prelim'] == 1) :?>
                        <?=Assets::img("icons/16/grey/lock-locked.png", array('title' => _('Mitgliedschaft muss beantragt werden')))?>
                    <? endif;?>
                </td>
            </tr>
    <? endforeach ; ?>

    </table>
    <? if($anzahl>20) :?>
    <div style="text-align:right;" class="table_foot">
    <?= $GLOBALS['template_factory']->render('shared/pagechooser', array("perPage" => 20, "num_postings" => $anzahl, "page" => $page, "pagelink" => $link)) ?>
    </div>
    <? endif;?>
