<?
# Lifter010: TEST
use Studip\Button, Studip\LinkButton;
?>
<form method="post" action="<?= UrlHelper::getLink('?change_object_properties='. $resObject->getId()) ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="view" value="edit_object_properties">

<table class="zebra" border="0" celpadding="2" cellspacing="0" width="99%" align="center">
    <colgroup>
        <col width="4%">
        <col>
        <col width="40%">
        <col>
    </colgroup>
    <tbody>
        <tr>
            <td>&nbsp;</td>
            <td>
                <label>
                    <?= _('Name:') ?><br>
                    <input name="change_name" value="<?= htmlReady($resObject->getName()) ?>" size="60" maxlength="255">
                </label>
            </td>
            <td>
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
                    <?= Button::create(_('Zuweisen'), 'assign')?>
                <? else : ?>
                    <b><?=  htmlReady($resObject->getCategoryName()) ?></b>
                    <input type="hidden" name="change_category_id" value="<?= $resObject->getCategoryId() ?>">
                <? endif; ?>
                </label>
            </td>

            <!-- Infobox -->
            <td class="blank" rowspan="5" valign="top" style="padding-left: 20px" align="right">
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
                $infobox->picture = 'infobox/schedules.jpg';
                $infobox->content = $content;

                echo $infobox->render();
            ?>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <label>
                    <?= _('Beschreibung:') ?><br>
                    <textarea name="change_description" rows="3" cols="60"><?= htmlReady($resObject->getDescription()) ?></textarea>
                </label>
            </td>
            <td valign="top">
                <?= _('verantwortlich:') ?><br>
                <a href="<?= $resObject->getOwnerLink()?>"><?= htmlReady($resObject->getOwnerName(true)) ?></a>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2">
                <b><?= _('Eigenschaften') ?></b><br>
            </td>
        </tr>
    <? if ($resObject->isRoom() && get_config('RESOURCES_ENABLE_ORGA_CLASSIFY')): ?>
        <tr>
            <td>&nbsp;</td>
            <td>
                <?= _('organisatorische Einordnung:') ?><br>
            <? if ($resObject->getInstitutId()) : ?>
                <a href="<?= $resObject->getOrgaLink() ?>">
                    <?= htmlReady($resObject->getOrgaName(TRUE)) ?>
                </a>
            <? else : ?>
                <?= _('keine Zuordnung') ?>
            <? endif ?>
            </td>
            <td>
            <? if ($ObjectPerms->havePerm('admin')) : ?>
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
                <?= MessageBox::info(_('Sie k&ouml;nnen die Einordnung in die Orga-Struktur nicht &auml;ndern.')) ?>
            <? endif; ?>
            </td>
        </tr>
    <? endif; ?>
<? if ($resObject->getCategoryId()) : ?>
    <? foreach ($EditResourceData->selectProperties() as $property): ?>
        <tr>
            <td>&nbsp;</td>
            <td>
                <label for="property_<?= $property['property_id'] ?>">
                    <?= htmlReady($property['name']); ?>
                </label>
            </td>
            <td width="40%">
                <input type="hidden" name="change_property_val[]" value="_id_<?= $property['property_id'] ?>">
            <? if ($property['type'] == 'bool'): ?>
                <label>
                    <input id="property_<?= $property['property_id'] ?>" type="checkbox"
                           name="change_property_val[]" <? if ($property['state']) echo 'checked'; ?>>
                        <?= htmlReady($property['options']) ?>
                </label>
            <? elseif ($property['type'] == 'num' && $property['system'] == 2): ?>
                <input id="property_<?= $property['property_id'] ?>" type="text"
                       name="change_property_val[]" value="<?= htmlReady($property['state']) ?>"
                       size="5" maxlength="10">
            <? elseif ($property['type'] == 'num'): ?>
                <input id="property_<?= $property['property_id'] ?>" type="text"
                       name="change_property_val[]" value="<?= htmlReady($property['state']) ?>"
                       size="30" maxlength="255">
            <? elseif ($property['type'] == 'text'): ?>
                <textarea id="property_<?= $property['property_id'] ?>" name="change_property_val[]"
                          cols="30" rows="2"><?= htmlReady($property['state']) ?></textarea>
            <? elseif ($property['type'] == 'select'): ?>
                <select id="property_<?= $property['property_id'] ?>" name="change_property_val[]">
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
            <td>&nbsp;</td>
            <td colspan="2" style="color: red">
                <?= _('Das Objekt wurde noch keinem Typ zugewiesen. Um Eigenschaften bearbeiten zu k&ouml;nnen, m&uuml;ssen Sie vorher einen Typ festlegen!') ?>
            </td>
        </tr>
<? endif; ?>
    <? if ($resObject->getCategoryId() && getGlobalPerms($user->id) == 'admin') : ?>
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('gleichzeitige Belegung') ?></b><br>
                <br>
                <label for="change_multiple_assign">
                    <?= _('Die Ressource darf mehrfach zur gleichen Zeit belegt werden - <br>&Uuml;berschneidungschecks finden <u>nicht</u> statt!') ?>
                </label>
            </td>
            <td>
                <input type="checkbox" id="change_multiple_assign" name="change_multiple_assign" value="1"
                       <? if ($resObject->getMultipleAssign()) echo 'checked'; ?>>
            </td>
        </tr>
    <? endif; ?>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2" align="center">
                <br>
                <?= Button::create(_('Übernehmen'))?>
                <? if ($resObject->isUnchanged()) : ?>
                    <?= LinkButton::createCancel(_('Abbrechen'), UrlHelper::getLink('?cancel_edit='. $resObject->id))?>
                <? endif; ?>
                <br>&nbsp;
            </td>
        </tr>
    </tbody>
</table>

</form>
<br><br>
