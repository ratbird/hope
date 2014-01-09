<?php
namespace API;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition course_id ^[a-f0-9]{32}$
 * @condition user_id ^[a-f0-9]{32}$
 */
class CourseRoute extends RouteMap
{

    /**
     * Lists all courses of a user including the semesters in which
     * that course is active.
     * Optionally filtered by a URL parameter 'semester'.
     *
     * @get /user/:user_id/courses
     */
    public function getUserCourses($user_id)
    {
        if ($GLOBALS['user']->id !== $user_id) {
            $this->error(401);
        }

        // setting up semester to filter by
        $semester = null;
        $semester_id = \Request::get('semester');
        if (strlen($semester_id)) {
            $semester = \Semester::find($semester_id);
            if (!$semester) {
                $this->error(400, "Semester not found.");
            }
        }

        $memberships = $this->findMembershipsByUserId($user_id, $semester);

        $total = count($memberships);
        $memberships = $memberships->limit($this->offset, $this->limit);
        return $this->paginated($this->membershipsToJSON($memberships),
                                $total,
                                compact('user_id'), array('semester' => $semester_id));
    }

    /**
     * Show a single course
     *
     * @get /course/:course_id
     */
    public function getCourse($course_id)
    {
        $course = $this->requireCourse($course_id);
        return $this->courseToJSON($course);
    }

    /**
     * List all members of a course.
     * Optionally filtered by a URL parameter 'status'.
     *
     * @get /course/:course_id/members
     */
    public function getMembers($course_id)
    {
        $status_filter = \Request::get('status');
        if ($status_filter && !in_array($status_filter, words("user autor tutor dozent"))) {
            $this->error(400, "Status may be one of: user, autor, tutor, dozent");
        }

        $course = $this->requireCourse($course_id);
        $members = $course->members;
        if ($status_filter) {
            $members = $members->findBy('status', $status_filter);
        }

        $total = count($members);
        $members = $members->limit($this->offset, $this->limit);
        return $this->paginated($this->membersToJSON($course, $members),
                                $total,
                                compact('course_id'), array('status' => $status_filter));
    }


    /**************************************************/
    /* PRIVATE HELPER METHODS                         */
    /**************************************************/

    private function findMembershipsByUserId($user_id, $semester)
    {
        $memberships = \SimpleORMapCollection::createFromArray(
            \CourseMember::findBySQL('user_id = ? ORDER BY mkdate ASC', array($user_id)));

        // filter by semester
        if ($semester) {

            $memberships = $memberships->filter(function ($m) use ($semester) {
                    $course = $m->course;
                    return
                        $course->start_time == $semester->beginn
                        || ($course->start_time <= $semester->beginn
                            && ($course->duration_time == -1 || $semester->beginn <= $course->end_time));
                });
        }

        return $memberships;
    }

    private function membershipsToJSON($memberships)
    {
        $json = array();

        foreach ($memberships as $membership) {
            $course_json = $this->courseToJSON($course = $membership->course);

            // add group color
            $course_json['group'] = (int) $membership->gruppe;

            $json[sprintf("/course/%s", $course->id)] = $course_json;
        }

        return $json;
    }

    private function courseToJSON($course)
    {
        $json = array();

        $json['course_id'] = $course->id;
        $json['number'] = $course->VeranstaltungsNummer;
        $json['title'] = $course->Name;
        $json['subtitle'] = $course->Untertitel;
        $json['type'] = $course->status;
        $json['description'] = $course->Beschreibung;
        $json['location'] = $course->Ort;

        // members
        $members = array();
        foreach ($course->members as $member) {
            $members[$member->status][] = $member;
        }

        // lecturers
        foreach ($members['dozent'] as $lecturer) {
            $url = sprintf('/user/%s', htmlReady($lecturer->user_id));
            $json['lecturers'][$url] = $lecturer->user->getFullName();
        }

        // other members
        foreach (words("user autor tutor dozent") as $status) {
            $json['members'][$status] = sprintf('/course/%s/members?status=%s', $course->id, $status);
            $json['members'][$status . '_count'] = sizeof($members[$status]);
        }

        foreach (words("start_semester end_semester") as $key) {
            $json[$key] = $course->$key ? sprintf('/semester/%s', htmlReady($course->$key->id)) : null;
        }

        $modules = new \Modules;
        $activated = $modules->getLocalModules($course->id, 'sem');
        $json['modules'] = array();
        foreach (array('forum'     => 'forum_categories',
                       'documents' => 'files',
                       'wiki'      => 'wiki') as $module => $uri) {

            if ($activated[$module]) {
                $json['modules'][$module] = sprintf('/course/%s/%s', htmlReady($course->id), $uri);
            }
        }

        return $json;
    }

    private function requireCourse($id)
    {
        if (!$course = \Course::find($id)) {
            $this->notFound("Course not found");
        }

        if (!$GLOBALS['perm']->have_studip_perm('user', $id, $GLOBALS['user']->id)) {
            $this->error(401);
        }

        return $course;
    }

    private function membersToJSON($course, $members)
    {
        $json = array();

        foreach ($members as $member) {
            $url = sprintf('/user/%s', $member->user_id);
            $avatar = \Avatar::getAvatar($member->user_id);
            $json[$url] = array(
                'user_id'       => $member->user_id,
                'fullname'      => $member->user->getFullName(),
                'status'        => $member->status,
                'avatar_small'  => $avatar->getURL(\Avatar::SMALL),
                'avatar_medium' => $avatar->getURL(\Avatar::MEDIUM),
                'avatar_normal' => $avatar->getURL(\Avatar::NORMAL)
            );
        }

        return $json;
    }
}
