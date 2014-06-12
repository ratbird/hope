<?php
/**
 * Literaturübersicht von Stud.IP
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   Rasmus Fuhse <fuhse@data-quest.de>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Stud.IP
 * @since    3.1
 */

require_once 'app/controllers/authenticated_controller.php';
require_once('lib/classes/StudipLitList.class.php');

class LiteratureController extends AuthenticatedController
{
    /**
     * Before filter, set up the page by initializing the session and checking
     * all conditions.
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!Config::Get()->LITERATURE_ENABLE ) {
            throw new AccessDeniedException(_('Die Literaturübersicht ist nicht aktiviert.'));
        }

        $this->attributes['text'] = array('style' => 'width:98%');
        $this->attributes['textarea'] = array('style' => 'width:98%','rows'=>2);
        $this->attributes['select'] = array();
        $this->attributes['date'] = array();
        $this->attributes['combo'] = array('style' => 'width:45%; display: inline;');
        $this->attributes['lit_select'] = array('style' => 'font-size:8pt;width:100%');
        
        if (Request::get('admin_inst_id')) {
            $this->view = 'lit_inst';
        }

        if (Request::option('_range_id') == "self"){
            $this->_range_id = $GLOBALS['user']->id;
        } else if (Request::option('_range_id')){
            $this->_range_id = Request::option('_range_id');
        } else {
            $this->_range_id = $_SESSION['_lit_range'];
        }
        if (!$this->_range_id) {
            $this->_range_id = $GLOBALS['user']->id;
        }
        $_SESSION['_lit_range'] = $this->_range_id;
        
        /*      checkObject(); // do we have an open object?
        checkObjectModule('literature');
        object_set_visit_module('literature');/**/
    }

    /**
     * Displays a page for literature list administration.
     */
    public function edit_list_action()
    {
        require_once ('lib/classes/StudipLitListViewAdmin.class.php');
        require_once ('lib/classes/StudipLitClipBoard.class.php');
        include_once('lib/lit_import.inc.php');
        PageLayout::setHelpKeyword("Basis.LiteraturListen");
        PageLayout::setTitle(_("Verwaltung von Literaturlisten"));
        
        if (Request::option('list')  || Request::option('view') || Request::option('view_mode') || $this->_range_id != $GLOBALS['user']->id){
            if ($GLOBALS['perm']->have_perm('admin')) {
                include 'lib/admin_search.inc.php';

                if ($_SESSION['links_admin_data']['topkat'] == 'sem') {
                    Navigation::activateItem('/admin/course/literature');
                } else {
                    Navigation::activateItem('/admin/institute/literature');
                }
            } else {
                Navigation::activateItem('/course/literature/edit');
            }
            $this->_range_id = ($_SESSION['SessSemName'][1]) ? $_SESSION['SessSemName'][1] : $this->_range_id;
        } else {
            Navigation::activateItem('/tools/literature');
            closeObject();
        }

        $_the_treeview = new StudipLitListViewAdmin($this->_range_id);
        $_the_tree =& $_the_treeview->tree;

        PageLayout::setTitle($_the_tree->root_name . " - " . PageLayout::getTitle());

        include 'lib/include/admin_search_form.inc.php';
        
        //checking rights
        if (($_the_tree->range_type == "sem" && !$GLOBALS['perm']->have_studip_perm("tutor", $this->_range_id)) ||
            (($_the_tree->range_type == "inst" || $_the_tree->range_type == "fak") && !$GLOBALS['perm']->have_studip_perm("autor", $this->_range_id))){
                throw new AccessDeniedException(_('Keine Berechtigung für diese Literaturliste.'));
        }

        //Literaturlisten-Import
        do_lit_import();

        $_the_treeview->parseCommand();

        //always show existing lists
        $_the_treeview->open_ranges['root'] = true;
        //if there are no lists always open root element
        if (!$_the_tree->hasKids('root')){
            $_the_treeview->open_items['root'] = true;
        }
        $_the_clipboard = StudipLitClipBoard::GetInstance();
        $_the_clip_form =& $_the_clipboard->getFormObject();

        if ($_the_clip_form->isClicked("clip_ok")){
            $clip_cmd = explode("_",$_the_clip_form->getFormFieldValue("clip_cmd"));
            if ($clip_cmd[0] == "ins"){
                if (is_array($_the_clip_form->getFormFieldValue("clip_content"))){
                    $inserted = $_the_tree->insertElementBulk($_the_clip_form->getFormFieldValue("clip_content"), $clip_cmd[1]);
                    if ($inserted){
                        $_the_tree->init();
                        $_the_treeview->open_ranges[$clip_cmd[1]] = true;
                        PageLayout::postMessage(MessageBox::success(sprintf(_("%s Einträge aus Ihrer Merkliste wurden in <b>%s</b> eingetragen."),
                        $inserted, htmlReady($_the_tree->tree_data[$clip_cmd[1]]['name']))));
                    }
                } else {
                    PageLayout::postMessage(MessageBox::info(_("Sie haben keinen Eintrag in Ihrer Merkliste ausgewählt!")));
                }
            }
            $_the_clipboard->doClipCmd();
        }

        if ( ($this->lists = $_the_tree->getListIds()) && $_the_clipboard->getNumElements()){
            for ($i = 0; $i < count($this->lists); ++$i){
                $_the_clip_form->form_fields['clip_cmd']['options'][]
                = array('name' => my_substr(sprintf(_("In \"%s\" eintragen"), $_the_tree->tree_data[$this->lists[$i]]['name']),0,50),
                'value' => 'ins_' . $this->lists[$i]);
            }
        }

        $this->msg = $_the_clipboard->msg;
        if (is_array($_the_treeview->msg)){
            foreach ($_the_treeview->msg as $t_msg){
                if (!$this->msg || ($this->msg && (strpos($t_msg, $this->msg) === false))){
                    $this->msg .= $t_msg . "§";
                }
            }
        }
        
        $this->lists = $_the_tree->getKids('root');
        if ($this->lists) {
            $this->list_count['visible'] = 0;
            $this->list_count['visible_entries'] = 0;
            $this->list_count['invisible'] = 0;
            $this->list_count['invisible_entries'] = 0;
            for ($i = 0; $i < count($this->lists); ++$i){
                if ($_the_tree->tree_data[$this->lists[$i]]['visibility']){
                    ++$this->list_count['visible'];
                    $this->list_count['visible_entries'] += $_the_tree->getNumKids($this->lists[$i]);
                } else {
                    ++$this->list_count['invisible'];
                    $this->list_count['invisible_entries'] += $_the_tree->getNumKids($this->lists[$i]);
                }
            }
        }
        $this->treeview = $_the_treeview;
        $this->tree = $_the_tree;
        $this->clipboard = $_the_clipboard;
        $this->clip_form = $_the_clip_form;
    }
    
    /**
     * Displays print view of literature list
     */
    public function print_view_action()
    {
        PageLayout::removeStylesheet('style.css');
        PageLayout::addStylesheet('print.css'); // use special stylesheet for printing
        $_range_id = Request::option('_range_id');
        if ($_range_id != $GLOBALS['user']->id && !$GLOBALS['perm']->have_studip_perm('user',$_range_id)){
            throw new AccessDeniedException(_('Kein Zugriff auf diesen Bereich.'));
        }
        $_the_tree = TreeAbstract::GetInstance("StudipLitList", $_range_id);
        $this->title = sprintf(_("Literatur %s"), $_the_tree->root_name);
        $this->list = StudipLitList::GetFormattedListsByRange($_SESSION["SessionSeminar"], false, false);
    }
    
    /**
     * Displays page for literature search
     */
    public function search_action()
    {
        $GLOBALS['perm']->check("autor");
        PageLayout::setHelpKeyword("Basis.Literatursuche");
        PageLayout::setTitle(_("Literatursuche"));
        Navigation::activateItem('/search/literature');

        $_the_search = new StudipLitSearch();
        $_the_clipboard = StudipLitClipBoard::GetInstance();
        $_the_clip_form = $_the_clipboard->getFormObject();

        if (Request::quoted('change_start_result')){
            $_the_search->start_result = Request::quoted('change_start_result');
        }

        if ($_the_clip_form->isClicked("clip_ok")){
            $_the_clipboard->doClipCmd();
        }

        if ($_the_search->outer_form->isClicked("search")
            || ($_the_search->outer_form->isSended()
            && !$_the_search->outer_form->isClicked("reset")
            && !$_the_search->outer_form->isClicked("change")
            && !$_the_search->outer_form->isClicked("search_add")
            && !$_the_search->outer_form->isClicked("search_sub")
            && !$_the_search->outer_form->isChanged("search_plugin") //scheiss IE
        )) {
            $hits = $_the_search->doSearch();
            if(!$_the_search->search_plugin->getNumError()) {
                if($_the_search->getNumHits() == 0) {
                    $_msg .= "info§" . sprintf(_("Ihre Suche ergab %s Treffer."), $_the_search->getNumHits()) . "§";
                } else {
                    $_msg .= "msg§" . sprintf(_("Ihre Suche ergab %s Treffer."), $_the_search->getNumHits()) . "§";
                }
            }
            $_the_search->start_result = 1;
        }

        if (Request::option('cmd') == "add_to_clipboard"){
            $catalog_id = Request::option('catalog_id');
            if ($catalog_id{0} == "_"){
                $parts = explode("__", $catalog_id);
                if ( ($fields = $_SESSION[$parts[0]][$parts[1]]) ){
                    $cat_element = new StudipLitCatElement();
                    $cat_element->setValues($fields);
                    $cat_element->setValue("catalog_id", "new_entry");
                    $cat_element->setValue("user_id", "studip");
                    if ( ($existing_element = $cat_element->checkElement()) ){
                        $cat_element->setValue('catalog_id', $existing_element);
                    }
                    $cat_element->insertData();
                    $catalog_id = $cat_element->getValue("catalog_id");
                    $_SESSION[$parts[0]][$parts[1]]['catalog_id'] = $catalog_id;
                    unset($cat_element);
                }
            }
            $_the_clipboard->insertElement($catalog_id);
        }

        $_msg .= $_the_clipboard->msg;
        $_msg .= $_the_search->search_plugin->getError("msg");
        
        $this->msg = $_msg;
        $this->search = $_the_search;
        $this->clipboard = $_the_clipboard;
        $this->clip_form = $_the_clip_form;
    }
    
    /**
     * Displays page to add new or edit existing literature element
     */
    public function edit_element_action()
    {
        if (Request::option('cmd') == "new_entry"){
            $_catalog_id = "new_entry";
        } else {
            $_catalog_id = Request::option('_catalog_id', "new_entry");
        }
        if ($_catalog_id == "new_entry"){
            $title = _("Literatureintrag anlegen");
        } else {
            $title = _("Literatureintrag bearbeiten");
        }
        PageLayout::setTitle($title);
        Navigation::activateItem('/tools/literature');
        
        //dump data into db if $_catalog_id points to a search result
        if ($_catalog_id{0} == "_"){
            $parts = explode("__", $_catalog_id);
            if ( ($fields = $_SESSION[$parts[0]][$parts[1]]) ){
                $cat_element = new StudipLitCatElement();
                $cat_element->setValues($fields);
                $cat_element->setValue("catalog_id", "new_entry");
                $cat_element->setValue("user_id", "studip");
                if ( ($existing_element = $cat_element->checkElement()) ){
                    $cat_element->setValue('catalog_id', $existing_element);
                }
                $cat_element->insertData();
                $_catalog_id = $cat_element->getValue("catalog_id");
                $_SESSION[$parts[0]][$parts[1]]['catalog_id'] = $_catalog_id;
                unset($cat_element);
            }
        }

        if (Request::option('cmd') == 'clone_entry'){
            $_the_element = StudipLitCatElement::GetClonedElement($_catalog_id);
            if ($_the_element->isNewEntry()){
                $_msg = "msg§" . _("Der Eintrag wurde kopiert, Sie können die Daten jetzt ändern.") . "§";
                $_msg .= "info§" . _("Der kopierte Eintrag wurde noch nicht gespeichert.") . "§";
                //$old_cat_id = $_catalog_id;
                $_catalog_id = $_the_element->getValue('catalog_id');
            } else {
                $_msg = "error§" . _("Der Eintrag konnte nicht kopiert werden!.") . "§";
            }
        }

        if(!is_object($_the_element)){
            $_the_element = new StudipLitCatElement($_catalog_id, true);
        }
        $_the_form = $_the_element->getFormObject();
        $_the_clipboard = StudipLitClipBoard::GetInstance();
        $_the_clip_form = $_the_clipboard->getFormObject();

        if (isset($old_cat_id) && $_the_clipboard->isInClipboard($old_cat_id)){
            $_the_clipboard->deleteElement($old_cat_id);
            $_the_clipboard->insertElement($_catalog_id);
        }

        $_the_clip_form->form_fields['clip_cmd']['options'][] = array('name' => _("In Merkliste eintragen"), 'value' => 'ins');
        $_the_clip_form->form_fields['clip_cmd']['options'][] = array('name' => _("Markierten Eintrag bearbeiten"), 'value' => 'edit');

        if ($_the_form->IsClicked("reset") || Request::option('cmd') == "new_entry"){
            $_the_form->doFormReset();
        }

        if ($_the_form->IsClicked("delete") && $_catalog_id != "new_entry" && $_the_element->isChangeable()){
            if ($_the_element->reference_count){
                $_msg = "info§" . sprintf(_("Sie k&ouml;nnen diesen Eintrag nicht l&ouml;schen, da er noch in %s Literaturlisten referenziert wird."),$_the_element->reference_count) ."§";
            } else {
                $_msg = "info§" . _("Wollen Sie diesen Eintrag wirklich l&ouml;schen?") . "<br>"
                        .LinkButton::createAccept(_('Ja'), URLHelper::getURL('?cmd=delete_element&_catalog_id=' . $_catalog_id), array('title' =>  _('löschen')))
                        . "&nbsp;"
                        .LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('?_catalog_id=' . $_catalog_id), array('title' =>  _('abbrechen')))
                        . "§";
            }
        }

        if (Request::option('cmd') == "delete_element" && $_the_element->isChangeable() && !$_the_element->reference_count){
            $_the_element->deleteElement();
        }

        if (Request::option('cmd') == "in_clipboard" && $_catalog_id != "new_entry"){
            $_the_clipboard->insertElement($_catalog_id);
        }

        if (Request::option('cmd') == "check_entry"){
            $lit_plugin_value = $_the_element->getValue('lit_plugin');
            $content = "<div style=\"font-size:70%\"<b>" ._("Verfügbarkeit in externen Katalogen:") . "</b><br>";
            foreach (StudipLitSearch::CheckZ3950($_the_element->getValue('accession_number')) as $plugin_name => $ret){
                $content .= "<b>&nbsp;" . htmlReady(StudipLitSearch::GetPluginDisplayName($plugin_name))."&nbsp;</b>";
                if ($ret['found']){
                    $content .= _("gefunden") . "&nbsp;";
                    $_the_element->setValue('lit_plugin', $plugin_name);
                    if (($link = $_the_element->getValue("external_link"))){
                        $content.= formatReady(" [" . $_the_element->getValue("lit_plugin_display_name"). "]" . $link);
                    } else {
                        $content .= _("(Kein Link zum Katalog vorhanden.)");
                    }
                } elseif (count($ret['error'])){
                    $content .= '<span style="color:red;">' . htmlReady($ret['error'][0]['msg']) . '</span>';
                } else {
                    $content .= _("<u>nicht</u> gefunden") . "&nbsp;";
                }
                $content .= "<br>";
            }
            $content .= "</div>";
            $_the_element->setValue('lit_plugin', $lit_plugin_value);
            $_msg = "info§" . $content . "§";
        }

        if ($_the_form->IsClicked("send")){
            $_the_element->setValuesFromForm();
            if ($_the_element->checkValues()){
                $_the_element->insertData();
            }
        }

        if ($_the_clip_form->isClicked("clip_ok")){
            if ($_the_clip_form->getFormFieldValue("clip_cmd") == "ins" && $_catalog_id != "new_entry"){
                $_the_clipboard->insertElement($_catalog_id);
            }
            if ($_the_clip_form->getFormFieldValue("clip_cmd") == "edit"){
                $marked = $_the_clip_form->getFormFieldValue("clip_content");
                if (count($marked) && $marked[0]){
                    $_the_element->getElementData($marked[0]);
                }
            }
            $_the_clipboard->doClipCmd();
        }

        $_catalog_id = $_the_element->getValue("catalog_id");

        if (!$_the_element->isChangeable())
            PageLayout::postMessage(MessageBox::info(_('Sie haben diesen Eintrag nicht selbst vorgenommen, und dürfen ihn daher nicht verändern! Wenn Sie mit diesem Eintrag arbeiten wollen, können Sie sich eine persönliche Kopie erstellen.')));
        $_msg .= $_the_element->msg;
        $_msg .= $_the_clipboard->msg;
        
        $this->msg = $_msg;
        $this->catalog_id = $_catalog_id;
        $this->element = $_the_element;
        $this->treeview = $_the_treeview;
        $this->tree = $_the_tree;
        $this->clipboard = $_the_clipboard;
        $this->clip_form = $_the_clip_form;
        $this->form = $_the_form;
    }
}
