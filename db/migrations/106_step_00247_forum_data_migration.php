<?php

set_time_limit(7200);

class Step00247ForumDataMigration extends Migration
{
    function description()
    {
        return 'Copy forum data to new forum core-plugin';
    }

    function up()
    {
        // get all seminars that need to be migrated
        $stmt = DBManager::get()->prepare("SELECT DISTINCT Seminar_id FROM px_topics
            WHERE topic_id = root_id
            ORDER BY mkdate ASC");
        $stmt->execute(); 
        
        while($seminar_id = $stmt->fetch(PDO::FETCH_COLUMN)) // for each seminar_id ... migrate data
        {
            if ($seminar_id != '30e0b89dcc9173d5fccf9f22b13b87bd') {    // FOR DEVELOPER-BOARD ONLY. REMOVE AFTER MIGRATION SUCCESSFULLY RUN.
            // prepare seminar for new forum
            self::checkRootEntry($seminar_id);
            
            // migrate content form old forum to the new one
            self::migrateEntries($seminar_id);
            
            // migrate visit-timestamps to the new forum
            self::migrateUserVisits($seminar_id);

            // migrate the connections with issues
            self::migrateIssues($seminar_id);
            }
        }   
    }

    static function migrateIssues($seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT p.topic_id FROM themen_termine t
            LEFT JOIN px_topics p ON (p.topic_id = t.issue_id)
            WHERE p.topic_id IS NOT NULL
                AND p.Seminar_id = ?");
        $stmt->execute(array($seminar_id));

        $stmt_insert = DBManager::get()->prepare("INSERT IGNORE INTO forum_entries_issues
            (topic_id, issue_id) 
            VALUES (?, ?)");

        while ($topic_id = $stmt->fetchColumn()) {
            $stmt_insert->execute(array($topic_id, $topic_id));
        }
    }

    static function migrateUserVisits($seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM object_user_visits
            WHERE object_id = ? AND type = 'forum'");
        $stmt->execute(array($seminar_id));
        
        $stmt_insert = DBManager::get()->prepare("REPLACE INTO forum_visits
            (user_id, seminar_id, visitdate, last_visitdate)
            VALUES (?, ?, ?, ?)");
        
        while ($data = $stmt->fetch()) {
            $stmt_insert->execute(array($data['user_id'], $data['object_id'], 
                $data['visitdate'], $data['last_visitdate']));
        }
    }

    static function getList($seminar_id, $get_childs = true)
    {
        $ret = array();
        
        $stmt = DBManager::get()->prepare("SELECT * FROM px_topics
            WHERE Seminar_id = ? AND topic_id = root_id
            ORDER BY mkdate ASC");
        $stmt->execute(array($seminar_id, $parent_id));
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // set depth-level
            $data['level'] = 0;
            $ret[] = $data;
            
            if ($get_childs) {
                // get childs
                $childs = self::getChilds($seminar_id, $data['topic_id']);

                if (!empty($childs)) {
                    $ret = array_merge($ret, $childs);
                }
            }
        }
        
        return $ret;
    }

    static function getEntries($seminar_id, $parent_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM px_topics
            WHERE Seminar_id = ? AND parent_id = ?
            ORDER BY mkdate ASC");
        $stmt->execute(array($seminar_id, $parent_id));
        
        return $stmt->fetchAll();
    }

    static function getChilds($seminar_id, $parent_id, $level = 1)
    {
        $ret = array();
        
        $stmt = DBManager::get()->prepare("SELECT * FROM px_topics
            WHERE Seminar_id = ? AND parent_id = ?
            ORDER BY mkdate ASC");
        $stmt->execute(array($seminar_id, $parent_id));
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // set depth-level
            $data['level'] = $level;
            $ret[] = $data;
            
            // get childs
            $childs = self::getChilds($seminar_id, $data['topic_id'], $level + 1);
            
            if (!empty($childs)) {
                $ret = array_merge($ret, $childs);
            }
        }
        
        return $ret;
    }
    
    static function migrateEntries($seminar_id)
    {
            foreach (self::getList($seminar_id, false) as $element) {
                self::insert(array(
                    'topic_id'    => $element['topic_id'],
                    'seminar_id'  => $seminar_id,
                    'user_id'     => $element['user_id'],
                    'name'        => $element['name'],
                    'content'     => $element['description'],
                    'author'      => $element['author'],
                    'author_host' => $element['author_host'],
                    'mkdate'      => $element['mkdate'],
                    'chdate'      => $element['chdate']
                ), $seminar_id);

                //echo $element['name'] . '<br>';
                
                foreach (self::getEntries($seminar_id, $element['topic_id']) as $child1) {
                    self::insert(array(
                        'topic_id'    => $child1['topic_id'],
                        'seminar_id'  => $seminar_id,
                        'user_id'     => $child1['user_id'],
                        'name'        => $child1['name'],
                        'content'     => $child1['description'],
                        'author'      => $child1['author'],
                        'author_host' => $child1['author_host'],
                        'mkdate'      => $child1['mkdate'],
                        'chdate'      => $child1['chdate']
                    ), $element['topic_id']);

                    //echo '&bullet; ' . $child1['name'] . '<br>';
                    foreach(self::getChilds($seminar_id, $child1['topic_id']) as $child2) {
                        self::insert(array(
                            'topic_id'    => $child2['topic_id'],
                            'seminar_id'  => $seminar_id,
                            'user_id'     => $child2['user_id'],
                            'name'        => $child2['name'],
                            'content'     => $child2['description'],
                            'author'      => $child2['author'],
                            'author_host' => $child2['author_host'],
                            'mkdate'      => $child2['mkdate'],
                            'chdate'      => $child2['chdate']
                        ), $child1['topic_id']);
                        
                        //echo '&bullet; &bullet;' . $child2['name'] . '<br>';
                    }
                }
            }
    }
    
   
    static function flattenList($list)
    {
        $new_list = array();
        $zw = array();

        foreach ($list as $element) {
            if ($element['level'] == 0) {
                if (!empty($zw)) {
                    $new_list[] = $zw;
                    $zw = array();
                }
                
                $zw = $element;
            } else {
                $zw['childs'][] = $element;
            }
        }
        
        if (!empty($zw)) {
            $new_list[] = $zw;
        }
        
        return $new_list;
    }

    static function insert($data, $parent_id) {
        $constraint = self::getConstraints($parent_id);
        
        DBManager::get()->exec('UPDATE forum_entries SET lft = lft + 2
            WHERE lft > '. $constraint['rgt'] ." AND seminar_id = '". $constraint['seminar_id'] ."'");
        DBManager::get()->exec('UPDATE forum_entries SET rgt = rgt + 2
            WHERE rgt >= '. $constraint['rgt'] ." AND seminar_id = '". $constraint['seminar_id'] ."'");
        
        $stmt = DBManager::get()->prepare("INSERT INTO forum_entries
            (topic_id, seminar_id, user_id, name, content, mkdate, chdate, author,
                author_host, lft, rgt, depth, anonymous)
            VALUES (? ,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array($data['topic_id'], $data['seminar_id'], $data['user_id'],
            $data['name'], $data['content'], $data['mkdate'], $data['chdate'], $data['author'], $data['author_host'] ?: '',
            $constraint['rgt'], $constraint['rgt'] + 1, $constraint['depth'] + 1, 0));
    }
    
    static function getConstraints($topic_id)
    {
        // look up the range of postings
        $range_stmt = DBManager::get()->prepare("SELECT *
            FROM forum_entries WHERE topic_id = ?");
        $range_stmt->execute(array($topic_id));
        if (!$data = $range_stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }
        
        if ($data['depth'] == 1) {
            $data['area'] = 1;
        }

        return $data;
    }    

    static function checkRootEntry($seminar_id) {
        // check, if the root entry in the topic tree exists
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forum_entries
            WHERE topic_id = ? AND seminar_id = ?");
        $stmt->execute(array($seminar_id, $seminar_id));
        if ($stmt->fetchColumn() == 0) {
            $stmt = DBManager::get()->prepare("INSERT INTO forum_entries
                (topic_id, seminar_id, name, mkdate, chdate, lft, rgt, depth)
                VALUES (?, ?, 'Übersicht', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 1, 0)");
            $stmt->execute(array($seminar_id, $seminar_id));
        }

        // make sure, that the category "Allgemein" exists
        $stmt = DBManager::get()->prepare("REPLACE INTO forum_categories
            (category_id, seminar_id, entry_name) VALUES (?, ?, 'Allgemein')");
        $stmt->execute(array($seminar_id, $seminar_id));
    }

    function down()
    {
        // empty
    }
  
}
