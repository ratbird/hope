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

<? if ($_SESSION['admin_modules_data']["orig_bin"] != $_SESSION['admin_modules_data']["changed_bin"]): ?>
    <?= MessageBox::info(_("Diese Daten sind noch nicht gespeichert.")) ?>
<? endif; ?>

<form action="<?= URLHelper::getLink($save_url) ?>" method="post">
<?= CSRFProtection::tokenTag() ?>

<table class="default nohover plus">
<!-- <caption><?=_("Inhaltselemente")?></caption> -->
<tbody>
<?
foreach ($available_modules as $category => $pluginlist) {
    if ($_SESSION['plus']['displaystyle'] != 'category' && $category != 'Plugins und Module A-Z') continue;
    if (isset($_SESSION['plus']) && !$_SESSION['plus']['Kategorie'][$category] && $category != 'Plugins und Module A-Z') continue;

    ?>
    <tr>
        <th colspan=3>
            <?= $category ?>
        </th>
    </tr>

    <? foreach ($pluginlist as $key => $val) {

        if ($val['type'] == 'plugin') {
            $plugin = $val['object'];
            $plugin_activated = $plugin->isActivated();
            $info = $plugin->getMetadata();

            //Checkbox
            $cb_name = 'plugin_' . $plugin->getPluginId();
            $cb_disabled = '';
            $cb_checked = $plugin_activated ? "checked" : "";

            $pluginname = isset($info['displayname']) ? $info['displayname'] : $plugin->getPluginname();
            $URL = $plugin->getPluginURL();

            $warning = $plugin->deactivationWarning($_SESSION['SessionSeminar']);

        } elseif ($val['type'] == 'modul') {

            $modul = $val['object'];

            $pre_check = null;
            if (isset($modul['preconditions'])) {
                $method = 'module' . $val['modulkey'] . 'Preconditions';
                if (method_exists($modules, $method)) {
                    $pre_check = $modules->$method($_SESSION['admin_modules_data']["range_id"], $modul['preconditions']);
                }
            }

            $cb_name = $val['modulkey'] . '_value';
            $cb_disabled = $pre_check ? 'disabled' : '';
            $cb_checked = $modules->isBit($_SESSION['admin_modules_data']["changed_bin"], $modul["id"]) ? "checked" : "";

            $pluginname = $modul['name'];
            
            $URL = $GLOBALS['ASSETS_URL'].'images';

            if ($sem_class) {
                $studip_module = $sem_class->getModule($sem_class->getSlotModule($val['modulkey']));
            }

            $info = ($studip_module instanceOf StudipModule) ? $studip_module->getMetadata() : ($modul['metadata'] ? $modul['metadata'] : array());

            $getModuleXxExistingItems = "getModule" . $val['modulkey'] . "ExistingItems";

        }
        //if(isset($info['complexity']) && isset($_SESSION['plus']) && !$_SESSION['plus']['Komplex'][$info['complexity']])continue;
        ?>

        <tr class="<?= $pre_check != null ? ' quiet' : '' ?>">
            <td colspan=3>

                <div class="plus_basic">

                    <!-- checkbox -->
                    <input type="checkbox" id="<?= $pluginname ?>" name="<?= $cb_name ?>" value="TRUE" <?= $cb_disabled ?> <?= $cb_checked ?>>

                    <div class="element_header">

                        <!-- Name -->
                        <label for="<?= $pluginname ?>"><strong><?= $pluginname ?></strong></label>
                        <? if ($val['type'] == 'modul' && $sem_class && is_a($studip_module, "StandardPlugin")) : ?>
                            <? $already_displayed_plugins[] = $mod ?>
                            (<?= htmlReady($studip_module->getPluginName()) ?>)
                        <? endif ?>

                        <!-- komplex -->
                        <? switch ($info['complexity']) {
                            case 3:
                                $complexname = 'Intensiv';
                                break;
                            case 2:
                                $complexname = 'Erweitert';
                                break;
                            case 1:
                                $complexname = 'Standard';
                                break;
                            default:
                                $complexname = 'Nicht angegeben';
                                break;
                        }

                        if (isset($info['complexity'])) {

                            $color1 = isset($info['complexity']) ? "hsl(57, 100%, 50%)" : "hsl(0, 0%, 100%)";
                            $color2 = isset($info['complexity']) && $info['complexity'] > 1 ? "hsl(42, 100%, 50%)" : "hsl(0, 0%, 100%)";
                            $color3 = isset($info['complexity']) && $info['complexity'] > 2 ? "hsl(15, 100%, 50%)" : "hsl(0, 0%, 100%)";
                            $border_color1 = isset($info['complexity']) ? "hsl(57, 100%, 45%)" : "hsl(0, 0%, 80%)";
                            $border_color2 = isset($info['complexity']) && $info['complexity'] > 1 ? "hsl(42, 100%, 45%)" : "hsl(0, 0%, 80%)";
                            $border_color3 = isset($info['complexity']) && $info['complexity'] > 2 ? "hsl( 15, 100%, 45%)" : "hsl(0, 0%, 80%)";

                            ?>
                            <div class="complexity" title="Komplexität: <?= $complexname ?>">
                                <div class="complexity_element"
                                     style="background-color: <?= $color1 ?>; border-color: <?= $border_color1 ?>;"></div>
                                <div class="complexity_element"
                                     style="background-color: <?= $color2 ?>; border-color: <?= $border_color2 ?>;"></div>
                                <div class="complexity_element"
                                     style="background-color: <?= $color3 ?>; border-color: <?= $border_color3 ?>;"></div>
                            </div>
                        <? } ?>

                    </div>

                    <div class="element_description">

                        <!-- icon -->
                        <? if (isset($info['icon'])) : ?>
                            <img class="plugin_icon" alt="" src="<?= $URL . "/" . $info['icon'] ?> ">
                        <? endif ?>

                        <!-- shortdesc -->
                        <strong class="shortdesc">
                            <?= formatReady($info['descriptionshort']) ?>
                            <? if (!isset($info['descriptionshort'])) : ?>
                                <? if (isset($info['summary'])) : ?>
                                    <?= formatReady($info['summary']) ?>
                                <? endif ?>
                            <? endif ?>
                        </strong>

                    </div>

                </div>

                <? if ($_SESSION['plus']['View'] == 'openall' || !isset($_SESSION['plus'])) { ?>

                    <div class="plus_expert">

                        <div class="screenshot_holder">
                            <? if (isset($info['screenshot'])) : 
                            	$fileext = end(explode(".", $info['screenshot']));
                        		$filename = str_replace("_"," ",basename($info['screenshot'], ".".$fileext));?>
								
                                <a href="<?= $URL . "/" . $info['screenshot'] ?>"
                                   data-lightbox="<?= $pluginname ?>" data-title="<?= $filename ?>">
                                    <img class="big_thumb" src="<?= $URL . "/" . $info['screenshot'] ?>"
                                         alt="<?= $pluginname ?>"/>
                                </a>

                                <?
                                if (isset($info['additionalscreenshots'])) {
                                    ?>

                                    <div class="thumb_holder">

                                        <? for ($i = 0; $i < count($info['additionalscreenshots']); $i++) { 
                                       		 $fileext = end(explode(".", $info['additionalscreenshots'][$i]));
                                			 $filename = str_replace("_"," ",basename($info['additionalscreenshots'][$i], ".".$fileext));?>

                                            <a href="<?= $URL . "/" . $info['additionalscreenshots'][$i] ?>"
                                               data-lightbox="<?= $pluginname ?>"
                                               data-title="<?= $filename ?>">
                                                <img class="small_thumb"
                                                     src="<?= $URL . "/" . $info['additionalscreenshots'][$i] ?>"
                                                     alt="<?= $pluginname ?>"/>
                                            </a>

                                        <? } ?>

                                    </div>

                                <? } ?>

                            <? endif ?>
                        </div>

                        <div class="descriptionbox">

                            <!-- inhaltlöschenbutton -->
                            <? if ($val['type'] == 'plugin' && method_exists($plugin, 'deleteContent')) echo LinkButton::create(_('Inhalte löschen'), URLHelper::getURL("?deleteContent=true&name=" . $key), array('style' => 'float:right; z-index: 1;')); ?>
 			    <? if ($val['type'] == 'modul' && $studip_module instanceOf StudipModule && method_exists($studip_module, 'deleteContent')) echo LinkButton::create(_('Inhalte löschen'), URLHelper::getURL("?deleteContent=true&name=" . $key), array('style' => 'float:right; z-index: 1;')); ?>
                           	
                            <!-- tags -->
                            <? if (isset($info['keywords'])) : ?>
                                <ul class="keywords">
                                    <? foreach (explode(';', $info['keywords']) as $keyword) {
                                        echo '<li>' . $keyword . '</li>';
                                    }?>
                                </ul>
                            <? endif ?>

                            <!-- longdesc -->
                            <? if (isset($info['descriptionlong'])) : ?>
                                <p class="longdesc">
                                    <?= formatReady($info['descriptionlong']) ?>
                                </p>
                            <? endif ?>

                            <? if (!isset($info['descriptionlong'])) : ?>
                                <p class="longdesc">
                                    <? if (isset($info['description'])) : ?>
                                        <?= formatReady($info['description']) ?>
                                    <? else: ?>
                                        <?= _("Für dieses Element ist keine Beschreibung vorhanden.") ?>
                                    <? endif ?>
                                </p>
                            <? endif ?>

                            <? if ($val['type'] == 'modul') {
                                $getModuleXxExistingItems = "getModule" . $val['modulkey'] . "ExistingItems";

                                if (method_exists($modules, $getModuleXxExistingItems)) {
                                    if ($modules->$getModuleXxExistingItems($_SESSION['admin_modules_data']["range_id"]) &&
                                        $_SESSION['admin_modules_data']["modules_list"][$val['modulkey']] && $registered_modules[$val['modulkey']]["msg_pre_warning"]
                                    )
                                        printf('<p><strong>' . _('Hinweis') . ':</strong> ' . $registered_modules[$val['modulkey']]["msg_pre_warning"] . '</p>',
                                            $modules->$getModuleXxExistingItems($_SESSION['admin_modules_data']["range_id"]));
                                }
                            }
                            ?>

                            <? if (isset($info['homepage'])) : ?>
                                <p>
                                    <strong><?= _('Weitere Informationen:') ?></strong>
                                    <a href="<?= htmlReady($info['homepage']) ?>"><?= htmlReady($info['homepage']) ?></a>
                                </p>
                            <? endif ?>

                            <? if (isset($warning)) : ?>
                                <p><strong><?= _('Hinweis') ?>:</strong> <?= formatReady($warning) ?></p>
                            <? endif ?>

                            <!-- helplink -->
                            <? if (isset($info['helplink'])) : ?>
                                <a class="helplink" href=" <?= formatReady($info['helplink']) ?> ">...mehr</a>
                            <? endif ?>

                        </div>
                    </div>
                <? } ?>
            </td>
        </tr>
    <?
    }
} ?>
</tbody>
<tfoot>
<tr>
    <td align="center" colspan="3">
        <?= Button::create(_('An- / Ausschalten'), 'uebernehmen') ?>
    </td>
</tr>
</tfoot>
</table>
</form>
