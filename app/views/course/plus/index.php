<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
$cssSw=new cssClassSwitcher;
use Studip\Button, Studip\LinkButton;
?>

<? if (isset($msg)) {
    parse_msg($msg);
} ?>

<form action="<?= URLHelper::getLink($save_url) ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
<tr><? $cssSw->switchClass() ?>
    <td class="<?= $cssSw->getClass() ?>" align="center" colspan="3">
        <?= Button::create(_('Übernehmen'), 'uebernehmen') ?>
        <? if ($_SESSION['admin_modules_data']["orig_bin"] != $_SESSION['admin_modules_data']["changed_bin"]) {?>
            <?= MessageBox::info(_("Diese Daten sind noch nicht gespeichert.")) ?>
        <? } ?>
    </td>
</tr>
<?
foreach ($modules->registered_modules as $key => $val) {
    if ($sem_class) {
        $mod = $sem_class->getSlotModule($key);
        $slot_editable = $mod 
            && $sem_class->isModuleAllowed($mod) 
            && !$sem_class->isModuleMandatory($mod);
    }
    if ($modules->isEnableable($key, $_SESSION['admin_modules_data']["range_id"])
            && (!$sem_class || $slot_editable)) {
        $pre_check = null;
        if (isset($val['preconditions'])){
            $method = 'module' . $key . 'Preconditions';
            if(method_exists($modules, $method)) $pre_check = $modules->$method($_SESSION['admin_modules_data']["range_id"],$val['preconditions']);
        }

        ?>
    <tr><? $cssSw->switchClass() ?>
        <td class="<?= $cssSw->getClass() ?>"  width="15%" align="left">
            <b><?=$val["name"]?></b>
            <? if ($sem_class) : ?>
            <? $studip_module = $sem_class->getModule($mod);
            if ($sem_class && is_a($studip_module, "StandardPlugin")) : ?>
                <? $already_displayed_plugins[] = $mod ?>
                (<?= htmlReady($studip_module->getPluginName()) ?>)
            <? endif ?>
            <? endif ?>
            <br>
        </td>
        <td class="<?= $cssSw->getClass() ?>" width="15%">
            <label>
                <input type="radio" <?=($pre_check ? 'disabled' : '')?> name="<?=$key?>_value" value="TRUE" <?=($modules->isBit($_SESSION['admin_modules_data']["changed_bin"], $val["id"])) ? "checked" : "" ?>>
                <?=_("an")?>
            </label>
            <label>
                <input type="radio" <?=($pre_check ? 'disabled' : '')?> name="<?=$key?>_value" value="FALSE" <?=($modules->isBit($_SESSION['admin_modules_data']["changed_bin"], $val["id"])) ? "" : "checked" ?>>
                <?=_("aus")?>
            </label>
            <br>
        </td>
        <td class="<?= $cssSw->getClass() ?>" width="70%">
            <?
            $getModuleXxExistingItems = "getModule".$key."ExistingItems";

            if (method_exists($modules,$getModuleXxExistingItems)) {
                if (($modules->$getModuleXxExistingItems($_SESSION['admin_modules_data']["range_id"])) && ($_SESSION['admin_modules_data']["modules_list"][$key]))
                    printf ("<font color=\"red\">".$modules->registered_modules[$key]["msg_pre_warning"]."</font>", $modules->$getModuleXxExistingItems($_SESSION['admin_modules_data']["range_id"]));
                else
                    print ($_SESSION['admin_modules_data']["modules_list"][$key]) ? $modules->registered_modules[$key]["msg_deactivate"] : ($pre_check ? $pre_check : $modules->registered_modules[$key]["msg_activate"]);
            } else
                print ($_SESSION['admin_modules_data']["modules_list"][$key]) ? $modules->registered_modules[$key]["msg_deactivate"] : ($pre_check ? $pre_check : $modules->registered_modules[$key]["msg_activate"]);
            ?>
        </td>
    </tr>
    <? }
}

foreach ($available_plugins as $plugin) {
    if (!$sem_class || 
            (!$sem_class->isModuleMandatory($plugin->getPluginname()) 
                && $sem_class->isModuleAllowed($plugin->getPluginname())
                && !$sem_class->isSlotModule(get_class($plugin))
            )) :
        $plugin_activated = $plugin->isActivated($_SESSION['SessionSeminar']);
        ?>
        <tr><? $cssSw->switchClass() ?>
            <td class="<?= $cssSw->getClass() ?>"  width="15%" align="left">
                <b><?=$plugin->getPluginname()?></b><br>
            </td>
            <td class="<?= $cssSw->getClass() ?>" width="15%">
                <!-- mark old state -->
                <label>
                    <input type="radio" name="plugin_<?=$plugin->getPluginId()?>" value="TRUE" <?= $plugin_activated ? "checked" : "" ?>>
                    <?=_("an")?>
                </label>
                <label>
                    <input type="radio" name="plugin_<?=$plugin->getPluginId()?>" value="FALSE" <?= $plugin_activated ? "" : "checked" ?>>
                    <?=_("aus")?>
                </label>
                <br>
            </td>
            <td class="<?= $cssSw->getClass() ?>" width="70%">
                <? if (!$plugin_activated): ?>
                    <?= _('Dieses Plugin kann jederzeit aktiviert werden.') ?>
                <? elseif ($warning = $plugin->deactivationWarning($_SESSION['SessionSeminar'])): ?>
                    <font color="red"><?= $warning ?></font>
                <? else: ?>
                    <?= _('Dieses Plugin kann jederzeit deaktiviert werden.') ?>
                <? endif ?>
            </td>
        </tr>
        <?php
    endif;
}
?>
</table>
</form>


<?
$infobox = array(
    array(
        'kategorie' => _("Information"),
        'eintrag'   => array(
            array(
                'icon' => "icons/16/black/info",
                'text' => _("Sie können hier einzelne Inhaltselemente nachträglich aktivieren oder deaktivieren.")
            )
        )
    )
    
);
$infobox = array(
    'picture' => "infobox/modules.jpg",
    'content' => $infobox
);
