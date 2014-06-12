<? if ($msg) : ?>
    <table width="99%" border="0" cellpadding="2" cellspacing="0">
        <?=parse_msg ($msg,"§","blank",1,false)?>
    </table>
<? endif ?>
<? if (! $lists) : ?>
    <?= _('Sie haben noch keine Listen angelegt.') ?><br>
    <br>
<? else : ?>
    <?=Assets::img('icons/16/black/visibility-visible.png');?>&nbsp;
    <?=sprintf(_("%s öffentlich sichtbare Listen, insgesamt %s Einträge"),$list_count['visible'],$list_count['visible_entries']).'<br>'?>
    <?=Assets::img('icons/16/black/visibility-invisible.png')?>&nbsp;
    <?=sprintf(_("%s unsichtbare Listen, insgesamt %s Einträge"),$list_count['invisible'],$list_count['invisible_entries']).'<br>'?>
    <br>
<? endif ?>
<? $treeview->showTree(); ?>
<br>
<? // Literaturlisten-Import
    print_lit_import_dlg();
?>
<?php
Helpbar::get()->load('literature/list');
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/literature-sidebar.png"));
$widget = new ActionsWidget();
$widget->addLink(_('Literatur suchen'), URLHelper::getLink('dispatch.php/literature/search'), 'icons/16/black/search.png');
$widget->addLink(_('Neue Literatur anlegen'), URLHelper::getLink('dispatch.php/literature/edit_element?_range_id=new_entry'), 'icons/16/black/add/literature.png');
$sidebar->addWidget($widget);
ob_start();
?>
<?=$clip_form->getFormStart(URLHelper::getLink($treeview->getSelf())); ?>
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