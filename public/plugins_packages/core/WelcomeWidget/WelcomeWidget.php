<?php
/*
 * WelcomeWidget.php - widget plugin for start page
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @copyright   2014 Stud.IP Core Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class WelcomeWidget extends StudIPPlugin implements PortalPlugin
{
    public function getPortalTemplate()
    {
        PageLayout::addStylesheet($this->getPluginURL().'/assets/stylesheets/welcomewidget.css');
        $this->template_factory = new Flexi_TemplateFactory(dirname(__FILE__) . '/templates/');
        $template = $this->template_factory->open('index');
        return $template;
    }

    public function getPluginName()
    {
        return _('Willkommen bei Stud.IP');
    }

}
