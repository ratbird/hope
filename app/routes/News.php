<?php
namespace RESTAPI\Routes;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition news_id ^[0-9a-f]{32}$
 * @condition course_id ^[0-9a-f]{32}$
 * @condition user_id ^[0-9a-f]{32}$
 * @condition comment_id ^[0-9a-f]{32}$
 */
class News extends \RESTAPI\RouteMap
{
    public static function before()
    {
        require_once 'lib/classes/StudipNews.class.php';
    }

    /**
     * Globale News auslesen
     *
     * @get /studip/news
     */
    public function getGlobalNews()
    {
        list($json, $total) = $this->getRangedNews('studip');
        return $this->paginated($json, $total);
    }

    /**
     * News einer Veranstaltung auslesen
     *
     * @get /course/:course_id/news
     */
    public function getCourseNews($course_id)
    {
        list($json, $total) = $this->getRangedNews($course_id);
        return $this->paginated($json, $total, compact('course_id'));
    }

    /**
     * News eines Nutzers auslesen
     *
     * @get /user/:user_id/news
     */
    public function getUserNews($user_id)
    {
        list($json, $total) = $this->getRangedNews($user_id);
        return $this->paginated($json, $total, compact('user_id'));
    }


    /**
     * News auslesen
     *
     * @get /news/:news_id
     */
    public function getNews($news_id)
    {
        $news = $this->requireNews($news_id);
        return $this->newsToJson($news);
    }

    /**
     * News l�schen
     *
     * @delete /news/:news_id
     */
    public function destroyNews($news_id)
    {
        $news = $this->requireNews($news_id);

        if (!$news->havePermission('delete', '', $GLOBALS['user']->id)) {
            $this->error(401);
        }

        $news->delete();
        $this->status(204);
    }


    /**
     * News updaten
     *
     * @put /news/:news_id
     */
    public function updateNews($news_id)
    {
        $news = $this->requireNews($news_id);
        if (!$news->havePermission('edit', '', $GLOBALS['user']->id)) {
            $this->error(401);
        }

        if (isset($this->data['topic'])) {
            if (!strlen(trim($topic = $this->data['topic']))) {
                $this->error(400, 'Topic must not be empty.');
            }
            $news->topic = $topic;
        }

        if (isset($this->data['body'])) {
            if (!strlen(trim($body = $this->data['body']))) {
                $this->error(400, 'Body must not be empty.');
            }
            $news->body = $body;
        }

        if (isset($this->data['expire'])) {
            $news->expire = (int) $this->data['expire'];
        }

        if (isset($this->data['allow_comments'])) {
            $news->allow_comments = (int) $this->data['allow_comments'];
        }

        $news->chdate_uid = $GLOBALS['user']->id;

        if (!$news->store()) {
            $this->error(500, 'Could not update news');

        }
        $this->status(204);
    }

    /**
     * News anlegen
     *
     * @post /course/:course_id/news
     * @post /user/:user_id/news
     * @post /studip/news
     */
    public function createNews($range_id = 'studip')
    {

        if (!\StudipNews::haveRangePermission('edit', $range_id, $GLOBALS['user']->id)) {
            $this->error(401, "Not authorized to create a news here.");
        }

        $news = new \StudipNews();
        $news->setData(
            array(
                'user_id'        => $GLOBALS['user']->id,
                'author'         => $GLOBALS['user']->getFullName(),
                'topic'          => trim(@$this->data['topic']),
                'body'           => trim(@$this->data['body']),
                'date'           => time(),
                'expire'         => isset($this->data['expire']) ? intval($this->data['expire']) : 2 * 7 * 24 * 60 * 60,
                'allow_comments' => isset($this->data['allow_comments']) ? intval($this->data['allow_comments']) : 0
            ));
        $news->addRange($range_id);

        if ($errors = $this->validateNews($news)) {
            $this->error(400, compact('errors'));
        }

        if (!$news->store()) {
            $this->error(500);
        }

        $news->storeRanges();

        $this->redirect('news/' . $news->id, 201, "ok");
    }

    /**
     * News-Comments auslesen
     *
     * @get /news/:news_id/comments
     */
    public function getNewsComments($news_id)
    {
        $comments = $this->requireNews($news_id)->comments->orderBy("mkdate asc");

        $total = count($comments);
        $json = array();
        foreach ($comments->limit($this->offset, $this->limit) as $comment) {
            $tmp = $comment->toArray("comment_id object_id user_id content mkdate chdate");
            $tmp['content_html'] = htmlReady($comment->content);
            $json[$this->urlf('/comment/%s', array(htmlReady($comment->id)))] = $tmp;
        }

        return $this->paginated($json, $total, compact('news_id'));
    }

    /**
     * News-Comment auslesen
     *
     * @get /comment/:comment_id
     */
    public function getComment($comment_id)
    {
        $comment = $this->requireComment($comment_id);
        return $this->commentToJson($comment);
    }

    /**
     * News-Comment anlegen
     *
     * @post /news/:news_id/comments
     */
    public function appendComment($news_id)
    {
        $news = $this->requireNews($news_id);

        if (!$news->allow_comments) {
            $this->error(409, 'Comments are not allowed');
        }

        if (!isset($this->data['content']) || !strlen($content = trim($this->data['content']))) {
            $this->error(400, 'Content required.');
        }

        $comment = new \StudipComments();
        $comment->setData(
            array(
                'object_id' => $news_id,
                'user_id'   => $GLOBALS['user']->id,
                'content'   => $content
            ));

        if (!$comment->store()) {
            $this->halt(500, 'Could not create comment.');
        }

        $this->redirect('comment/' . $comment->id, 201, "ok");
    }

    /**
     * News-Comment l�schen
     *
     * @delete /comment/:comment_id
     */
    public function destroyComment($comment_id)
    {
        $comment = $this->requireComment($comment_id);

        if (!$comment->delete()) {
            $this->error(500, 'Comment could not be deleted.');
        }

        $this->halt(204);
    }


    /**************************************************/
    /* PRIVATE HELPER METHODS                         */
    /**************************************************/

    private function getRangedNews($range_id)
    {

        $news = \StudipNews::getNewsByRange($range_id, true, true);

        if (!self::checkRangePermission($range_id, $GLOBALS['user']->id)) {
            $this->error(401);
        }

        $total = count($news);
        $news = array_slice($news, $this->offset, $this->limit);

        $json = array();
        foreach ($news as $n) {
            $json[$this->urlf('/news/%s', array($n->id))] = $this->newsToJson($n);
        }

        return array($json, $total);
    }

    private function validateNews($news)
    {
        $errors = array();

        $retain = $_SESSION['messages'];
        $_SESSION['messages'] = array();

        if (!$news->validate()) {
            foreach ($_SESSION['messages'] as $message_box) {
                $errors[] = $message_box->message;
            }
        }

        $_SESSION['messages'] = $retain;
        return $errors;
    }

    private static function checkRangePermission($range_id, $user_id)
    {
        return \StudipNews::haveRangePermission('view', $range_id, $user_id);
    }


    private function requireNews($id)
    {
        if (!$news = \StudipNews::find($id)) {
            $this->notFound("News not found");
        }

        if (!$news->havePermission('view', '', $GLOBALS['user']->id)) {
            $this->error(401);
        }

        return $news;
    }

    private function newsToJson($news)
    {
        $json = $news->toArray(words("news_id topic body date user_id expire allow_comments chdate chdate_uid mkdate"));

        $json['body_html'] = formatReady($news->body);
        $json['chdate_uid'] = trim($json['chdate_uid']);

        if ($news->allow_comments) {
            $json['comments'] = $this->urlf('/news/%s/comments', array($news->id));
            $json['comments_count'] = sizeof($news->comments);
        }

        $json['ranges'] = array();
        foreach ($news->news_ranges as $range) {
            if (self::checkRangePermission($range->range_id, $GLOBALS['user']->id)) {
                switch ($range->type) {
                case 'global': $url = $this->url('/studip/news'); break;
                case 'sem':    $url = $this->urlf('/course/%s/news', array($range->range_id)); break;
                case 'user':   $url = $this->urlf('/user/%s/news', array($range->range_id)); break;
                case 'inst':   $url = $this->urlf('/TODO/%s/news', array($range->range_id)); break;
                case 'fak':    $url = $this->urlf('/TODO/%s/news', array($range->range_id)); break;
                }

                $json['ranges'][] = $url;
            }
        }

        return $json;
    }

    private function requireComment($id)
    {
        if (!$comment = \StudipComment::find($id)) {
            $this->notFound("Comment not found");
        }
        if (!$comment->news->havePermission('view', '', $GLOBALS['user']->id)) {
            $this->error(401);
        }

        return $comment;
    }

    private function commentToJson($comment)
    {
        $json = $comment->toArray(words("comment_id mkdate chdate content"));
        $json['content_html'] = formatReady($json['content']);
        $json['author']       = $this->urlf('/user/%s', array($comment->user_id));
        $json['news']         = $this->urlf('/news/%s', array($comment->object_id));
        return $json;
    }
}
