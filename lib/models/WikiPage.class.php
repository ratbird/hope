<?php

class WikiPage extends SimpleORMap {

    function __construct($id = null)
    {
        $this->db_table = 'wiki';

        $this->has_one['author'] = array(
            'class_name' => 'User',
            'assoc_foreign_key' => 'user_id',
            'foreign_key' => 'user_id'
        );

        parent::__construct($id);
    }

    static function findLatestPages($course_id)
    {
        $query = "SELECT
                    range_id,
                    keyword,
                    MAX(version) as version
                  FROM wiki
                  WHERE range_id = ?
                  GROUP BY keyword
                  ORDER BY keyword ASC";

        $st = DBManager::get()->prepare($query);
        $st->execute(array($course_id));
        $ids = $st->fetchAll(PDO::FETCH_NUM);

        $pages = new SimpleORMapCollection();
        $pages->setClassName(__CLASS__);

        foreach ($ids as $id) {
            $pages[] = self::find($id);

        }

        return $pages;
    }

    static function findLatestPage($course_id, $keyword)
    {
        $results = self::findBySQL("range_id = ? AND keyword = ? ORDER BY version DESC LIMIT 1",
                                   array($course_id, $keyword));

        if (!sizeof($results)) {
            return null;
        }

        return $results[0];
    }

    public function isVisibleTo($user)
    {
        $user_id = is_object($user) ? $user->id : $user;
        return $GLOBALS['perm']->have_studip_perm('user', $this->range_id, $user_id);
    }


    public function isCreatableBy($user)
    {
        $user_id = is_object($user) ? $user->id : $user;
        return $GLOBALS['perm']->have_studip_perm('autor', $this->range_id, $user_id);
    }

    public static function getStartPage($course_id)
    {
        $start = self::findLatestPage($course_id, '');

        if (!$start) {
            $start = new self(array($course_id, 'WikiWikWeb', 0));
            $start->body = _("Dieses Wiki ist noch leer. Bearbeiten Sie es!\nNeue Seiten oder Links werden einfach durch Eingeben von WikiNamen angelegt.");
            $start->user_id = 'nobody';
        }

        return $start;
    }
}
