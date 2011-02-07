<?php
/**
 * webservice_access.php - access rules für webservices admin controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 */

require_once 'lib/classes/WebserviceAccessRule.class.php';
require_once 'app/controllers/authenticated_controller.php';

class Admin_WebserviceAccessController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm, $template_factory;

        parent::before_filter($action, $args);

        $perm->check('root');

        if (!Config::get()->WEBSERVICES_ENABLE) {
            throw new AccessDeniedException(_("Die Webservices sind in diesem System nicht aktiviert."));
        }


        $layout = $template_factory->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setTitle(_('Verwaltung der Zugriffsregeln für Webservices'));
        Navigation::activateItem('/admin/tools/webservice_access');

        $this->get_all_rules();
    }

    /**
     * Display the list of ws access rules
     */
    function index_action()
    {
    }

    function edit_action($id)
    {
        $this->edit = $id;
        $this->render_action('index');
    }

    function new_action()
    {
        array_unshift($this->ws_rules, new WebserviceAccessRule());
        $this->edit = 0;
        $this->render_action('index');
    }

    function delete_action($id)
    {
        $rule = $this->ws_rules[$id];
        if ($rule && !$rule->isNew() && $rule->delete()) {
            PageLayout::postMessage(MessageBox::success(_("Die Regel wurde gelöscht.")));
            $this->get_all_rules();
        }
        $this->render_action('index');
    }

    function update_action()
    {
        CSRFProtection::verifyUnsafeRequest();
        if (!($rule = $this->ws_rules[Request::int('ws_rule_id')])) {
            $rule = new WebserviceAccessRule();
            $rule->id = 0;
            array_unshift($this->ws_rules, $rule);
        }
        $rule->api_key = trim(Request::get('ws_rule_api_key'));
        $rule->method = trim(Request::get('ws_rule_method'));
        $rule->ip_range = trim(Request::get('ws_rule_ip_range'));
        $rule->type = trim(Request::get('ws_rule_type'));

        $msg = array();

        if (strlen($rule->api_key) < 5) {
            $msg['error'][] = _("Bitte geben Sie einen API-KEY mit min. 5 Zeichen an.");
        }
        if (!$rule->method) {
            $msg['info'][] = _("Eine Regel ohne angegebene Methode gilt für alle Methoden!");
        }
        if (!$rule->ip_range) {
            $msg['info'][] = _("Eine Regel ohne IP Bereich gilt für alle IP Adressen!");
        }
        if ($msg['error']) {
            PageLayout::postMessage(MessageBox::error(_("Die Regel wurde nicht gespeichert."), $msg['error']));
            $this->edit = $rule->id;
        } else {
            if ($rule->store()) {
                PageLayout::postMessage(MessageBox::success(_("Die Regel wurde gespeichert."), $msg['info']));
                $this->get_all_rules();
            }
        }
        $this->render_action('index');
    }

    function get_all_rules()
    {
        $this->ws_rules = array();
        foreach (WebserviceAccessRule::findAll() as $rule) {
            $this->ws_rules[$rule->id] = $rule;
        }
    }
}