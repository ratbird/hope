<?php

/**
 * Score.php
 * model class for table Score
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.2
 */
class Score extends SimpleORMap {

    private $score_content_cache;

    // How often should the score be updated
    const UPDATE_INTERVAL = 86400;

    /**
     * DB Settings
     */
    protected static function configure($config = array()) {
        $config['db_table'] = 'user_score';
        $config['belongs_to']['user'] = array(
            'class_name' => 'User'
        );
        $config['additional_fields']['title'] = true;
        $config['additional_fields']['king'] = true;
        parent::configure($config);
    }
    
    /**
     * Returns the score for a user (Refresh score if it is to old)
     * 
     * @return int score
     */
    public function getScore() {
        if (!$this->content['score'] || (time() - $this->chdate) > static::UPDATE_INTERVAL) {
            $this->content['score'] = $this->calculate();
            $this->store();
        }
        return $this->content['score'];
    }

    /**
     * Get kings of a score
     * 
     * @return mixed Kings
     */
    public function getKing() {
        return StudipKing::is_king($this->user_id, true);
    }

    /**
     * Returns the title of a scorer
     * 
     * @return String title
     */
    public function getTitle() {
        $allTitle = self::getAllTitle();
        $title = min(array(count($allTitle), floor(log10($this->score) / log10(2))));
        return $allTitle[$title][$this->user->info->geschlecht];
    }

    /**
     * Returns array with all titles
     * 
     * @return array titles
     */
    protected static function getAllTitle() {
        return array(
            array(1 => _("Unbeschriebenes Blatt"), 2 => _("Unbeschriebenes Blatt")),
            array(1 => _("Unbeschriebenes Blatt"), 2 => _("Unbeschriebenes Blatt")),
            array(1 => _("Unbeschriebenes Blatt"), 2 => _("Unbeschriebenes Blatt")),
            array(1 => _("Neuling"), 2 => _("Neuling")),
            array(1 => _("Greenhorn"), 2 => _("Greenhorn")),
            array(1 => _("Anfänger"), 2 => _("Anfängerin")),
            array(1 => _("Einsteiger"), 2 => _("Einsteigerin")),
            array(1 => _("Beginner"), 2 => _("Beginnerin")),
            array(1 => _("Novize"), 2 => _("Novizin")),
            array(1 => _("Fortgeschrittener"), 2 => _("Fortgeschrittene")),
            array(1 => _("Kenner"), 2 => _("Kennerin")),
            array(1 => _("Könner"), 2 => _("Könnerin")),
            array(1 => _("Profi"), 2 => _("Profi")),
            array(1 => _("Experte"), 2 => _("Expertin")),
            array(1 => _("Meister"), 2 => _("Meisterin")),
            array(1 => _("Großmeister"), 2 => _("Großmeisterin")),
            array(1 => _("Idol"), 2 => _("Idol")),
            array(1 => _("Guru"), 2 => _("Hohepriesterin")),
            array(1 => _("Lichtgestalt"), 2 => _("Lichtgestalt")),
            array(1 => _("Halbgott"), 2 => _("Halbgöttin")),
            array(1 => _("Gott"), 2 => _("Göttin")),
        );
    }

    /**
     * Calculates the score for a user. Will pass the function on to the first
     * ScorePlugin, that is found
     * 
     * @return int
     */
    private function calculate() {

        $plugins = PluginEngine::getPlugins('ScorePlugin');
        if ($plugins) {
            return $plugins[0]::calculate($this->user_id);
        }

        // set user id
        $user_id = $this->user_id;

        // Foren
        $postings = 0;
        foreach (PluginEngine::getPlugins('ForumModule') as $plugin) {
            $postings += $plugin->getNumberOfPostingsForUser($user_id);
        }

        $query = "SELECT COUNT(*) FROM dokumente WHERE user_id = ? AND range_id <> 'provisional'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $dokumente = $statement->fetchColumn();

        $query = "SELECT COUNT(*) FROM seminar_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $seminare = $statement->fetchColumn();

        $query = "SELECT COUNT(*) FROM archiv_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $archiv = $statement->fetchColumn();

        $query = "SELECT COUNT(*) FROM user_inst WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $institut = $statement->fetchColumn();

        $query = "SELECT COUNT(*) FROM news WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $news = $statement->fetchColumn();

        $query = "SELECT COUNT(contact_id) FROM contact WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $contact = $statement->fetchColumn();

        // TODO: Count only visible categories.
        $query = "SELECT LEAST(50, COUNT(kategorie_id)) FROM kategorien WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $katcount = $statement->fetchColumn();

        $query = "SELECT mkdate FROM user_info WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $age = $statement->fetchColumn() ? : 1011275740; // = Thu, 17 Jan 2002 13:55:40 GMT, TODO Why this exact date??
        $age = (time() - $age) / 31536000; // = 365 * 24 * 60 * 60 = 1 year
        $age = 2 + log($age);
        if ($age < 1) {
            $age = 1;
        }

        if (get_config('VOTE_ENABLE')) {
            $query = "SELECT COUNT(*) FROM vote WHERE range_id = ? AND state IN ('active', 'stopvis')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user_id));
            $vote = 2 * $statement->fetchColumn();

            $query = "SELECT COUNT(*) FROM vote_user WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user_id));
            $vote += $statement->fetchColumn();

            $query = "SELECT COUNT(DISTINCT vote_id)
                      FROM voteanswers_user
                      LEFT JOIN voteanswers USING (answer_id)
                      WHERE user_id = ?
                      GROUP BY user_id";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user_id));
            $vote += $statement->fetchColumn();

            $query = "SELECT COUNT(*)
                      FROM eval
                      WHERE author_id = ? AND startdate < UNIX_TIMESTAMP()
                        AND ((stopdate IS NULL AND timespan IS NULL)
                             OR stopdate > UNIX_TIMESTAMP()
                             OR stopdate + timespan > UNIX_TIMESTAMP())";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user_id));
            $vote += 2 * $statement->fetchColumn();

            $query = "SELECT COUNT(*) FROM eval_user WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user_id));
            $vote += $statement->fetchColumn();
        }

        if (get_config('WIKI_ENABLE')) {
            $query = "SELECT COUNT(*) FROM wiki WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user_id));
            $wiki = $statement->fetchColumn();
        }

        $query = "SELECT COUNT(*) FROM blubber JOIN seminare USING (Seminar_id) WHERE user_id = ? AND (context_type = 'public' OR (context_type = 'course' AND seminare.status NOT IN (?)))";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id, studygroup_sem_types()));
        $blubber = $statement->fetchColumn();

        $visits = object_return_views($user_id);

        $scoreplugins = PluginEngine::getPlugins('SystemPlugin') + PluginEngine::getPlugins('StandardPlugin');
        $pluginscore = 0;
        $pluginscount = 0;

        foreach ($scoreplugins as $scoreplugin) {
            if ($scoreplugin instanceof AbstractStudIPSystemPlugin ||
                    $scoreplugin instanceof AbstractStudIPStandardPlugin) {
                $pluginscore += $scoreplugin->getScore();
                $pluginscount++;
            }
        }
        if ($pluginscount > 0) {
            $pluginscore = round($pluginscore / $pluginscount);
        }


        // Die HOCHGEHEIME Formel:
        $score = (5 * $postings) + (5 * $news) + (20 * $dokumente) + (2 * $institut) + (10 * $archiv * $age) + (10 * $contact) + (20 * $katcount) + (5 * $seminare) + (1 * $gaeste) + (5 * $vote) + (5 * $wiki) + (5 * $blubber) + (3 * $visits);
        $score += $pluginscore;
        $score = round($score / $age);

        if (Avatar::getAvatar($user_id)->is_customized()) {
            $score *=10;
        }
        return $score;
    }

    /**
     * Get personal content (This has not to be cached anymore since we will
     * never display all users anymore)
     * 
     * @param md5 $user_id
     */
    public function getScoreContent() {
        
        // Fetch username
        $username = User::find($this->user_id)->username;
        
        // Get DB Connection
        $db = DBManager::get();

        // News
        $news = $db->fetchColumn("SELECT COUNT(*) FROM news_range WHERE range_id = ?", array($this->user_id));
        if ($news) {
            $tmp = sprintf(ngettext('Eine persönliche Ankündigung', '%s persönliche Ankündigungen', $news), $news);
            $content .= sprintf('<a href="%s">%s</a> ', URLHelper::getLink('dispatch.php/profile', compact('username')), Assets::img('icons/16/blue/news.png', tooltip2($tmp)));
        } else {
            $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
        }

        // Votes
        $vote = $db->fetchColumn("SELECT COUNT(*) FROM vote WHERE range_id = ?", array($this->user_id));
        if ($vote) {
            $tmp = sprintf(ngettext('Eine Umfrage', '%s Umfragen', $vote), $vote);
            $content .= sprintf('<a href="%s">%s</a> ', URLHelper::getLink('dispatch.php/profile', compact('username')), Assets::img('icons/16/blue/vote.png', tooltip2($tmp)));
        } else {
            $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
        }

        // Termine
        $termin = $db->fetchColumn("SELECT COUNT(*) FROM calendar_events WHERE range_id = ? AND class = 'PUBLIC'", array($this->user_id));
        if ($termin) {
            $tmp = sprintf(ngettext('Ein Termin', '%s Termine', $termin), $termin);
            $content .= sprintf('<a href="%s">%s</a> ', URLHelper::getLink('dispatch.php/profile#a', compact('username')), Assets::img('icons/16/blue/schedule.png', tooltip2($tmp)));
        } else {
            $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
        }

        // Literaturangaben
        $lit =  $db->fetchColumn("SELECT COUNT(*) FROM lit_list WHERE range_id = ?", array($this->user_id));
        if ($lit) {
            $tmp = sprintf(ngettext('Eine Literaturangabe', '%s Literaturangaben', $lit), $lit);
            $content .= sprintf('<a href="%s">%s</a> ', URLHelper::getLink('dispatch.php/profile', compact('username')), Assets::img('icons/16/blue/literature.png', tooltip2($tmp)));
        } else {
            $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
        }
        return $content;
    }

}
