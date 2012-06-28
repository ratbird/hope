<?php
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
require_once ("lib/visual.inc.php");
require_once ("lib/functions.php");
require_once ("lib/dates.inc.php");
require_once ("lib/classes/StudipStmSearch.class.php");
require_once ("lib/classes/StudipStmInstanceTreeViewSimple.class.php");


class StmBrowse {
    
    var $sem_browse_data;
    var $persistent_fields = array("level","cmd","start_item_id","show_class","group_by","search_result","default_sem","sem_status","show_entries","sset");
    var $search_obj;
    var $show_result;
    var $seminar_id;
    var $group_by_fields = array();
    var $target_url;
    var $target_id;

    function StmBrowse($sem_browse_data_init = array()){
               
        $sem_browse_data_init['level'] = 'ev';
        
        $this->group_by_fields = array( array('name' => _("Semester"), 'group_field' => 'beginn'),
                                        array('name' => _("Empf. Studiensemester"), 'group_field' => 'recommed')
                                        );

        if (!$_SESSION['sem_browse_data']){
            $_SESSION['sem_browse_data'] = $sem_browse_data_init;
        }
        $this->sem_browse_data =& $_SESSION['sem_browse_data'];
        $level_change = isset($_REQUEST['start_item_id']);
        for ($i = 0; $i < count($this->persistent_fields); ++$i){
            if (isset($_REQUEST[$this->persistent_fields[$i]])){
            $this->sem_browse_data[$this->persistent_fields[$i]] = $_REQUEST[$this->persistent_fields[$i]];
            }
        }
        
        $this->search_obj = new StudipStmSearch("search_stm", false);
        if ($this->search_obj->search_button_clicked){
            $this->sem_browse_data["start_item_id"] = $this->search_obj->form->getFormFieldValue("scope_choose");
        }
        
        if (isset($_REQUEST['keep_result_set']) || $this->sem_browse_data['sset'] || (count($this->sem_browse_data['search_result']) && $this->sem_browse_data['show_entries'])){
            $this->show_result = true;
        }
        
        /*if ($this->sem_browse_data['cmd'] == "xts"){
            $this->sem_browse_data['level'] = "f";
            if($this->search_obj->new_search_button_clicked){
                $this->show_result = false;
                $this->sem_browse_data['sset'] = false;
                $this->sem_browse_data['search_result'] = array();
            }
        }
        
        if ($this->sem_browse_data['cmd'] == "qs"){
            $this->sem_browse_data['default_sem'] = "all";
        }
        
        if($this->sem_browse_data["default_sem"] != 'all'){
            $this->sem_number[0] = $this->sem_browse_data["default_sem"];
        } else {
            $this->sem_number = false;
        }
        */
        if (!$this->sem_browse_data['start_item_id']){
            $this->sem_browse_data['start_item_id'] = "root";
        }
            
        $this->stm_tree = new StudipStmInstanceTreeViewSimple($this->sem_browse_data["start_item_id"], $this->seminar_id);
        $this->sem_browse_data['cmd'] = "qs";
        if ($_REQUEST['cmd'] != "show_stm_tree" && $level_change && !$this->search_obj->search_button_clicked ){
            $this->get_stm_range($this->sem_browse_data["start_item_id"], false);
            $this->show_result = true;
            $this->sem_browse_data['show_entries'] = "level";
            $this->sem_browse_data['sset'] = false;
        }
        
        if ($this->search_obj->search_button_clicked && !$this->search_obj->new_search_button_clicked){
            $this->search_obj->doSearch();
            if ($this->search_obj->found_rows){
                $this->sem_browse_data['search_result'] = $this->search_obj->search_result->getRows("stm_instance_id");
            } else {
                $this->sem_browse_data['search_result'] = array();
            }
            $this->show_result = true;
            $this->sem_browse_data['show_entries'] = false;
            $this->sem_browse_data['sset'] = true;
        }
        
        if ($_REQUEST['cmd'] == "show_stm_tree"){
            $tmp = explode("_",$_REQUEST['item_id']);
            $this->get_stm_range($tmp[0],isset($tmp[1]));
            $this->show_result = true;
            $this->sem_browse_data['show_entries'] = (isset($tmp[1])) ? "sublevels" : "level";
            $this->sem_browse_data['sset'] = false;
        }
        
        if ($_REQUEST['do_show_class'] == 'mod'){
            $this->get_stm_range('root', true);
            $this->sem_browse_data['sset'] = true;
            $this->show_result = true;
        }
    }
    
    function get_stm_range($item_id, $with_kids){
        $stm_ids = $this->stm_tree->tree->getStmIds($item_id,$with_kids);
        if (is_array($stm_ids)){
            $this->sem_browse_data['search_result'] = $stm_ids;
        } else {
            $this->sem_browse_data['search_result'] = array();
        }
    }
    
        
    function print_qs(){
         //Quicksort Formular... fuer die eiligen oder die DAUs....
        echo "<table border=\"0\" align=\"center\" cellspacing=0 cellpadding=0 width = \"99%\">\n";
        echo $this->search_obj->getFormStart(URLHelper::getLink("?send=yes"));
        echo "<tr><td height=\"40\" class=\"steel1\" align=\"center\" valign=\"middle\" ><font size=\"-1\">";
        echo _("Schnellsuche:") . "&nbsp;";
        echo $this->search_obj->getSearchField("qs_choose",array('style' => 'vertical-align:middle;font-size:9pt;'));
        $this->search_obj->stm_tree =& $this->stm_tree->tree; 
        if ($this->stm_tree->start_item_id != 'root'){
            $this->search_obj->search_scopes[] = $this->stm_tree->start_item_id;
        }
        echo "&nbsp;" . _("in:") . "&nbsp;" . $this->search_obj->getSearchField("scope_choose",array('style' => 'vertical-align:middle;font-size:9pt;'),$this->stm_tree->start_item_id);
        echo "\n<input type=\"hidden\" name=\"level\" value=\"vv\">";
        echo "&nbsp;";
        
        echo $this->search_obj->getSearchField("quick_search",array( 'style' => 'vertical-align:middle;font-size:9pt;','size' => 20));
        echo $this->search_obj->getSearchButton(array('style' => 'vertical-align:middle'));
        echo "</td></tr>";
        echo $this->search_obj->getFormEnd();
        echo "</table>\n";
    }
    
    function print_xts(){
        $this->search_obj->attributes_default = array('style' => 'width:100%;font-size:10pt;');
        $this->search_obj->search_fields['type']['size'] = 40 ;
        echo "<table border=\"0\" align=\"center\" cellspacing=0 cellpadding=2 width = \"99%\">\n";
        echo $this->search_obj->getFormStart(URLHelper::getLink("?send=yes"));
        echo "<tr><td class=\"steel1\" align=\"right\" width=\"15%\">" . _("Titel:") . " </td>";
        echo "<td class=\"steel1\" align=\"left\" width=\"35%\">";
        echo $this->search_obj->getSearchField("title");
        echo "</td><td class=\"steel1\" align=\"right\" width=\"15%\">" . _("Typ:") . "</td><td class=\"steel1\" align=\"left\" width=\"35%\">";
        echo $this->search_obj->getSearchField("type",array('style' => 'width:*;font-size:10pt;'));
        echo "</td></tr>\n";
        echo "<tr><td class=\"steel1\" align=\"right\" width=\"15%\">" . _("Untertitel:") . " </td>";
        echo "<td  class=\"steel1\" align=\"left\" width=\"35%\">";
        echo $this->search_obj->getSearchField("sub_title");
        echo "</td><td class=\"steel1\" align=\"right\" width=\"15%\">" . _("Semester:") . " </td>";
        echo "<td class=\"steel1\" align=\"left\" width=\"35%\">";
        echo $this->search_obj->getSearchField("sem",array('style' => 'width:*;font-size:10pt;'),$this->sem_browse_data['default_sem']);
        echo "</td></tr>";
        echo "<tr><td class=\"steel1\" align=\"right\" width=\"15%\">" . _("Kommentar:") . " </td>";
        echo "<td class=\"steel1\" align=\"left\" width=\"35%\">";
        echo $this->search_obj->getSearchField("comment");
        echo "</td><td class=\"steel1\" align=\"right\" width=\"15%\">&nbsp;</td><td class=\"steel1\" align=\"left\" width=\"35%\">&nbsp; </td></tr>\n";
        echo "<tr><td class=\"steel1\" align=\"right\" width=\"15%\">" . _("DozentIn:") . " </td>";
        echo "<td class=\"steel1\" align=\"left\" width=\"35%\">";
        echo $this->search_obj->getSearchField("lecturer");
        echo "</td><td class=\"steel1\" align=\"right\" width=\"15%\">" . _("Verkn&uuml;pfung:") . " </td>";
        echo "<td class=\"steel1\" align=\"left\" width=\"35%\">";
        echo $this->search_obj->getSearchField("combination",array('style' => 'width:*;font-size:10pt;'));
        echo "</td></tr>\n";
        if ($this->show_class()) {
            echo "<tr><td class=\"steel1\" align=\"right\" width=\"15%\">" . _("Bereich:") . " </td>";
            echo "<td class=\"steel1\" align=\"left\" width\"35%\">";
            echo $this->search_obj->getSearchField("scope");
            echo "</td><td class=\"steel1\" colspan=\"2\">&nbsp</td></tr>";
        }
        echo "<tr><td class=\"steel1\" align=\"center\" colspan=\"4\">";
        echo $this->search_obj->getSearchButton();
        echo "&nbsp;";
        echo $this->search_obj->getNewSearchButton();
        echo "&nbsp</td></tr>\n";
        echo $this->search_obj->getFormEnd();
        echo "</table>\n";
    }
    
    function do_output(){
        if ($this->sem_browse_data['cmd'] == "xts"){
            $this->print_xts();
        } else {
            $this->print_qs();
        }
        $this->print_level();
        if ($this->show_result){
            $this->print_result();
        }
    }
        
    function print_level(){
        ob_start();
        global $_language_path;
        echo "\n<table border=\"0\" align=\"center\" cellspacing=0 cellpadding=0 width = \"99%\">\n";
        if ($this->sem_browse_data['level'] == "f"){
            echo "\n<tr><td align=\"center\" class=\"steelgraulight\" height=\"40\" valign=\"middle\"><div style=\"margin-top:10px;margin-bottom:10px;\"><font size=\"-1\">";
            if (($this->show_result && count($this->sem_browse_data['search_result'])) || $this->sem_browse_data['cmd'] == "xts") {
                printf(_("Suche im %sModulverzeichnis%s"),"<a href=\"".URLHelper::getLink('?level=ev&cmd=qs&sset=0')."\">","</a>");
            } else {
                printf ("<table align=\"center\" cellspacing=\"10\">
                        <tr>
                        <td nowrap align=\"center\">
                        <a href=\"".URLHelper::getLink('?level=ev&cmd=qs&sset=0')."\">
                        <b>%s</b>
                        <br><br>
                        <img src=\"{$GLOBALS['ASSETS_URL']}images/institute.jpg\" %s border=\"0\">
                        </a></td>", _("Suche im Modulverzeichnis"), $_language_path, tooltip(_("Suche im Einrichtungsverzeichnis")));
                printf ("</tr></table>");
            }
            echo "</font></div>"; 
        } else {
            echo "\n<tr><td align=\"center\">";
            $this->stm_tree->show_entries = $this->sem_browse_data['show_entries'];
            $this->stm_tree->showStmInstanceTree();
        }   
        echo "</td></tr>\n</table>";
        ob_end_flush();
    }
    
    function print_result(){
        ob_start();
        global $_fullname_sql,$SEM_TYPE,$SEM_CLASS;
        
        if (is_array($this->sem_browse_data['search_result']) && count($this->sem_browse_data['search_result']) && strlen($this->sem_browse_data['search_result'][0]) == 32) {
            $constraint = "1";
            if($this->sem_browse_data["start_item_id"] != 'root' && !$this->sem_browse_data['sset']){
                list($abschl, $stg) = explode('-', $this->sem_browse_data["start_item_id"]);
                if($abschl) $constraint .= " AND sam.abschl='$abschl' ";
                if($stg) $constraint .= " AND sam.stg='$stg' ";
                
            }
            $query = ("SELECT stm_instances.*, stm_instances_text.*,CONCAT_WS('-',sam.recommed, sam.abschl,sam.stg) as recommed, semester_data.name as sem_name, semester_data.beginn
                FROM stm_instances INNER JOIN semester_data USING(semester_id)
                INNER JOIN stm_instances_text ON stm_instances.stm_instance_id =stm_instances_text.stm_instance_id AND stm_instances_text.lang_id='".LANGUAGE_ID."'
                INNER JOIN stm_abstract_assign sam ON stm_instances.stm_abstr_id=sam.stm_abstr_id AND $constraint
                WHERE stm_instances.stm_instance_id IN('" . join("','", $this->sem_browse_data['search_result']) . "') ORDER BY title");
            $db = new DB_Seminar($query);
            $snap = new DbSnapShot($db);
            $group_field = $this->group_by_fields[$this->sem_browse_data['group_by']]['group_field'];
            $data_fields[0] = "stm_instance_id";
            $data_fields[1] = "sem_name";
            $group_by_data = $snap->getGroupedResult($group_field, $data_fields);
            echo "\n" . '<a name="result"></a>';
            echo "\n<table border=\"0\" align=\"center\" cellspacing=0 cellpadding=2 width = \"99%\">\n";
            echo "\n<tr><td class=\"steelgraulight\" colspan=\"2\"><div style=\"margin-top:10px;margin-bottom:10px;\"><font size=\"-1\"><b>&nbsp;"
                . sprintf(_(" %s Module gefunden %s, Gruppierung: %s"),count($snap->getDistinctRows('stm_instance_id')),
                (($this->sem_browse_data['sset']) ? _("(Suchergebnis)") : ""),
                $this->group_by_fields[$this->sem_browse_data['group_by']]['name']) 
                . "</b></font></div></td></tr>";
            
            
            switch ($this->sem_browse_data["group_by"]){
                    case 0:
                    krsort($group_by_data, SORT_NUMERIC);
                    break;
                    case 1:
                    ksort($group_by_data, SORT_STRING);
                    break;
                    default:
                            
            }
            
            foreach ($group_by_data as $group_field => $stm_ids){
                echo "\n<tr><td class=\"steelkante\" colspan=\"2\"><font size=-1><b>";
                switch ($this->sem_browse_data["group_by"]){
                    case 0:
                    echo key($stm_ids['sem_name']);
                    break;
                    case 1:
                    list($stdsem, $abschl, $stg) = explode('-', $group_field);
                    echo (!$stdsem ? _("kein Studiensemester") : $stdsem . '. ' . _("Studiensemester"));
                    echo '&nbsp;(' . $this->stm_tree->tree->getValue($abschl,'name') . ' - ' . $this->stm_tree->tree->getValue("$abschl-$stg",'name')  . ')';
                    break;
                    default:
                    echo htmlReady($group_field);
                    break;
                    
                }
                echo "</b></font></td></tr>";
                ob_end_flush();
                ob_start();
                if (is_array($stm_ids['stm_instance_id'])){
                    while(list($stm_id,) = each($stm_ids['stm_instance_id'])){
                        $stm_obj = new StudipStmInstance($stm_id);
                        echo "<tr><td colspan=\"2\" class=\"steel1\" width=\"66%\"><font size=-1><a href=\"stm_details.php?stm_instance_id={$stm_id}&send_from_search=1&send_from_search_page="
                        .$_SERVER['PHP_SELF']. "?keep_result_set=1\">" . htmlReady($stm_obj->getValue('displayname')). "</a></td></tr>";
                        echo "<tr><td colspan=\"2\" class=\"steel1\" width=\"66%\">";
                        $el_group = false;
                        foreach(array_keys($stm_obj->elements) as $element_id){
                            if (strcmp($el_group, ($el_group = $stm_obj->elements[$element_id]->getValue('elementgroup')))){
                                echo "<div style=\"margin-left:10px;margin-right:10px\" class=\"steel1\"><font size=-2><b><u>".($el_group + 1) .". "._("Modulausprägung")."</u></b></font></div>";
                            }
                            $dozenten = '';
                            foreach($stm_obj->elements[$element_id]->getValue('dozenten') as $dozent){
                                if ($dozenten) $dozenten .= ', ';
                                $dozenten .= sprintf("<a href=\"about.php?username=%s\">%s</a>", $dozent['username'], htmlReady($dozent['Nachname'] . ', ' . $dozent['Vorname']{0} . '.'));
                            }
                            if($stm_obj->elements[$element_id]->getValue('sem_id')){
                            echo "<div style=\"margin-left:10px;margin-right:10px\"><font size=-2><a href=\"{$this->target_url}?{$this->target_id}=".$stm_obj->elements[$element_id]->getValue('sem_id')."&send_from_search=1&send_from_search_page="
                                . $_SERVER['PHP_SELF']. "?keep_result_set=1\">"
                                . $stm_obj->elements[$element_id]->getValue('type_abbrev')
                                . ': ' . htmlReady($stm_obj->elements[$element_id]->getValue('seminar_name'))
                                . "</a>&nbsp;&nbsp;&nbsp;($dozenten)</div>";
                            } else {
                                echo "<div style=\"margin-left:10px;margin-right:10px\"><font size=-2>"
                                . $stm_obj->elements[$element_id]->getValue('type_abbrev')
                                . ': n.a.'
                                . "</div>";
                            }
                        }
                        echo "</td></tr>";
                        
                    }
                }
            }
            echo "</table>";
        } elseif($this->search_obj->search_button_clicked && !$this->search_obj->new_search_button_clicked){
            echo "\n<table border=\"0\" align=\"center\" cellspacing=0 cellpadding=2 width = \"99%\">\n";
            echo "\n<tr><td class=\"steelgraulight\"><font size=\"-1\"><b>&nbsp;" . _("Ihre Suche ergab keine Treffer") ;
            if ($this->search_obj->found_rows === false){
                echo "<br>" . _("(Der Suchbegriff fehlt oder ist zu kurz)");
            }
            echo "</b></font></td></tr>";
            echo "\n</table>";
            $this->sem_browse_data["sset"] = 0;
        }
        echo '<script type="text/javascript">location.hash = "#result";</script>';
    ob_end_flush();
    }
}
?>
