<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>
<form method="post" action="<?= UrlHelper::getLink('?change_object_perms='. $resObject->getId()) ?>">
<?= CSRFProtection::tokenTag() ?>
<table border="0" celpadding="2" cellspacing="0" width="99%" align="center">
    <tr>
        <td class="<?= $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<?= $cssSw->getClass() ?>" colspan="2">
            <?=_("verantwortlich:")?><br>
            <a href="<?= $resObject->getOwnerLink()?>"><?= htmlReady($resObject->getOwnerName(TRUE)) ?></a>
        </td>
        <td class="<? echo $cssSw->getClass() ?>" width="50%">
        <? if ($owner_perms) : ?>
            <?=_("verantworlicheN NutzerIn &auml;ndern:") ?><br>
            <? showSearchForm("search_owner", $search_string_search_owner, FALSE,TRUE);
        else : ?>
            <?= MessageBox::info(_("Sie können den/die verantwortlicheN NutzerIn nicht ändern.")) ?>

        <? endif; ?>
        </td>

        <!-- Infobox -->
        <td rowspan="8" valign="top" style="padding-left: 20px" align="right">
            <?
                $content[] = array('kategorie' => _("Informationen:"),
                    'eintrag' => array(
                        array(
                            'icon' => 'icons/16/black/info.png',
                            'text' => _("Hier können Sie Berechtigungen für den Zugriff auf die Ressource vergeben.") ."<br>".
                                _("<b>Achtung:</b> Alle hier erteilten Berechtigungen gelten ebenfalls für die Ressourcen, die der gewählten Ressource untergeordnet sind!")
                        ),

                        array(
                            'icon' => 'icons/16/black/search.png',
                            'text' => '<a href="'. URLHelper::getLink('resources.php?view=search&quick_view_mode=' . $view_mode) .'">'
                                   . _('zur Ressourcensuche') . '</a>'
                        )
                    )
                );

                $infobox = $GLOBALS['template_factory']->open('infobox/infobox_generic_content.php');

                $infobox->set_attribute('picture', 'infobox/schedules.jpg' );
                $infobox->set_attribute('content', $content );

                echo $infobox->render();
            ?>
        </td>

    </tr>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<? echo $cssSw->getClass() ?>" colspan="2" valign="top">
            <?=_("Berechtigungen:")?>
        </td>
        <td class="<? echo $cssSw->getClass() ?>" width="50%" valign="top">
            <?=_("Berechtigung hinzuf&uuml;gen")?><br>
            <? showSearchForm("search_perm_user", $search_string_search_perm_user, FALSE, FALSE, FALSE, TRUE) ?>
        </td>
    </tr>
    <?
    $i=0;
    if ($selectPerms) :
        while ($db->next_record()) : ?>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<? echo $cssSw->getClass() ?>" width="20%">
            <input type="hidden" name="change_user_id[]" value="<?= $db->f("user_id")?>">
            <a href="<?= $resObject->getOwnerLink($db->f("user_id"))?>"><?= htmlReady($resObject->getOwnerName(TRUE, $db->f("user_id"))) ?></a>
        </td>
        <td class="<? echo $cssSw->getClass() ?>" width="*" nowrap style="padding-right: 20px">
            &nbsp;
            <!-- admin-perms -->
            <? if (($resObject->getOwnerType($db->f("user_id")) == "user") && ($owner_perms)) :
                printf ("<input type=\"RADIO\" name=\"change_user_perms[%s]\" value=\"admin\" %s>admin", $i, ($db->f("perms") == "admin") ? "checked" : "");
            else :
                printf ("<input type=\"RADIO\" disabled name=\"FALSE\" %s><span color=\"#888888\">admin</span>", ($db->f("perms") == "admin") ? "checked" : "");
            endif; ?>

            <!-- tutor-perms -->
            <? if (($resObject->getOwnerType($db->f("user_id")) == "user") && ($admin_perms) && ((($db->f("perms") == "tutor") || ($owner_perms)))) :
                printf ("<input type=\"RADIO\" name=\"change_user_perms[%s]\" value=\"tutor\" %s>tutor", $i, ($db->f("perms") == "tutor") ? "checked" : "");
            else :
                printf ("<input type=\"RADIO\" disabled name=\"FALSE\" %s><span color=\"#888888\">tutor</span>", ($db->f("perms") == "tutor") ? "checked" : "");
            endif; ?>

            <!-- autor-perms -->
            <? if (($admin_perms) && ((($db->f("perms") == "autor") || ($owner_perms)))) :
                printf ("<input type=\"RADIO\" name=\"change_user_perms[%s]\" value=\"autor\" %s>autor", $i, ($db->f("perms") == "autor") ? "checked" : "");
            else :
                printf ("<input type=\"RADIO\" disabled name=\"FALSE\" %s><span color=\"#888888\">autor</span>", ($db->f("perms") == "autor") ? "checked" : "");
            endif; ?>

            &nbsp;
            <!-- Trash  -->
            <? if (($owner_perms) || (($admin_perms) && ($db->f("perms") == "autor"))) : ?>
                <a href="<?= UrlHelper::getLink('?change_object_perms='. $resObject->getId() .'&delete_user_perms='. $db->f("user_id")) ?>">
                    <?= Assets::img('icons/16/blue/trash.png', array('title' => _("Berechtigung löschen"))) ?>
                </a>
            <? else : ?>
                <?= Assets::img('icons/16/grey/decline/trash.png', array('title' => _("Sie dürfen diese Berechtigung leider nicht löschen"))); ?>
            <? endif; ?>
        </td>
        <td class="<? echo $cssSw->getClass() ?>" width="50%">
            <?
            switch ($db->f("perms")) :
                case "admin":
                    print _("Nutzer ist <b>Admin</b> und kann s&auml;mtliche Belegungen und Eigenschaften &auml;ndern und Rechte vergeben.");
                break;
                case "tutor":
                    print _("Nutzer ist <b>Tutor</b> und kann s&auml;mtliche Belegungen &auml;ndern.");
                break;
                case "autor":
                    print _("Nutzer ist <b>Autor</b> und kann nur eigene Belegungen &auml;ndern.");
                break;
            endswitch;
            ?>
        </td>
    </tr>
    <?  $i++;
        endwhile;
    else : ?>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
        </td>
        <td class="<? echo $cssSw->getClass() ?>" colspan=3>
            <?= MessageBox::info(_("Es sind keine weiteren Berechtigungen eingetragen")) ?>
        </td>
    </tr>
    <? endif; // selectPerms

    if ((getGlobalPerms($user->id) == "admin") && ($resObject->isRoom())) :
    ?>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<? echo $cssSw->getClass() ?>" colspan="3">
            <?=_("Blockierung:")?><br>
            <?=_("Diesen Raum bei globaler Blockierung gegen eine Bearbeitung durch lokale Administratoren und andere Personen sperren:")?>
            <input type="CHECKBOX" name="change_lockable" <?=($resObject->isLockable()) ? "checked" : "" ?>> <br>
            <?print _("<b>aktueller Zustand</b>:")." "; print ($resObject->isLockable()) ? _("Raum <u>kann</u> blockiert werden") : _("Raum kann <u>nicht</u> blockiert werden") ?>
        </td>
    </tr>
    <? endif; ?>

    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<? echo $cssSw->getClass() ?>" colspan=3 align="center">
            <br><?= Button::create(_('übernehmen'), array('title' => _('Zuweisen')))?>
        </td>
    </tr>
</table>
</form>
