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
        global $user;

        parent::__construct(_('Start'), 'index.php');

        $db = DBManager::get();

        if (is_object($user) && $user->id != 'nobody') {
            $result = $db->query("SELECT COUNT(IF(chdate > IFNULL(b.visitdate, 0) AND nw.user_id !='{$user->id}', nw.news_id, NULL)) AS neue
                                    FROM news_range a LEFT JOIN news nw ON (a.news_id = nw.news_id AND UNIX_TIMESTAMP() BETWEEN date AND date + expire)
                                    LEFT JOIN object_user_visits b ON (b.object_id = nw.news_id AND b.user_id = '{$user->id}' AND b.type ='news')
                                    WHERE a.range_id = 'studip' GROUP BY a.range_id");
            $news = $result->fetchColumn();

            if (get_config('VOTE_ENABLE')) {
                $result = $db->query("SELECT COUNT(IF(chdate > IFNULL(b.visitdate, 0) AND a.author_id !='{$user->id}' AND a.state != 'stopvis', vote_id, NULL)) AS neue
                                        FROM vote a LEFT JOIN object_user_visits b ON (b.object_id = vote_id AND b.user_id = '{$user->id}' AND b.type='vote')
                                        WHERE a.range_id = 'studip' AND a.state IN('active', 'stopvis') GROUP BY a.range_id");
                $vote = $result->fetchColumn();

                $result = $db->query("SELECT COUNT(IF(chdate > IFNULL(b.visitdate, 0) AND d.author_id !='{$user->id}', a.eval_id, NULL)) AS neue
                                        FROM eval_range a INNER JOIN eval d ON (a.eval_id = d.eval_id AND d.startdate < UNIX_TIMESTAMP() AND
                                            (d.stopdate > UNIX_TIMESTAMP() OR d.startdate + d.timespan > UNIX_TIMESTAMP() OR (d.stopdate IS NULL AND d.timespan IS NULL)))
                                        LEFT JOIN object_user_visits b ON (b.object_id = d.eval_id AND b.user_id = '{$user->id}' AND b.type='eval')
                                        WHERE a.range_id = 'studip' GROUP BY a.range_id");
                $vote += $result->fetchColumn();
            }
        }

        $homeinfo = _('Zur Startseite');
        $homeinfo .= $news ? ' - ' . sprintf(_('%s neue Ankündigungen'), $news) : '';
        $homeinfo .= $vote ? ' - ' . sprintf(_('%s neue Umfrage(n)'), $vote) : '';
        $homeclass = $vote + $news ? 'new' : '';

        $this->setImage('header/home.png', array('title' => $homeinfo, 'class' => $homeclass));
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

        $sem_create_perm = in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent';

        // my courses
        if ($perm->have_perm('root')) {
            $navigation = new Navigation(_('Veranstaltungsübersicht'), 'sem_portal.php');
        } else if ($perm->have_perm('admin')) {
            $navigation = new Navigation(_('Veranstaltungen an meinen Einrichtungen'), 'meine_seminare.php');
        } else {
            $navigation = new Navigation(_('Meine Veranstaltungen'), 'meine_seminare.php');

            if (!$perm->have_perm('dozent')) {
                $navigation->addSubNavigation('browse', new Navigation(_('Veranstaltung hinzufügen'), 'sem_portal.php'));

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
            $navigation = new Navigation(_('Verwaltung globaler Einstellungen'), 'dispatch.php/admin/user/');
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
                $navigation->addSubNavigation('start_planning', new Navigation(_('Raumplanung'), 'resources.php?cancel_edit_request_x=1', array('view' => 'requests_start')));
                if ((getGlobalPerms($user->id) == 'admin') || ($perm->have_perm('root'))) {
                    $navigation->addSubNavigation('edit_types', new Navigation(_('Anpassen'), 'resources.php', array('view' => 'edit_types')));
                }
                $this->addSubNavigation('ressources', $navigation);
                
            }
        }
        
        // messaging
        $navigation = new Navigation(_('Nachrichten'));
        $navigation->addSubNavigation('in', new Navigation(_('Posteingang'), 'sms_box.php', array('sms_inout' => 'in')));
        $navigation->addSubNavigation('out', new Navigation(_('Gesendet'), 'sms_box.php', array('sms_inout' => 'out')));
        $navigation->addSubNavigation('write', new Navigation(_('Neue Nachricht schreiben'), 'sms_send.php?cmd=new'));
        $this->addSubNavigation('messaging', $navigation);
        
        // community
        $navigation = new Navigation(_('Community'));
        $navigation->addSubNavigation('online', new Navigation(_('Wer ist online?'), 'online.php'));
        $navigation->addSubNavigation('contacts', new Navigation(_('Meine Kontakte'), 'contact.php', array('view' => 'alpha')));
        if (get_config('CHAT_ENABLE')) {
            $navigation->addSubNavigation('chat', new Navigation(_('Chat'), 'chat_online.php'));
        }
        // study groups
        if (get_config('STUDYGROUPS_ENABLE')) {
            $navigation->addSubNavigation('browse',new Navigation(_('Studiengruppen'), 'dispatch.php/studygroup/browse'));
        }
        // ranking
        $navigation->addSubNavigation('score', new Navigation(_('Rangliste'), 'score.php'));
        $this->addSubNavigation('community', $navigation);

        // calendar / home page
        if (!$perm->have_perm('admin')) {
            
            $navigation = new Navigation(_('Profil'), 'about.php');

            if ($perm->have_perm('autor')) {
                $navigation->addSubNavigation('settings', new Navigation(_('Einstellungen'), 'edit_about.php?view=allgemein'));
            }

            $this->addSubNavigation('profile', $navigation);
            $navigation = new Navigation(_('Mein Planer'));

            if (get_config('CALENDAR_ENABLE')) {
                $navigation->addSubNavigation('calendar', new Navigation(_('Terminkalender'), 'calendar.php'));
            }

            $navigation->addSubNavigation('schedule', new Navigation(_('Stundenplan'), 'dispatch.php/calendar/schedule'));
            $this->addSubNavigation('planner', $navigation);
        }

        // module administration
        if ($perm->have_perm('dozent') && get_config('STM_ENABLE')) {
            $navigation = new Navigation(_('Studienmodule'), 'auswahl_module.php');
            $this->addSubNavigation('admin_modules', $navigation);
        }

        // global search
        $navigation = new Navigation(_('Suchen'), 'sem_portal.php');
        $navigation->addSubNavigation('user', new Navigation(_('Personensuche'), 'browse.php'));
        $navigation->addSubNavigation('course', new Navigation(_('Veranstaltungssuche'), 'sem_portal.php'));
        $this->addSubNavigation('search', $navigation);
        
        // tools
        $navigation = new Navigation(_('Tools'));
        $navigation->addSubNavigation('news', new Navigation(_('Ankündigungen'), 'admin_news.php?range_id=self'));
        
        if (get_config('VOTE_ENABLE')) {
            $navigation->addSubNavigation('vote', new Navigation(_('Umfragen und Tests'), 'admin_vote.php', array('page' => 'overview', 'showrangeID' => $username)));
            $navigation->addSubNavigation('evaluation',new Navigation(_('Evaluationen'), 'admin_evaluation.php', array('rangeID' => $username)));
        }
        
        // literature
        if (get_config('LITERATURE_ENABLE')) {
            $navigation->addSubNavigation('literature', new Navigation(_('Literatur'), 'admin_lit_list.php', array('_range_id' => 'self')));
        }

        // elearning
        if (get_config('ELEARNING_INTERFACE_ENABLE')) {
            $navigation->addSubNavigation('elearning', new Navigation(_('Lernmodule'), 'my_elearning.php'));
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
