<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
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
            <label>
                <?= _('Typ des Objektes:') ?><br>
                <? if (!$resObject->isAssigned()): ?>
                    <select name="change_category_id">
                    <? if (!$resObject->getCategoryId()) : ?>
                        <option value=""><?= _('nicht zugeordnet') ?></option>
                    <? endif; ?>
                    <? foreach ($EditResourceData->selectCategories(allowCreateRooms()) as $category_id => $name): ?>
                        <option value="<?= $category_id ?>"
                                <? if ($category_id == $resObject->getCategoryId()) echo 'selected'; ?>>
                            <?= htmlReady($name) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                    <?= Button::create(_("Zuweisen"), 'assign')?>
                <? else : ?>
                    <b><?=  htmlReady($resObject->getCategoryName()) ?></b>
                    <input type="hidden" name="change_category_id" value="<?= $resObject->getCategoryId() ?>">
                <? endif; ?>
            </label>
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
<? if ($resObject->isRoom() && get_config('RESOURCES_ENABLE_ORGA_CLASSIFY')): ?>
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
                <option value="0">&lt;<?= _('keine Zuordnung') ?>&gt;</option>
            <? foreach ($EditResourceData->selectFaculties() as $institute_id => $faculty): ?>
                <option style="font-weight:bold;" value="<?= $institute_id ?>"
                        <? if ($institute_id == $resObject->getInstitutId()) echo 'selected'; ?>>
                    <?= my_substr($faculty['Name'], 0, 50) ?>
                </option>
                <? foreach ($faculty['institutes'] as $institute_id => $name): ?>
                    <option style="padding-left: 1.5em;" value="<?= $institute_id ?>"
                            <? if ($institute_id == $resObject->getInstitutId()) echo 'selected'; ?>>
                        <?= my_substr($name, 0, 50) ?>
                    </option>
                <? endforeach; ?>
            <? endforeach; ?>
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
    <? foreach ($EditResourceData->selectProperties() as $property): ?>
    <tr>
        <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
        </td>
        <td class="<?= $cssSw->getClass() ?>">
            <?= htmlReady($property['name']); ?>
        </td>
        <td class="<? echo $cssSw->getClass() ?>" width="40%">
            <input type="hidden" name="change_property_val[]" value="_id_<?= $property['property_id'] ?>">
        <? if ($property['type'] == 'bool'): ?>
            <input type='CHECKBOX' name="change_property_val[]" <? if ($property['state']) echo 'checked'; ?>>
                <?= htmlReady($property['options']) ?>
        <? elseif ($property['type'] == 'num' && $property['system'] == 2): ?>
            <input type="text" name="change_property_val[]" value="<?= htmlReady($property['state']) ?>" size="5" maxlength="10">
        <? elseif ($property['type'] == 'num'): ?>
            <input type="text" name="change_property_val[]" value="<?= htmlReady($property['state']) ?>" size="30" maxlength="255">
        <? elseif ($property['type'] == 'text'): ?>
            <textarea name="change_property_val[]" cols="30" rows="2"><?= htmlReady($property['state']) ?></textarea>
        <? elseif ($property['type'] == 'select'): ?>
            <select name="change_property_val[]">
            <? foreach (explode(';', $property['options']) as $option): ?>
                <option value="<?= $option ?>" <? if ($property['state'] == $option) echo 'selected'; ?>>
                    <?= htmlReady($option) ?>
                </option>
            <? endforeach; ?>
            </select>
        <? endif; ?>
        </td>
    </tr>
    <? endforeach; ?>
<? else : ?>
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
<? endif; ?>
<? if ($resObject->getCategoryId() && getGlobalPerms($user->id) == 'admin') : ?>
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
            <?= Button::create(_('Übernehmen'))?>
            <? if ($resObject->isUnchanged()) : ?>
                <?= LinkButton::createCancel(_('Abbrechen'), UrlHelper::getLink('?cancel_edit='. $resObject->id))?>
            <? endif; ?>
            <br>&nbsp;
        </td>
    </tr>
    </form>
</table>
<br><br>
