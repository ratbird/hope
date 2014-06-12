<? use Studip\Button, Studip\LinkButton ?>
<? if ($msg) : ?>
<table width="99%" border="0" cellpadding="2" cellspacing="0">
    <?=parse_msg ($msg,"§","blank",1,false)?>
</table>
<? endif ?>
<?=$form->getFormStart(URLHelper::getLink('?_catalog_id='.$catalog_id), array('class' => 'studip_form'))?>
    <fieldset>
        <legend><?= ($element->isNewEntry()) ? _("Neuer Eintrag (noch nicht gespeichert)") : _('Eintrag') ?></legend>
        <? if (! $element->isNewEntry()) : ?>
            <?= sprintf(_("Anzahl an Referenzen für diesen Eintrag: %s"), (int)$element->reference_count) ?><br>
            <b><?= ($element->getValue("user_id") == "studip") ? _("Systemeintrag:") : _("Eingetragen von:") ?></b><br>
            <?= ($element->getValue("user_id") == "studip") ? _("Dies ist ein vom System generierter Eintrag.") : get_fullname($element->getValue("user_id"),'full',true) ?><br>
            <b><?= _("Letzte Änderung am:")?></b><br>
            <?= strftime("%d.%m.%Y",$element->getValue("chdate")) ?><br>
        <? endif ?>
        <p style="font-size:-1">
            <?= sprintf(_('Alle mit einem Sternchen %s markierten Felder müssen ausgefüllt werden.'),'<span style="font-size:1.5em;color:red;font-weigth:bold;">*</span>')?>
        </p>
        <table width="100%" border="0" cellpadding="2" cellspacing="0">
        <? foreach ($element->fields as $field_name => $field_detail) : ?>
            <? if ($field_detail['caption']) : ?>
            <tr><td width="40%">
                <label for="<?= $field_name ?>" class="caption">
                <?= $field_detail['caption'] ?>
                <? if ($field_detail['mandatory']) : ?>
                    <span style="font-size:1.5em;color:red;font-weight:bold;">*</span>
                <? endif ?>
                <?= $form->getFormFieldInfo($field_name) ?>
                </label>
            </td><td>
                <?
                $element_attributes = $attributes[$form->form_fields[$field_name]['type']];
                if (!$element->isChangeable()){
                    $attributes['readonly'] = 'readonly';
                    $attributes['disabled'] = 'disabled';
                }
                ?>
                <?= $form->getFormField($field_name,$element_attributes) ?>
                <? if ($field_name == "lit_plugin") : ?>
                    &nbsp;&nbsp;<span style="font-size:10pt;">
                    <?= (($link = $element->getValue("external_link"))) ? formatReady("=) [Link zum Katalog]" . $link) : _("(Kein Link zum Katalog vorhanden.)") ?>
                    </span>
                <? endif ?>
            </td></tr>
            <? endif ?>
        <? endforeach ?>
        </table>
        <div class="submit_wrapper">
            <?= CSRFProtection::tokenTag() ?>
            <? if ($element->isChangeable()) : ?>
                <?= $form->getFormButton("send") .  $form->getFormButton("delete") . $form->getFormButton("reset") ?>
            <? elseif ($catalog_id != "new_entry") : ?>
                <?= LinkButton::create(_('Kopie erstellen'), URLHelper::getURL('?cmd=clone_entry&_catalog_id='.$catalog_id), array('title' => _("Eine Kopie dieses Eintrages anlegen"))) ?>
            <? endif ?>
            <img src="<?= $GLOBALS['ASSETS_URL']."images/blank.gif"?>" height="28" width="15" border="0">
            <?= LinkButton::create(_('Neu anlegen'), URLHelper::getURL('?cmd=new_entry'), array('title' =>  _("Neuen Eintrag anlegen"))) ?>
            <? if ($catalog_id != "new_entry") : ?>
                <img src="<?= $GLOBALS['ASSETS_URL']."images/blank.gif" ?>" height="28" width="15" border="0">
                <?= LinkButton::create(_('Verfügbarkeit'), URLHelper::getURL('?cmd=check_entry&_catalog_id='.$catalog_id), array('title' =>  _("Verfügbarkeit überprüfen"))) ?>
            <? endif ?>
            <? if ($catalog_id != "new_entry" && !$clipboard->isInClipboard($catalog_id)) : ?>
                <img src="<?= $GLOBALS['ASSETS_URL']."images/blank.gif" ?>" height="28" width="15" border="0">
                <?= LinkButton::create(_('Merkliste'), URLHelper::getURL('?cmd=in_clipboard&_catalog_id='.$catalog_id), array('title' =>  _("Eintrag in Merkliste aufnehmen"))) ?>
            <? endif ?>
        </div>
    </fieldset>
<?php
echo $form->getFormEnd();

Helpbar::get()->load('literature/edit_element');
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/literature-sidebar.png"));
$widget = new ActionsWidget();
$widget->addLink(_('Literatur suchen'), URLHelper::getLink('dispatch.php/literature/search'), 'icons/16/black/search.png');
$widget->addLink(_('Literaturlisten bearbeiten'), URLHelper::getLink('dispatch.php/literature/edit_list'), 'icons/16/black/add/literature.png');
$sidebar->addWidget($widget);
ob_start();
?>
<?=$clip_form->getFormStart(URLHelper::getLink('?_catalog_id='.$catalog_id)); ?>
<?=$clip_form->getFormField("clip_content", array_merge(array('size' => $clipboard->getNumElements()),(array) $attributes['lit_select']))?>
<?=$clip_form->getFormField("clip_cmd", $attributes['lit_select'])?>
<div align="center">
<?=$clip_form->getFormButton("clip_ok",array('style'=>'vertical-align:middle;margin:3px;'))?>
</div>
<?= $clip_form->getFormEnd(); ?>
<?
$content = ob_get_clean();
$widget = new SidebarWidget();
$widget->setTitle(_('Merkliste'));
$widget->addElement(new WidgetElement($content));
$sidebar->addWidget($widget);