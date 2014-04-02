<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
use Studip\Button, Studip\LinkButton;
?>

<? if (isset($msg)): ?>
    <?= parse_msg($msg) ?>
<? endif; ?>

<? if ($_SESSION['admin_modules_data']["orig_bin"] != $_SESSION['admin_modules_data']["changed_bin"]): ?>
    <?= MessageBox::info(_("Diese Daten sind noch nicht gespeichert.")) ?>
<? endif; ?>

<form action="<?= URLHelper::getLink($save_url) ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <caption>Inhaltselemente</caption>
    <thead>
        <tr>
            <th></th>
            <th>Name</th>
            <th>Beschreibung</th>
        </tr>
    </thead>
    <tbody>
<?
foreach ($registered_modules as $key => $val) {
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
            if(method_exists($modules, $method)) {
                $pre_check = $modules->$method($_SESSION['admin_modules_data']["range_id"],$val['preconditions']);
            }
        }

        ?>
    <tr <?= $pre_check != null ? 'class="quiet"' : '' ?>>
        <? if ($sem_class) {
            $studip_module = $sem_class->getModule($mod);
        } ?>
        <td>
            <input type="checkbox" name="<?=$key?>_value" value="TRUE" <?= $pre_check ? 'disabled' : '' ?>
            <?= $modules->isBit($_SESSION['admin_modules_data']["changed_bin"], $val["id"]) ? "checked" : "" ?>>
        </td>
        <td>
            <b><?=$val["name"]?></b>
            <? if ($sem_class && is_a($studip_module, "StandardPlugin")) : ?>
                <? $already_displayed_plugins[] = $mod ?>
                (<?= htmlReady($studip_module->getPluginName()) ?>)
            <? endif ?>
        </td>
        <td>
            <? $info = ($studip_module instanceOf StudipModule) ? $studip_module->getMetadata() : ($val['metadata'] ? $val['metadata'] : array()) ?>
            <? if (isset($info['description'])) : ?>
                <?= formatReady($info['description']) ?>
            <? else: ?>
                <?= _("Für dieses Element ist keine Beschreibung vorhanden.") ?>
            <? endif ?>

            <? if (isset($info['homepage'])) : ?>
                <p>
                    <strong><?= _('Weitere Informationen:') ?></strong>
                    <a href="<?= htmlReady($info['homepage']) ?>"><?= htmlReady($info['homepage']) ?></a>
                </p>
            <? endif ?>
            <?
            $getModuleXxExistingItems = "getModule".$key."ExistingItems";

            if (method_exists($modules,$getModuleXxExistingItems)) {
                if ($modules->$getModuleXxExistingItems($_SESSION['admin_modules_data']["range_id"]) &&
                    $_SESSION['admin_modules_data']["modules_list"][$key] && $registered_modules[$key]["msg_pre_warning"])
                    printf('<p><strong>' . _('Hinweis') . ':</strong> ' . $registered_modules[$key]["msg_pre_warning"] . '</p>',
                        $modules->$getModuleXxExistingItems($_SESSION['admin_modules_data']["range_id"]));
            }
            ?>
        </td>
    </tr>
    <? }
}

foreach ($available_plugins as $plugin) {
    if ((!$sem_class && !$plugin->isCorePlugin()) || 
            ($sem_class && !$sem_class->isModuleMandatory($plugin->getPluginname()) 
                && $sem_class->isModuleAllowed($plugin->getPluginname())
                && !$sem_class->isSlotModule(get_class($plugin))
        )) :
                $plugin_activated = $plugin->isActivated();
        ?>
        <tr>
            <td>
                <input type="checkbox" name="plugin_<?=$plugin->getPluginId()?>" value="TRUE" <?= $plugin_activated ? "checked" : "" ?>>
            </td>
            <td>
                <strong><?=$plugin->getPluginname()?></strong>
            </td>
            <td>
                <? $info = $plugin->getMetadata() ?>
                <? if (isset($info['description'])) : ?>
                    <?= formatReady($info['description']) ?>
                <? else: ?>
                    <?= _("Für dieses Element ist keine Beschreibung vorhanden.") ?>
                <? endif ?>

                <? if (isset($info['homepage'])) : ?>
                    <p>
                        <strong><?= _('Weitere Informationen:') ?></strong>
                        <a href="<?= htmlReady($info['homepage']) ?>"><?= htmlReady($info['homepage']) ?></a>
                    </p>
                <? endif ?>

                <? $warning = $plugin->deactivationWarning($_SESSION['SessionSeminar']) ?>
                <? if (isset($warning)) : ?>
                    <p><strong><?= _('Hinweis') ?>:</strong> <?= formatReady($warning) ?></p>
                <? endif ?>
            </td>
        </tr>
    <? endif;
    }
?>
    </tbody>
    <tfoot>
        <tr>
            <td align="center" colspan="3">
                <?= Button::create(_('Übernehmen'), 'uebernehmen') ?>
            </td>
        </tr>
    </tfoot>
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
    'picture' => "sidebar/plugin-sidebar.png",
    'content' => $infobox
);
