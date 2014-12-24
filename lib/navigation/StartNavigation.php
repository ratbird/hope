<?php
# Lifter010: TODO
/*
 * StartNavigation.php - navigation for start page
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class StartNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        parent::__construct(_('Start'), URLHelper::getLink('dispatch.php/start'));
    }

    public function initItem()
    {
        parent::initItem();

        if (is_object($GLOBALS['user']) && $GLOBALS['user']->id != 'nobody') {
            if (WidgetHelper::hasWidget($GLOBALS['user']->id, 'News')) {
                $query = "SELECT SUM(chdate > IFNULL(b.visitdate, 0) AND nw.user_id != :user_id)
                          FROM news_range a
                          LEFT JOIN news nw ON (a.news_id = nw.news_id AND UNIX_TIMESTAMP() BETWEEN date AND date + expire)
                          LEFT JOIN object_user_visits b ON (b.object_id = nw.news_id AND b.user_id = :user_id AND b.type = 'news')
                          WHERE a.range_id = 'studip'
                          GROUP BY a.range_id";
                $statement = DBManager::get()->prepare($query);
                $statement->bindValue(':user_id', $GLOBALS['user']->id);
                $statement->execute();
                $news = (int)$statement->fetchColumn();
            }

            if (Config::get()->VOTE_ENABLE && WidgetHelper::hasWidget($GLOBALS['user']->id, 'Evaluations')) {
                $query = "SELECT COUNT(IF(chdate > IFNULL(b.visitdate, 0) AND a.author_id != :user_id AND a.state != 'stopvis', vote_id, NULL))
                          FROM vote a
                          LEFT JOIN object_user_visits b ON (b.object_id = vote_id AND b.user_id = :user_id AND b.type = 'vote')
                          WHERE a.range_id = 'studip' AND a.state IN ('active', 'stopvis')
                          GROUP BY a.range_id";
                $statement = DBManager::get()->prepare($query);
                $statement->bindValue(':user_id', $GLOBALS['user']->id);
                $statement->execute();
                $vote = (int)$statement->fetchColumn();

                $query = "SELECT COUNT(IF(chdate > IFNULL(b.visitdate, 0) AND d.author_id != :user_id, a.eval_id, NULL))
                          FROM eval_range a
                          INNER JOIN eval d ON (a.eval_id = d.eval_id AND d.startdate < UNIX_TIMESTAMP() AND
                                            (d.stopdate > UNIX_TIMESTAMP() OR d.startdate + d.timespan > UNIX_TIMESTAMP() OR (d.stopdate IS NULL AND d.timespan IS NULL)))
                          LEFT JOIN object_user_visits b ON (b.object_id = d.eval_id AND b.user_id = :user_id AND b.type = 'eval')
                          WHERE a.range_id = 'studip'
                          GROUP BY a.range_id";
                $statement = DBManager::get()->prepare($query);
                $statement->bindValue(':user_id', $GLOBALS['user']->id);
                $statement->execute();
                $vote += (int)$statement->fetchColumn();
            }
        }

        $homeinfo = _('Zur Startseite');
        if ($news) {
            $homeinfo .= ' - ';
            $homeinfo .= sprintf(ngettext('%u neue Ankündigung', '%u neue Ankündigungen', $news), $news);
        }
        if ($vote) {
            $homeinfo .= ' - ';
            $homeinfo .= sprintf(ngettext('%u neue Umfrage', '%u neue Umfragen', $vote), $vote);
        }
        $this->setBadgeNumber($vote + $news);

        $this->setImage('icons/lightblue/home.svg', array('title' => $homeinfo));
    }

    /**
     * Determine whether this navigation item is active.
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        global $perm, $auth;
        $username = $auth->auth['uname'];

        parent::initSubNavigation();

        if (!$perm->have_perm('user')) {
            return;
        }

        $sem_create_perm = in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent';

        // my courses
        if ($perm->have_perm('root')) {
            $navigation = new Navigation(_('Veranstaltungsübersicht'), 'dispatch.php/search/courses');
        } else if ($perm->have_perm('admin')) {
            $navigation = new Navigation(_('Veranstaltungen an meinen Einrichtungen'), 'dispatch.php/my_courses');
        } else {
            $navigation = new Navigation(_('Meine Veranstaltungen'), 'dispatch.php/my_courses');

            if (!$perm->have_perm('dozent')) {
                $navigation->addSubNavigation('browse', new Navigation(_('Veranstaltung hinzufügen'), 'dispatch.php/search/courses'));

                if ($perm->have_perm('autor') && get_config('STUDYGROUPS_ENABLE')) {
                    $navigation->addSubNavigation('new_studygroup', new Navigation(_('Studiengruppe anlegen'), 'dispatch.php/course/studygroup/new'));
                }
            } else {
                if ($perm->have_perm($sem_create_perm)) {
                    $navigation->addSubNavigation('new_course', new Navigation(_('Neue Veranstaltung anlegen'), 'admin_seminare_assi.php?new_session=TRUE'));
                }
                if (get_config('STUDYGROUPS_ENABLE')) {
                    $navigation->addSubNavigation('new_studygroup', new Navigation(_('Studiengruppe anlegen'), 'dispatch.php/course/studygroup/new'));
                }

            }
        }

        $this->addSubNavigation('my_courses', $navigation);

        // course administration
        if ($perm->have_perm('admin')) {
            $navigation = new Navigation(_('Verwaltung von Veranstaltungen'), 'adminarea_start.php?list=TRUE');

            if ($perm->have_perm($sem_create_perm)) {
                $navigation->addSubNavigation('new_course', new Navigation(_('Neue Veranstaltung anlegen'), 'admin_seminare_assi.php?new_session=TRUE'));
            }

            if (get_config('STUDYGROUPS_ENABLE')) {
                $navigation->addSubNavigation('new_studygroup', new Navigation(_('Studiengruppe anlegen'), 'dispatch.php/course/studygroup/new'));
            }

            $this->addSubNavigation('admin_course', $navigation);
        }

        // insitute administration
        if ($perm->have_perm('admin')) {
            $navigation = new Navigation(_('Verwaltung von Einrichtungen'), 'admin_institut.php?list=TRUE');
            $this->addSubNavigation('admin_inst', $navigation);
        }

        // user administration
        if ($perm->have_perm('root')) {
            $navigation = new Navigation(_('Verwaltung globaler Einstellungen'), 'admin_range_tree.php');
            $this->addSubNavigation('admin_user', $navigation);
        } else if ($perm->have_perm('admin') && !get_config('RESTRICTED_USER_MANAGEMENT')) {
            $navigation = new Navigation(_('Globale Benutzerverwaltung'), 'dispatch.php/admin/user/');
            $this->addSubNavigation('admin_user', $navigation);
        }

        // plugin and role administration
        if ($perm->have_perm('root')) {
            $navigation = new Navigation(_('Verwaltung von Plugins'), 'dispatch.php/admin/plugin');
            $navigation->addSubNavigation('admin_roles', new Navigation(_('Verwaltung von Rollen'), 'dispatch.php/admin/role'));
            $this->addSubNavigation('admin_plugins', $navigation);
        }
        // administration of ressources
        if ($perm->have_perm('admin')) {

            if (get_config('RESOURCES_ENABLE')) {
                $navigation = new Navigation(_('Verwaltung von Ressourcen'));
                $navigation->addSubNavigation('hierarchy', new Navigation(_('Struktur'), 'resources.php#a', array('view' => 'resources')));
                if ($perm->have_perm('admin') && get_config('RESOURCES_ALLOW_ROOM_REQUESTS')) {
                    if (getGlobalPerms($GLOBALS['user']->id) !== 'admin') {
                        require_once 'lib/resources/lib/ResourcesUserRoomsList.class.php';
                        $resList = new ResourcesUserRoomsList($GLOBALS['user']->id, false, false);
                        $show_roomplanning = $resList->roomsExist();
                    } else {
                        $show_roomplanning = true;
                    }
                    if ($show_roomplanning) {
                        $navigation->addSubNavigation('start_planning', new Navigation(_('Raumplanung'), 'resources.php?cancel_edit_request_x=1', array('view' => 'requests_start')));
                    }
                }
                if (getGlobalPerms($GLOBALS['user']->id) == 'admin') {
                    $navigation->addSubNavigation('edit_types', new Navigation(_('Anpassen'), 'resources.php', array('view' => 'edit_types')));
                }
                $this->addSubNavigation('ressources', $navigation);

            }
        }

        // messaging
        $navigation = new Navigation(_('Nachrichten'));
        $navigation->addSubNavigation('in', new Navigation(_('Posteingang'), 'dispatch.php/messages/overview'));
        $navigation->addSubNavigation('out', new Navigation(_('Gesendet'), 'dispatch.php/messages/sent'));
        $this->addSubNavigation('messaging', $navigation);

        // community
        $navigation = new Navigation(_('Community'));
        $navigation->addSubNavigation('online', new Navigation(_('Wer ist online?'), 'dispatch.php/online'));
        $navigation->addSubNavigation('contacts', new Navigation(_('Meine Kontakte'), 'contact.php', array('view' => 'alpha')));
        // study groups
        if (get_config('STUDYGROUPS_ENABLE')) {
            $navigation->addSubNavigation('browse',new Navigation(_('Studiengruppen'), 'dispatch.php/studygroup/browse'));
        }
        // ranking
        if (get_config('SCORE_ENABLE')) {
            $navigation->addSubNavigation('score', new Navigation(_('Rangliste'), 'dispatch.php/score'));
            $this->addSubNavigation('community', $navigation);
        }

        // calendar / home page
        if (!$perm->have_perm('admin')) {
            $navigation = new Navigation(_('Mein Profil'), 'dispatch.php/profile');

            if ($perm->have_perm('autor')) {
                $navigation->addSubNavigation('settings', new Navigation(_('Einstellungen'), 'dispatch.php/settings/general'));
            }

            $this->addSubNavigation('profile', $navigation);

            $navigation = new Navigation(_('Mein Planer'));

            if (get_config('CALENDAR_ENABLE')) {
                $navigation->addSubNavigation('calendar', new Navigation(_('Terminkalender'), 'dispatch.php/calendar/single'));
            }

            if (get_config('SCHEDULE_ENABLE')) {
                $navigation->addSubNavigation('schedule', new Navigation(_('Stundenplan'), 'dispatch.php/calendar/schedule'));
            }

            $this->addSubNavigation('planner', $navigation);
        }

        // global search
        $navigation = new Navigation(_('Suchen'), 'dispatch.php/search/courses');
        $navigation->addSubNavigation('user', new Navigation(_('Personensuche'), 'browse.php'));
        $navigation->addSubNavigation('course', new Navigation(_('Veranstaltungssuche'), 'dispatch.php/search/courses'));
        $this->addSubNavigation('search', $navigation);

        // tools
        $navigation = new Navigation(_('Tools'));
        $navigation->addSubNavigation('news', new Navigation(_('Ankündigungen'), 'dispatch.php/news/admin_news'));

        if (get_config('VOTE_ENABLE')) {
            $navigation->addSubNavigation('vote', new Navigation(_('Umfragen und Tests'), 'admin_vote.php', array('page' => 'overview', 'showrangeID' => $username)));
            $navigation->addSubNavigation('evaluation',new Navigation(_('Evaluationen'), 'admin_evaluation.php', array('rangeID' => $username)));
        }

        // literature
        if (get_config('LITERATURE_ENABLE')) {
            $navigation->addSubNavigation('literature', new Navigation(_('Literatur'), 'dispatch.php/literature/edit_list.php', array('_range_id' => 'self')));
        }

        // elearning
        if (get_config('ELEARNING_INTERFACE_ENABLE')) {
            $navigation->addSubNavigation('elearning', new Navigation(_('Lernmodule'), 'dispatch.php/elearning/my_accounts'));
        }

        // export
        if (get_config('EXPORT_ENABLE') && $perm->have_perm('tutor')) {
            $navigation->addSubNavigation('export', new Navigation(_('Export'), 'export.php'));
        }

        $this->addSubNavigation('tools', $navigation);


        // external help
        $navigation = new Navigation(_('Hilfe'), format_help_url('Basis.Allgemeines'));
        $navigation->addSubNavigation('intro', new Navigation(_('Schnelleinstieg'), format_help_url('Basis.SchnellEinstiegKomplett')));
        $this->addSubNavigation('help', $navigation);
    }
}
