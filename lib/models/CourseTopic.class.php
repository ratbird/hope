<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author     Rasmus Fuhse <fuhse@data-quest.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string issue_id database column
 * @property string id alias column for issue_id
 * @property string seminar_id database column
 * @property string author_id database column
 * @property string title database column
 * @property string description database column
 * @property string priority database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property DocumentFolder folder belongs_to DocumentFolder
 * @property Course course belongs_to Course
 * @property User author belongs_to User
 * @property SimpleORMapCollection dates has_and_belongs_to_many CourseDate
 */

class CourseTopic extends SimpleORMap {

    static public function findByTermin_id($termin_id)
    {
        return self::findBySQL("INNER JOIN themen_termine USING (issue_id)
            WHERE themen_termine.termin_id = ?
            ORDER BY priority ASC",
            array($termin_id)
        );
    }

    static public function findBySeminar_id($seminar_id, $order_by = "ORDER BY priority")
    {
        return parent::findBySeminar_id($seminar_id, $order_by);
    }

    static public function findByTitle($seminar_id, $name)
    {
        return self::findOneBySQL("seminar_id = ? AND title = ?", array($seminar_id, $name));
    }

    static public function getMaxPriority($seminar_id)
    {
        return DbManager::get()->fetchColumn("SELECT MAX(priority) FROM themen WHERE seminar_id=?", array($seminar_id));
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'themen';
        $config['has_and_belongs_to_many']['dates'] = array(
            'class_name' => 'CourseDate',
            'thru_table' => 'themen_termine',
            'order_by' => 'ORDER BY date',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['belongs_to']['folder'] = array(
            'class_name' => 'DocumentFolder',
            'assoc_foreign_key' => "range_id"
        );
        $config['belongs_to']['course'] = array(
            'class_name'  => 'Course',
            'foreign_key' => 'seminar_id'
        );
        $config['belongs_to']['author'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'author_id'
        );

        $config['additional_fields']['forum_thread_url']['get'] = 'getForumThreadURL';

        parent::configure($config);
    }

    function __construct($id = null)
    {
        parent::__construct($id);
        $this->registerCallback('before_create', 'cbDefaultValues');
        $this->registerCallback('after_store', 'cbUpdateConnectedContentModules');
    }

    /**
    *
    * @deprecated
    */
    public function createFolder()
    {
        $this->connectWithDocumentFolder();
        return $this->folder;
    }

    /**
    * set or update connection with document folder
    */
    function connectWithDocumentFolder()
    {
        if ($this->seminar_id) {
            $document_module = Seminar::getInstance($this->seminar_id)->getSlotModule('documents');
            if ($document_module) {
                if (!$this->folder) {
                    $folder = new DocumentFolder();
                    $folder['range_id'] = $this->getId();
                    $folder['priority'] = $this['priority'];
                    $folder['seminar_id'] = $this['seminar_id'];
                    $folder['user_id'] = $GLOBALS['user']->id;
                    $folder['permission'] = 15;
                    $this->folder = $folder;
                }
                $this->folder['name'] = $this['title'];
                $this->folder['description'] = $this['description'];
                return $this->folder->store();
            }
        }
        return false;
    }

    /**
    * set or update connection with forum thread
    */
    function connectWithForumThread()
    {
        if ($this->seminar_id) {
            $forum_module = Seminar::getInstance($this->seminar_id)->getSlotModule('forum');
            if ($forum_module instanceOf ForumModule) {
                $forum_module->setThreadForIssue($this->id, $this->title, $this->description);
                return true;
            }
        }
        return false;
    }

    function getForumThreadURL()
    {
        if ($this->seminar_id) {
            $forum_module = Seminar::getInstance($this->seminar_id)->getSlotModule('forum');
            if ($forum_module instanceOf ForumModule) {
                return html_entity_decode($forum_module->getLinkToThread($this->id));
            }
        }
        return '';
    }

    protected function cbUpdateConnectedContentModules()
    {
        if ($this->isFieldDirty('title') || $this->isFieldDirty('description')) {
            if ($this->folder) {
                $this->connectWithDocumentFolder();
            }
            if ($this->forum_thread_url) {
                $this->connectWithForumThread();
            }
        }
    }

    protected function cbDefaultValues()
    {
        if (empty($this->content['priority'])) {
            $this->content['priority'] = self::getMaxPriority($this->seminar_id) + 1;
        }
    }
}
