<?php

require_once 'app/controllers/authenticated_controller.php';

class Course_TopicsController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        checkObject();
        checkObjectModule("schedule");
        PageLayout::setTitle(sprintf('%s - %s', Course::findCurrent()->getFullname(), _("Themen")));
    }

    public function index_action()
    {
        if (Request::isPost() && Request::get("edit") && $GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            $topic = new CourseTopic(Request::option("issue_id"));
            if ($topic['seminar_id'] && ($topic['seminar_id'] !== $_SESSION['SessionSeminar'])) {
                throw new AccessDeniedException("Kein Zugriff");
            }
            if (Request::submitted("delete_topic")) {
                $topic->delete();
                PageLayout::postMessage(MessageBox::success(_("Thema gelöscht.")));
            } else {
                $topic['title'] = Request::get("title");
                $topic['description'] = Request::get("description");
                if ($topic->isNew()) {
                    $topic['seminar_id'] = $_SESSION['SessionSeminar'];
                }
                $topic->store();

                //change dates for this topic
                $former_date_ids = $topic->dates->pluck("termin_id");
                $new_date_ids = array_keys(Request::getArray("date"));
                foreach (array_diff($former_date_ids, $new_date_ids) as $delete_termin_id) {
                    $topic->dates->unsetByPk($delete_termin_id);
                }
                foreach (array_diff($new_date_ids, $former_date_ids) as $add_termin_id) {
                    $date = CourseDate::find($add_termin_id);
                    if ($date) {
                        $topic->dates[] = $date;
                    }
                }
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

                if (Request::option("issue_id") === "new") {
                    Request::set("open", $topic->getId());
                }
                PageLayout::postMessage(MessageBox::success(_("Thema gespeichert.")));
                $this->redirect("course/topics/index");
            }
        }
        if (Request::isPost() && Request::option("move_down")) {
            $topics = CourseTopic::findBySeminar_id($_SESSION['SessionSeminar']);
            $mainkey = null;
            foreach ($topics as $key => $topic) {
                if ($topic->getId() === Request::option("move_down")) {
                    $mainkey = $key;
                }
                $topic['priority'] = $key + 1;
            }
            if ($mainkey !== null && $mainkey < count($topics)) {
                $topics[$mainkey]->priority++;
                $topics[$mainkey + 1]->priority--;
            }
            foreach ($topics as $key => $topic) {
                $topic->store();
            }
        }
        if (Request::isPost() && Request::option("move_up")) {
            $topics = CourseTopic::findBySeminar_id($_SESSION['SessionSeminar']);
            foreach ($topics as $key => $topic) {
                if (($topic->getId() === Request::option("move_up")) && $key > 0) {
                    $topic['priority'] = $key;
                    $topics[$key - 1]->priority = $key + 1;
                    $topics[$key - 1]->store();
                } else {
                    $topic['priority'] = $key + 1;
                }
                $topic->store();
            }
        }

        Navigation::activateItem('/course/schedule/topics');
        $this->topics = CourseTopic::findBySeminar_id($_SESSION['SessionSeminar']);
        $this->cancelled_dates_locked = LockRules::Check($_SESSION['SessionSeminar'], 'cancelled_dates');
    }

    public function edit_action($topic_id = null)
    {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        $this->topic = new CourseTopic($topic_id);
        $this->dates = CourseDate::findBySeminar_id($_SESSION['SessionSeminar']);

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->response->add_header('X-Title', $topic_id ? _("Bearbeiten").": ".$this->topic['title'] : _("Neues Thema erstellen"));
        }
    }

    public function copy_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        if (Request::submitted("copy")) {
            $prio = 1;
            foreach (Course::find($_SESSION['SessionSeminar'])->topics as $topic) {
                $prio = max($prio, $topic['priority']);
            }
            foreach (Request::getArray("topic") as $topic_id => $value) {
                $topic = new CourseTopic($topic_id);
                $topic = clone $topic;
                $topic['seminar_id'] = $_SESSION['SessionSeminar'];
                $topic['priority'] = $prio;
                $prio++;
                $topic->setId($topic->getNewId());
                $topic->setNew(true);
                $topic->store();
            }
            PageLayout::postMessage(MessageBox::success(sprintf(_("%s Themen kopiert."), count(Request::getArray("topic")))));
            $this->redirect("course/topics");
        }
        if ($GLOBALS['perm']->have_perm("root")) {
            $this->courseSearch = new SQLSearch("
                SELECT seminare.Seminar_id, CONCAT(seminare.VeranstaltungsNummer, ' ', seminare.name, ' (', IF(seminare.duration_time = 0, semester_data.name, 'unbegrenzt'), ') (', (SELECT COUNT(*) FROM themen WHERE themen.seminar_id = seminare.Seminar_id), ')')
                FROM seminare
                    LEFT JOIN semester_data ON (semester_data.beginn = seminare.start_time)
                WHERE CONCAT(seminare.VeranstaltungsNummer, ' ', seminare.name, ' (', IF(seminare.duration_time = 0, semester_data.name, 'unbegrenzt'), ') (', (SELECT COUNT(*) FROM themen WHERE themen.seminar_id = seminare.Seminar_id), ')') LIKE :input
                GROUP BY seminare.Seminar_id
                ORDER BY semester_data.beginn DESC, seminare.VeranstaltungsNummer ASC, seminare.name ASC
                ",
                _("Veranstaltung suchen"),
                "seminar_id"
            );
        } elseif ($GLOBALS['perm']->have_perm("admin")) {
            $this->courseSearch = new SQLSearch("
                SELECT seminare.Seminar_id, CONCAT(seminare.VeranstaltungsNummer, ' ', seminare.name, ' (', IF(seminare.duration_time = 0, semester_data.name, 'unbegrenzt'), ') (', (SELECT COUNT(*) FROM themen WHERE themen.seminar_id = seminare.Seminar_id), ')')
                FROM seminare
                    INNER JOIN seminar_inst ON (seminare.Seminar_id = seminar_inst.seminar_id)
                    INNER JOIN user_inst ON (user_inst.Institut_id = seminar_inst.institut_id)
                    LEFT JOIN semester_data ON (semester_data.beginn = seminare.start_time)
                WHERE CONCAT(seminare.VeranstaltungsNummer, ' ', seminare.name, ' (', IF(seminare.duration_time = 0, semester_data.name, 'unbegrenzt'), ') (', (SELECT COUNT(*) FROM themen WHERE themen.seminar_id = seminare.Seminar_id), ')') LIKE :input
                    AND user_inst.user_id = ".DBManager::get()->quote($GLOBALS['user']->id)."
                    AND user_inst.inst_perms = 'admin'
                GROUP BY seminare.Seminar_id
                ORDER BY semester_data.beginn DESC, seminare.VeranstaltungsNummer ASC, seminare.name ASC
                ",
                _("Veranstaltung suchen"),
                "seminar_id"
            );
        } else {
            $this->courseSearch = new SQLSearch("
                SELECT seminare.Seminar_id, CONCAT(seminare.VeranstaltungsNummer, ' ', seminare.name, ' (', IF(seminare.duration_time = 0, semester_data.name, 'unbegrenzt'), ') (', (SELECT COUNT(*) FROM themen WHERE themen.seminar_id = seminare.Seminar_id), ')')
                FROM seminare
                    INNER JOIN seminar_user ON (seminare.Seminar_id = seminar_user.Seminar_id)
                    LEFT JOIN semester_data ON (semester_data.beginn = seminare.start_time)
                WHERE CONCAT(seminare.VeranstaltungsNummer, ' ', seminare.name, ' (', IF(seminare.duration_time = 0, semester_data.name, 'unbegrenzt'), ') (', (SELECT COUNT(*) FROM themen WHERE themen.seminar_id = seminare.Seminar_id), ')') LIKE :input
                    AND seminar_user.status IN ('tutor', 'dozent')
                    AND seminar_user.user_id = ".DBManager::get()->quote($GLOBALS['user']->id)."
                GROUP BY seminare.Seminar_id
                ORDER BY semester_data.beginn DESC, seminare.VeranstaltungsNummer ASC, seminare.name ASC
                ",
                _("Veranstaltung suchen"),
                "seminar_id"
            );
        }

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
            $this->response->add_header('X-Title', _("Themen aus Veranstaltung kopieren"));
        }
    }

    public function fetch_topics_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", Request::option("seminar_id"))) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        $this->topics = CourseTopic::findBySeminar_id(Request::option("seminar_id"));
        $output = array(
            'html' => $this->render_template_as_string("course/topics/_topiclist.php")
        );
        $this->render_json($output);
    }


}
