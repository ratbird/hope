<?php
namespace API;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @condition course_id ^[0-9a-f]{32}$
 */
class WikiRoute extends RouteMap
{
    public function before()
    {
        require_once 'lib/wiki.inc.php';
    }

    /**
     * Wikiseitenindex einer Veranstaltung
     *
     * @get /course/:course_id/wiki
     */
    public function getCourseWiki($course_id)
    {
        $pages = \WikiPage::findLatestPages($course_id);
        if (!sizeof($pages->findBy('keyword', 'WikiWikWeb'))) {
            $pages[] = \WikiPage::getStartPage($course_id);
        }

        if (!$pages->first()->isVisibleTo($GLOBALS['user']->id)) {
            $this->error(401);
        }

        $total = sizeof($pages);
        $pages = $pages->limit($this->offset, $this->limit);

        $linked_pages = array();
        foreach ($pages as $page) {
            $url = sprintf('/course/%s/wiki/%s', $course_id, htmlReady($page['keyword']));
            $linked_pages[$url] = self::wikiPageToJson($page, array("content"));
        }

        return $this->paginated($linked_pages, $total, compact('course_id'));
    }

    /**
     * Wikiseite auslesen
     *
     * @get /course/:course_id/wiki/:keyword
     * @get /course/:course_id/wiki/:keyword/:version
     */
    public function getCourseWikiKeyword($course_id, $keyword, $version = null)
    {
        $page = $this->requirePage($course_id, $keyword, $version);
        return self::wikiPageToJson($page);
    }

    /**
     * Wikiseite ändern/hinzufügen
     *
     * @put /course/:course_id/wiki/:keyword
     */
    public function putCourseWikiKeyword($course_id, $keyword)
    {
        if (!isset($this->data['content'])) {
            $this->error(400, 'No content provided');
        }

        $last_version = \WikiPage::findLatestPage($course_id, $keyword);
        if (!$last_version) {
            $last_version = new \WikiPage(array($course_id, $keyword, 0));
        }

        if (!$last_version->isCreatableBy($user_id = $GLOBALS['user']->id)) {
            $this->error(401);
        }

        // TODO: rewrite this code and put #submitWikiPage into
        // class \WikiPage
        if (!isset($GLOBALS['SessSemName'])) {
            $GLOBALS['SessSemName'] = array(1 => $course_id);
        }
        submitWikiPage($keyword, $last_version->version, $this->data['content'], $user_id, $course_id);

        $new_version = \WikiPage::findLatestPage($course_id, $keyword);

        $this->status(204);
        $this->headers(
            array(
                'Content-Location' => sprintf('/course/%s/wiki/%s/%d',
                                              htmlReady($course_id),
                                              htmlReady($keyword),
                                              $new_version->version)));
    }

    /**************************************************/
    /* PRIVATE HELPER METHODS                         */
    /**************************************************/

    private function requirePage($course_id, $keyword, $version)
    {
        if ($version) {
            $page = \WikiPage::find(array($course_id, $keyword, $version));
        } else {
            $page = \WikiPage::findLatestPage($course_id, $keyword);
        }

        if (!$page) {
            $this->notFound();
        }

        if (!$page->isVisibleTo($GLOBALS['user']->id)) {
            $this->error(401);
        }

        return $page;
    }

    private static function wikiPageToJson($page, $without = array())
    {
        $json = $page->toArray(words("range_id user_id keyword chdate version"));

        // (pre-rendered) content
        if (!in_array("content", $without)) {
            $json['content']      = $page->body;
            $json['content_html'] = wikiReady($page->body);
        }

        foreach ($without as $key) {
            if (isset($json[$key])) {
                unset($json[$key]);
            }
        }

        return $json;
    }


}
