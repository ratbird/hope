<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * news.php - News controller
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>, Rasmus Fuhse <fuhse@data-quest.de>,
 * Nadine Werner <nadwerner@uos.de>
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
*/

require_once 'lib/functions.php';
require_once 'lib/user_visible.inc.php';
require_once 'lib/classes/WidgetHelper.php';

class QuickSelection extends StudIPPlugin implements PortalPlugin {

    function __construct() {

        parent::__construct();
        PageLayout::addScript($this->getPluginUrl() . '/js/QuickSelection.js');

    }

    public function getPortalTemplate() {
        global $perm,$user;
        $this->factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
        $template = $this->factory->open('list');
        $names = WidgetHelper::getWidgetUserConfig($user->id, 'QUICK_SELECTION');
        $navs = array();
        $testkeys = "";
        if (empty($names)) {
            $navs = Navigation::getItem('/start');
        } else {
            
            foreach ($names as $key) {
                $nav = Navigation::getItem('/start/'.$key);
                $testkeys = $testkeys.$key;
                $navs[] = $nav;
            }

        }

        $template->test = $testkeys;
        $template->add_removesNames = $navs;

        $title = '';
        // Fetch headline
        switch ($perm->get_perm()) {
            case 'root':
                $title =_("Startseite für Root bei Stud.IP");
                break;
            case 'admin':
                $title = _("Startseite für AdministratorInnen bei Stud.IP");
                break;
            case 'dozent':
                $title = _("Startseite für DozentInnen bei Stud.IP");
                break;
            default:
                $title = _("Ihre persönliche Startseite bei Stud.IP");
                break;
        }

        $template->title = $title;
        $template->icon_url = 'icons/16/white/home.png';


        $template->admin_url =
                "javascript:QuickSelection.openDialog('" .
                    URLHelper::getURl('plugins.php/QuickSelection/openConfiguration')."');";
        $template->admin_title = _('QuickSelection konfigurieren');

        return $template;
    }

    function getHeaderOptions() {

        global $perm;
        $options[] = array('url'=>'#', 'img' => 'icons/16/blue/edit.png', 'onclick'=>"QuickSelection.openDialog('". PluginEngine::getLink($this, array(),'openConfiguration') ."');return false;",'tooltip'=>_('konfigurieren'));
        return $options;
    }

    function getURL() {

    }

    function getRange() {
        global $user;
        return $user->id;
    }

    function save_action() {
        global $user;
        // if(isset($_POST['add_removes[]'])) {
        $params = array();
        parse_str($_POST['data'], $params);
        if(isset($params['add_removes'])) {
            $addrem = studip_utf8decode($params['add_removes']);
            $addremArr = array();
            foreach ($addrem as $value) {
                // $path = studip_utf8decode($params[$value]);
                $addremArr[$value] = $value;
            }
            if (get_config('QUICK_SELECTION') === NULL) {
                Config::get()->create('QUICK_SELECTION', array('range' => 'user', 'type' => 'array', 'description' => 'Einstellungen des QuickSelection-Widgets'));
            }
            WidgetHelper::addWidgetUserConfig($user->id, 'QUICK_SELECTION', $addremArr);
            foreach ($addremArr as $val) {
                $nav = Navigation::getItem('/start/'.$val);
                if ($nav->isVisible()) {
                    $data =  $data .'<div class="mainmenu" id="quickSelectionDiag">';

                    if (is_internal_url($url = $nav->getURL())) {
                        $data =  $data .'<a href="'.URLHelper::getLink($url).'">';
                    }else {
                        $data =  $data .'<a href="'.htmlReady($url).'" target="_blank">';
                    }
                    $data =  $data .htmlReady($nav->getTitle()).'</a>';
                    $pos = 0 ;
                    foreach ($nav as $subnav) {
                        if ($subnav->isVisible()) {
                            $data =  $data .'<font size="-1">';
                            $pos++ ? $data = $data .' / ' : $data =  $data .'<br>';
                            if (is_internal_url($url = $subnav->getURL())) {
                                $data =  $data .'<a href="'.URLHelper::getLink($url).'">';
                            } else {
                                $data =  $data .'<a href="'.htmlReady($url).'" target="_blank">';
                            }
                            $data =  $data .htmlReady($subnav->getTitle()).'</a> </font>';
                        }
                    }
                    $data =  $data .'</div>';
                }

            }


            echo studip_utf8encode($data);
        }

    }
    function openConfiguration_action() {
        global $user;
        $links = Navigation::getItem('/start');
        $config = WidgetHelper::getWidgetUserConfig($user->user_id, 'QUICK_SELECTION');//$id, 'QuickSelection');
        $this->factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
        $template = $this->factory->open('edit');
        $template->save_conf = 'saveConfiguration';
        $template->links = $links;
        $template->config = $config;
        $template->plugin = $this;

        echo $template->render();

    }

    function getPluginName(){
        return _("Schnellzugriff");
    }


}
