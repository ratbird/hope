<?php

/**
 * closed.php
 *
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author      Stefan Osterloh <s.osterloh@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0
 * @copyright   Stud.IP Core-Group
 * @since       3.1
 */

require_once dirname(__FILE__) .'/../siteinfo.php';
require_once 'document_controller.php';

class Document_ClosedController  extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        //Configurations for the Documentarea for this user
        $this->userConfig = DocUsergroupConfig::getUserConfig($GLOBALS['user']->id);
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        PageLayout::setTitle(_('Dateiverwaltung'));
        PageLayout::setHelpKeyword('Basis.Dateien');
        Navigation::activateItem('/document/files');
    }

    public function index_action()
    {
        if(strlen($this->userConfig['area_close_text']) == 0 || 
                empty($this->userConfig)){
            $this->message = sprintf('%s','keine Begründung');
        }else{
            $this->message = sprintf('%s',$this->userConfig['area_close_text']);
        }
        $contact = new SiteinfoMarkupEngine();
        $this->support = $contact->uniContact();
    }
}