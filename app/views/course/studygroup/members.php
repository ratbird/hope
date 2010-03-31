<?
if ($rechte) {
    $text = _('Hier können Sie die TeilnehmerInnen der Studiengruppen verwalten. TeilnehmerInnen können je nach Status zu einem Moderator hoch oder runtergestuft werden und aus der Studiengruppe entlassen werden.');
    $aktionen = array(
        'kategorie' => _("Aktionen"),
        'eintrag'   => array(
            array(
                'text' => _("Klicken Sie auf ein Gruppenmitglied, um ModeratorInnen zu berufen, abzuberufen oder ein Mitglieder der Studiengruppe zu entfernen."),
                'icon' => "icon-cont.gif"
            ),
            array(
                'text' => _("<a href=\"".URLHelper::getLink('dispatch.php/course/studygroup/massmsg/'.$sem_id)."\">Versenden Sie eine Nachricht an alle Mitglieder der Studiengruppe.</a>"),
                'icon' => "cont_nachricht_pfeil.gif"
            )
        )
    );
} else {
    $text = _('Studiengruppen sind eine einfache Möglichkeit, mit Kommilitonen, Kollegen und anderen zusammenzuarbeiten. Jeder kann Studiengruppen anlegen.');
    $aktionen = array();
}

$infobox = array();
$infobox['picture'] = StudygroupAvatar::getAvatar($sem_id)->getUrl(Avatar::NORMAL);

$infobox['content'] = array(
    array(
        'kategorie' => _("Information"),
        'eintrag'   => array(array("text" => $text, "icon" => "ausruf_small2.gif"))
    ),
    $aktionen
);

?>

<?= $this->render_partial("course/studygroup/_feedback") ?>

<h1><?= _("Mitglieder") ?></h1>

<? if ($rechte) : ?>
<p>
    <?= _("Klicken Sie auf ein Gruppenmitglied, um ModeratorInnen zu berufen, abzuberufen oder ein Mitglied der Studiengruppe zu entfernen. ") ?>
</p>
<? endif; ?>
<ul style="overflow:hidden;display:block;list-style-type:none;list-style-image:none;
list-style-position:outside;list-style-type:none;">
<? foreach ($cmembers as $m) : ?>

<? $this->m = $m ?>
    <li style="position:relative;width:200px;display:inline-block;overflow:hidden;vertical-align:top;" align="left">

        <? if (($GLOBALS['perm']->have_studip_perm('dozent', $sem_id) && $m['status'] != 'dozent') || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)) : ?>
            <div id="usercontainer" style="float:left;position:relative;cursor:hand;"onMouseOver="$(this).down('.invitation').show();"
               onMouseOut ="$(this).down('.invitation').hide();"
               onClick    ="STUDIP.Arbeitsgruppen.toggleOption('<?= $m['user_id'] ?>')"
               title="klicken für weitere Optionen">
                <?= Avatar::getAvatar($m['user_id'])->getImageTag(Avatar::MEDIUM, array("title" => _("klicken für weitere Optionen"))) ?>
                <div class='invitation' style="display:none;position:absolute;bottom:10px;right:10px;width:10px;height:10px">
                    <?= Assets::img('einst2') ?>
                </div>
            </div>
        <? else : ?>
            <div style="float:left;position:relative;">
                <?= Avatar::getAvatar($m['user_id'])->getImageTag(Avatar::MEDIUM) ?>
            </div>
        <? endif ?>

        <? if (($GLOBALS['perm']->have_studip_perm('dozent', $sem_id) && $m['status'] != 'dozent') || $GLOBALS['perm']->have_studip_perm('admin', $sem_id)) : ?>
        <noscript>
            <div id="user_<?= $m['user_id']?>" style="float:left; margin-right: 10px; width: 110px;" align="left" valign="top">
                <div id="user_opt_<?= $m['user_id'] ?>">
                <div class="blue_gradient" style="text-align: center"><?= _('Optionen') ?></div>
                <br>
                <?= $this->render_partial('course/studygroup/_members_options.php') ?>
            </div>
        </noscript>

        <div id="user_<?= $m['user_id'] ?>" style="float:left; margin-right: 10px; width: 0px;" align="left" valign="top">
            <div id="user_opt_<?= $m['user_id'] ?>" style="display: none">
                <div class="blue_gradient" style="text-align: center"><?= _('Optionen') ?></div>
                <br>
                <?= $this->render_partial('course/studygroup/_members_options.php') ?>
            </div>
        </div>
        <? endif ?>

        <div style="clear: both; margin-right: 25px;">
        <a href="<?= URLHelper::getLink('about.php?username='.$m['username']) ?>">
            <?= htmlReady($m['fullname']) ?><br>
            <?  if (isset($moderators[$m['user_id']])) : ?>
              <em><?= _("GruppengründerIn") ?></em>
            <? elseif (isset($tutors[$m['user_id']])) : ?>
              <em><?= _("ModeratorIn") ?></em>
            <? endif ?>
                    <br>
                    <br>
          </a>
        </div>
    </li>
<? endforeach ?>
</ul>
<? $link = "dispatch.php/course/studygroup/members/$sem_id/%s"; ?>
<? if($anzahl>20) :?>
<div style="text-align:right; padding-top: 2px; padding-bottom: 2px; margin-top:-1.5em" class=""><?=
    $pages = $GLOBALS['template_factory']->open('shared/pagechooser');
    $pages->set_attributes(array("perPage" => 20, "num_postings" => $anzahl, "page"=>$page, "pagelink" => $link));
    echo $this->render_partial($pages);?>
</div>
<? endif;?>
</br>
<? if ($rechte) : ?>
    <?=$this->render_partial("course/studygroup/_invite_members", array('members' => $flash['members'], 'results_choose_members' => $flash['results_choose_members']));?>
    <br>
    <? if (count($accepted) > 0) : ?>
        <h2 style="clear:left; padding-top: 50px;"><?= _("Offene Mitgliedsanträge") ?></h2>
        <table cellspacing="0" cellpadding="2" border="0" style="max-width: 100%; min-width: 70%">
            <tr>
                <th colspan="2" width="70%">
                    <?= _("Name") ?>
                </th>
                <th width="30%">
                    <?= _("Aktionen") ?>
                </th>
            </tr>

            <? foreach($accepted as $p) : ?>
            <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
                <td>
                    <a href="<?= URLHelper::getLink('about.php?username='.$p['username']) ?>">
                        <?= Avatar::getAvatar($p['user_id'])->getImageTag(Avatar::SMALL) ?>
                    </a>
                </td>
                <td>
                    <a href="<?= URLHelper::getLink('about.php?username='.$p['username']) ?>">
                        <?= htmlReady($p['fullname']) ?>
                    </a>
                </td>
                <td style='padding-left:1em;'>
                    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/'.$p['username'].'/accept') ?>">
                        <?= makebutton('eintragen') ?>
                    </a>
                    <a href="<?= $controller->url_for('course/studygroup/edit_members/'.$sem_id.'/'.$p['username'].'/deny') ?>">
                        <?= makebutton('ablehnen') ?>
                    </a>
                </td>
            </tr>
            <? endforeach ?>
        </table>
    <? endif; ?>
<? endif; ?>
