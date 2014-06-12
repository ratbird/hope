<? use Studip\Button, Studip\LinkButton; ?>
<? if ($msg) : ?>
    <table width="99%" border="0" cellpadding="2" cellspacing="0">
        <?=parse_msg ($msg,"§","blank",1,false)?>
    </table>
<? endif ?>
<?
$class_changer = new CssClassSwitcher();
$attributes['search_plugin'] = $attributes['text'];
$attributes['search_plugin']['onChange'] = 'document.' . $search->outer_form->form_name . '.submit()';
?>
<b><?=_("Ausgew&auml;hlter Katalog:") ?></b><br>
<?= $search->search_plugin->description ?><br>
<br>
<table width="99%" border="0" cellpadding="2" cellspacing="0" style="font-size:10pt">
<tr>
<?=$search->outer_form->getFormStart();?>
</tr>
<tr><td <?=$class_changer->getFullClass()?> width="30%">
<?=$search->outer_form->getFormFieldCaption('search_plugin') ;?>
</td><td <?=$class_changer->getFullClass()?> width="40%" align="right">
<?=$search->outer_form->getFormField('search_plugin',$attributes['search_plugin']). $search->outer_form->getFormFieldInfo('search_plugin',$attributes['button']);?>
</td><td <?=$class_changer->getFullClass()?> width="30%" align="center">
<?=$search->outer_form->getFormButton('change');?>
</td>
</tr>
<?
$class_changer->switchClass();
for ($i = 0 ; $i < $search->term_count; ++$i){
    if ($i > 0){
        echo "<tr><td " . $class_changer->getFullClass() ." width=\"30%\">";
        echo $search->inner_form->getFormFieldCaption("search_operator_" . $i);
        echo "</td><td " . $class_changer->getFullClass() ." width=\"40%\" align=\"right\">";
        echo $search->inner_form->getFormField("search_operator_" . $i, $attributes['radio']);
        echo "&nbsp;";
        echo $search->inner_form->getFormFieldInfo("search_operator_" . $i);
        echo "</td><td " . $class_changer->getFullClass() ." width=\"30%\">&nbsp;</td></tr>";
    }
    echo "<tr><td " . $class_changer->getFullClass() ." width=\"30%\">";
    echo $search->inner_form->getFormFieldCaption("search_field_" . $i);
    echo "</td><td " . $class_changer->getFullClass() ." width=\"40%\" align=\"right\">";
    echo $search->inner_form->getFormField("search_field_" . $i, $attributes['text']);
    echo $search->inner_form->getFormFieldInfo("search_field_" . $i);
    echo "</td><td " . $class_changer->getFullClass() ." width=\"30%\">&nbsp;</td></tr>";
    echo "<tr><td " . $class_changer->getFullClass() ." width=\"30%\">";
    echo $search->inner_form->getFormFieldCaption("search_truncate_" . $i);
    echo "</td><td " . $class_changer->getFullClass() ." width=\"40%\" align=\"right\">";
    echo $search->inner_form->getFormField("search_truncate_" . $i, $attributes['text']);
    echo $search->inner_form->getFormFieldInfo("search_truncate_" . $i);
    echo "</td><td " . $class_changer->getFullClass() ." width=\"30%\">&nbsp;</td></tr>";
    echo "<tr><td " . $class_changer->getFullClass() ." width=\"30%\">";
    echo $search->inner_form->getFormFieldCaption("search_term_" . $i);
    echo "</td><td " . $class_changer->getFullClass() ." width=\"40%\" align=\"right\">";
    echo $search->inner_form->getFormField("search_term_" . $i, $attributes['text']);
    echo $search->inner_form->getFormFieldInfo("search_term_" . $i);
    echo "</td><td " . $class_changer->getFullClass() ." width=\"30%\" align=\"center\">";
    if ($i == $search->term_count - 1){
        echo $search->outer_form->getFormButton('search_add');
        if ($search->term_count > 1){
            echo "&nbsp;" . $search->outer_form->getFormButton('search_sub');
        }
    } else {
        echo "&nbsp;";
        $class_changer->switchClass();
    }
    echo "</td></tr>";
}
?>

<tr>
<td colspan="3" class="table_footer" align="center">&nbsp;
<?=$search->outer_form->getFormButton('search',$attributes['button']);?>
&nbsp;
<?=$search->outer_form->getFormButton('reset',$attributes['button']);?>
</td></tr>
</table>
<?=$search->outer_form->getFormEnd();?>
&nbsp;<br>
<?
if (($num_hits = $search->getNumHits())){
    if ($search->start_result < 1 || $search->start_result > $num_hits){
        $search->start_result = 1;
    }
    $end_result = (($search->start_result + 5 > $num_hits) ? $num_hits : $search->start_result + 4);
?>
<table width="99%" border="0" cellpadding="2" cellspacing="0" style="font-size:10pt">
<tr>
<td class="table_footer" align="left">
<?printf(_("%s Treffer in Ihrem Suchergebnis."), $num_hits);?>
</td><td class="table_footer" align="right">
<?
echo _("Anzeige: ");
if ($search->start_result > 1) {
    $link=URLHelper::getLink('',array('change_start_result'=>($search->start_result - 5)));
    echo "<a href=\"$link\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_2left.png\" hspace=\"3\" border=\"0\"></a>";
} else {
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" width=\"17\" height=\"18\" border=\"0\">";
}
echo $search->start_result . " - " . $end_result;
if ($search->start_result + 4 < $num_hits) {
    $link=URLHelper::getLink('',array('change_start_result'=>($search->start_result + 5)));
    echo "<a href=\"$link\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_2right.png\" hspace=\"3\" border=\"0\"></a>";
} else {
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" width=\"17\" height=\"18\" border=\"0\">";
}
?>
</td></tr>
<tr><td colspan="2">
<?
for ($i = $search->start_result; $i <= $end_result; ++$i){
    $element = $search->getSearchResult($i);
    if ($element){
        echo "\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr>";
        $link=URLHelper::getLink('',array('cmd'=>'add_to_clipboard','catalog_id'=>$element->getValue("catalog_id")));
        if ($clipboard->isInClipboard($element->getValue("catalog_id"))) {
            $addon="<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/exclaim.png\" hspace=\"4\"  border=\"0\" " .
                tooltip(_("Dieser Eintrag ist bereits in Ihrer Merkliste")) . ">";
        } else {
            $addon="<a href=\"$link\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/exclaim.png\" hspace=\"4\"  border=\"0\" " .
                tooltip(_("Eintrag in Merkliste aufnehmen")) . "></a>";
        }
        printhead(0,0,false,"open",true,"<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/literature.png\" border=\"0\" align=\"bottom\">",
              htmlReady(my_substr($element->getShortName(),0,85)),$addon);
        echo "\n</tr></table>";
        $content = "";
        $link=URLHelper::getURL('dispatch.php/literature/edit_element.php',array('_catalog_id'=>$element->getValue("catalog_id")));
        $edit = LinkButton::create(_("Details"), $link);
        $link=URLHelper::getURL('',array("cmd"=>"add_to_clipboard","catalog_id"=>$element->getValue("catalog_id")));
        if (!$clipboard->isInClipboard($element->getValue("catalog_id"))){
            $edit .= LinkButton::create(_("In Merkliste >"), $link);
        }
        echo "\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">";
        $content .= "<b>" . _("Titel:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("dc_title"),true,true) . "<br>";
        $content .= "<b>" . _("Autor; weitere Beteiligte:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("authors"),true,true) . "<br>";
        $content .= "<b>" . _("Erschienen:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("published"),true,true) . "<br>";
        $content .= "<b>" . _("Identifikation:") ."</b>&nbsp;&nbsp;" . formatLinks($element->getValue("dc_identifier")) . "<br>";
        $content .= "<b>" . _("Schlagw&ouml;rter:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("dc_subject"),true,true) . "<br>";
        if ($element->getValue("lit_plugin") != "Studip"){
            $content .= "<b>" . _("Externer Link:") ."</b>&nbsp;&nbsp;";
            if (($link = $element->getValue("external_link"))){
                $content.= formatReady(" [" . $element->getValue("lit_plugin_display_name"). "]" . $link);
            } else {
                $content .= _("(Kein Link zum Katalog vorhanden.)");
            }
            $content .= "<br>";
        }
        printcontent(0,0,$content,$edit);
        echo "\n</table>";
    }
}
?>
<? if (! $num_hits) : ?>
    <?=Assets::img('icons/16/black/info.png');?>&nbsp;
    <?=_("Es liegt kein Suchergebnis vor.").'<br>'?>
<? endif ?>
<br>
</td></tr>
<tr>
<td class="table_footer" align="left">
<?printf(_("%s Treffer in Ihrem Suchergebnis."), $num_hits);?>
</td><td class="table_footer" align="right">
<?
echo _("Anzeige: ");
if ($search->start_result > 1) {
    $link=URLHelper::getLink('',array('change_start_result'=>($search->start_result - 5)));
    echo "<a href=\"$link\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2left.png\" hspace=\"3\" border=\"0\"></a>";
} else {
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" width=\"17\" height=\"18\" border=\"0\">";
}
echo $search->start_result . " - " . $end_result;
if ($search->start_result + 4 < $num_hits) {
    $link=URLHelper::getLink('',array('change_start_result'=>($search->start_result + 5)));
    echo "<a href=\"$link\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_2right.png\" hspace=\"3\" border=\"0\"></a>";
} else {
    echo "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumleer.gif\" width=\"17\" height=\"18\" border=\"0\">";
}
?>
</td></tr>
</table>

<?
}
?>
</td></tr>
</table>

<?php
Helpbar::get()->load('literature/search');
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/literature-sidebar.png"));
$widget = new ActionsWidget();
$widget->addLink(_("Literaturlisten bearbeiten"), URLHelper::getLink('dispatch.php/literature/edit_list?_range_id=self'), 'icons/16/black/literature.png');
$widget->addLink(_('Neue Literatur anlegen'), URLHelper::getLink('dispatch.php/literature/edit_element?_range_id=new_entry'), 'icons/16/black/add/literature.png');
$sidebar->addWidget($widget);
ob_start();
?>
<?=$clip_form->getFormStart(URLHelper::getLink('?_catalog_id='.$catalog_id)); ?>
<?=$clip_form->getFormField("clip_content", array_merge(array('size' => $clipboard->getNumElements()),(array) $attributes['lit_select']))?>
<?=$clip_form->getFormField("clip_cmd", $attributes['lit_select'])?>
<div align="center">
<?=$clip_form->getFormButton("clip_ok",array('style'=>'vertical-align:middle;margin:3px;'))?>
</div>
<?=$clip_form->getFormEnd(); ?>
<?
$content = ob_get_clean();
$widget = new SidebarWidget();
$widget->setTitle(_('Merkliste'));
$widget->addElement(new WidgetElement($content));
$sidebar->addWidget($widget);