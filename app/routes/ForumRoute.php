<?php
namespace API;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @todo
 * @condition course_id ^[a-f0-9]{32}$
 */
class ForumRoute extends RouteMap
{

    public static function before()
    {
        require_once 'public/plugins_packages/core/Forum/models/ForumCat.php';
        require_once 'public/plugins_packages/core/Forum/models/ForumEntry.php';
        require_once 'public/plugins_packages/core/Forum/models/ForumPerm.php';
    }

    /**
     * List all categories of a forum
     *
     * @get /course/:course_id/forum_categories
     */
    public function getForumCategories($course_id)
    {
        // TODO: how to authorize?

        $categories = $this->findCategories($course_id, $this->offset, $this->limit);
        $total      = $this->countCategories($course_id);

        $this->paginate('/course/:course_id/forum_categories?offset=%u&limit=%u', $total);

        $json = array();
        foreach ($categories as $cat) {
            $uri = sprintf('/forum_category/%s', htmlReady($cat['category_id']));
            $json[$uri] = self::categoryToJson($cat);
        }

        return $this->collect($json);
    }

    /**
     * Create a new category
     *
     * @post /course/:course_id/forum_categories
     */
    public function createForumCategory($course_id)
    {
        if (!\ForumPerm::has("add_category", $course_id)) {
            $this->error(401);
        }

        if (!isset($this->data['name']) || !strlen($name = trim($this->data['name']))) {
            $this->error(400, 'Category name required.');
        }

        $category_id = \ForumCat::add($course_id, $name);
        if (!$category_id) {
            $this->error(500, 'Error creating the forum category.');
        }

        $this->redirect('forum_category/' . $category_id, 201, 'ok');
    }

    /**
     * Read a category
     *
     * @get /forum_category/:category_id
     */
    public function getForumCategory($category_id)
    {
        // TODO: how to authorize?

        return self::categoryToJson($this->findCategory($category_id));
    }

    /**
     * Update a category
     *
     * @put /forum_category/:category_id
     */
    public function updateForumCategory($category_id)
    {
        $category = $this->findCategory($category_id);

        if (!\ForumPerm::has("edit_category", $category['course_id'])) {
            $this->error(401);
        }

        if (!isset($this->data['name']) || !strlen($name = trim($this->data['name']))) {
            $this->error(400, 'Category name required.');
        }

        \ForumCat::setName($category_id, $this->data['name']);

        $this->status(204);
    }

    /**
     * Delete a category
     *
     * @delete /forum_category/:category_id
     */
    public function deleteForumCategory($category_id)
    {
        $category = $this->findCategory($category_id);
        $cid = $category['course_id'];

        if (!\ForumPerm::has("remove_category", $cid)) {
            $this->error(401);
        }

        \ForumCat::remove($category_id, $cid);

        $this->status(204);
    }

    /**
     * Show entries of a category
     *
     * @get /forum_category/:category_id/topics
     */
    public function getCategoryEntries($category_id)
    {
        // TODO: how to authorize?
        $topics = $this->getTopics($category_id, $this->offset, $this->limit);
        $this->paginate('/course/:course_id/forum_categories?offset=%u&limit=%u', $this->countTopics($category_id));


        return $this->collect($topics);
    }



    /**
     * Add a new forum entry to an existing one
     *
     * @post /forum_category/:category_id/topics
     */
    public function appendForumEntry($category_id)
    {
        $category = $this->findCategory($category_id);
        $cid = $category['course_id'];

        if (!\ForumPerm::has('add_area', $cid)) {
            $this->error(401);
        }

        if (!isset($this->data['subject']) || !strlen($subject = trim($this->data['subject']))) {
            $this->error(400, 'Subject required.');
        }

        if (!isset($this->data['content'])) {
            $this->error(400, 'Content required.');
        }
        $content = trim($this->data['content']);

        $anonymous = isset($this->data['anonymous']) ? intval($this->data['anonymous']) : 0;

        $entry_id = $this->createEntry($cid, $cid, $subject, $content, $anonymous);

        \ForumCat::addArea($category_id, $entry_id);

        $this->redirect('forum_entry/' . $entry_id, 201, "ok");
    }

    /**
     * Get a forum entry
     *
     * @get /forum_entry/:entry_id
     */
    public function getForumEntry($entry_id)
    {
        return $this->findEntry($entry_id);
    }

    /**
     * Add a new forum entry to an existing one
     *
     * @post /forum_entry/:entry_id
     */
    public function addForumEntry($parent_id)
    {
        $parent = $this->findEntry($parent_id);
        $cid = $parent['course_id'];

        if (!\ForumPerm::has('add_entry', $cid)) {
            $this->error(401);
        }

        // TODO: ist das subject wirklich erforderlich?
        if (!isset($this->data['subject']) || !strlen($subject = trim($this->data['subject']))) {
            $this->error(400, 'Subject required.');
        }

        // TODO: content darf doch leer sein, oder?
        if (!isset($this->data['content'])) {
            $this->error(400, 'Content required.');
        }
        $content = trim($this->data['content']);

        $anonymous = isset($this->data['anonymous']) ? intval($this->data['anonymous']) : 0;

        $entry_id = $this->createEntry($parent_id, $cid, $subject, $content, $anonymous);

        $this->redirect('forum_entry/' . $entry_id, 201, "ok");
    }

    /**
     * Update an existing one forum entry
     *
     * @put /forum_entry/:entry_id
     */
    public function updateForumEntry($entry_id)
    {
        $entry = $this->findEntry($entry_id);
        $cid = $entry['course_id'];

        $perm = self::isArea($entry) ? 'edit_area' : 'edit_entry';

        if (!\ForumPerm::has($perm, $cid)) {
            $this->error(401);
        }

        // TODO: ist subject wirklich nötig
        if (!isset($this->data['subject']) || !strlen($subject = trim($this->data['subject']))) {
            $this->error(400, 'Subject required.');
        }

        // TODO: ist content wirklich nötig
        if (!isset($this->data['content']) || !strlen($content = trim($this->data['content']))) {
            $this->error(400, 'Content required.');
        }

        \ForumEntry::update($entry_id, $subject, $content);

        $this->status(204);
    }

    /**
     * Delete an entry
     *
     * @delete /forum_entry/:entry_id
     */
    public function deleteForumEntry($entry_id)
    {
        $entry = $this->findEntry($entry_id);
        $cid = $entry['course_id'];

        $perm = self::isArea($entry) ? 'remove_area' : 'remove_entry';

        if (!\ForumPerm::has($perm, $cid)) {
            $this->error(401);
        }

        \ForumEntry::delete($entry_id);

        $this->status(204);
    }

    /*********************
     *                   *
     * PRIVATE FUNCTIONS *
     *                   *
     *********************/


    private function findEntry($entry_id)
    {
        $raw = \ForumEntry::getConstraints($entry_id);
        if ($raw === false) {
            $this->notFound();
        }

        $entry = self::convertEntry($raw);

        # TODO offset/limit
        $children = \ForumEntry::getEntries($entry_id, \ForumEntry::WITHOUT_CHILDS, '', 'ASC', 0, false);

        if (isset($children['list'][$entry_id])) {
            unset($children['list'][$entry_id]);
        }

        $entry['children'] = array_map(function ($entry) {
                return ForumRoute::convertEntry($entry);
            },
            array_values($children['list']));

        return $entry;
    }

    public static function convertEntry($raw)
    {
        $entry = array();
        foreach(words("topic_id mkdate chdate anonymous depth") as $key) {
            $entry[$key] = $raw[$key];
        }

        $entry['subject']      = $raw['name'];
        $entry['user']         = sprintf('/user/%s', htmlReady($raw['user_id']));
        $entry['course']       = sprintf('/course/%s', htmlReady($raw['seminar_id']));
        $entry['content_html'] = formatReady(\ForumEntry::parseEdit($raw['content']));
        $entry['content']      = \ForumEntry::killEdit($raw['content']);

        return $entry;
    }


    private static function isArea($entry)
    {
        return 1 === $entry['depth'];
    }

    private static function createEntry($parent_id, $course_id, $subject, $content, $anonymous)
    {
        $topic_id  = self::generateID();

        $data = array(
            'topic_id'    => $topic_id,
            'seminar_id'  => $course_id,
            'user_id'     => $GLOBALS['user']->id,
            'name'        => $subject,
            'content'     => $content,
            'author'      => $GLOBALS['user']->getFullName(),
            'author_host' => $_SERVER['REMOTE_ADDR'],
            'anonymous'   => (int) $anonymous
        );
        \ForumEntry::insert($data, $parent_id);

        return $topic_id;
    }


    private function findCategories($course_id, $offset = 0, $limit = 10)
    {
        $offset = (int) $offset;
        $limit  = (int) $limit;
        $query = "SELECT category_id, seminar_id AS course_id, entry_name AS name, pos AS position
                  FROM forum_categories
                  WHERE seminar_id = :course_id
                  ORDER BY pos ASC, category_id ASC
                  LIMIT {$offset}, {$limit}";
        $statement = \DBManager::get()->prepare($query);
        $statement->bindValue(':course_id', $course_id);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function countCategories($course_id)
    {
        $query = "SELECT COUNT(*)
                  FROM forum_categories
                  WHERE seminar_id = :course_id";
        $statement = \DBManager::get()->prepare($query);
        $statement->bindValue(':course_id', $course_id);
        $statement->execute();

        return $statement->fetchColumn() ?: 0;
    }

    private function findCategory($category_id)
    {
        $query = "SELECT category_id, seminar_id AS course_id, entry_name AS name, pos AS position
                  FROM forum_categories
                  WHERE category_id = :category_id";
        $statement = \DBManager::get()->prepare($query);
        $statement->bindValue(':category_id', $category_id);
        $statement->execute();
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!$result) {
            $this->error(404);
        }
        return $result;
    }

    private static function categoryToJson($category)
    {
        $json = $category;

        $json['course'] = sprintf('/course/%s', htmlReady($json['course_id']));
        unset($json['course_id']);

        $json['topics'] = sprintf('/forum_category/%s/topics', $json['category_id']);
        $json['topics_count'] = self::countTopics($json['category_id']);

        return $json;
    }

    private static function countTopics($category_id)
    {
        $query = "SELECT COUNT(*)
                  FROM forum_entries
                  JOIN forum_categories_entries USING (topic_id)
                  WHERE category_id = :cat_id AND depth = 1";
        $statement = \DBManager::get()->prepare($query);
        $statement->bindValue(':cat_id', $category_id);
        $statement->execute();

        return $statement->fetchColumn() ?: 0;
    }

    private function getTopics($category_id, $offset = 0, $limit = 10)
    {
        $offset = (int) $offset;
        $limit  = (int) $limit;

        $query = "SELECT *
                  FROM forum_entries
                  JOIN forum_categories_entries USING (topic_id)
                  WHERE category_id = :category_id AND depth = 1
                  ORDER BY mkdate DESC
                  LIMIT {$offset}, {$limit}";
        $statement = \DBManager::get()->prepare($query);
        $statement->bindValue(':category_id', $category_id);
        $statement->execute();

        $topics = array();
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $topic) {
            $url = sprintf('/forum_entry/%s', htmlReady($topic['topic_id']));
            $topics[$url] = self::convertEntry($topic);
        }

        return $topics;
    }

    private static function generateID()
    {
        return md5(uniqid(rand()));
    }
}
