<?php
/*
 * AboutController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      David Siegfried <david.siegfried@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

global $RELATIVE_PATH_CALENDAR;
global $RELATIVE_PATH_CHAT;

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/user.php';
require_once 'app/models/kategorie.php';
require_once 'app/models/about.php';

require_once 'lib/messaging.inc.php';
require_once('lib/object.inc.php');
require_once('lib/statusgruppe.inc.php');
require_once('lib/showNews.inc.php');
require_once('lib/show_dates.inc.php');
require_once('lib/user_visible.inc.php');
require_once('lib/dates.inc.php');

require_once('lib/classes/score.class.php');
require_once('lib/classes/StudipLitList.class.php');
require_once('lib/classes/Avatar.class.php');
require_once('lib/classes/Institute.class.php');
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/classes/StudipKing.class.php');


class AboutController extends AuthenticatedController
{
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        
        // Checks if user is logged in
        $GLOBALS['auth']->login_if(($GLOBALS['auth']->auth['uid'] === 'nobody'));
        
        // Checks if voting is enabled
        if (get_config('VOTE_ENABLE')) {
            include_once ("lib/vote/vote_show.inc.php");
        }
        
        // Checks if chat is enabled
        if (get_config('CHAT_ENABLE')) {
            include_once $RELATIVE_PATH_CHAT.'/chat_func_inc.php';
            
            if (Request::get('kill_chat')) {
                chat_kill_chat(Request::option('kill_chat'));
            }
        }
        
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));

        Navigation::activateItem('/profile/index');
        PageLayout::setHelpKeyword('Basis.Homepage');
        SkipLinks::addIndex(_('Benutzerprofil'), 'user_profile', 100);
        
        
        $this->user         = $GLOBALS['user']; // current logged in user
        $this->perm         = $GLOBALS['perm']; // perms of current logged in user
        $this->current_user = User::findByUsername(Request::get('username', $this->user->username)); // current selected user
        // get additional informations to selected user
        $this->about        = new AboutModel($this->current_user->user_id, $this->user->user_id);
        
        // set the page title depending on user selection
        if($this->current_user['user_id'] == $this->user->id && !$this->current_user['locked']) {
            PageLayout::setTitle(_('Mein Profil'));
        } elseif ($this->current_user['user_id'] && ($this->perm->have_perm('root') || (!$this->current_user['locked'] && get_visibility_by_id($this->current_user['user_id'])))) {
            PageLayout::setTitle(_('Profil')  .' - ' . $this->current_user->getFullname());
        } else {
            PageLayout::setTitle(_('Profil'));
            $action = 'not_available';
        }      
                
        // count views of Page
        if ($this->current_user->user_id != $this->user->id) {
            object_add_view($this->current_user->user_id);
        } else {
            UserConfig::get($this->current_user->user_id)->store('PROFILE_LAST_VISIT', time());
        }    
    }

    /**
     * Entry point of the controller that displays all the information of the selected or current user
     * @return void
     */
    public function index_action()
    {   
        //opening and closing for dates
        if (Request::option('dopen')) {
            $about_data['dopen'] = Request::option('dopen');
        }

        if (Request::option('dclose')) {
            $about_data['dopen']='';
        }
        
        if ($_SESSION['sms_msg']) {
            $this->msg = $_SESSION['sms_msg'];
            unset($_SESSION['sms_msg']);
        }
        
        
        // Template Index_Box for render-partials
        $layout = $GLOBALS['template_factory']->open('shared/index_box');
        $this->shared_box = $layout;
        
        // if he has not yet stored into user_info, he comes in with no values
        if ($this->current_user->mkdate === null) {
            $this->current_user->store();
        }
        
        if (get_config('NEWS_RSS_EXPORT_ENABLE')) {
            $news_author_id = StudipNews::GetRssIdFromUserId($this->current_user->user_id);
            if ($news_author_id) {
                PageLayout::addHeadElement('link', array('rel'   => 'alternate',
                                                         'type'  => 'application/rss+xml',
                                                         'title' => 'RSS',
                                                         'href'  => 'rss.php?id='.$news_author_id));
            }
        }

        $msging = new messaging;
        //Buddie hinzufuegen
        if (Request::option('cmd') == 'add_user') {
            $msging->add_buddy (Request::get('add_uname'), 0);
        }


        // Get Avatar
        $avatar_user_id = $this->about->checkVisibility('picture') ? $this->current_user->user_id : 'nobody';
        $this->avatar   = Avatar::getAvatar($avatar_user_id)->getImageTag(Avatar::NORMAL);

        // GetScroreList
        $score  = new Score($this->current_user->user_id);
        if ($score->IsMyScore()) {
            $this->score        = $score->ReturnMyScore();
            $this->score_title  = $score->ReturnMyTitle();
        } elseif ($score->ReturnPublik()) {
            $this->score         = $score->GetScore($this->current_user->user_id);
            $this->score_title   = $score->gettitel($score->GetScore($this->current_user->user_id), $score->GetGender($this->current_user->user_id));
        }
        
        // Additional user information
        $this->public_email = get_visible_email($this->current_user->user_id);
        $this->motto        = $this->about->getVisibilityValue('motto');
        $this->private_nr   = $this->about->getVisibilityValue('privatnr','private_phone');
        $this->private_cell = $this->about->getVisibilityValue('privatcell','private_cell');
        $this->privadr      = $this->about->getVisibilityValue('privadr','privadr');
        $this->homepage     = $this->about->getVisibilityValue('Home','homepage');
        

        // skypein formations
        if (get_config('ENABLE_SKYPE_INFO') && $this->about->checkVisibility('skype_name')) { 
            $this->skype_name   = UserConfig::get($this->current_user->user_id)->SKYPE_NAME;
            $this->skype_status = UserConfig::get($this->current_user->user_id)->SKYPE_ONLINE_STATUS && $this->about->checkVisibility('skype_online_status');
        }

        // get generic datafield entries
        $this->shortDatafields  = $this->about->getShortDatafields();
        $this->longDatafields   = $this->about->getLongDatafields();
        
        // get working station of an user (institutes)
        $this->institutes = $this->about->getInstitutInformations();
        
        // get studying informations of an user
        if($this->current_user->perms != 'dozent') {
            $study_institutes = UserModel::getUserInstitute($this->current_user->user_id, true);

            if (count($study_institutes) > 0 && $this->about->checkVisibility('studying')) {
                $this->study_institutes = $study_institutes;
            }
        }

        if (($this->current_user->user_id == $this->user->user_id) && $GLOBALS['has_denoted_fields']) {
            $this->has_denoted_fields = true;
        }
        
        // get kings informations
        if ($score->IsMyScore() || $score->ReturnPublik()) {
            $kings = $this->about->getKingsInformations();

            if($kings != null) {
                $this->kings = $kings;
            }
        }

        // show news on profile page
        $show_admin = ($this->perm->have_perm('autor') && $this->user->user_id == $this->current_user->user_id) ||
            (isDeputyEditAboutActivated() && isDeputy($this->user->user_id, $this->current_user->user_id, true));
        if (($this->show_news   = $this->about->checkVisibility('news')) === true) {
            $this->about_data   = $about_data;
            $this->show_admin   = $show_admin;
        }

        // calendar
        if (get_config('CALENDAR_ENABLE')) {
            if ($this->current_user->perms != "root" && $this->current_user->perms != "admin") {
                if (($this->terms       = $this->about->checkVisibility('termine'))) {
                    $this->show_admin   = ($this->perm->have_perm("autor") && $this->user->user_id == $this->current_user->user_id);
                    $this->about_data   = $about_data;
                }
            }
        }

        // include and show votes and tests
        $this->show_votes = get_config('VOTE_ENABLE') && $this->about->checkVisibility('votes');
      
        // include and show friend-of-a-friend list
        // (direct/indirect connection via buddy list   
        if ($GLOBALS['FOAF_ENABLE'] && ($this->user->user_id != $this->current_user->user_id) && UserConfig::get($this->current_user->user_id)->FOAF_SHOW_IDENTITY) {
            include("lib/classes/FoafDisplay.class.php");
            
            $foaf =new FoafDisplay($this->user->user_id, $this->current_user->user_id, $this->current_user->username);
           
            $this->foaf = $foaf;
        }
        
        // show Guestbook
        $guest = new Guestbook($this->current_user->user_id, Request::int('guestpage', 0));

        if (($guest->active == true || $guest->rights == true) && $this->about->checkVisibility('guestbook')) {
            if (Request::quoted('guestbook') && $this->perm->have_perm('autor')) {
                $guestbook      = Request::quoted('guestbook');
                $post           = Request::quoted('post');
                $deletepost     = Request::quoted('deletepost');
                $studipticket   = Request::option('studipticket');
                
                $guest->actionsGuestbook($guestbook,$post,$deletepost,$studipticket);
            }
            
            $this->guestbook = $guest;
        }

        // Hier werden Lebenslauf, Hobbys, Publikationen und Arbeitsschwerpunkte ausgegeben:
        $ausgabe_felder = array(
            'lebenslauf'    => _('Lebenslauf'),
            'hobby'         => _('Hobbys'),
            'publi'         => _('Publikationen'),
            'schwerp'       => _('Arbeitsschwerpunkte')
        );

        foreach ($ausgabe_felder as $key => $value) {
            if ($this->about->checkVisibility($key)) {
                $ausgabe_inhalt[$value] = $this->current_user[$key];
            }
        }

        $this->ausgabe_inhalt = array_filter($ausgabe_inhalt);

        // Anzeige der Seminare, falls User = dozent
        if ($this->current_user['perms'] == 'dozent') {
            $this->seminare = $this->about->getDozentSeminars();
        }

        // Hompageplugins
        $homepageplugins = PluginEngine::getPlugins('HomepagePlugin');

        foreach ($homepageplugins as $homepageplugin) {
            // get homepageplugin tempaltes
            $template = $homepageplugin->getHomepageTemplate($this->current_user->user_id);
            // create output of the plugins
            if(!empty($template)) {
                $render .= $template->render(null, $layout);
            }
            $layout->clear_attributes();
        }

        $this->hompage_plugin = $render;
        
        // CHAT-Info
        if (get_config('CHAT_ENABLE')) {
            $this->chat_info = chat_show_info($this->current_user->user_id);
        }
        
        // show literature info
        if (get_config('LITERATURE_ENABLE')) {
            $lit_list = StudipLitList::GetFormattedListsByRange($this->current_user->user_id);
            if ($this->current_user->user_id == $this->user->user_id) {
                $this->admin_url    = 'admin_lit_list.php?_range_id=self';
                $this->admin_title  = _('Literaturlisten bearbeiten');
            }

            if ($this->about->checkVisibility('literature')) {
                $this->show_lit     = true;
                $this->lit_list     = $lit_list;
            }
        }

        // get categories
        $category = Kategorie::findByUserId($this->current_user->user_id);

        foreach($category as $cat) {
            $head = $cat->name;
            $body = $cat->content;    
            unset($vis_text);
            
            if ($this->user->user_id == $this->current_user->user_id) {
                $visibility = $this->about->getSpecificVisibilityValue('kat_' . $cat->kategorie_id);
                
                if($visibility) {
                    $vis_text .= '( ';
                    
                    switch ($visibility) {
                        case VISIBILITY_ME: $vis_text .= _('nur für mich sichtbar');
                            break;
                        case VISIBILITY_BUDDIES: $vis_text .= _('nur für meine Buddies sichtbar');
                            break;
                        case VISIBILITY_DOMAIN: $vis_text .= _('nur für meine Nutzerdomäne sichtbar');
                            break;
                        case VISIBILITY_EXTERN: $vis_text .= _('auf externen Seiten sichtbar');
                            break;
                        case VISIBILITY_STUDIP: $vis_text .= _('für alle Stud.IP-Nutzer sichtbar');
                            break;
                        default:
                    }

                    $vis_text .= ' )';
                }
            }
            
            if($this->about->checkVisibility('kat_'.$cat->kategorie_id)) {
                $categories[$cat->kategorie_id]['head']             = $head;
                $categories[$cat->kategorie_id]['zusatz']           = $vis_text;
                $categories[$cat->kategorie_id]['content']          = $body;
            }
        }
        
        if(!empty($categories)) {
            $this->categories = array_filter($categories, function ($item) { return !empty($item['content']); });
        }
        
    }

    /**
     * Action for a selection, where the user not exists
     * 
     * @return void 
     */
    public function not_available_action()
    {
        Navigation::getItem('/profile')->setActive(false);
        
        $message         = _('Dieses Profil ist nicht verfügbar.');
        $info[]          = _('Der Benutzer hat sich unsichtbar geschaltet oder ist im System nicht vorhanden.');
        $this->message   = $message;
        $this->info      = $info;
    }

}