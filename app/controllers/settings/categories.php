<?php
/*
 * Settings/CategoriesController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */
 
require_once 'settings.php';

class Settings_CategoriesController extends Settings_SettingsController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        require_once 'app/models/kategorie.php';

        Navigation::activateItem('/profile/categories');
        PageLayout::setHelpKeyword('Basis.HomepageSonstiges');
        PageLayout::setTitle(_('Eigene Kategorien bearbeiten'));
        SkipLinks::addIndex(_('Eigene Kategorien bearbeiten'), 'layout_content', 100);
        
        if ($action === 'verify') {
            $action = 'index';
        }
    }

    public function index_action($verify_action = null, $verify_id = null)
    {
        $categories = Kategorie::findByUserId($this->user->user_id);
        usort($categories, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        $visibilities = array();
        $hidden_count = 0;
        foreach ($categories as $index => $category) {
            $visibility = get_homepage_element_visibility($this->user->user_id, 'kat_' . $category->kategorie_id);
            $visibilities[$category->kategorie_id] = $visibility;

            if ($this->restricted && $GLOBALS['perm']->have_perm('admin') && $visibility == VISIBILITY_ME) {
                $hidden_count += 1;
                unset($categories[$index]);
            }
        }

        $this->categories   = array_values($categories);
        $this->count        = count($categories);
        $this->hidden_count = $hidden_count;
        $this->visibilities = $visibilities;
        $this->verify       = $verify_action
                            ? array('action' => $verify_action, 'id' => $verify_id)
                            : false;
        
        $this->setInfoboxImage('infobox/groups.jpg');
        $this->addToInfobox(_('Aktionen'),
                            sprintf('<a href="%s">%s</a>',
                                    $this->url_for('settings/categories/create'),
                                    _('Neue Kategorie anlegen')),
                            'icons/16/black/plus');
        $this->addToInfobox(_('Informationen'), _('Hier können Sie beliebige eigene Kategorien anlegen. Diese Kategorien erscheinen je nach eingestellter Sichtbarkeit auf Ihrer Profilseite. Mit den Pfeilsymbolen k&ouml;nnen Sie die Reihenfolge, in der die Kategorien angezeigt werden, ver&auml;ndern.'), 'icons/16/black/info');
        $this->addToInfobox(_('Informationen'), sprintf(_('Für wen Ihre angelegten Kategorien genau sichtbar sein sollen, können Sie in Ihren %sPrivatsphäre-Einstellungen%s festlegen.'), '<a href="'.URLHelper::getURL('dispatch.php/settings/privacy').'">', '</a>'), 'icons/16/black/visibility-invisible');
    }

    public function create_action()
    {
        Kategorie::increatePrioritiesByUserId($this->user->user_id);

        $category = new Kategorie;
        $category->range_id = $this->user->user_id;
        $category->name     = _('neue Kategorie');
        $category->content  = _('Inhalt der Kategorie');
        $category->priority = 0;
        
        if ($category->store()) {
            $this->reportSuccess(_('Neue Kategorie angelegt.'));
        } else {
            $this->reportSuccess(_('Anlegen der Kategorie fehlgeschlagen.'));
        }

        $this->redirect('settings/categories');
    }

    public function delete_action($id, $verified = false)
    {
        $category = Kategorie::find($id);
        $name     = $category->name;

        if ($category->range_id !== $GLOBALS['user']->user_id) {
            $this->reportError(_('Sie haben leider nicht die notwendige Berechtigung für diese Aktion.'))
                 ->redirect('settings/categories');
            return;
        }

        if (!$verified) {
            $this->redirect($this->url_for('settings/categories/verify', 'delete', $id));
            return;
        }

        if ($category->delete()) {
            $this->reportSuccess(_('Kategorie "%s" gelöscht!'), $name);
        } else {
            $this->reportError(_('Kategorie "%s" konnte nicht gelöscht werden!'), $name);
        }

        $this->redirect('settings/categories');
    }

    public function store_action()
    {
        $request = Request::getInstance();
        $categories = $request['categories'];
        foreach ($categories as $id => $data) {
            if (empty($data['name'])) {
                $this->reportError(_('Kategorien ohne Namen können nicht gespeichert werden!'));
                continue;
            }
            $category = Kategorie::find($id);
            $category->name    = $data['name'];
            $category->content = $data['content'];
            if ($category->store()) {
                $this->reportSuccess(_('Kategorien geändert!'));
            }
        }

        $this->redirect('settings/categories');
    }
    
    public function swap_action($id0, $id1)
    {
        $category0  = Kategorie::find($id0);
        $category1  = Kategorie::find($id1);
        $priorities = $category0->priority + $category1->priority;

        $category0->priority = $priorities - $category0->priority;
        $category1->priority = $priorities - $category1->priority;

        if ($category0->store() && $category1->store()) {
            $this->reportSuccess(_('Kategorien wurden neu geordnet'));
        } else {
            $this->reportError(_('Kategorien konnten nicht neu geordnet werden.'));
        }

        $this->redirect('settings/categories');
    }
}
