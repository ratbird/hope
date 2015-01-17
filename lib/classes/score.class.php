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
    var $title; // Title that refers to the score
    var $myscore;   // my own Score
    var $mygender;
    var $score_content_cache = null;

    // How long is the duration of a score-block?
    const MEASURING_STEP = 1800; // half an hour

    // Konstruktor
    function Score($user_id)
    {
        $this->myscore = $this->GetMyScore();
        $this->mygender = $this->GetGender($user_id);
        $this->title = $this->gettitel($this->myscore, $this->mygender);
        $this->publik = $this->CheckScore($user_id);
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
                      INNER JOIN calendar_event ON (range_id = u.user_id)
                      INNER JOIN event_data ON (calendar_event.event_id = event_data.event_id AND class = 'PUBLIC')
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
        $content = Assets::img('blank.gif', array('width' => 16)) . ' ';

        // News
        if ($news = $this->score_content_cache[$user_id]['newscount']) {
            $tmp = sprintf(ngettext('Eine persönliche Ankündigung', '%s persönliche Ankündigungen', $news), $news);
            $content .= sprintf('<a href="%s">%s</a> ',
                                URLHelper::getLink('dispatch.php/profile', compact('username')),
                                Assets::img('icons/16/blue/news.png', tooltip2($tmp)));
        } else {
            $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
        }

        // Votes
        if ($vote = $this->score_content_cache[$user_id]['votecount']) {
            $tmp = sprintf(ngettext('Eine Umfrage', '%s Umfragen', $vote), $vote);
            $content .= sprintf('<a href="%s">%s</a> ',
                                URLHelper::getLink('dispatch.php/profile', compact('username')),
                                Assets::img('icons/16/blue/vote.png', tooltip2($tmp)));
        } else {
            $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
        }

        // Termine
        if ($termin = $this->score_content_cache[$user_id]['eventcount']) {
            $tmp = sprintf(ngettext('Ein Termin', '%s Termine', $termin), $termin);
            $content .= sprintf('<a href="%s">%s</a> ',
                                URLHelper::getLink('dispatch.php/profile#a', compact('username')),
                                Assets::img('icons/16/blue/schedule.png', tooltip2($tmp)));
        } else {
            $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
        }

        // Literaturangaben
        if ($lit = $this->score_content_cache[$user_id]['litcount']) {
            $tmp = sprintf(ngettext('Eine Literaturangabe', '%s Literaturangaben', $lit), $lit);
            $content .= sprintf('<a href="%s">%s</a> ',
                                URLHelper::getLink('dispatch.php/profile', compact('username')),
                                Assets::img('icons/16/blue/literature.png', tooltip2($tmp)));
        } else {
            $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
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
        $titel[5]  =    array(0 => _("Anfänger"), 1 => _("Anfängerin"));
        $titel[6]  =    array(0 => _("Einsteiger"), 1 => _("Einsteigerin"));
        $titel[7]  =    array(0 => _("Beginner"), 1 => _("Beginnerin"));
        $titel[8]  =    array(0 => _("Novize"), 1 => _("Novizin"));
        $titel[9]  =    array(0 => _("Fortgeschrittener"), 1 => _("Fortgeschrittene"));
        $titel[10] =    array(0 => _("Kenner"), 1 => _("Kennerin"));
        $titel[11] =    array(0 => _("Könner"), 1 => _("Könnerin"));
        $titel[12] =    array(0 => _("Profi"), 1 => _("Profi"));
        $titel[13] =    array(0 => _("Experte"), 1 => _("Expertin"));
        $titel[14] =    array(0 => _("Meister"), 1 => _("Meisterin"));
        $titel[15] =    array(0 => _("Großmeister"), 1 => _("Großmeisterin"));
        $titel[16] =    array(0 => _("Idol"), 1 => _("Idol"));
        $titel[17] =    array(0 => _("Guru"), 1 => _("Hohepriesterin"));
        $titel[18] =    array(0 => _("Lichtgestalt"), 1 => _("Lichtgestalt"));
        $titel[19] =    array(0 => _("Halbgott"), 1 => _("Halbgöttin"));
        $titel[20] =    array(0 => _("Gott"), 1 => _("Göttin"));

        return $titel[$logscore][$gender == 2 ? 1 : 0];
    }

    /**
    * Retrieves the score for the current user
    *
    * @return       integer the score
    *
    */
    function GetMyScore($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        $cache = StudipCacheFactory::getCache();
        if ($cache->read("user_score_of_".$user_id)) {
            return $cache->read("user_score_of_".$user_id);
        }
        //Behold! The all new mighty score algorithm!
        //Step 1: Select all activities as mkdate-timestamps.
        //Step 2: Group these activities to timeslots of halfhours
        //        with COUNT(*) as a weigh of the timeslot.
        //Step 3: Calculate the measurement of the timeslot from the weigh of it.
        //        This makes the first activity count fully, the second
        //        almost half and so on. We use log_n to make huge amounts of
        //        activities to not count so much.
        //Step 4: Calculate a single score for each timeslot depending on the
        //        measurement and the mkdate-timestamp. Use arctan as the function
        //        here so that older activities tend to zero.
        //Step 5: Sum all scores from all timeslots together.
        $sql = "
            SELECT round(SUM((-atan(measurement / " . round(31556926 / self::MEASURING_STEP) . ") / PI() + 0.5) * 200)) as score
            FROM (
                SELECT ((unix_timestamp() / " . self::MEASURING_STEP . ") - timeslot) / (LN(weigh) + 1) AS measurement
                FROM (
                    SELECT (round(mkdate / " . self::MEASURING_STEP . ")) as timeslot, COUNT(*) AS weigh
                    FROM (
                        " . $this->createTimestampQuery() . "
                    ) as mkdates
                    GROUP BY timeslot
                ) as measurements
            ) as dates
        ";
        $stmt = DBManager::get()->prepare($sql);
        $stmt->execute(array(':user' => $user_id));
        $score = $stmt->fetchColumn();

        $query = "UPDATE user_info SET score = ? WHERE user_id = ? AND score > 0";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($score, $user_id));

        $cache->write("user_score_of_".$user_id, $score, 60 * 5);

        return $score;
    }

    protected function createTimestampQuery() {
        $statements = array();
        foreach ($this->getActivityTables() as $table) {
            $statements[] = "SELECT "
                . ($table['date_column'] ? : 'mkdate')
                . " AS mkdate FROM "
                . $table['table']
                . " WHERE "
                . ($table['user_id_column'] ? : 'user_id')
                . " = :user "
                . ($table['where'] ? (' AND ' . $table['where']) : '');
        }
        return join(' UNION ', $statements);
    }

    protected function getActivityTables() {
        $tables = array();
        $tables[] = array('table' => "user_info");
        $tables[] = array('table' => "comments");
        $tables[] = array('table' => "dokumente");
        $tables[] = array('table' => "forum_entries");
        $tables[] = array('table' => "news");
        $tables[] = array('table' => "seminar_user");
        $tables[] = array(
            'table' => "blubber",
            'where' => "context_type != 'private'"
        );
        $tables[] = array(
            'table' => "kategorien",
            'user_id_column' => "range_id"
        );
        $tables[] = array(
            'table' => "message",
            'user_id_column' => "autor_id"
        );
        $tables[] = array(
            'table' => "vote",
            'user_id_column' => "range_id"
        );
        $tables[] = array(
            'table' => "voteanswers_user",
            'date_column' => "votedate"
        );
        $tables[] = array(
            'table' => "vote_user",
            'date_column' => "votedate"
        );
        $tables[] = array(
            'table' => "wiki",
            'date_column' => "chdate"
        );

        foreach (PluginManager::getInstance()->getPlugins("ScorePlugin") as $plugin) {
            foreach ((array) $plugin->getPluginActivityTables() as $table) {
                if ($table['table']) {
                    $tables[] = $table;
                }
            }
        }

        return $tables;
    }
}
