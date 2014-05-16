<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

if(isset($flash['question']) && isset($flash['candidate'])) {
    $dialog = $GLOBALS['template_factory']->open('shared/question');
    echo $this->render_partial($dialog,array(
                        "question"        => $flash['question'],
                        "approvalLink"    => $controller->url_for('course/studygroup/edit_members/'
                                          .  $sem_id . '/remove_approved/todo/' . get_ticket()
                                          . '?user=' . $flash['candidate']),
                        "disapprovalLink" => $controller->url_for('course/studygroup/members/' . $sem_id . '/' . $page)
                    ));
}
?>

<?= $this->render_partial("course/studygroup/_feedback") ?>

<h1><?= _("Mitglieder") ?></h1>

<? if ($rechte) : ?>
<p>
    <?= _('Klicken Sie auf ein Gruppenmitglied, um ModeratorInnen zu berufen, abzuberufen oder ein Mitglied der Studiengruppe zu entfernen.') ?>
</p>
<? endif; ?>
<ul style="overflow:hidden;display:block;list-style-type:none;list-style-image:none;
list-style-position:outside;list-style-type:none;">
<? foreach ($cmembers as $m) : ?>
<? ($last_visitdate <= $m['mkdate'] && $GLOBALS['perm']->have_studip_perm('tutor', $sem_id))
    ? $options = array('style' => 'border: 3px solid rgb(255, 100, 100);'
        . 'border: 3px solid rgba(255, 0, 0, 0.5)')
    : $options = array() ?>
<? $this->m = $m ?>
    <li style="position:relative;width:200px;display:inline-block;overflow:hidden;vertical-align:top;" align="left">

        <? if (($GLOBALS['perm']->have_studip_perm('tutor', $sem_id) && $m['status'] != 'dozent')
               || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)) : ?>
            <div style="float:left;cursor:hand;" onMouseOver="$('.invitation', this).fadeIn();"
               onMouseOut ="$('.invitation', this).fadeOut();"
               onClick    ="STUDIP.Arbeitsgruppen.toggleOption('<?= $m['user_id'] ?>')"
               title="klicken für weitere Optionen">
                <? $options['title'] = _('klicken für weitere Optionen'); ?>
                <?= Avatar::getAvatar($m['user_id'])->getImageTag(Avatar::MEDIUM, $options) ?>
                <div class='invitation' style="display:none; position:absolute; top:0px; left:83px; width:16px; height:16px">
                    <?= Assets::img('icons/16/blue/edit.png') ?>
                </div>
            </div>
        <? else : ?>
            <div style="float:left;position:relative;">
                <?= Avatar::getAvatar($m['user_id'])->getImageTag(Avatar::MEDIUM, $options) ?>
            </div>
        <? endif ?>

        <? if (($GLOBALS['perm']->have_studip_perm('tutor', $sem_id) && $m['status'] != 'dozent')
               || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)) : ?>
        <noscript>
            <div id="user_<?= $m['user_id']?>" style="float:left; margin-right: 10px; width: 110px;" align="left" valign="top">
                <div id="user_opt_<?= $m['user_id'] ?>">
                    <div class="table_header_bold" style="text-align: center"><?= _('Optionen') ?></div>
                    <?= $this->render_partial('course/studygroup/_members_options.php') ?>
                </div>
            </div>
        </noscript>

        <div id="user_<?= $m['user_id'] ?>" style="float:left; margin-right: 10px; width: 0px;" align="left" valign="top">
            <div id="user_opt_<?= $m['user_id'] ?>" style="display: none">
                <div class="table_header_bold" style="text-align: center"><?= _('Optionen') ?></div>
                <?= $this->render_partial('course/studygroup/_members_options.php') ?>
            </div>
        </div>
        <? endif ?>

        <div style="clear: both; margin-right: 25px;">
        <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $m['username']) ?>">
            <?= htmlReady($m['fullname']) ?>
            <?  if (isset($moderators[$m['user_id']])) : ?>
              <em><?= _("GruppengründerIn") ?></em>
            <? elseif (isset($tutors[$m['user_id']])) : ?>
              <em><?= _("ModeratorIn") ?></em>
            <? endif ?>
          </a>
        </div>
    </li>
<? endforeach ?>
</ul>
<? $link = "dispatch.php/course/studygroup/members/$sem_id/%s"; ?>
<? if($anzahl>20) :?>
<div style="text-align:right; padding-top: 2px; padding-bottom: 2px; margin-top:-1.5em">
<?= $GLOBALS['template_factory']->render('shared/pagechooser', array("perPage" => 20, "num_postings" => $anzahl, "page"=>$page, "pagelink" => $link)) ?>
</div>
<? endif;?>
<br>
<? if ($rechte) : ?>
    <? if (count($accepted) > 0) : ?>
       <table class="default">
            <caption><?= _("Offene Mitgliedsanträge") ?></caption>
            <colgroup>
                <col width="40"></col>
                <col></col>
                <col width="80"></col>
            </colgroup>
            <tr>
                <th></th>
                <th>
                    <?= _("Name") ?>
                </th>
                <th>
                    <?= _("Aktionen") ?>
                </th>
            </tr>

            <? foreach($accepted as $p) : ?>
            <tr>
                <td>
                    <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p['username']) ?>">
                        <?= Avatar::getAvatar($p['user_id'])->getImageTag(Avatar::SMALL) ?>
                    </a>
                </td>
                <td>
                    <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p['username']) ?>">
                        <?= htmlReady($p['fullname']) ?>
                    </a>
                </td>
                <td style='padding-left:1em;white-space:nowrap'>
                    <?= LinkButton::create(_("Eintragen"), $controller->url_for('course/studygroup/edit_members/' . $sem_id . '/accept?user='.$p['username'])) ?>
                    <?= LinkButton::createCancel(_("Ablehnen"),$controller->url_for('course/studygroup/edit_members/' . $sem_id . '/deny?user='.$p['username'])) ?>
                </td>
            </tr>
            <? endforeach ?>
        </table>
    <? endif; ?>
    
    <? if (count($invitedMembers) > 0) : ?>
        <table class="default">
            <caption><?= _("Verschickte Einladungen") ?></caption>
            <colgroup>
                <col width="40"></col>
                <col></col>
                <col width="80"></col>
            </colgroup>
            <tr>
                <th></th>
                <th>
                    <?= _("Name") ?>
                </th>
                <th>
                    <?= _("Aktionen") ?>
                </th>
            </tr>

            <? foreach($invitedMembers as $p) : ?>
            <tr>
                <td>
                    <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p['username']) ?>">
                        <?= Avatar::getAvatar($p['user_id'])->getImageTag(Avatar::SMALL) ?>
                    </a>
                </td>
                <td>
                    <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $p['username']) ?>">
                        <?= htmlReady($p['fullname']) ?>
                    </a>
                </td>
                <td style='padding-left:1em;white-space:nowrap'>
                    <?= LinkButton::createCancel(_("Löschen"),$controller->url_for('course/studygroup/edit_members/' . $sem_id . '/cancelInvitation?user='.$p['username'])) ?>
                </td>
            </tr>
            <? endforeach ?>
        </table>
    <? endif; ?>
<? endif; ?>
