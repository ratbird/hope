<?
# Lifter002: TODO
# Lifter010: TODO
/**
 * page_intros.inc.php
 *
 * library for the messages on the pages, contents of the sidebar and stuff
 * to display
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @copyright   2003-2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     resources
*/

require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/ResourceObject.class.php");
require_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/RoomGroups.class.php");


if ($_SESSION['resources_data']['actual_object']) {
    // tlx: WTF is this chunk of code supposed to do?
    // Lines 31-34 are absolutely useless, aren't they?
    $currentObject = ResourceObject::Factory($_SESSION['resources_data']['actual_object']);
    $currentObjectTitelAdd = ': ' . ($currentObject->getCategoryName() ?: _('Hierachieebene'));
    if ($currentObjectTitelAdd) {
        $currentObjectTitelAdd = ': ';
    }
    $currentObjectTitelAdd = ': ' . $currentObject->getName() . ' (' . $currentObject->getOwnerName() . ')';
}

$helpbar = Helpbar::get();
$sidebar = Sidebar::get();

switch ($view) {
    //Reiter "Uebersicht"
    case 'plan':
        $helpbar->addPlainText(_('Informationen'),
                               _('Auf dieser Seite können Sie sich einen Wochenplan als CSV-Datei ausgeben lassen.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Spezielle Funktionen'));
    break;
    case 'regular':
        $helpbar->addPlainText(_('Informationen'),
                               _('Auf dieser Seite können Sie sich einen Semesterplan als CSV-Datei ausgeben lassen.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Spezielle Funktionen'));
    break;
    case 'diff':
        $helpbar->addPlainText(_('Informationen'),
                               _('Auf dieser Seite können Sie sich die wöchentliche Differenzliste der '
                                .'Belegung aller Räume als CSV-Datei ausgeben lassen.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Spezielle Funktionen'));
    break;

    case 'resources':
        $helpbar->addPlainText(_('Informationen'),
                               _('Auf dieser Seite können Sie durch alle Ressourcen bzw. Ebenen, auf die '
                                .'Sie Zugriff haben, navigieren und Ressourcen verwalten.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Übersicht der Ressourcen'));
        Navigation::activateItem('/resources/view/hierarchy');
    break;
    case 'search':
        $helpbar->addPlainText(_('Informationen'),
                               _('Sie können hier nach Ressourcen suchen. '
                                .'Sie haben die Möglichkeit, über ein Stichwort oder bestimmte Eigenschaften '
                                .'Ressourcen zu suchen oder sich durch die Ebenen zu navigieren.'),
                               'icons/16/white/info.png');

        PageLayout::setTitle(_('Suche nach Ressourcen'));
        Navigation::activateItem('/search/resources');

        $widget = new OptionsWidget();
        $widget->setTitle(_('Suchoptionen'));
        $widget->addCheckbox(_('Eigenschaften anzeigen'),
                             $_SESSION['resources_data']['search_mode'] === 'properties',
                             URLHelper::getLink('?view=search&quick_view_mode=' . $view_mode . '&mode=properties'),
                             URLHelper::getLink('?view=search&quick_view_mode=' . $view_mode . '&mode=browse'));
        $widget->addCheckbox(_('Belegungszeit anzeigen'),
                             $_SESSION['resources_data']['check_assigns'],
                             URLHelper::getLink('?view=search&quick_view_mode=' . $view_mode . '&check_assigns=TRUE'),
                             URLHelper::getLink('?view=search&quick_view_mode=' . $view_mode . '&check_assigns=FALSE'));
        $widget->addCheckbox(_('Nur Räume anzeigen'),
                             $_SESSION['resources_data']['search_only_rooms'],
                             URLHelper::getLink('?view=search&quick_view_mode=' . $view_mode . '&search_only_rooms=1'),
                             URLHelper::getLink('?view=search&quick_view_mode=' . $view_mode . '&search_only_rooms=0'));
        
        $sidebar->addWidget($widget);
    break;
    //Reiter "Listen"
    case 'lists':

        PageLayout::setTitle(_('Bearbeiten und ausgeben von Listen'));
        Navigation::activateItem('/resources/lists/show');

        if ($_SESSION['resources_data']['list_open']) {
            // tlx: What is this line good for?
            $title.=" - "._("Ebene").": ".getResourceObjectName($_SESSION['resources_data']["list_open"]);

            $helpbar->addPlainText(_('Informationen'),
                                   sprintf(_('Sie sehen alle Einträge in der Ebene **%s**'),
                                           getResourceObjectName($_SESSION['resources_data']['list_open'])),
                                   'icons/16/white/info.png');

            $widget = new OptionsWidget();
            $widget->addCheckbox(_('Untergeordnete Ebenen ausgeben'),
                                 $_SESSION['resources_data']['list_recurse'],
                                 URLHelper::getLink('?recurse_list=TRUE'),
                                 URLHelper::getLink('?nrecurse_list=TRUE'));
            $sidebar->addWidget($widget);
        }
    break;

    //Reiter "Objekt"
    case 'objects':
    case 'edit_object_assign':
        $helpbar->addPlainText(_('Informationen'),
                               _('Sie sehen hier die Einzelheiten der Belegung. '
                                .'Falls Sie über entsprechende Rechte verfügen, können Sie '
                                .'sie bearbeiten oder eine neue Belegung erstellen.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_("Belegungen anzeigen/bearbeiten").$currentObjectTitelAdd);
        Navigation::activateItem('/resources/objects/edit_assign');

        if ($view_mode === 'no_nav') {
            $navigation = new Navigation(_('Zurück zum Belegungsplan'),
                                         URLHelper::getLink('?quick_view=view_schedule&quick_view_mode=' . $view_mode));
            $navigation->setImage('icons/16/black/schedule.png');
            Navigation::getItem('/resources/objects')->addSubNavigation('search', $navigation);
        } else {
            $page_intro = '<h2>' . sprintf(_('Raum: %s'), $currentObject->getName()) . '</h2>';

            if ($ActualObjectPerms->havePerm('autor') && $currentObject->getCategoryId()) {
                $qv = $view_mode === 'oobj'
                    ? 'openobject_assign'
                    : 'edit_object_assign';
                
                $widget = new ActionsWidget();
                $widget->addLink(_('Neue Belegung erstellen'),
                                 URLHelper::getLink('?cancel_edit_assign=1&quick_view=' . $qv . '&quick_view_mode=' . $view_mode),
                                 'icons/16/black/add/date.png');
                $sidebar->addWidget($widget);
            }

            $navigation = new Navigation(_('Zur Ressourcensuche'),
                                         URLHelper::getLink('resources.php?view=search&quick_view_mode=' . $view_mode));
            $navigation->setImage('icons/16/black/search.png');
            Navigation::getItem('/resources/objects')->addSubNavigation('search', $navigation);
        }
    break;
    case 'edit_object_properties':
        PageLayout::setTitle(_('Eigenschaften bearbeiten') . $currentObjectTitelAdd);
        Navigation::activateItem('/resources/objects/edit_properties');
    break;
    case 'edit_object_perms':
        PageLayout::setTitle(_('Rechte bearbeiten') . $currentObjectTitelAdd);
        Navigation::activateItem('/resources/objects/edit_perms');
    break;
    case 'view_schedule':
        $helpbar->addPlainText(_('Informationen'),
                               _('Hier können Sie sich die Belegungszeiten der Ressource anzeigen '
                                .'und auf unterschiedliche Art darstellen lassen.'),
                               'icons/16/white/info.png');

        PageLayout::setTitle(_('Belegungszeiten ausgeben') . $currentObjectTitelAdd);
        Navigation::activateItem('/resources/objects/view_schedule');

        $page_intro = '<h2>' . sprintf(_('Raum: %s'), $currentObject->getName()) . '</h2>';

        $widget = new ViewsWidget();
        $widget->addLink(_('Eigenschaften anzeigen'),
                         URLHelper::getLink('?quick_view=view_details&quick_view_mode=' . $view_mode),
                         'icons/16/black/resources.png');
        if (Config::get()->RESOURCES_ENABLE_SEM_SCHEDULE) {
            $widget->addLink(_('Semesterplan anzeigen'),
                             URLHelper::getLink('?quick_view=view_sem_schedule&quick_view_mode=' . $view_mode),
                             'icons/16/black/schedule.png');
        }
        $sidebar->addWidget($widget);

        if ($ActualObjectPerms->havePerm('autor') && $currentObject->getCategoryId()) {
            $qv = $view_mode === 'oobj'
                ? 'openobject_assign'
                : 'edit_object_assign';
            
            $widget = new ActionsWidget();
            $widget->addLink(_('Neue Belegung erstellen'),
                             URLHelper::getLink('?cancel_edit_assign=1&quick_view=' . $qv . '&quick_view_mode=' . $view_mode),
                             'icons/16/black/add/date.png');
            $sidebar->addWidget($widget);
        }

        if ($view_mode !== 'no_nav') {
            if ($SessSemName['class'] === 'sem') {
                $navigation = new Navigation(_('Zurück zur Veranstaltung'),
                                             URLHelper::getLink('seminar_main.php'));
                $navigation->setImage('icons/16/black/schedule.png');
                Navigation::getItem('/resources/objects')->addSubNavigation('back', $navigation);
            }
            if ($SessSemName['class'] === 'inst') {
                $navigation = new Navigation(_('Zurück zur Einrichtung'),
                                             URLHelper::getLink('institut_main.php'));
                $navigation->setImage('icons/16/black/schedule.png');
                Navigation::getItem('/resources/objects')->addSubNavigation('back', $navigation);
            }
        }

        $navigation = new Navigation(_('Zur Ressourcensuche'),
                                     URLHelper::getLink('resources.php?view=search&quick_view_mode=' . $view_mode));
        $navigation->setImage('icons/16/black/search.png');
        Navigation::getItem('/resources/objects')->addSubNavigation('search', $navigation);

        $widget = new ExportWidget();
        $widget->addLink(_('Druckansicht'),
                         URLHelper::getLink('?view=view_schedule&print_view=1'),
                         'icons/16/black/print.png',
                         array('target' => '_blank'));
        $sidebar->addWidget($widget);
    break;
    case 'view_sem_schedule':
        $helpbar->addPlainText(_('Informationen'),
                               _('Hier können Sie sich die Belegungszeiten der Ressource anzeigen '
                                .'und auf unterschiedliche Art darstellen lassen.'),
                               'icons/16/white/info.png');

        PageLayout::setTitle(_('Belegungszeiten pro Semester ausgeben') . $currentObjectTitelAdd);
        Navigation::activateItem('/resources/objects/view_sem_schedule');

        $page_intro = '<h2>' . sprintf(_('Raum: %s'), $currentObject->getName()) . '</h2>';

        $widget = new ViewsWidget();
        $widget->addLink(_('Eigenschaften anzeigen'),
                         URLHelper::getLink('?quick_view=view_details&quick_view_mode=' . $view_mode),
                         'icons/16/black/resources.png');
        if ($view_mode === 'no_nav') {
            $qv = $view_mode === 'oobj'
                ? 'openobject_schedule'
                : 'view_schedule';

            $widget->addLink(_('Belegungsplan anzeigen'),
                             URLHelper::getLink('?quick_view=' . $qv . '&quick_view_mode=no_nav'),
                             'icons/16/black/schedule.png');
        }
        $sidebar->addWidget($widget);

        if ($ActualObjectPerms->havePerm('autor') && $currentObject->getCategoryId()) {
            $qv = $view_mode === 'oobj'
                ? 'openobject_assign'
                : 'edit_object_assign';
            
            $widget = new ActionsWidget();
            $widget->addLink(_('Neue Belegung erstellen'),
                             URLHelper::getLink('?cancel_edit_assign=1&quick_view=' . $qv . '&quick_view_mode=' . $view_mode),
                             'icons/16/black/add/date.png');
            $sidebar->addWidget($widget);
        }

        if ($view_mode !== 'no_nav') {
            if ($SessSemName['class'] === 'sem') {
                $navigation = new Navigation(_('Zurück zur Veranstaltung'),
                                             URLHelper::getLink('seminar_main.php'));
                $navigation->setImage('icons/16/black/schedule.png');
                Navigation::getItem('/resources/objects')->addSubNavigation('back', $navigation);
            }
            if ($SessSemName['class'] === 'inst') {
                $navigation = new Navigation(_('Zurück zur Einrichtung'),
                                             URLHelper::getLink('institut_main.php'));
                $navigation->setImage('icons/16/black/schedule.png');
                Navigation::getItem('/resources/objects')->addSubNavigation('back', $navigation);
            }
        }

        $navigation = new Navigation(_('Zur Ressourcensuche'),
                                     URLHelper::getLink('resources.php?view=search&quick_view_mode=' . $view_mode));
        $navigation->setImage('icons/16/black/search.png');
        Navigation::getItem('/resources/objects')->addSubNavigation('search', $navigation);

        $widget = new ExportWidget();
        $widget->addLink(_('Druckansicht'),
                         URLHelper::getLink('?view=view_sem_schedule&print_view=1'),
                         'icons/16/black/print.png',
                         array('target' => '_blank'));
        $sidebar->addWidget($widget);
    break;
    case 'view_group_schedule':
        $helpbar->addPlainText(_('Informationen:'),
                               _('Hier können Sie sich die Belegungszeiten einer Raumgruppe anzeigen lassen.'),
                               'icons/16/white/info.png');

        $room_groups = RoomGroups::GetInstance();
        PageLayout::setTitle(_('Belegungszeiten einer Raumgruppe pro Semester ausgeben:') . ' ' . $room_groups->getGroupName($_SESSION['resources_data']['actual_room_group']));
        Navigation::activateItem('/resources/view/group_schedule');

        $widget = new ExportWidget();
        $widget->addLink(_('Druckansicht'),
                         URLHelper::getLink('?view=view_group_schedule&print_view=1'),
                         'icons/16/black/print.png',
                         array('target' => '_blank'));
        $sidebar->addWidget($widget);
    break;
    case 'view_group_schedule_daily':
        $helpbar->addPlainText(_('Informationen:'),
                               _('Hier können Sie sich die Belegungszeiten einer Raumgruppe anzeigen lassen.'),
                               'icons/16/white/info.png');
        $room_groups = RoomGroups::GetInstance();
        PageLayout::setTitle(_('Belegungszeiten einer Raumgruppe pro Tag ausgeben:') . ' ' . $room_groups->getGroupName($_SESSION['resources_data']['actual_room_group']));
        Navigation::activateItem('/resources/view/group_schedule_daily');

        $widget = new ExportWidget();
        $widget->addLink(_('Druckansicht'),
                         URLHelper::getLink('?view=view_group_schedule_daily&print_view=1'),
                         'icons/16/black/print.png',
                         array('target' => '_blank'));
        $sidebar->addWidget($widget);
    break;
    //Reiter "Anpassen"
    case 'settings':
    case 'edit_types':
        $helpbar->addPlainText(_('Informationen:'),
                               _('Verwalten Sie auf dieser Seite die Ressourcen-Typen, wie etwa Räume, '
                                .'Geräte oder Gebäude. Sie können jedem Typ beliebig viele Eigenschaften zuordnen.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Typen bearbeiten'));
        Navigation::activateItem('/resources/settings/edit_types');
    break;
    case 'edit_properties':
        $helpbar->addPlainText(_('Informationen:'),
                               _('Verwalten Sie auf dieser Seite die einzelnen Eigenschaften. Diese '
                                .'Eigenschaften können Sie beliebigen Ressourcen-Typen zuweisen.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Eigenschaften bearbeiten'));
        Navigation::activateItem('/resources/settings/edit_properties');
    break;
    case 'edit_perms':
        $helpbar->addPlainText(_('Informationen:'),
                               _('Verwalten Sie hier AdministratorInnen des Systems, die Rechte über alle '
                                .'Ressourcen erhalten.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('globale Rechte der Ressourcenadministratoren bearbeiten'));
        Navigation::activateItem('/resources/settings/edit_perms');
    break;
    case 'edit_settings':
        $helpbar->addPlainText(_('Informationen:'),
                               _('Verwalten Sie hier grundlegende Einstellungen der Ressourcenverwaltung.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Einstellungen der Ressourcenverwaltung'));
        Navigation::activateItem('/resources/settings/edit_settings');
    break;

    //Reiter Raumplanung
    case 'requests_start':
        $helpbar->addPlainText(_('Informationen:'),
                               _('Auf dieser Seite wird Ihnen der Status der Anfragen aus Ihren Bereichen '
                                .'angezeigt. Sie können das Bearbeiten der Anfragen von hier aus starten.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Übersicht des Raumplanungs-Status'));
        Navigation::activateItem('/resources/room_requests/start');
    break;
    case 'edit_request':
        $helpbar->addPlainText(_('Informationen:'),
                               _('Sie können hier die einzelnen Anfragen einsehen und passenden Räume '
                                .'auswählen sowie zuweisen.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Bearbeiten der Anfragen'));
        Navigation::activateItem('/resources/room_requests/edit');

        $widget = new ActionsWidget();
        $widget->addLink(_('Ressourcen suchen'),
                         URLHelper::getLink('resources.php?view=search&quick_view_mode=no_nav'),
                         'icons/16/black/search.png',
                         array('onclick' => "windows.open(this.href, '', 'scrollbars=yes,left=10,top=10,width=1000,height=680,resizable=yes');return false;"));
        $widget->addLink(_('Nachrichten zu zugewiesenen Anfragen versenden'),
                         URLHelper::getLink('?snd_closed_request_sms=TRUE'),
                         'icons/16/black/mail.png');
        $sidebar->addWidget($widget);

        $widget = new OptionsWidget();
        $widget->addCheckbox(_('Bearbeitete Anfragen anzeigen'),
                             $_SESSION['resources_data']['skip_closed_requests'],
                             URLHelper::getLink('?skip_closed_requests=TRUE'),
                             URLHelper::getLink('?skip_closed_requests=FALSE'));

    break;
    case 'list_requests':
        $text = sprintf(_('Sie sehen hier eine Liste aller offenen Anfragen, die Sortierung folgt der Einstellung unter [Übersicht]%s .'),
                        URLHelper::getLink('resources.php?view=requests_start&cancel_edit_request_x=1'));
        $helpbar->addPlainText(_('Informationen:'),
                               array(
                                   $text,
                                   _('Ein Klick auf das Symbol nebem dem Zähler erlaubt es Ihnen, direkt zu der Anfrage zu springen.')
                               ),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Anfragenliste'));
        Navigation::activateItem('/resources/room_requests/list');
    break;
    //all the intros in an open object (Veranstaltung, Einrichtung)
    case 'openobject_main':
        // TODO
        $text = array();
        $text[] = sprintf(_('Auf dieser Seite sehen Sie alle der %s zugeordneten Ressourcen.'), $SessSemName['art_generic']);
        if ($perm->have_studip_perm('autor', $SessSemName[1])) {
            $text[] = $SessSemName['class'] === 'sem'
                ? _('Als Teilnehmer der Veranstaltung haben Sie die Möglichkeit, diese Ressourcen frei zu belegen oder den Belegungsplan einzusehen.')
                : _('Als Mitarbeiter der Einrichtung haben Sie die Möglichkeit, diese Ressourcen frei zu belegen oder den Belegungsplan einzusehen.');
        } else {
            $text[] = $SessSemName['class'] === 'sem'
                ? _('Sie können hier die Details und den Belegungsplan der dieser Veranstaltung zugeordneten Ressourcen einsehen.')
                : _('Sie können hier den Details und Belegungsplan der dieser Einrichtung zugeordneten Ressourcen einsehen.');
        }
        $helpbar->addPlainText(_('Informationen'), $text, 'icons/16/white/info.png');

        PageLayout::setTitle($SessSemName["header_line"]." - "._("Ressourcenübersicht"));
        Navigation::activateItem('/course/resources/overview');
    break;
    case 'openobject_details':
    case 'view_details':
        if ($view_mode === 'oobj') {
            PageLayout::setTitle($SessSemName['header_line'] . ' - ' . _('Ressourcendetails') . $currentObjectTitelAdd);
            Navigation::activateItem('/course/resources/view_details');
        } else {
            PageLayout::setTitle(_('Anzeige der Ressourceneigenschaften') . $currentObjectTitelAdd);
            Navigation::activateItem('/resources/objects/view_details');
        }

        if ($view_mode == 'no_nav' && is_object($currentObject) && $currentObject->getCategoryId()) {
            $widget = new ViewsWidget();
            $widget->addLink(_('Belegungsplan anzeigen'),
                             URLHelper::getLink('?quick_view=view_schedule&quick_view_mode=no_nav'),
                             'icons/16/black/schedule.png');
            $sidebar->addWidget($widget);

            if ($ActualObjectPerms->havePerm('autor')) {
                $widget = new ActionsWidget();
                $widget->addLink(_('Neue Belegung erstellen'),
                                 URLHelper::getLink('?cancel_edit_assign=1&quick_view=edit_object_assign&quick_view_mode=' . $view_mode),
                                 'icons/16/black/add/date.png');
                $sidebar->addWidget($widget);
            }
        }

    break;

    case 'openobject_schedule':
        if ($_SESSION['resources_data']['actual_object']) {
            $text = sprintf(_('Hier können Sie sich die Belegungszeiten der Ressource **%s** (%s) ausgeben lassen'),
                            $currentObject->getName(),
                            $currentObject->getCategoryName());
            $helpbar->addPlainText(_('Informationen'), $text, 'icons/16/white/info.png');
            
        }

        PageLayout::setTitle($SessSemName['header_line'] . ' - ' . _('Ressourcenbelegung'));
        Navigation::activateItem('/course/resources/view_schedule');
    break;
    case 'openobject_assign':
        if ($_SESSION['resources_data']['actual_object']) {
            $text = array();
            $text[] = sprintf(_('Anzeigen der Belegung der Ressource **%s** (%s).'),
                              $currentObject->getName(),
                              $currentObject->getCategoryName());
            $text[] = _('Sie können die Belegung auch bearbeiten, falls Sie entsprechende '
                       .'Rechte besitzen, oder eine neue Belegung erstellen.');
            $helpbar->addPlainText(_('Informationen'), $text, 'icons/16/white/info.png');
        }
        PageLayout::setTitle($SessSemName["header_line"]." - ".("Belegung anzeigen/bearbeiten"));
        Navigation::activateItem('/course/resources/edit_assign');
    break;
    case 'openobject_group_schedule':
        $helpbar->addPlainText(_('Informationen'),
                               _('Hier können Sie sich die Belegungszeiten aller Ressourcen '
                                .'dieser Veranstaltung anzeigen lassen.'),
                               'icons/16/white/info.png');

        PageLayout::setTitle($SessSemName['header_line'] . ' - ' . _('Belegungszeiten aller Ressourcen pro Tag ausgeben'));
        Navigation::activateItem('/course/resources/group_schedule');

        $widget = new ExportWidget();
        $widget->addLink(_('Druckansicht'),
                         URLHelper::getLink('?view=openobject_group_schedule&print_view=1'),
                         'icons/16/black/print.png',
                         array('target' => '_blank'));
        $sidebar->addWidget($widget);
    break;
    case 'view_requests_schedule':
        $helpbar->addPlainText(_('Informationen'),
                               _('Hier können Sie sich eine Übersicht über alle Anfragen und vorhandenenen '
                                .'Belegungen eines angeforderten Raums anzeigen lassen.'),
                               'icons/16/white/info.png');
        PageLayout::setTitle(_('Anfragenübersicht eines Raums:') . ' ' . ResourceObject::Factory($_SESSION['resources_data']['resolve_requests_one_res'])->getName());
        Navigation::activateItem('/resources/room_requests/schedule');

        $widget = new ViewsWidget();
        $widget->addLink(_('Semesterplan'),
                         URLHelper::getLink('resources.php?actual_object=' . $_SESSION['resources_data']['resolve_requests_one_res'] . '&quick_view=view_sem_schedule&quick_view_mode=no_nav'),
                         'icons/16/black/schedule.png',
                         array('onclick' => "window.open(this.href, '', 'scrollbars=yes,left=10,top=10,width=1000,height=680,resizable=yes');return false;"));
        $sidebar->addWidget($widget);
    break;
    //default
    default:
        $helpbar->addPlainText(_('Informationen'),
                               _('Sie befinden sich in der Ressourcenverwaltung von Stud.IP. '
                                .'Sie können hier Räume, Gebäude, Geräte und andere Ressourcen verwalten.'),
                               'icons/16/white/info.png');

        PageLayout::setTitle(_('Übersicht der Ressourcen'));
        Navigation::activateItem('/resources/view/hierarchy');
    break;
}

//general naming of resources management pages
if (!$SessSemName) {
    PageLayout::setTitle(_('Ressourcenverwaltung:') . ' ' . PageLayout::getTitle());
}
