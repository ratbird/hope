<?
# Lifter010: TODO
?>
<!-- Startseite (nicht eingeloggt) -->
<? if ($logout) : ?>
    <?= MessageBox::success(_("Sie sind nun aus dem System abgemeldet."), array($GLOBALS['UNI_LOGOUT_ADD'])) ?>
<? endif; ?>
<table class="index_box">
    <tr>
        <td colspan="2" class="topic">
            &nbsp;<b><?= htmlentities($GLOBALS['UNI_NAME_CLEAN']) ?></b>
        </td>
    </tr>
    <tr>
        <td class="blank" height="270" valign="top" colspan="2" style="background:url(<?=$GLOBALS['ASSETS_URL']?>images/startseite.jpg) no-repeat left top; background-color:#FFFFFF; padding-top:30px;">
            <? foreach (Navigation::getItem('/login') as $key => $nav) : ?>
                <? if ($nav->isVisible()) : ?>
                    <? list($name, $title) = explode(' - ', $nav->getTitle()) ?>
                    <div style="margin-left:70px; margin-top:10px; padding: 2px;">
                        <? if (is_internal_url($url = $nav->getURL())) : ?>
                            <a class="index" href="<?= URLHelper::getLink($url) ?>">
                        <? else : ?>
                            <a class="index" href="<?= htmlspecialchars($url) ?>" target="_blank">
                        <? endif ?>
                        <? SkipLinks::addLink($name, $url) ?>
                        <font size="4"><b><?= htmlReady($name) ?></b></font>
                        <font color="#555555" size="1"><br><?= htmlReady($title ? $title : $nav->getDescription()) ?></font>
                        </a>
                    </div>
                <? endif ?>
            <? endforeach ?>
        </td>
    </tr>
    <? if($GLOBALS['UNI_LOGIN_ADD']) : ?>
    <tr>
        <td colspan="2" bgcolor="#FFFFFF">
            <p class="info">
            &nbsp;<br>
            <?=$GLOBALS['UNI_LOGIN_ADD']?>
            </p>
        </td>
    </tr>
    <? endif; ?>
    <tr>
        <td class="blank" valign="middle" align="left" style="padding-left:76px">
            <a href="http://www.studip.de">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/logos/logoklein.gif" border="0"  <?=tooltip(_("Zur Portalseite"))?> >
            </a>
        </td>
        <td class="blank" align="right" nowrap valign="middle">
            <table cellspacing="0" cellpadding="0">
                <tr>
                    <td class="table_row_even">
                    <font size="2" color="#555555">&nbsp; <?=_("Aktive Veranstaltungen")?>:</font>
                    </td>
                    <td class="table_row_even" align="right">
                    <font size="2" color="#555555">&nbsp; <?=$num_active_courses?>&nbsp;</font>
                    </td>
                    <td class="blank">&nbsp; &nbsp; </td>
                </tr>
                <tr>
                    <td class="table_row_even">
                    <font size="2" color="#555555">&nbsp; <?=_("Registrierte NutzerInnen")?>:</font>
                    </td>
                    <td class="table_row_even" align="right">
                    <font size="2" color="#555555">&nbsp; <?=$num_registered_users?>&nbsp; </font>
                    </td>
                    <td class="blank">&nbsp; &nbsp; </td>
                </tr>
                <tr>
                    <td class="table_row_even">
                    <font size="2" color="#555555">&nbsp; <?=_("Davon online")?>:</font>
                    </td>
                    <td class="table_row_even" align="right">
                    <font size="2" color="#555555">&nbsp; <?=$num_online_users?>&nbsp; </font>
                    </td>
                    <td class="blank">&nbsp; &nbsp; </td>
                </tr>
                <tr>
                    <td height="30" class="blank" valign="middle" align="left">
                    <?foreach ($GLOBALS['INSTALLED_LANGUAGES'] as $temp_language_key => $temp_language) {?>
                        &nbsp;
                        <a href="index.php?set_language=<?=$temp_language_key?>">
                        <img src="<?=$GLOBALS['ASSETS_URL']?>images/languages/<?=$temp_language['picture']?>" border="0" <?=tooltip($temp_language['name'])?>>
                        </a>
                    <?}?>
                    </td>
                    <td align="right" valign="top" class="blank">
                    <a href="dispatch.php/siteinfo/show">
                    <font size="2" color="#888888"><?=_("mehr")?>...</font>
                    </a>
                    </td>
                    <td class="blank">
                    &nbsp; &nbsp;
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
