<?php

/**
 * closed.php
 *
 * @author      Stefan Osterloh <s.osterloh@uni-oldenburg.de>
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     GPL2 or any later version
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
        if (empty($this->userConfig) || strlen($this->userConfig['area_close_text']) == 0) {
            $this->message = _('keine Begründung');
        } else {
            $this->message = $this->userConfig['area_close_text'];
        }
        $contact = new SiteinfoMarkupEngine();
        $this->support = $contact->uniContact();
    }
}
