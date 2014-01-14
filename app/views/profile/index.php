<? if ($msg) : ?>
    <?= parse_msg($msg) ?>
<? endif ?>
<table class="default nohover">
    <tr>
        <td valign="top">
            <?=$avatar?>
            <br>
            <br>
            <?= _("Besucher dieses Profils:") ?> <?= object_return_views($current_user->user_id) ?>
            <br />
            <? if(!empty($score) && !empty($score_title)) :?>
                <br />
                <a href="<?=URLhelper::getLink("dispatch.php/score")?>" <?=tooltip(_("Zur Rangliste"))?>><?=_("Stud.IP-Punkte:")?> <?=$score?><br>
                    <?=_("Rang:")?> <?=$score_title?>
                </a>
            <? endif?>

            <?if ($current_user->username != $user->username) : ?>
                <?if (!CheckBuddy($current_user->username)) : ?>
                    <br />
                    <a href="<?= URLHelper::getLink($controller->url_for('profile/add_buddy?username=' . urlencode($current_user->username))) ?>">
                        <?=Assets::img('icons/16/blue/person.png', array('title' =>_("zu den Kontakten hinzuf�gen"), 'class' => 'middle'))?>
                        <?=_("zu den Kontakten hinzuf�gen")?>
                    </a>
                <? endif?>

                <br />
                <a href="<?=URLHelper::getLink('sms_send.php', array('sms_source_page'=>'dispatch.php/profile?username='.$current_user->username,'rec_uname'=>$current_user->username))?>">
                    <?=Assets::img('icons/16/blue/mail.png', array('title' => _("Nachricht an Nutzer verschicken"), 'class' => 'middle'))?>
                    <?=_("Nachricht an Nutzer")?>
                </a>
            <?endif?>

            <br />
            <a href="<?=URLHelper::getLink("contact_export.php", array('username' => $current_user->username))?>">
                <?=Assets::img('icons/16/blue/vcard.png', array('title' => _("vCard herunterladen"), 'class' => 'middle'))?>
                <?=_("vCard herunterladen")?>
            </a>

            <?if (($current_user->username != $user->username) && $perm->have_perm('root')) : ?>
                <br />
                <a href="<?=URLHelper::getLink('dispatch.php/admin/user/edit/'.$current_user->user_id)?>">
                    <?=Assets::img('icons/16/blue/edit', array('title' => _('Diesen Benutzer bearbeiten'), 'class' => 'middle'))?>
                    <?=_('Diesen Benutzer bearbeiten')?>
                </a>
            <?endif?>
        </td>


        <td width="99%" valign="top" style="padding: 10px;">
            <h1><?= htmlReady($current_user->getFullname()) ?></h1>

            <? if(!empty($motto)) : ?>
                 <h3><?= htmlReady($motto) ?></h3>
            <?endif?>

            <? if (!get_visibility_by_id($current_user->user_id)) : ?>
                <? if ($current_user->user_id != $user->user_id) : ?>
                    <p>
                        <font color="red"><?= _("(Dieser Nutzer ist unsichtbar.)") ?></font>
                    </p>
                <? else : ?>
                    <p>
                        <font color="red"><?= _("(Sie sind unsichtbar. Deshalb k�nnen nur Sie diese Seite sehen.)") ?></font>
                    </p>
                <? endif ?>
            <? endif ?>
            <? if ($current_user->auth_plugin === null) : ?>
                <p>
                    <font color="red"><?= _("(vorl�ufiger Benutzer)") ?></font>
                </p>
            <? endif ?>
            <? if ($public_email != '') : ?>
                <b><?= _("E-Mail:") ?></b>
                <a href="mailto:<?= htmlReady($public_email) ?>"><?= htmlReady($public_email) ?></a>
                <br />
            <? endif ?>

            <? if(!empty($private_nr)) : ?>
                <b><?= _("Telefon (privat):") ?></b>
                <?= htmlReady($private_nr) ?>
                <br />
            <?endif?>

            <? if(!empty($private_cell)) : ?>
                <b><?= _("Mobiltelefon:") ?></b>
                <?= htmlReady($private_cell) ?>
                <br />
            <?endif?>

            <? if(!empty($skype_name)) : ?>
                <b><?= _("Skype:") ?></b>
                <? if($skype_status) : ?>
                    <img src="http://mystatus.skype.com/smallicon/<?= htmlReady($skype_name) ?>" style="vertical-align:middle;" width="16" height="16" alt="My status">
                <? else :?>
                    <?= Assets::img('icon_small_skype.gif', array('style' => 'vertical-align:middle;')) ?>
                <?endif?>
                <?= htmlReady($skype_name) ?>
                <br />
            <?endif?>

            <? if(!empty($privadr)) : ?>
                <b><?= _("Adresse (privat):") ?></b>
                <?= htmlReady($privadr) ?>
                <br />
            <?endif?>

            <? if(!empty($homepage)) : ?>
                <b><?= _("Homepage:") ?></b>
                <?= formatLinks($homepage) ?>
                <br />
            <?endif?>

            <? if ($perm->have_perm("root") && $current_user['locked']) : ?>
                <br>
                <b><font color="red"><?= _("BENUTZER IST GESPERRT!") ?></font></b>
                <br>
            <? endif ?>

            <? if(count($study_institutes) > 0): ?>
                <br><b><?=_("Wo ich studiere:")?></b><br>
                <? foreach($study_institutes as $inst_result) :?>
                    <a href="<?=URLHelper::getLink('institut_main.php', array('auswahl' => $inst_result["Institut_id"]))?>"><?=htmlReady($inst_result["Name"])?></a><br>
                <?endforeach?>
                    <br />
            <?endif?>

            <? if(count($institutes) > 0) : ?>
                <?= $this->render_partial("profile/working_place") ?>
            <? endif?>

            <? if($has_denoted_fields): ?>
                <br>
                * Diese Felder sind nur f�r Sie und AdministratorInnen sichtbar.<br>
            <?endif?>
               <br>
            <? if (isset($kings)): ?>
                <?= $kings ?><br>
            <? endif; ?>
        <? if(!empty($shortDatafields)) : ?>
            <? foreach ($shortDatafields as $name => $entry) : ?>
                <strong><?= htmlReady($name) ?>:</strong>
                <?= $entry['content'] ?>
                <span class="minor"><?= $entry['visible'] ?></span>
                <br>
            <? endforeach ?>
        <?endif?>
        </td>
    </tr>
</table>
<br />

<? if ($show_news): ?>
    <? show_news($current_user->user_id, $show_admin, 0, $profile_data["nopen"], "100%", 0, $about_data) ?>
<? endif; ?>

<? if ($terms) show_personal_dates($current_user->user_id, time(), -1, FALSE, $show_admin, Request::option('dopen')) ?>

<? if ($show_votes) show_votes($current_user->username, $user->user_id, $perm, YES) ?>

<? if($foaf) $foaf->show(Request::option ('foaf_open'))?>


<? if(!empty($ausgabe_inhalt)) : ?>
    <? foreach($ausgabe_inhalt as $key => $inhalt) :?>
        <?=$this->render_partial($shared_box,array('admin_url' => null, 'title' => $key, 'content_for_layout' => formatReady($inhalt)));?>
        <? $shared_box->clear_attributes()?>
    <?endforeach?>
<? endif?>

<? if ($current_user['perms'] == 'dozent' && !empty($seminare)) : ?>
    <?= $this->render_partial("profile/seminare") ?>
<? endif?>

<?if($show_lit) :?>
    <?=$this->render_partial($shared_box, array('title' => _('Literaturlisten'), 'content_for_layout' => $lit_list));?>
    <? $shared_box->clear_attributes()?>
<?endif?>

<? if(!empty($longDatafields)) :?>
    <? foreach ($longDatafields as $name => $entry) : ?>
    <?=$this->render_partial($shared_box, array('admin_url' => null, 'title' => $name, 'content_for_layout' => $entry));?>
    <? endforeach ?>
    <? $shared_box->clear_attributes()?>
<?endif?>

<?=$hompage_plugin?>

<?if(!empty($categories)) :?>
    <? foreach($categories as $cat) : ?>
        <?=$this->render_partial($shared_box, array('admin_url' => null, 'title' => $cat['head'].$cat['zusatz'], 'content_for_layout' => formatReady($cat['content'])));?>
    <?endforeach?>
    <? $shared_box->clear_attributes()?>
<? endif; ?>

