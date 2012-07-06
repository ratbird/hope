<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* class to show content-module data
*
* This class contains methods for output of connected module data.
*
* @author   Arne Schröder <schroeder@data-quest.de>
* @access   public
* @modulegroup  elearning_interface_modules
* @module       ContentModuleView
* @package  ELearning-Interface
*/
class ContentModuleView
{
    var $view_mode;
    var $change_date;
    var $module_new;
    var $cms_type;
    /**
    * constructor
    *
    * init class. don't call directly, class is loaded by ContentModule.
    * @access public
    * @param string $cms system-type
    */ 
    function ContentModuleView($cms)
    {
        global $connected_cms;
        
        $this->change_date = 0;
        $this->module_new = false;
        $this->cms_type = $cms;
        $this->setViewMode("closed");
    }

    /**
    * show module-data
    *
    * show module-data in printhead/printcontent-style. user-mode
    * @access public
    */
    function show($mode = "")
    {
            global $connected_cms, $view, $search_key, $cms_select, $current_module, $anker_target;

            $content_module = $connected_cms[$this->cms_type]->content_module[$current_module];

            if ( (! $content_module->isDummy()) AND ($connected_cms[$this->cms_type]->isAuthNecessary() == true) AND ($connected_cms[$this->cms_type]->user->isConnected() == true))
            {
                if (! $content_module->isAllowed(OPERATION_VISIBLE))
                {
//                  echo "not allowed!";
                    return false;
                }
            }
//          echo implode($connected_cms[$this->cms_type]->permissions->allowed_operations," ");
            
            if ($_SESSION['elearning_open_close'][$content_module->getReferenceString()] == true)
                $this->setViewMode("open");
            $module_title = $content_module->getTitle();
/*/
            if ($mode == "searchresult")
            {
                $module_title = $module_title . " (ID " . $content_module->getId() . ", ";
                if ($content_module->isAllowed(OPERATION_WRITE))
                    $module_title = $module_title . " " . _("Schreibzugriff") . ")";
                else
                    $module_title = $module_title . " " . _("Lesezugriff") . ")";
            }/**/
            if ($this->isOpen() == true)
                $printlink = "<a href=\"".URLHelper::getLink('?do_close='. $content_module->getReferenceString() . '&view='.$view.'&search_key='.$search_key.'&cms_select='.$cms_select.'#anker')."\" class=\"tree\">" . $module_title . "</a>";
            else
                $printlink = "<a href=\"".URLHelper::getLink('?do_open=' . $content_module->getReferenceString() . '&view='.$view.'&search_key='.$search_key.'&cms_select='.$cms_select.'#anker')."\" class=\"tree\">" . $module_title . "</a>";
            $printimage = $content_module->getIcon();
            $printdesc = $content_module->getCMSName() . " / " . $content_module->getModuleTypeName();
            if ($content_module->getAuthors() != "") 
                $printdesc .= " / " . $content_module->getAuthors();
            $printcontent = $content_module->getDescription() . "<br><br>\n\n";
            if (! $content_module->isDummy())
                $printcontent .= "<center>" . $connected_cms[$this->cms_type]->link->getUserModuleLinks() . "</center>";
            if ($anker_target == $content_module->getReferenceString())
                echo "<a name='anker'></a>";
                ?>
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <?
                        if ($this->isOpen() == true)
                            printhead ("99%", FALSE, URLHelper::getLink('?do_close=' . $content_module->getReferenceString() . '&view='.$view.'&search_key='.$search_key.'&cms_select='.$cms_select.'#anker'), "open", $this->module_new, $printimage, $printlink, $printdesc, $this->change_date);
                        else
                            printhead ("99%", FALSE, URLHelper::getLink('?do_open=' .  $content_module->getReferenceString() . '&view='.$view.'&search_key='.$search_key.'&cms_select='.$cms_select.'#anker'), "close", $this->module_new, $printimage, $printlink, $printdesc, $this->change_date);
                        ?>
                    </tr>
                </table>
                <? if ($this->isOpen() == true) 
                { ?>
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <?
                        printcontent("99%", FALSE, $printcontent, "");
                        ?>
                    </tr>
                </table>
                <? }
    }

    /**
    * show module-data to admin
    *
    * show module-data in printhead/printcontent-style. admin-mode
    * @access public
    */
    function showAdmin($mode = "")
    {
            global $connected_cms, $view, $search_key, $cms_select, $SessSemName, $current_module, $anker_target;

            $content_module = $connected_cms[$this->cms_type]->content_module[$current_module];

            if ($_SESSION['elearning_open_close'][$content_module->getReferenceString()] == true)
                $this->setViewMode("open");

            $module_title = $content_module->getTitle();
//          if ($mode == "searchresult")
            {
                $module_title = $module_title . " (ID " . $content_module->getId();
                if ($content_module->isAllowed(OPERATION_WRITE))
                    $module_title = $module_title . ", " . _("Schreibzugriff");
                elseif ($content_module->isAllowed(OPERATION_READ))
                    $module_title = $module_title . ", " . _("Lesezugriff");
                else
                    $module_title = $module_title . ", " . _("kein Lesezugriff");
                $module_title = $module_title . ")";
            }   
            if ($this->isOpen() == true)
                $printlink = "<a href=\"".URLHelper::getLink('?do_close=' . $content_module->getReferenceString() . '&view='.$view.'&search_key='.$search_key.'&cms_select='.$cms_select.'#anker')."\" class=\"tree\">" . $module_title . "</a>";
            else
                $printlink = "<a href=\"".URLHelper::getLink('?do_open='  . $content_module->getReferenceString() . '&view='.$view.'&search_key='.$search_key.'&cms_select='.$cms_select.'#anker')."\" class=\"tree\">" . $module_title . "</a>";
            $printimage = $content_module->getIcon();
            $printdesc = $content_module->getCMSName() . " / " . $content_module->getModuleTypeName();
            if ($content_module->getAuthors() != "") 
                $printdesc .= " / " . $content_module->getAuthors();
            $printcontent =  $content_module->getDescription() . "<br><br>\n\n";
            if ($content_module->isAllowed(OPERATION_READ))
                $printcontent .= "<center>" . $connected_cms[$this->cms_type]->link->getAdminModuleLinks() . "</center>";
            if ($anker_target == $content_module->getReferenceString())
                echo "<a name='anker'></a>";
                ?>
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <?
                        if ($this->isOpen() == true)
                            printhead ("99%", FALSE, URLHelper::getLink('?do_close=' . $content_module->getReferenceString() . '&view='.$view.'&search_key='.$search_key.'&cms_select='.$cms_select.'#anker'), "open", $this->module_new, $printimage, $printlink, $printdesc, $this->change_date);
                        else
                            printhead ("99%", FALSE, URLHelper::getLink('?do_open=' . $content_module->getReferenceString() . '&view='.$view.'&search_key='.$search_key.'&cms_select='.$cms_select.'#anker'), "close", $this->module_new, $printimage, $printlink, $printdesc, $this->change_date);
                        ?>
                    </tr>
                </table>
                <? if ($this->isOpen() == true) 
                { ?>
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <?
                        printcontent("99%", FALSE, $printcontent, "");
                        ?>
                    </tr>
                </table>
                <? }
    }

    /**
    * get open-status
    *
    * returns true, if module is opened
    * @access public
    * @return boolean open-status
    */
    function isOpen()
    {
        if ($this->view_mode == "open")
            return true;
        else
            return false;
    }

    /**
    * set view-mode
    *
    * sets view-mode
    * @access public
    * @param boolean $module_mode view-mode
    */
    function setViewMode($module_mode)
    {
        $this->view_mode = $module_mode;
    }

    /**
    * set changedate
    *
    * sets changedate for view
    * @access public
    * @param string $module_chdate changedate
    */
    function setChangeDate($module_chdate)
    {
        global $SessSemName;
        $this->change_date = $module_chdate;

        if (object_get_visit($SessSemName[1], "elearning_interface") < $this->change_date) 
            $this->module_new = true;
        else
            $this->module_new = false;
    }
}
?>