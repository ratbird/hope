<?php

require_once 'app/controllers/authenticated_controller.php';

class Course_TopicsController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        if (Request::isPost() && Request::option("issue_id") && $GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            $topic = new CourseTopic(Request::option("issue_id"));
            if ($topic['seminar_id'] !== $_SESSION['SessionSeminar']) {
                throw new AccessDeniedException("Kein Zugriff");
            }
            if (Request::submitted("delete_topic")) {
                $topic->delete();
                PageLayout::postMessage(MessageBox::success(_("Thema gelöscht.")));
            } else {
                $topic['title'] = Request::get("title");
                $topic['description'] = Request::get("description");
                $topic->store();

                if (Request::get("folder") && !$topic->folder) {
                    $topic->createFolder();
                }
                if (Request::get("forumthread") && class_exists("ForumIssue")) {
                    ForumIssue::setThreadForIssue(
                        $_SESSION['SessionSeminar'],
                        $topic->getId(),
                        $topic['title'],
                        $topic['description']
                    );
                }

                PageLayout::postMessage(MessageBox::success(_("Thema gespeichert.")));
            }
        }

        Navigation::activateItem('/course/schedule/topics');
        $this->topics = CourseTopic::findBySeminar_id($_SESSION['SessionSeminar']);
    }

    public function edit_action($topic_id)
    {
        $this->topic = new CourseTopic($topic_id);

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->response->add_header('X-Title', _("Bearbeiten").": ".$this->topic['title']);
        }
    }


}
