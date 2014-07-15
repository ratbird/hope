<?php
# Lifter010: TODO
/**
 * lockrules.php - lock rules admin controller
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

require_once 'app/controllers/authenticated_controller.php';

class Admin_LockrulesController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm, $template_factory;

        parent::before_filter($action, $args);

        $perm->check(Config::get()->LOCK_RULE_ADMIN_PERM ? Config::get()->LOCK_RULE_ADMIN_PERM : 'admin');

        $layout = $template_factory->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setTitle(_('Verwaltung der Sperrebenen'));
        Navigation::activateItem('/admin/locations/lock_rules');

        URLHelper::bindLinkParam('lock_rule_type', $this->lock_rule_type);

        if (!$this->lock_rule_type || !$GLOBALS['perm']->have_perm('root')) {
            $this->lock_rule_type = 'sem';
        }
        if ($this->lock_rule_type == 'sem') {
            $this->lock_rule_permissions = $GLOBALS['perm']->have_perm('root') ? array('tutor','dozent','admin','root') : array('tutor','dozent');
        } elseif ($this->lock_rule_type == 'inst') {
            $this->lock_rule_permissions = array('admin','root');
        } elseif ($this->lock_rule_type == 'user') {
            $this->lock_rule_permissions = array('tutor','dozent','admin','root');
        }
        if ($this->flash['message']) {
            $this->message = $this->flash['message'];
        }
        $this->rule_type_names = array('sem' => _("Veranstaltung"), 'inst' => _("Einrichtung"), 'user' => _("Nutzer"));
    }

    /**
     * Display the list of lock rules
     */
    function index_action()
    {
        if ($this->lock_rule_type == 'sem') {
           $this->lock_rules = LockRules::getAdministrableSeminarRules($GLOBALS['user']->id);
        } else {
            $this->lock_rules = LockRule::findAllByType($this->lock_rule_type);
        }
    }

    /**
     * edit one lock rule
     */
    function edit_action($lock_rule_id)
    {
        $this->lock_rule = LockRule::find($lock_rule_id);
        $this->lock_config = LockRules::getLockRuleConfig($this->lock_rule_type);

        if (Request::submitted('ok')) {
            $ok = $this->handle_form_data();
            if ($ok === false) {
               $this->message = MessageBox::error("Die Änderungen der Sperrebene konnten nicht gespeichert werden.", $this->msg['error']);
           } else if ($ok) {
               $this->message = MessageBox::success("Die Änderungen wurden gespeichert.");
           }
        }
    }

    function new_action()
    {
        $this->lock_rule = new LockRule();
        $this->lock_config = LockRules::getLockRuleConfig($this->lock_rule_type);

        if (Request::submitted('ok')) {
           $this->lock_rule->user_id = $GLOBALS['user']->id;
           $this->lock_rule->object_type = $this->lock_rule_type;
           if (!$this->handle_form_data()) {
               $this->message = MessageBox::error("Die neue Sperrebene konnte nicht gespeichert werden.", $this->msg['error']);
           } else {
               $this->flash['message'] = MessageBox::success("Die neue Sperrebene wurde gespeichert");
               $this->redirect($this->url_for('admin/lockrules/edit/' . $this->lock_rule->getid()));
           }
        }
    }

    function delete_action($lock_rule_id)
    {
        $this->lock_rule = LockRule::find($lock_rule_id);
        if (!(!$this->lock_rule->isNew() && ($GLOBALS['perm']->have_perm('root') || $this->lock_rule->user_id == $GLOBALS['user']->id))) {
            throw new Trails_Exception(403);
        }
        if (Request::isGet()) {
            $factory = new Flexi_TemplateFactory($this->dispatcher->trails_root . '/views/');
            $template = $factory->open('admin/lockrules/_del.php');
            $template->action = $this->url_for('admin/lockrules/delete/' . $this->lock_rule->getid());
            if ($this->lock_rule->getUsage()) {
                $template->question = sprintf(_("Sie beabsichtigen die Ebene %s zu löschen. Diese Ebene wird von %s Objekten benutzt. Soll sie trotzdem gelöscht werden?"), $this->lock_rule->name, $this->lock_rule->getUsage());
            } else {
                $template->question = sprintf(_("Möchten Sie die Ebene %s löschen?"), $this->lock_rule->name);
            }
            $this->flash['message'] = $template->render();
        } else {
            CSRFProtection::verifyUnsafeRequest();
            if (Request::submitted('kill')) {
                if ($this->lock_rule->delete()) {
                    $this->flash['message'] = MessageBox::success("Die Sperrebene wurde gelöscht.");
                }
            }
        }
        $this->redirect($this->url_for('admin/lockrules'));
    }

    function handle_form_data()
    {
        CSRFProtection::verifyUnsafeRequest();
        $this->lock_rule->name = Request::get('lockdata_name');
        $this->lock_rule->description = Request::get('lockdata_description');
        $this->lock_rule->permission = Request::option('lockdata_permission');
        $this->lock_rule->attributes = Request::intArray('lockdata_attributes');
        if (!$this->lock_rule->name) {
            $this->msg['error'][] = _("Bitte geben Sie einen Namen für die Sperrebene an!");
            return false;
        }
        return $this->lock_rule->store();
    }

}
