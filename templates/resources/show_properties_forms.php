<?
# Lifter010: TODO
?>
<table border="0" celpadding="2" cellspacing="0" width="99%" align="center">
<form method="post" action="<?= UrlHelper::getLink('?change_object_properties='. $resObject->getId()) ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="view" value="edit_object_properties">
    <tr>
        <td class="<?= $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<?= $cssSw->getClass() ?>">
            <?=_("Name:")?><br>
            <input name="change_name" value="<?= htmlReady($resObject->getName()) ?>" size="60" maxlength="255">
        </td>
        <td class="<?= $cssSw->getClass() ?>" width="40%">
            <?=_("Typ des Objektes:")?><br>
            <? if (!$resObject->isAssigned()) : ?>
                <select name="change_category_id">
                <?
                $EditResourceData->selectCategories(allowCreateRooms());
                if (!$resObject->getCategoryId()) : ?>
                    <option select value=""><?= _("nicht zugeordnet") ?></option>
                <? endif;
                while ($db->next_record()) :
                    if ($db->f("category_id")==$resObject->getCategoryId()) : ?>
                        <option selected value="<?= $db->f("category_id") ?>"><?= htmlReady($db->f("name")) ?></option>
                    <? else : ?>
                        <option value="<?= $db->f("category_id") ?>"><?= htmlReady($db->f("name")) ?></option>
                    <? endif;
                endwhile; ?>
                </select>
                <input type="image" name="assign" <?=makeButton("zuweisen", "src")?> value="<?=_("Zuweisen")?>">
            <? else : ?>
                <b><?=  htmlReady($resObject->getCategoryName()) ?></b>
                <input type="hidden" name="change_category_id" value="<?= $resObject->getCategoryId() ?>">
            <? endif; ?>
        </td>

        <!-- Infobox -->
        <td rowspan="5" valign="top" style="padding-left: 20px" align="right">
            <?
                $content[] = array('kategorie' => _("Informationen:"),
                    'eintrag' => array(
                        array(
                            'icon' => 'icons/16/black/info.png',
                            'text' => _("Hier können Sie Ressourcen-Eigenschaften bearbeiten.")
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
        <td class="<?= $cssSw->getClass() ?>">
            <?=_("Beschreibung:")?><br>
            <textarea name="change_description" rows="3" cols="60"><?= htmlReady($resObject->getDescription()) ?></textarea>
        </td>
        <td class="<?= $cssSw->getClass() ?>" width="40%" valign="top">
            <?=_("verantwortlich:")?><br>
            <a href="<?= $resObject->getOwnerLink()?>"><?= htmlReady($resObject->getOwnerName(true)) ?></a>
        </td>
    </tr>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<? echo $cssSw->getClass() ?>" colspan="2">
            <b><?=_("Eigenschaften")?></b><br>
        </td>
    </tr>
    <?
    if (($resObject->isRoom()) && (get_config("RESOURCES_ENABLE_ORGA_CLASSIFY"))) :
    ?>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<?= $cssSw->getClass() ?>" >
            <?=_("organisatorische Einordnung:")?><br>
            <? if ($resObject->getInstitutId()) : ?>
                <a href="<?= $resObject->getOrgaLink() ?>">
                    <?= htmlReady($resObject->getOrgaName(TRUE)) ?>
                </a>
            <? else : ?>
                <?= _("keine Zuordnung") ?>
            <? endif ?>
        </td>
        <td class="<?= $cssSw->getClass() ?>" width="40%">
        <? if ($ObjectPerms->havePerm("admin")) : ?>
            <br>
            <select name="change_institut_id">
                <option value="0">&lt;<?=_("keine Zuordnung")?>&gt;</option>
                <?
                $EditResourceData->selectFacultys();
                while ($db->next_record()) :
                    printf ("<option style=\"font-weight:bold;\" value=\"%s\" %s>%s</option>", $db->f("Institut_id"), ($db->f("Institut_id") == $resObject->getInstitutId()) ? "selected" : "", my_substr($db->f("Name"),0,50));
                    $EditResourceData->selectInstitutes($db->f("fakultaets_id"));
                        while ($db2->next_record()) {
                            printf ("<option value=\"%s\" %s>&nbsp;&nbsp;&nbsp;&nbsp;%s</option>", $db2->f("Institut_id"), ($db2->f("Institut_id") == $resObject->getInstitutId()) ? "selected" : "", my_substr($db2->f("Name"),0,50));
                        }
                endwhile; ?>
            </select>
        <? else : ?>
            <?= MessageBox::info(_("Sie k&ouml;nnen die Einordnung in die Orga-Struktur nicht &auml;ndern.")) ?>
        <? endif; ?>
        </td>
    </tr>
    <? endif; ?>
    <? if ($resObject->getCategoryId()) : ?>
    <tr>
        <td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
        </td>
        <td class="<? echo $cssSw->getClass() ?>" colspan=2 align="center">
        </td>
    </tr>
        <?
        $EditResourceData->selectProperties();
        while ($db->next_record()) : ?>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
        </td>
        <td class="<?= $cssSw->getClass() ?>">
            <?= htmlReady($db->f("name")); ?>
        </td>
        <td class="<? echo $cssSw->getClass() ?>" width="40%">
        <?
            $db2->query("SELECT * FROM resources_objects_properties WHERE resource_id = '".$resObject->getId()."' AND property_id = '".$db->f("property_id")."' "); $db2->next_record();
            printf ("<input type=\"HIDDEN\" name=\"change_property_val[]\" value=\"%s\">", "_id_".$db->f("property_id"));
            switch ($db->f("type")) :
                case "bool":
                    printf ("<input type=\"CHECKBOX\" name=\"change_property_val[]\" %s>&nbsp;%s", ($db2->f("state")) ? "checked":"", htmlReady($db->f("options")));
                break;
                case "num":
                    if ($db->f("system") == 2)
                        printf ("<input type=\"TEXT\" name=\"change_property_val[]\" value=\"%s\" size=5 maxlength=10>", htmlReady($db2->f("state")));
                    else
                        printf ("<input type=\"TEXT\" name=\"change_property_val[]\" value=\"%s\" size=30 maxlength=255>", htmlReady($db2->f("state")));
                break;
                case "text":
                    printf ("<textarea name=\"change_property_val[]\" cols=30 rows=2 >%s</textarea>", htmlReady($db2->f("state")));
                break;
                case "select":
                    $options=explode (";",$db->f("options"));
                    printf ("<select name=\"change_property_val[]\">");
                    foreach ($options as $a) :
                        printf ("<option %s value=\"%s\">%s</option>", ($db2->f("state") == $a) ? "selected":"", $a, htmlReady($a));
                    endforeach;
                    printf ("</select>");
                break;
            endswitch;
        ?></td>
    </tr>
    <? endwhile;
    else : ?>
    <tr>
        <td class="<?= $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<?= $cssSw->getClass() ?>" colspan="2">
            <span stlye="color: red">
                <?=_("Das Objekt wurde noch keinem Typ zugewiesen. Um Eigenschaften bearbeiten zu k&ouml;nnen, m&uuml;ssen Sie vorher einen Typ festlegen!")?>
            </span>
        </td>
    </tr>
    <? endif;
    if ((getGlobalPerms($user->id) == "admin") && ($resObject->getCategoryId())) : ?>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<?= $cssSw->getClass() ?>">
            <b><?=_("gleichzeitige Belegung")?></b><br>
            <br>
            <?=_("Die Ressource darf mehrfach zur gleichen Zeit belegt werden - <br>&Uuml;berschneidungschecks finden <u>nicht</u> statt!")?>
        </td>
        <td class="<? echo $cssSw->getClass() ?>" width="40%">
            <input type="CHECKBOX" name="change_multiple_assign" <?=($resObject->getMultipleAssign()) ? "checked" : "" ?>><br>
        </td>
    </tr>
    <? endif; ?>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">
            &nbsp;
        </td>
        <td class="<?= $cssSw->getClass() ?>" colspan="2" align="center">
            <br>
            <input type="image" align="absmiddle" <?=makeButton("uebernehmen", "src")?> name="submit" value="<?=_("Zuweisen")?>">
            <? if ($resObject->isUnchanged()) : ?>
                <a href="<?= UrlHelper::getLink('?cancel_edit='. $resObject->id) ?>"><?= makeButton("abbrechen", "img") ?></a>
            <? endif; ?>
            <br>&nbsp;
        </td>
    </tr>
    </form>
</table>
<br><br>
