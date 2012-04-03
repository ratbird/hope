<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
 * score.class.php - Score class
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Ralf Stockmann <rstockm@gwdg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class Score
{
    var $score; // Score of the user
    var $publik;    // whether or not the score is published
    var $ismyscore; // wheter or not this is my own score
    var $title; // Title that refers to the score
    var $myscore;   // my own Score
    var $mygender;
    var $score_content_cache = null;


    // Konstruktor
    function Score($user_id)
    {
        $this->ismyscore = $this->CheckOwner($user_id);
        if ($this->ismyscore){
            $this->myscore = $this->GetMyScore();
        }
        $this->mygender = $this->GetGender($user_id);
        $this->title = $this->gettitel($this->myscore, $this->mygender);
        $this->publik = $this->CheckScore($user_id);
    }

    function CheckOwner($user_id)
    {
        global $user;
        if ($user_id == $user->id)
            return TRUE;
        else
            return FALSE;
    }

    function GetGender($user_id)
    {
        $query = "SELECT geschlecht FROM user_info WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        return $statement->fetchColumn();
    }

    function PublishScore()
    {
        global $user;
        
        $query = "UPDATE user_info SET score = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->myscore, $user->id));

        $this->publik = $this->myscore;
    }

    function KillScore()
    {
        global $user;
        
        $query = "UPDATE user_info SET score = 0 WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id));
        
        $this->publik = FALSE;
    }

    function IsMyScore()
    {
        return $this->ismyscore;
    }

    function ReturnMyScore()
    {
        return $this->myscore;
    }

    function ReturnMyTitle()
    {
        return $this->title;
    }

    function ReturnPublik()
    {
        return $this->publik;
    }

    function GetScore($user_id)
    {
        $query = "SELECT score FROM user_info WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        return $statement->fetchColumn();
    }

    function CheckScore($user_id)
    {
        $query = "SELECT score FROM user_info WHERE user_id = ? AND score > 0";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        return $statement->fetchColumn();
    }

    function doRefreshScoreContentCache()
    {
        $query = "SELECT a.user_id, username
                  FROM user_info AS a
                  LEFT JOIN auth_user_md5 AS b USING (user_id)
                  WHERE score > 0";
        $statement = DBManager::get()->query($query);
        
        $s = 0;
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $this->score_content_cache[$row['user_id']]['username'] = $row['username'];
            ++$s;
        }

        if ($s) {
            // Guestbook
            $query = "SELECT u.user_id, COUNT(u.user_id) AS guestcount
                      FROM user_info AS u
                      INNER JOIN guestbook ON (range_id = u.user_id)
                      WHERE score > 0 AND guestbook = 1
                      GROUP BY u.user_id
                      ORDER BY NULL"; // <- see http://stackoverflow.com/questions/5231907/order-by-null-in-mysql
            $statement = DBManager::get()->query($query);
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->score_content_cache[$row['user_id']]['guestcount'] = $row['guestcount'];
            }

            // News
            $query = "SELECT u.user_id, COUNT(u.user_id) AS newscount
                      FROM user_info AS u
                      JOIN news_range AS nr ON (nr.range_id = u.user_id)
                      INNER JOIN news AS n ON (nr.news_id = n.news_id)
                      WHERE u.score > 0 AND (? - n.date) <= n.expire
                      GROUP BY u.user_id
                      ORDER BY NULL";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(gmmktime()));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->score_content_cache[$row['user_id']]['newscount'] = $row['newscount'];
            }

            // Events
            $query = "SELECT u.user_id, COUNT(u.user_id) AS eventcount
                      FROM user_info AS u
                      INNER JOIN calendar_events ON (range_id = u.user_id AND class = 'PUBLIC')
                      WHERE score > 0 AND ? <= end
                      GROUP BY u.user_id
                      ORDER BY NULL";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(gmmktime()));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->score_content_cache[$row['user_id']]['eventcount'] = $row['eventcount'];
            }

            // Literature
            $query = "SELECT u.user_id, COUNT(u.user_id) AS litcount
                      FROM user_info AS u
                      INNER JOIN lit_list ON (range_id = u.user_id)
                      INNER JOIN lit_list_content USING (list_id)
                      WHERE score > 0 AND visibility = 1
                      GROUP BY u.user_id
                      ORDER BY NULL";
            $statement = DBManager::get()->query($query);
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->score_content_cache[$row['user_id']]['litcount'] = $row['litcount'];
            }

            // Votes
            if (get_config('VOTE_ENABLE')){
                $query = "SELECT u.user_id, COUNT(u.user_id) AS votecount
                          FROM user_info AS u
                          INNER JOIN vote ON (range_id = u.user_id)
                          WHERE score > 0
                          GROUP BY u.user_id
                          ORDER BY NULL";
                $statement = DBManager::get()->query($query);
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->score_content_cache[$row['user_id']]['votecount'] = $row['votecount'];
                }
            }
        }
        return true;
    }

    /**
     *
     * @param md5 $user_id
     */
    function GetScoreContent($user_id)
    {
        if (!is_array($this->score_content_cache)){
            $this->doRefreshScoreContentCache();
        }
        $username = $this->score_content_cache[$user_id]['username'];
        if ( ($gaeste = $this->score_content_cache[$user_id]['guestcount']) !== null ) {
            if ($gaeste == 1)
                $tmp = _("Gästebuch mit einem Eintrag");
            else
                $tmp = sprintf(_("Gästebuch mit %s Einträgen"), $gaeste);
            $content .= "<a href=\"about.php?username=$username&guestbook=open#guest\"><img src=\"".Assets::image_path('icons/16/blue/guestbook.png')."\" ".tooltip("$tmp")."></a> ";
        } else {
            $content .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"16\"> ";
        }

        if ( ($news = $this->score_content_cache[$user_id]['newscount']) ) {
            if ($news == 1) {
                $tmp = _("Eine persönliche Ankündigung");
            } else {
                $tmp = sprintf(_("%s persönliche Ankündigungen"), $news);
            }
            $content .= "<a href=\"about.php?username=$username\"><img src=\"".Assets::image_path('icons/16/blue/breaking-news.png')."\" ".tooltip($tmp)."></a> ";
        } else {
            $content .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"16\"> ";
        }
        if ( ($vote = $this->score_content_cache[$user_id]['votecount']) ) {
            if ($vote == 1) {
                $tmp = _("Eine Umfrage");
            } else {
                $tmp = sprintf(_("%s Umfragen"), $vote);
            }
            $content .= "<a href=\"about.php?username=$username\"><img src=\"".Assets::image_path('icons/16/blue/vote.png')."\" ".tooltip($tmp)."></a> ";
        } else {
            $content .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"16\"> ";
        }

        if ( ($termin = $this->score_content_cache[$user_id]['eventcount']) ) {
            if ($termin == 1)
                $tmp = _("Termin");
            else
                $tmp = _("Termine");
            $content .= "<a href=\"about.php?username=$username#a\"><img src=\"".Assets::image_path('icons/16/blue/schedule.png')."\" ".tooltip("$termin $tmp")."></a> ";
        } else {
            $content .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"16\"> ";
        }

        if ( ($lit = $this->score_content_cache[$user_id]['litcount']) ) {
            if ($lit == 1)
                $tmp = _("Literaturangabe");
            else
                $tmp = _("Literaturangaben");
            $content .= "<a href=\"about.php?username=$username\"><img src=\"".Assets::image_path('icons/16/blue/literature.png')."\" ".tooltip("$lit $tmp")."></a> ";
        } else {
            $content .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"16\"> ";
        }
        return $content;
    }

    /**
    * Retrieves the titel for a given studip score
    *
    * @param        integer a score value
    * @param        integer gender (0: unknown, 1: male; 2: female)
    * @return       string  the titel
    *
    */
    function gettitel($score, $gender = 0)
    {
        if ($score)
            $logscore = floor(log10($score) / log10(2));
        else
            $logscore = 0;

        if ($logscore > 20)
            $logscore = 20;

        $titel[0]  =    array(0 => _("Unbeschriebenes Blatt"), 1 => _("Unbeschriebenes Blatt"));
        $titel[1]  =    array(0 => _("Unbeschriebenes Blatt"), 1 => _("Unbeschriebenes Blatt"));
        $titel[2]  =    array(0 => _("Unbeschriebenes Blatt"), 1 => _("Unbeschriebenes Blatt"));
        $titel[3]  =    array(0 => _("Neuling"), 1 => _("Neuling"));
        $titel[4]  =    array(0 => _("Greenhorn"), 1 => _("Greenhorn"));
        $titel[5]  =    array(0 => _("Anf&auml;nger"), 1 => _("Anf&auml;ngerin"));
        $titel[6]  =    array(0 => _("Einsteiger"), 1 => _("Einsteigerin"));
        $titel[7]  =    array(0 => _("Beginner"), 1 => _("Beginnerin"));
        $titel[8]  =    array(0 => _("Novize"), 1 => _("Novizin"));
        $titel[9]  =    array(0 => _("Fortgeschrittener"), 1 => _("Fortgeschrittene"));
        $titel[10] =    array(0 => _("Kenner"), 1 => _("Kennerin"));
        $titel[11] =    array(0 => _("K&ouml;nner"), 1 => _("K&ouml;nnerin"));
        $titel[12] =    array(0 => _("Profi"), 1 => _("Profi"));
        $titel[13] =    array(0 => _("Experte"), 1 => _("Expertin"));
        $titel[14] =    array(0 => _("Meister"), 1 => _("Meisterin"));
        $titel[15] =    array(0 => _("Gro&szlig;meister"), 1 => _("Gro&szlig;meisterin"));
        $titel[16] =    array(0 => _("Idol"), 1 => _("Idol"));
        $titel[17] =    array(0 => _("Guru"), 1 => _("Hohepriesterin"));
        $titel[18] =    array(0 => _("Lichtgestalt"), 1 => _("Lichtgestalt"));
        $titel[19] =    array(0 => _("Halbgott"), 1 => _("Halbg&ouml;ttin"));
        $titel[20] =    array(0 => _("Gott"), 1 => _("G&ouml;ttin"));

        return $titel[$logscore][$gender == 2 ? 1 : 0];
    }

    /**
    * Retrieves the score for the current user
    *
    * @return       integer the score
    *
    */
    function GetMyScore()
    {
        global $user, $auth;

        $user_id = $user->id; //damit keiner schummelt...

        // Werte holen...
        $query = "SELECT COUNT(*) FROM px_topics WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $postings = $statement->fetchColumn();

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

        $query = "SELECT COUNT(*) FROM guestbook WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $gaeste = $statement->fetchColumn();

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
        $age = $statement->fetchColumn() ?: 1011275740; // = Thu, 17 Jan 2002 13:55:40 GMT, TODO Why this exact date??
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
        $score = (5*$postings) + (5*$news) + (20*$dokumente) + (2*$institut) + (10*$archiv*$age) + (10*$contact) + (20*$katcount) + (5*$seminare) + (1*$gaeste) + (5*$vote) + (5*$wiki) + (3*$visits);
        $score += $pluginscore;
        $score = round($score/$age);

        if (Avatar::getAvatar($user_id)->is_customized()) {
            $score *=10;
        }

        //Schreiben des neuen Wertes
        $query = "UPDATE user_info SET score = ? WHERE user_id = ? AND score > 0";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($score, $user_id));

        return $score;
    }
}
